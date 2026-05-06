<?php

namespace WilokeListingTools\Framework\Payment\PayPal;

use PayPal\Api\Agreement;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Payment\PayPalPayment;
use WilokeListingTools\Framework\Payment\ProceededPaymentHook;
use WilokeListingTools\Framework\Payment\WebhookInterface;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;

class PayPalWebhook extends PayPalPayment implements WebhookInterface
{
    protected $oEvent;
    protected $subscriptionID;
    protected $intentID;
    protected $billingType;
    protected $aPaymentMeta;
    protected $oPrePareInvoiceFormat;
    protected $aInvoiceFormat;
    protected $nextBillingDateGMT;
    
    public function __set($name, $value)
    {
        $this->$name = $value;
    }
    
    public function observer()
    {
        if (!isset($_REQUEST['wiloke-submission-listener']) ||
            ($_REQUEST['wiloke-submission-listener'] != $this->gateway)
        ) {
            return false;
        }
        
        $this->handler();
    }
    
    public function verify()
    {
        switch ($this->oEvent->event_type) {
            case 'BILLING.SUBSCRIPTION.CREATED':
                $this->nextBillingDateGMT                 =
                    $this->oEvent->resource->agreement_details->next_billing_date;
                $this->subscriptionID                     = $this->oEvent->resource->id;
                $this->aPaymentMeta['nextBillingDateGMT'] = Time::timestampUTCNow($this->nextBillingDateGMT);
                break;
            case 'PAYMENT.SALE.COMPLETED':
                if (isset($this->oEvent->resource->billing_agreement_id)) {
                    $this->setupConfiguration();
                    $oAgreementCheck                          =
                        Agreement::get($this->oEvent->resource->billing_agreement_id, $this->oApiContext);
                    $this->subscriptionID                     = $this->oEvent->resource->billing_agreement_id;
                    $oAgreementDetails                        = $oAgreementCheck->getAgreementDetails();
                    $this->nextBillingDateGMT                 = $oAgreementDetails->getNextBillingDate();
                    $this->aPaymentMeta['nextBillingDateGMT'] = Time::timestampUTCNow($this->nextBillingDateGMT);
                } else {
                    $this->intentID = $this->oEvent->resource->parent_payment;
                }
                break;
            case 'PAYMENT.SALE.REFUNDED':
                $this->intentID = $this->oEvent->resource->parent_payment;
                break;
            case 'BILLING.SUBSCRIPTION.CANCELLED':
            case 'BILLING.SUBSCRIPTION.SUSPENDED':
            case 'BILLING.SUBSCRIPTION.RE-ACTIVATED':
                $this->subscriptionID = $this->oEvent->resource->id;
                if ($this->oEvent->event_type == 'BILLING.SUBSCRIPTION.RE-ACTIVATED') {
                    $this->setupConfiguration();
                    try {
                        $oAgreement                               =
                            Agreement::get($this->oEvent->resource->id, $this->oApiContext);
                        $this->aPaymentMeta['nextBillingDateGMT'] = Time::timeFromNow
                        ($oAgreement->agreement_details->next_billing_date);
                    } catch (\Exception $ex) {
                        FileSystem::logError('We could not reactivate PayPal Subscription. Payment ID:'.
                                             $this->paymentID);
                        
                        return false;
                    }
                }
                break;
        }
        
        if (!empty($this->subscriptionID)) {
            $this->paymentID = PaymentMetaModel::getPaymentIDBySubscriptionID($this->subscriptionID);
            $msg             =
                'Maybe Error: We could not found payment id by this subscription payment id '.$this->subscriptionID;
        } else if (!empty($this->intentID)) {
            $msg             =
                'Maybe Error: We could not find any payment id by the following payment intent id '.$this->intentID;
            $this->paymentID = PaymentMetaModel::getPaymentIDByIntentID($this->intentID);
        }
        
        if (empty($this->paymentID)) {
            if (isset($msg)) {
                FileSystem::logError($msg);
            }
            
            return false;
        }
        
        return true;
    }
    
    public function handler()
    {
        $rawdata = file_get_contents('php://input');
        if (empty($rawdata)) {
            return false;
        }
        
        $this->oEvent = json_decode($rawdata);
        FileSystem::logPayment('paypal-webhook.log', $rawdata);
        
        if (!$this->verify()) {
            return false;
        }
        
        $this->billingType  = PaymentModel::getField('billingType', $this->paymentID);
        $this->aPaymentMeta = PaymentMetaModel::getPaymentInfo($this->paymentID);
        
        if (GetWilokeSubmission::isNonRecurringPayment($this->billingType)) {
            $oProceedWebhook       = new ProceededPaymentHook(new PayPalProceededNonRecurringPaymentHook($this));
            $oPrePareInvoiceFormat = new PayPalNonRecurringPrepareInvoiceFormat($this);
        } else {
            $oProceedWebhook       = new ProceededPaymentHook(new PayPalProceededRecurringPaymentHook($this));
            $oPrePareInvoiceFormat = new PayPalRecurringPrepareInvoiceFormat($this);
        }
        
        switch ($this->oEvent->event_type) {
            case 'BILLING.SUBSCRIPTION.CREATED':
                $oProceedWebhook->doCompleted();
                break;
            case 'PAYMENT.SALE.COMPLETED':
                $this->aInvoiceFormat = $oPrePareInvoiceFormat->prepareInvoiceParam()->getParams();
                $oProceedWebhook->doCompleted();
                break;
            case 'PAYMENT.SALE.REFUNDED':
                $this->aInvoiceFormat = $oPrePareInvoiceFormat->prepareInvoiceParam()->getParams();
                $oProceedWebhook->doRefunded();
                break;
            case 'BILLING.SUBSCRIPTION.CANCELLED':
                $oProceedWebhook->doCancelled();
                break;
            case 'BILLING.SUBSCRIPTION.SUSPENDED':
                $oProceedWebhook->doSuspended();
                break;
            case 'BILLING.SUBSCRIPTION.RE-ACTIVATED':
                $oProceedWebhook->doReactive();
                break;
        }
    }
}
