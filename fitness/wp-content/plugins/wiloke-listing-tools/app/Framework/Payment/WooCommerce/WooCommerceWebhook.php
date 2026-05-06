<?php

namespace WilokeListingTools\Framework\Payment\WooCommerce;

use WC_Subscriptions_Renewal_Order;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\Logger;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Helpers\WooCommerce;
use WilokeListingTools\Framework\Payment\CreatedPaymentHook;
use WilokeListingTools\Framework\Payment\ProceededPaymentHook;
use WilokeListingTools\Framework\Payment\Receipt\ReceiptStructureInterface;
use WilokeListingTools\Framework\Payment\WebhookInterface;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;

class WooCommerceWebhook implements WebhookInterface
{
    protected $billingType;
    protected $paymentID;
    protected $aPaymentIDs;
    protected $nextBillingDateGMT;
    protected $customerID;
    protected $oPrePareInvoiceFormat;
    protected $aPaymentMeta;
    protected $aInvoiceFormat;
    protected $orderID;
    protected $oOrder;
    protected $token;
    protected $isRefunded = false;

    public function observer()
    {
        $this->handler();
    }

    public function verify()
    {
    }

    public function handler()
    {
        add_action('woocommerce_order_status_completed', [$this, 'paymentSucceeded'], 1);
        add_action('woocommerce_order_status_failed', [$this, 'paymentFailed'], 5);
        add_action('woocommerce_order_status_refunded', [$this, 'paymentRefunded'], 5);
        add_action('woocommerce_order_status_cancelled', [$this, 'paymentCancelled'], 5);
        add_action('woocommerce_order_status_processing', [$this, 'paymentSucceeded'], 5);

        add_action('woocommerce_subscription_payment_complete', [$this, 'subscriptionCreated'], 10);
//        add_action(
//            'woocommerce_scheduled_subscription_payment',
//            [$this, 'woocommerce_subscription_renewal_payment_complete']
//        );
        add_action('woocommerce_subscription_payment_failed', [$this, 'subscriptionFailed']);
        add_action('woocommerce_subscription_renewal_payment_failed', [$this, 'subscriptionFailed']);
        add_action('woocommerce_subscription_status_cancelled', [$this, 'subscriptionCancelled']);
        add_action('woocommerce_subscription_status_pending-cancel', [$this, 'subscriptionPendingCancelled']);

//        add_action('admin_init', [$this, 'reGenerateInvoice']);
    }

    public function subscriptionPendingCancelled(\WC_Subscription $that)
    {
//        $aPaymentIDs = PaymentModel::getPaymentIDsByWooOrderID($that->get_parent_id());
//        if (empty($aPaymentIDs)) {
//            return false;
//        }

        $this->aPaymentMeta['subscriptionID'] = $that->get_id();
        $this->orderID = $that->get_id();
        $parentOrderID = $that->get_parent_id();

        $this->getPaymentIDs($parentOrderID);
        if (empty($this->aPaymentIDs)) {
            return false;
        }

        foreach ($this->aPaymentIDs as $aPayment) {
            $this->paymentID = $aPayment['ID'];
            $this->aPaymentMeta = PaymentMetaModel::getPaymentInfo($this->paymentID);
            $this->billingType = WooCommerce::getBillingType($parentOrderID);

            if (!GetWilokeSubmission::isNonRecurringPayment($this->billingType)) {
                $oProceedWebhook = new ProceededPaymentHook(
                    new WooCommerceProceededRecurringPaymentHook($this)
                );

                $oPrePareInvoiceFormat = new WooCommereNonRecurringPrepareInvoiceFormat($this);

                $this->aInvoiceFormat = $oPrePareInvoiceFormat->prepareInvoiceParam()->getParams();
                $oProceedWebhook->doSuspended();
            }
        }
    }

    public function subscriptionCancelled(\WC_Subscription $that)
    {
//        $aPaymentIDs = PaymentModel::getPaymentIDsByWooOrderID($that->get_parent_id());
//        if (empty($aPaymentIDs)) {
//            return false;
//        }
        $this->aPaymentMeta['subscriptionID'] = $that->get_id();
        $this->orderID = $that->get_id();
        $parentOrderID = $that->get_parent_id();

        $this->getPaymentIDs($parentOrderID);
        if (empty($this->aPaymentIDs)) {
            return false;
        }

        foreach ($this->aPaymentIDs as $aPayment) {
            $this->paymentID = $aPayment['ID'];
            $this->aPaymentMeta = PaymentMetaModel::getPaymentInfo($this->paymentID);
            $this->billingType = WooCommerce::getBillingType($parentOrderID);

            if (!GetWilokeSubmission::isNonRecurringPayment($this->billingType)) {
                $oProceedWebhook = new ProceededPaymentHook(
                    new WooCommerceProceededRecurringPaymentHook($this)
                );

                $oPrePareInvoiceFormat = new WooCommereNonRecurringPrepareInvoiceFormat($this);

                $this->aInvoiceFormat = $oPrePareInvoiceFormat->prepareInvoiceParam()->getParams();
                $oProceedWebhook->doCancelled();
            }
        }
    }

    public function subscriptionFailed(\WC_Subscription $that)
    {
//        $aPaymentIDs = PaymentModel::getPaymentIDsByWooOrderID($that->get_parent_id());
//        if (empty($aPaymentIDs)) {
//            return false;
//        }

        $this->aPaymentMeta['subscriptionID'] = $that->get_id();
        $this->orderID = $that->get_id();
        $parentOrderID = $that->get_parent_id();

        $this->getPaymentIDs($parentOrderID);
        if (empty($this->aPaymentIDs)) {
            return false;
        }

        foreach ($this->aPaymentIDs as $aPayment) {
            $this->paymentID = $aPayment['ID'];
            $this->aPaymentMeta = PaymentMetaModel::getPaymentInfo($this->paymentID);
            $this->billingType = WooCommerce::getBillingType($parentOrderID);

            if (!GetWilokeSubmission::isNonRecurringPayment($this->billingType)) {
                $oProceedWebhook = new ProceededPaymentHook(
                    new WooCommerceProceededRecurringPaymentHook($this)
                );

                $oPrePareInvoiceFormat = new WooCommereNonRecurringPrepareInvoiceFormat($this);

                $this->aInvoiceFormat = $oPrePareInvoiceFormat->prepareInvoiceParam()->getParams();
                $oProceedWebhook->doFailed();
            }
        }
    }


    public function testPaymentIds()
    {
        $orderID = 20432;
        $this->oOrder = new \WC_Order($orderID);
        $this->aPaymentIDs = PaymentModel::getPaymentIDsByWooOrderID($orderID, false);

        $this->aPaymentMeta = PaymentMetaModel::getPaymentInfo($this->paymentID);
        $this->billingType = WooCommerce::getBillingType($orderID);

        foreach ($this->aPaymentIDs as $aPayment) {
            $this->paymentID = $aPayment['ID'];
            $this->aPaymentMeta = PaymentMetaModel::getPaymentInfo($this->paymentID);
            $this->billingType = WooCommerce::getBillingType($orderID);

            if (!GetWilokeSubmission::isNonRecurringPayment($this->billingType)) {
                $oProceedWebhook = new ProceededPaymentHook(
                    new WooCommerceProceededRecurringPaymentHook($this)
                );

                $oPrePareInvoiceFormat = new WooCommereNonRecurringPrepareInvoiceFormat($this);

                $this->aInvoiceFormat = $oPrePareInvoiceFormat->prepareInvoiceParam()->getParams();
                $oProceedWebhook->doCompleted();
            }
        }

    }

    public function reGenerateInvoice()
    {
        if (!isset($_GET['debug']) || !class_exists('wcs_get_subscriptions')) {
            return false;
        }


        $aSubscriptions = wcs_get_subscriptions([
            'order_id' => 5806
        ]);

        /**
         * @var $oSubscription  \WC_Subscription
         */
        foreach ($aSubscriptions as $oSubscription) {
            if ($oSubscription->get_status() === 'active') {
                $this->subscriptionCreated($oSubscription);
                $aRenewals = $oSubscription->get_related_orders();
                foreach ($aRenewals as $renewalId) {
                    $aSubscriptions = wcs_get_subscriptions_for_renewal_order($renewalId);

                    if (empty($aSubscriptions)) {
                        return false;
                    }

                    $oSubscription = end($aSubscriptions);

                    $this->generateInvoice($renewalId, $oSubscription, true);
                }
            }
        }
    }

    public function subscriptionCreated(\WC_Subscription $that)
    {
        $this->generateInvoice($that->get_id(), $that);
    }

    /**
     * @param $orderId . It may a subscriptionId or a renewal Id
     * @param \WC_Subscription $oSubscription
     * @return false
     */
    private function generateInvoice($orderId, \WC_Subscription $oSubscription, $isRenewal = false)
    {
        $this->nextBillingDateGMT = Time::timestampUTCNow($oSubscription->get_date('next_payment'));
        $this->aPaymentMeta['nextBillingDateGMT'] = $oSubscription->nextBillingDateGMT;
        $this->aPaymentMeta['subscriptionID'] = $orderId;

        $this->orderID = $orderId;
        $this->token = $orderId;
        $parentOrderID = $oSubscription->get_parent_id();
        $this->getPaymentIDs($parentOrderID);

        if (empty($this->aPaymentIDs)) {
            return false;
        }

        foreach ($this->aPaymentIDs as $aPayment) {
            $this->paymentID = $aPayment['ID'];
            $this->aPaymentMeta = PaymentMetaModel::getPaymentInfo($this->paymentID);
            $this->billingType = WooCommerce::getBillingType($parentOrderID);

            if (!GetWilokeSubmission::isNonRecurringPayment($this->billingType)) {
                $oProceedWebhook = new ProceededPaymentHook(
                    new WooCommerceProceededRecurringPaymentHook($this)
                );

                $oPrePareInvoiceFormat = new WooCommereNonRecurringPrepareInvoiceFormat($this);

                $this->aInvoiceFormat = $oPrePareInvoiceFormat->prepareInvoiceParam()->getParams();
                $oProceedWebhook->doCompleted();

//                if ($orderId == 7099) {
//                    var_export($this->aInvoiceFormat);die;
//                }
            }
        }
    }

    private function getPaymentIDs($orderID = '')
    {
        $orderID = empty($orderID) ? $this->orderID : $orderID;
        $this->oOrder = new \WC_Order($orderID);
        $this->aPaymentIDs = PaymentModel::getPaymentIDsByWooOrderID($orderID, false);
    }

    public function paymentSucceeded($orderID)
    {
        $this->orderID = $orderID;
        $this->getPaymentIDs();

        if (empty($this->aPaymentIDs)) {
            return false;
        }

        foreach ($this->aPaymentIDs as $aPayment) {
            $this->paymentID = $aPayment['ID'];
            $this->aPaymentMeta = PaymentMetaModel::getPaymentInfo($this->paymentID);
            $this->billingType = WooCommerce::getBillingType($orderID);

            if (GetWilokeSubmission::isNonRecurringPayment($this->billingType)) {
                $oProceedWebhook = new ProceededPaymentHook(
                    new WooCommerceProceededNonRecurringPaymentHook($this)
                );

                $oPrePareInvoiceFormat = new WooCommereNonRecurringPrepareInvoiceFormat($this);

                $this->aInvoiceFormat = $oPrePareInvoiceFormat->prepareInvoiceParam()->getParams();
                $oProceedWebhook->doCompleted();
            }
        }

        /**
         * @hooked SessionController:deletePaymentSessions
         */
        do_action('wiloke-submission/payment-succeeded-and-updated-everything');
    }

    public function paymentFailed($orderID)
    {
        $this->orderID = $orderID;
        $this->getPaymentIDs();
        if (empty($this->aPaymentIDs)) {
            return false;
        }

        foreach ($this->aPaymentIDs as $aPayment) {
            $this->paymentID = $aPayment['ID'];
            $this->aPaymentMeta = PaymentMetaModel::getPaymentInfo($this->paymentID);
            $this->billingType = WooCommerce::getBillingType($orderID);
//            if (GetWilokeSubmission::isNonRecurringPayment($this->billingType)) {
            $oProceedWebhook = new ProceededPaymentHook(
                new WooCommerceProceededNonRecurringPaymentHook($this)
            );

            $oProceedWebhook->doFailed();
//            }
        }

        /**
         * @hooked SessionController:deletePaymentSessions
         */
        do_action('wiloke-submission/payment-succeeded-and-updated-everything');
    }

    public function paymentRefunded($orderID)
    {
        $this->orderID = $orderID;
        $this->getPaymentIDs();
        if (empty($this->aPaymentIDs)) {
            return false;
        }

        foreach ($this->aPaymentIDs as $aPayment) {
            $this->paymentID = $aPayment['ID'];
            $this->aPaymentMeta = PaymentMetaModel::getPaymentInfo($this->paymentID);
            $this->billingType = WooCommerce::getBillingType($orderID);
//            if (GetWilokeSubmission::isNonRecurringPayment($this->billingType)) {
            $oProceedWebhook = new ProceededPaymentHook(
                new WooCommerceProceededNonRecurringPaymentHook($this)
            );
            $this->isRefunded = true;

            $oPrePareInvoiceFormat = new WooCommereNonRecurringPrepareInvoiceFormat($this);
            $this->aInvoiceFormat = $oPrePareInvoiceFormat->prepareInvoiceParam()->getParams();
            $oProceedWebhook->doRefunded();
//            }
        }

        /**
         * @hooked SessionController:deletePaymentSessions
         */
        do_action('wiloke-submission/payment-succeeded-and-updated-everything');
    }

    public function paymentCancelled($orderID)
    {
        $this->orderID = $orderID;
        $this->getPaymentIDs();
        if (empty($this->aPaymentIDs)) {
            return false;
        }

        foreach ($this->aPaymentIDs as $aPayment) {
            $this->paymentID = $aPayment['ID'];
            $this->aPaymentMeta = PaymentMetaModel::getPaymentInfo($this->paymentID);
            $this->billingType = WooCommerce::getBillingType($orderID);
//            if (GetWilokeSubmission::isNonRecurringPayment($this->billingType)) {
            $oProceedWebhook = new ProceededPaymentHook(
                new WooCommerceProceededNonRecurringPaymentHook($this)
            );
            $oProceedWebhook->doCancelled();
//            }
        }
    }

    public function __get($name)
    {
        return $this->$name;
    }

    public function __isset($name)
    {
        return !empty($this->$name);
    }
}
