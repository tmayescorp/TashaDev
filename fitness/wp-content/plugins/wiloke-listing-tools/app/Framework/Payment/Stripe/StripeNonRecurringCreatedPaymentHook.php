<?php

namespace WilokeListingTools\Framework\Payment\Stripe;

use WilokeListingTools\Framework\Payment\PaymentHook\CreatedPaymentHookInterface;
use WilokeListingTools\Framework\Payment\PaymentHook\NonRecurringCreatedPaymentHookAbstract;

class StripeNonRecurringCreatedPaymentHook extends NonRecurringCreatedPaymentHookAbstract implements
    CreatedPaymentHookInterface
{
    protected $aArgs;
    
    public function success()
    {
        $this->aArgs             = $this->setupSuccessArgs();
        $this->aArgs['token']    = $this->oPaymentInterface->token;
        $this->aArgs['postID']   = $this->oPaymentInterface->postID;
        $this->aArgs['discount'] = $this->oPaymentInterface->oReceipt->getDiscount();
        $this->aArgs['total']    = $this->oPaymentInterface->oReceipt->getTotal();
        $this->aArgs['subTotal'] = $this->oPaymentInterface->oReceipt->getSubTotal();
        $this->aArgs['tax']      = $this->oPaymentInterface->oReceipt->getTax();
        
        /**
         * WilokeListingTools\Controllers\PaymentController@insertNewPayment
         * WilokeListingTools\Controllers\SessionController@destroySessionAfterCreatedStripeSession 99
         */
        do_action('wilcity/wiloke-listing-tools/before/insert-payment', $this->aArgs);
    }
    
    public function error()
    {
        // TODO: Implement error() method.
    }
}
