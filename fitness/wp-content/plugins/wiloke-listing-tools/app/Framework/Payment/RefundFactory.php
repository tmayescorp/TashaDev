<?php

namespace WilokeListingTools\Framework\Payment;

use WilokeListgoFunctionality\Framework\Payment\PayPal\PayPalRefundNonRecurringPayment;
use WilokeListingTools\Framework\Payment\Stripe\StripeRefundNonRecurringPayment;

class RefundFactory
{
    private static $oPayment;
    
    public static function get($gateway)
    {
        switch ($gateway) {
            case 'stripe':
                self::$oPayment = new StripeRefundNonRecurringPayment();
                break;
            case 'paypal':
                self::$oPayment = new PayPalRefundNonRecurringPayment();
                break;
            default:
                break;
        }
        
        return self::$oPayment;
    }
}
