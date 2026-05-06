<?php

namespace WilokeListingTools\Framework\Payment\Stripe;

use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Payment\PaymentHook\ProceededNonRecurringPaymentHookAbstract;
use WilokeListingTools\Framework\Payment\PaymentHook\ProceededPaymentHookInterface;

class StripeProceededNonRecurringPaymentHook extends ProceededNonRecurringPaymentHookAbstract implements
    ProceededPaymentHookInterface
{
    protected $oStripeWebhook;
    protected $aArgs;
    
    public function __construct(StripeWebhook $oStripeWebhook)
    {
        $this->oStripeWebhook = $oStripeWebhook;
        parent::__construct($this->oStripeWebhook->paymentID);
        $this->getCommonArgs();
    }
    
    private function getCommonArgs()
    {
        $this->aArgs = $this->setupSuccessArgs();
    }
    
    public function completed()
    {
        if (!empty($this->oStripeWebhook->intentID)) {
            $this->aArgs['intentID'] = $this->oStripeWebhook->intentID;
        } else {
            FileSystem::logError('Missing Intent ID. Payment ID: '.json_encode($this->oStripeWebhook->oEvent).
                                 ' Intend ID '.$this->oStripeWebhook->intentID);
            
            return false;
        }
        
        $this->aArgs['oEvent']         = $this->oStripeWebhook->oEvent;
        $this->aArgs['stripeEventID']  = $this->oStripeWebhook->oEvent->id;
        $this->aArgs['aInvoiceFormat'] = $this->oStripeWebhook->aInvoiceFormat;
        $this->aArgs['chargeID']       = $this->oStripeWebhook->chargeID;
        
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
        // TODO: Implement refunded() method.
    }
    
    public function active()
    {
        // TODO: Implement active() method.
    }
}
