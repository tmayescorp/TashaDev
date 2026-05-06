<?php

namespace WilokeListingTools\Framework\Payment;

use WilokeListingTools\Controllers\MessageController;
use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Controllers\Retrieve\RetrieveFactory;
use WilokeListingTools\Controllers\RetrieveController;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Payment\DirectBankTransfer\DirectBankTransferNonRecurringPayment;
use WilokeListingTools\Framework\Payment\DirectBankTransfer\DirectBankTransferRecurringPayment;
use WilokeListingTools\Framework\Payment\FreePlan\FreePlan;
use WilokeListingTools\Framework\Payment\PayPal\DirectBankTransferNonRecurringCreatedPaymentHook;
use WilokeListingTools\Framework\Payment\PayPal\PayPalNonRecurringPaymentHook;
use WilokeListingTools\Framework\Payment\PayPal\PayPalNonRecurringPaymentMethod;
use WilokeListingTools\Framework\Payment\PayPal\PayPalRecurringPaymentMethod;
use WilokeListingTools\Framework\Payment\Receipt\ReceiptStructureInterface;
use WilokeListingTools\Framework\Payment\Stripe\StripeNonRecurringPaymentMethod;
use WilokeListingTools\Framework\Payment\Stripe\StripeRecurringPaymentMethod;
use WilokeListingTools\Framework\Payment\Stripe\StripeSCANonRecurringPaymentMethod;
use WilokeListingTools\Framework\Payment\Stripe\StripeSCARecurringPaymentMethod;
use WilokeListingTools\Framework\Payment\WooCommerce\WooCommerceNonRecurringPaymentMethod;
use WilokeListingTools\Framework\Payment\WooCommerce\WooCommerceRecurringPaymentMethod;

class PaymentGatewayStaticFactory
{
    /**
     * @param $gateway
     * @param $isNonRecurringPayment
     *
     * @return mixed
     */
    public static function get($gateway, $isNonRecurringPayment)
    {
        $oRetrieve = RetrieveFactory::retrieve('normal');

        switch ($gateway) {
            case 'stripe':
                if ($isNonRecurringPayment) {
                    $oPaymentMethod = new StripeSCANonRecurringPaymentMethod();
                } else {
                    $oPaymentMethod = new  StripeSCARecurringPaymentMethod();
                }
                break;
            case 'paypal':
                if ($isNonRecurringPayment) {
                    $oPaymentMethod = new PayPalNonRecurringPaymentMethod();
                } else {
                    $oPaymentMethod = new PayPalRecurringPaymentMethod();
                }
                break;
            case 'woocommerce':
                if ($isNonRecurringPayment) {
                    $oPaymentMethod = new WooCommerceNonRecurringPaymentMethod();
                } else {
                    $oPaymentMethod = new WooCommerceRecurringPaymentMethod();
                }
                break;
            case 'banktransfer':
                if ($isNonRecurringPayment) {
                    $oPaymentMethod = new DirectBankTransferNonRecurringPayment();
                } else {
                    $oPaymentMethod = new DirectBankTransferRecurringPayment();
                }
                break;
            case 'free':
                $oPaymentMethod = new FreePlan();
                break;
        }

        if (!isset($oPaymentMethod)) {
            return $oRetrieve->error([
                'msg' => 'We have not found any payment gateway yet '.__CLASS__.' '.__METHOD__
            ]);
        }

        return $oRetrieve->success(
            [
                'oPaymentMethod' => $oPaymentMethod
            ]
        );
    }
}
