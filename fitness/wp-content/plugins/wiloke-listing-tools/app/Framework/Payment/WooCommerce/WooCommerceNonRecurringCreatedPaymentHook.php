<?php

namespace WilokeListingTools\Framework\Payment\WooCommerce;

use WilokeListingTools\Framework\Payment\PaymentHook\CreatedPaymentHookInterface;
use WilokeListingTools\Framework\Payment\PaymentHook\NonRecurringCreatedPaymentHookAbstract;

class WooCommerceNonRecurringCreatedPaymentHook extends NonRecurringCreatedPaymentHookAbstract implements
    CreatedPaymentHookInterface
{
    public function success()
    {
        $aArgs              = $this->setupSuccessArgs();
        $aArgs['token']     = $this->oPaymentInterface->token;
        $aArgs['postID']    = $this->oPaymentInterface->postID;
        $aArgs['productID'] = $this->oPaymentInterface->oReceipt->getProductID();
        $aArgs['orderID']   = $this->oPaymentInterface->oReceipt->getOrderID();
        
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
