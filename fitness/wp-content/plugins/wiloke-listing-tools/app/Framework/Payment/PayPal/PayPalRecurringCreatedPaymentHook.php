<?php

namespace WilokeListingTools\Framework\Payment\PayPal;

use WilokeListingTools\Framework\Payment\PaymentHook\CreatedPaymentHookInterface;
use WilokeListingTools\Framework\Payment\PaymentHook\RecurringCreatedPaymentHookAbstract;

class PayPalRecurringCreatedPaymentHook extends RecurringCreatedPaymentHookAbstract implements CreatedPaymentHookInterface
{
    public function success()
    {
        $aArgs           = $this->setupSuccessArgs();
        $aArgs['postID'] = $this->oPaymentInterface->postID;
        $aArgs['token']  = $this->oPaymentInterface->token;
        
        /**
         * WilokeListingTools\Controllers\PaymentController@insertNewPayment
         * WilokeListingTools\Controllers\SessionController@destroySessionAfterCreatedStripeSession 99
         */
        do_action('wilcity/wiloke-listing-tools/before/insert-payment', $aArgs);
    }
    
    public function error()
    {
        // TODO: Implement error() method.
    }
}
