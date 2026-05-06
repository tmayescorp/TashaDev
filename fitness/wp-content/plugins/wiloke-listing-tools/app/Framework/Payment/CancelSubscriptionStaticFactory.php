<?php

namespace WilokeListingTools\Framework\Payment;

use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Controllers\RetrieveController;
use WilokeListingTools\Framework\Payment\PayPal\PayPalCancelRecurringPayment;
use WilokeListingTools\Framework\Payment\Stripe\StripeCancelRecurringPayment;
use WilokeListingTools\Framework\Payment\WooCommerce\WooCommerceCancelRecurringPayment;

class CancelSubscriptionStaticFactory
{
    /**
     * @param $gateway
     * @param $isNonRecurringPayment
     *
     * @return mixed
     */
    public static function get($gateway)
    {
        switch ($gateway) {
            case 'stripe':
                $oPaymentMethod = new StripeCancelRecurringPayment();
                break;
            case 'paypal':
                $oPaymentMethod = new PayPalCancelRecurringPayment();
                break;
            case 'woocommerce':
                $oPaymentMethod = new WooCommerceCancelRecurringPayment();
                break;
            case 'banktransfer':
                break;
        }
        
        if (!isset($oPaymentMethod)) {
            $oRetrieveController = new RetrieveController(new AjaxRetrieve());
            $oRetrieveController->error([
                'msg' => esc_html__('We have not found any payment gateway yet', 'wiloke-listing-tools')
            ]);
            return false;
        }
        
        return $oPaymentMethod;
    }
}
