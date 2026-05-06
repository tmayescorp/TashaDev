<?php

namespace WilokeListingTools\Framework\Payment\DirectBankTransfer;

use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Payment\PaymentHook\ProceededPaymentHookInterface;
use WilokeListingTools\Framework\Payment\PaymentHook\ProceedRecurringPaymentHookAbstract;

final class DirectBankTransferProceededRecurringPaymentHook extends ProceedRecurringPaymentHookAbstract implements
    ProceededPaymentHookInterface
{
    protected $aArgs;
 
    private $oDirectBankTransfer;
    
    /**
     * DirectBankTransferProceededRecurringPaymentHook constructor.
     *
     * @param DirectBankTransferWebhook $oDirectBankTransfer
     */
    public function __construct(DirectBankTransferWebhook $oDirectBankTransfer)
    {
        $this->oDirectBankTransfer = $oDirectBankTransfer;
        $nextBillingDateGMT        = '';
        if (isset($this->oDirectBankTransfer->nextBillingDateGMT)) {
            $nextBillingDateGMT = $this->oDirectBankTransfer->nextBillingDateGMT;
        } elseif (isset($this->oDirectBankTransfer->aPaymentMeta['nextBillingDateGMT'])) {
            $nextBillingDateGMT = $this->oDirectBankTransfer->aPaymentMeta['nextBillingDateGMT'];
        }
        
        parent::__construct(
            $this->oDirectBankTransfer->paymentID,
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
        if (isset($this->oDirectBankTransfer->subscriptionID)) {
            $this->aArgs['subscriptionID'] = $this->oDirectBankTransfer->subscriptionID;
        } elseif (isset($this->oDirectBankTransfer->aPaymentMeta['subscriptionID'])) {
            $this->aArgs['subscriptionID'] = $this->oDirectBankTransfer->aPaymentMeta['subscriptionID'];
        }
        
        if (empty($this->aArgs['subscriptionID'])) {
            FileSystem::logError('Missing subscriptionID ID. Payment ID: '.$this->oDirectBankTransfer->paymentID);
            
            return false;
        }
        
        return true;
    }
    
    public function completed()
    {
        if (!$this->verifySubscriptionID()) {
            return false;
        }
        
        $this->aArgs['aInvoiceFormat'] = $this->oDirectBankTransfer->aInvoiceFormat;
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
