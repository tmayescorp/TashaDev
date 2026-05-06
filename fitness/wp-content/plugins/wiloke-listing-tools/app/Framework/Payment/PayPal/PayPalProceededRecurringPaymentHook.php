<?php

namespace WilokeListingTools\Framework\Payment\PayPal;

use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Payment\PaymentHook\ProceededPaymentHookInterface;
use WilokeListingTools\Framework\Payment\PaymentHook\ProceedRecurringPaymentHookAbstract;
use WilokeListingTools\Framework\Payment\PayPalPayment;
use WilokeListingTools\Framework\Payment\WebhookInterface;

final class PayPalProceededRecurringPaymentHook extends ProceedRecurringPaymentHookAbstract implements
    ProceededPaymentHookInterface
{
    protected $aArgs;
    private $oPayPalExecution;
    
    /**
     * PayPalProceededNonRecurringPayment constructor.
     *
     * @param PayPalExecuteNonRecurringPayment $oPayPalExecution
     */
    public function __construct(PayPalPayment $oPayPalExecution)
    {
        $this->oPayPalExecution = $oPayPalExecution;
        $nextBillingDateGMT     = '';
        if (isset($this->oPayPalExecution->nextBillingDateGMT)) {
            $nextBillingDateGMT = $this->oPayPalExecution->nextBillingDateGMT;
        } elseif (isset($this->oPayPalExecution->aPaymentMeta['nextBillingDateGMT'])) {
            $nextBillingDateGMT = $this->oPayPalExecution->aPaymentMeta['nextBillingDateGMT'];
        }
        
        parent::__construct(
            $this->oPayPalExecution->paymentID,
            $nextBillingDateGMT
        );
        $this->getCommonArgs();
    }
    
    private function getCommonArgs()
    {
        $this->aArgs = $this->setupSuccessArgs();
    }
    
    private function verifySubscriptionID()
    {
        if (isset($this->oPayPalExecution->subscriptionID)) {
            $this->aArgs['subscriptionID'] = $this->oPayPalExecution->subscriptionID;
        } elseif (isset($this->oPayPalExecution->aPaymentMeta['subscriptionID'])) {
            $this->aArgs['subscriptionID'] = $this->oPayPalExecution->aPaymentMeta['subscriptionID'];
        }
    
        if (empty($this->aArgs['subscriptionID'])) {
            FileSystem::logError('Missing subscriptionID ID. Payment ID: '.$this->oPayPalExecution->paymentID);
        
            return false;
        }
        
        return true;
    }
    
    public function completed()
    {
        if (!$this->verifySubscriptionID()) {
            return false;
        }
        
        $this->aArgs['aInvoiceFormat'] = $this->oPayPalExecution->aInvoiceFormat;
        
        /**
         * @hooked: WilokeListingTools\Controllers\PaymentController:updatePaymentCompletedStatus 5
         */
        do_action('wilcity/wiloke-listing-tools/'.$this->aArgs['billingType'].'/payment-gateway-completed',
            $this->aArgs);
        
        /**
         * @hooked: SessionController:deletePaymentSessions
         */
        do_action('wiloke-submission/payment-succeeded-and-updated-everything');
    }
    
    public function active()
    {
        $this->completed();
    }
    
    public function suspended()
    {
        if (!$this->verifySubscriptionID()) {
            return false;
        }
        
        do_action('wilcity/wiloke-listing-tools/'.$this->aArgs['billingType'].'/payment-gateway-suspended',
            $this->aArgs);
    
        /**
         * @hooked: SessionController:deletePaymentSessions
         */
        do_action('wiloke-submission/payment-succeeded-and-updated-everything');
    }
    
    public function reactivate()
    {
        if (!$this->verifySubscriptionID()) {
            return false;
        }
    
        $this->completed();
    }
}
