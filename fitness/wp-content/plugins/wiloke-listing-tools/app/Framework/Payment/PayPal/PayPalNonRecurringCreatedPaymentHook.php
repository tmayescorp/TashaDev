<?php

namespace WilokeListingTools\Framework\Payment\PayPal;


use WilokeListingTools\Framework\Payment\PaymentHook\CreatedPaymentHookInterface;
use WilokeListingTools\Framework\Payment\PaymentHook\NonRecurringCreatedPaymentHookAbstract;

final class PayPalNonRecurringCreatedPaymentHook extends NonRecurringCreatedPaymentHookAbstract implements
    CreatedPaymentHookInterface
{
    public function success()
    {
        $aArgs = $this->setupSuccessArgs();
        if (method_exists($this->oPaymentInterface, 'getToken')) {
            $aArgs['token'] = $this->oPaymentInterface->getToken();
        } else {
            $aArgs['token'] = $this->oPaymentInterface->token;
        }
        $aArgs['postID'] = $this->oPaymentInterface->postID;

        /**
         * WilokeListingTools\Controllers\PaymentController@insertNewPayment
         * WilokeListingTools\Controllers\SessionController@destroySessionAfterCreatedStripeSession 99
         */
//        do_action('wilcity/wiloke-listing-tools/paypal/created-section', $aArgs);
        do_action('wilcity/wiloke-listing-tools/before/insert-payment', $aArgs);
    }

    public function error()
    {
        // TODO: Implement error() method.
    }
}
