<?php

namespace WilokeListingTools\Framework\Payment\Stripe;

use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Payment\PaymentHook\ProceededPaymentHookInterface;
use WilokeListingTools\Framework\Payment\PaymentHook\ProceedRecurringPaymentHookAbstract;

class StripeProceededRecurringPayment extends ProceedRecurringPaymentHookAbstract implements
    ProceededPaymentHookInterface
{
    protected $oStripeWebhook;
    protected $aArgs;
    
    public function __construct(StripeWebhook $oStripeWebhook)
    {
        $this->oStripeWebhook = $oStripeWebhook;
        parent::__construct($this->oStripeWebhook->paymentID, $this->oStripeWebhook->nextBillingDateGMT);
        $this->getCommonArgs();
    }
    
    private function getCommonArgs()
    {
        $this->aArgs = $this->setupSuccessArgs();
    }
    
    private function verifySubscriptionID()
    {
        $this->aArgs['subscriptionID'] = $this->oStripeWebhook->subscriptionID;
        
        if (empty($this->aArgs['subscriptionID'])) {
            FileSystem::logError('Missing subscriptionID. Payment ID: '.$this->oStripeWebhook->paymentID);
            
            return false;
        }
        
        return true;
    }
    
    public function completed()
    {
        if (!$this->verifySubscriptionID()) {
            return false;
        }
        
        $this->aArgs['oEvent']         = $this->oStripeWebhook->oEvent;
        $this->aArgs['stripeEventID']  = $this->oStripeWebhook->oEvent->id;
        $this->aArgs['aInvoiceFormat'] = $this->oStripeWebhook->aInvoiceFormat;
        
        /**
         * @hooked: WilokeListingTools\Controllers\PaymentController:updatePaymentCompletedStatus 5
         * @hooked: WilokeListingTools\Controllers\InvoiceController:prepareInsertInvoice 6
         */
        do_action('wilcity/wiloke-listing-tools/'.$this->aArgs['billingType'].'/payment-gateway-completed',
            $this->aArgs);
    }
    
    public function disputed()
    {
        $this->aArgs['oEvent'] = $this->oStripeWebhook->oEvent;
        
        /**
         * @hooked: PaymentController:'wilcity/wiloke-listing-tools/'.$billingType
         * .'/stripe/payment-disputed'
         */
        do_action('wilcity/wiloke-listing-tools/'.$this->aArgs['billingType'].'/payment-gateway-disputed',
            $this->aArgs);
    }
    
    public function failed()
    {
        /**
         * @hooked: WilokeListingTools\Controllers\PaymentController:updatePaymentFailedStatus 5
         */
        do_action('wilcity/wiloke-listing-tools/'.$this->aArgs['billingType'].'/stripe/payment-gateway-failed',
            $this->aArgs);
    }
    
    public function refunded()
    {
    
    }
    
    public function active()
    {
        // TODO: Implement active() method.
    }
    
    public function reactivate()
    {
        // TODO: Implement reactivate() method.
    }
}
