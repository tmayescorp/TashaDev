<?php

namespace WilokeListingTools\Framework\Payment\FreePlan;

use WilokeListingTools\Framework\Payment\PaymentHook\CreatedPaymentHookInterface;
use WilokeListingTools\Framework\Payment\PaymentHook\NonRecurringCreatedPaymentHookAbstract;

final class FreePlanCreatedPaymentHook extends NonRecurringCreatedPaymentHookAbstract implements
    CreatedPaymentHookInterface
{
    public function success()
    {
        $aArgs                = $this->setupSuccessArgs();
        $aArgs['token']       = $this->oPaymentInterface->token;
        $aArgs['postID']      = $this->oPaymentInterface->postID;
        $aArgs['total']       = $this->oPaymentInterface->oReceipt->getTotal();
        $aArgs['subTotal']    = $this->oPaymentInterface->oReceipt->getSubTotal();
        $aArgs['discount']    = $this->oPaymentInterface->oReceipt->getDiscount();
        $aArgs['tax']         = $this->oPaymentInterface->oReceipt->getTax();
        $aArgs['status']      = 'succeeded';
        $aArgs['billingType'] = wilokeListingToolsRepository()->get('payment:billingTypes', true)->sub('nonrecurring');
      
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
