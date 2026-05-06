<?php

namespace WilokeListingTools\Framework\Payment\Stripe;

use Exception;
use Stripe\Webhook;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Payment\ProceededPaymentHook;
use WilokeListingTools\Framework\Payment\StripePayment;
use WilokeListingTools\Framework\Payment\WebhookInterface;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;

final class StripeWebhook extends StripePayment implements WebhookInterface
{
    protected $paymentID;
    protected $rawPayloadData;
    protected $oEvent;
    protected $nextBillingDateGMT;
    protected $subscriptionID;
    protected $intentID;
    protected $customerID;
    protected $oPrePareInvoiceFormat;
    protected $aPaymentMeta;
    protected $aInvoiceFormat;
    protected $chargeID;

    protected function getPayload()
    {
        $this->rawPayloadData = file_get_contents('php://input');
    }

    private function verifyWebhook()
    {
        if (isset($this->oEvent->data->object->subscription) && !empty($this->oEvent->data->object->subscription)) {
            $this->subscriptionID = $this->oEvent->data->object->subscription;
        } else if (isset($this->oEvent->subscription) && !empty($this->oEvent->subscription)) {
            $this->subscriptionID = $this->oEvent->subscription;
        }

        if (!empty($this->subscriptionID)) {
            FileSystem::logSuccess('Starting catching subscription');

            $this->paymentID = PaymentMetaModel::getPaymentIDBySubscriptionID($this->subscriptionID);

            if (empty($this->paymentID)) {
                if (isset($this->oEvent->data->object->lines) && !empty($this->oEvent->data->object->lines)) {
                    $oMetaData = $this->oEvent->data->object->lines->data[0]->metadata;
                } else {
                    $oMetaData = $this->oEvent->data->object->metadata;
                }

                if (!isset($oMetaData->paymentID) || empty($oMetaData->paymentID)) {
                    FileSystem::logError(sprintf('We could not found the payment ID. Stripe Info %s',
                        json_encode($this->oEvent)));

                    return false;
                }

                $this->paymentID = $oMetaData->paymentID;
            }

            $this->aPaymentMeta = PaymentMetaModel::getPaymentInfo($this->paymentID);
            if (isset($this->oEvent->data->object->trial_period_days) && !empty
                ($this->oEvent->data->object->trial_period_days)
            ) {
                $this->nextBillingDateGMT = $this->oEvent->data->object->trial_period_days;
            } else {
                $now = current_time('timestamp', 1);

                if (!empty($this->aPaymentMeta['trialPeriodDays'])) {
                    if (!Time::compareTwoTimes($this->nextBillingDateGMT, $now,
                        $this->aPaymentMeta['trialPeriodDays'])
                    ) {
                        $this->nextBillingDateGMT = strtotime('+' . $this->aPaymentMeta['trialPeriodDays'] . ' days');
                        FileSystem::logSuccess('This is a Trial plan. Next Billing Date: ' . date('Y m d',
                                $this->nextBillingDateGMT));
                    }
                }

                if (empty($this->nextBillingDateGMT)) {
                    if (isset($this->oEvent->data->object->lines) && isset
                        ($this->oEvent->data->object->lines->data[0])
                    ) {
                        $this->nextBillingDateGMT = $this->oEvent->data->object->lines->data[0]->period->end;
                    } elseif (isset($this->oEvent->data->object->period_end)) {
                        $this->nextBillingDateGMT = $this->oEvent->data->object->period_end;
                    } else {
                        if (isset($this->aPaymentMeta['planID'])) {
                            $aPlanSettings = GetSettings::getPlanSettings($this->aPaymentMeta['planID']);
                            if (empty($aPlanSettings['regular_period'])) {
                                $nextBillingDate = strtotime('2100-1-1');
                            } else {
                                $nextBillingDate = strtotime('+' . $aPlanSettings['regular_period'] . ' days');
                            }

                            $this->nextBillingDateGMT = Time::timestampUTC($nextBillingDate);
                        }
                    }
                }
            }

            return true;
        } else {
            if ($this->oEvent->type == 'invoice.payment_succeeded') {
                $this->intentID = $this->oEvent->data->object->payment_intent;
            } else {
                $this->intentID = $this->oEvent->data->object->id;
            }

            if (isset($this->oEvent->data->object->charges) && isset($this->oEvent->data->object->charges->data[0])) {
                $this->chargeID = $this->oEvent->data->object->charges->data[0]->id;
            }

            if (empty($this->intentID)) {
                FileSystem::logError('The stripe intent is emptied');

                return false;
            }
            $this->paymentID = PaymentMetaModel::getPaymentIDByToken($this->intentID);
            $this->aPaymentMeta = PaymentMetaModel::getPaymentInfo($this->paymentID);

            if (empty($this->paymentID)) {
                FileSystem::logError(sprintf('We could not found payment id by %s stripe intent', $this->intentID));

                return false;
            }
        }

        return true;
    }

    public function handler()
    {
        FileSystem::logPayment('stripe-webhook.log', json_encode($this->oEvent));

        if (!in_array($this->oEvent->type, [
            'invoice.payment_succeeded',
            'charge.failed',
            'charge.dispute.created',
            'payment_intent.succeeded',
            'checkout.session.completed',
            'customer.subscription.deleted',
        ])
        ) {
            return false;
        }

        $status = $this->verifyWebhook();
        FileSystem::logPayment(
            "stripe.log",
            "Log status $status"
        );

        if (!$status) {
            return false;
        }

        $billingType = PaymentModel::getField('billingType', $this->paymentID);
        if (GetWilokeSubmission::isNonRecurringPayment($billingType)) {
            $oProceedWebhook = new ProceededPaymentHook(new StripeProceededNonRecurringPaymentHook($this));
            $oPrePareInvoiceFormat = new StripeNonRecurringIPreparenvoiceFormat($this);
        } else {
            $oProceedWebhook = new ProceededPaymentHook(new StripeProceededRecurringPayment($this));
            $oPrePareInvoiceFormat = new StripeRecurringPrepareInvoiceFormat($this);
        }

        switch ($this->oEvent->type) {
            case 'payment_intent.succeeded':
                if (!GetWilokeSubmission::isNonRecurringPayment($billingType)) {
                    return false;
                }

                $this->aInvoiceFormat = $oPrePareInvoiceFormat->prepareInvoiceParam()->getParams();
                $oProceedWebhook->doCompleted();
                break;

            case 'checkout.session.completed':
                $this->aInvoiceFormat = $oPrePareInvoiceFormat->prepareInvoiceParam()->getParams();
                $oProceedWebhook->doCompleted();
                break;
            case 'invoice.payment_succeeded':
                FileSystem::logPayment(
                    "stripe.log",
                    'Starting stripe webhook ' . $billingType . ' Payment ID: ' . $this->paymentID,
                    __CLASS__
                );

                if (GetWilokeSubmission::isNonRecurringPayment($billingType)) {
                    /**
                     * @hooked: WilokeListingTools\Controllers\PaymentController:updatePaymentCompletedStatus 5
                     * @hooked: WilokeListingTools\Controllers\InvoiceController:stripePrepareInsertNonRecurringPaymentInvoice 6
                     */
                    $oProceedWebhook->doCompleted();

                    if (isset($this->oEvent->data->object->dispute) && !empty($this->oEvent->data->object->dispute)) {
                        FileSystem::logSuccess('Stripe Dispute', __CLASS__);
                        PaymentMetaModel::setDispute($this->paymentID, $this->oEvent->data->object->dispute);
                        PaymentMetaModel::setDisputeInfo($this->paymentID, $this->oEvent);
                        $this->oEvent = GetSettings::getOptions($this->oEvent->data->object->dispute);
                        SetSettings::deleteOption($this->oEvent->data->object->dispute);

                        $oProceedWebhook->doFailed();
                    }
                } else {
                    FileSystem::logSuccess('Stripe: Stripe Subscription Charged money of user. Payment ID: '
                        . $this->paymentID);

                    $this->aInvoiceFormat = $oPrePareInvoiceFormat->prepareInvoiceParam()->getParams();
                    /**
                     * @hooked: WilokeListingTools\Controllers\PaymentController:updatePaymentCompletedStatus 5
                     * @hooked: WilokeListingTools\Controllers\InvoiceController:stripePrepareInsertRecurringPayment 6
                     */
                    $oProceedWebhook->doCompleted();
                }
                break;
            case 'charge.failed':
                /**
                 * @hooked: WilokeListingTools\Controllers\PaymentController:updatePaymentFailedStatus 5
                 */
                $oProceedWebhook->doFailed();
                break;
            case 'charge.dispute.created':
                $disputeID = $this->oEvent->data->object->id;
                SetSettings::setOptions($disputeID, json_encode($this->oEvent));
                break;
            case 'customer.subscription.deleted':
                $oProceedWebhook->doCancelled();
                break;
        }
    }

    /**
     * @return bool
     */
    public function verify()
    {
        if (!isset($_SERVER['HTTP_STRIPE_SIGNATURE'])) {
            return false;
        }

        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'];

        try {
            $this->getPayload();
            $this->oEvent = Webhook::constructEvent(
                $this->rawPayloadData,
                $sigHeader,
                $this->getEndpointSecret()
            );
            /**
             * WebhookLog@logSuccess
             */
            do_action(
                'wilcity/wiloke-listing-tools/app/Framework/Payment/Stripe/StripeWebhook/success',
                [
                    'msg'        => $this->rawPayloadData,
                    'codeStatus' => 200,
                    'method'     => __METHOD__,
                    'class'      => __CLASS__
                ]
            );

            return true;
        }
        catch (Exception $e) {
            /**
             * WebhookLog@logError
             */
            do_action(
                'wilcity/wiloke-listing-tools/app/Framework/Payment/Stripe/StripeWebhook/error',
                [
                    'msg'        => $e->getMessage(),
                    'codeStatus' => 400,
                    'method'     => __METHOD__,
                    'class'      => __CLASS__
                ]
            );

            http_response_code(400); // PHP 5.4 or greater
            exit();
        }
    }

    public function observer()
    {
        if (isset($_REQUEST['wiloke-submission-listener']) &&
            trim($_REQUEST['wiloke-submission-listener']) == $this->gateway
        ) {
            $this->setApiContext();
            $status = $this->verify();

            if (!$status) {
                return false;
            }

            $this->handler();
        }
    }

    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        return null;
    }

    public function __isset($name)
    {
        return !empty($this->$name);
    }
}
