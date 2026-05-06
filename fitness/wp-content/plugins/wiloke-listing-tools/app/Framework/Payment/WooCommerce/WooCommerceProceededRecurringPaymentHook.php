<?php

namespace WilokeListingTools\Framework\Payment\WooCommerce;

use WilokeListingTools\Controllers\WooCommerceController;
use WilokeListingTools\Framework\Payment\PaymentHook\ProceededNonRecurringPaymentHookAbstract;
use WilokeListingTools\Framework\Payment\PaymentHook\ProceededPaymentHookInterface;
use WilokeListingTools\Framework\Payment\PaymentHook\ProceedRecurringPaymentHookAbstract;

final class WooCommerceProceededRecurringPaymentHook extends ProceedRecurringPaymentHookAbstract implements
    ProceededPaymentHookInterface
{
    private $oWooCommerceWebhook;

    public function disputed()
    {
        // TODO: Implement disputed() method.
    }

    public function reactivate()
    {
        $this->completed();
    }

    public function active()
    {
        $this->completed();
    }

    public function completed()
    {
        $this->aArgs                   = $this->setupSuccessArgs();
        $this->aArgs['token']          = $this->oWooCommerceWebhook->token;
        $this->aArgs['orderID']        = $this->oWooCommerceWebhook->orderID;
        $this->aArgs['aInvoiceFormat'] = $this->oWooCommerceWebhook->aInvoiceFormat;
        /**
         * @hooked: WilokeListingTools\Controllers\PaymentController:updatePaymentCompletedStatus 5
         * @hooked: WilokeListingTools\Controllers\InvoiceController:prepareInsertInvoice 6
         */
        do_action(
            'wilcity/wiloke-listing-tools/'.$this->aArgs['billingType'].'/payment-gateway-completed',
            $this->aArgs
        );
    }

    private function getCommonArgs()
    {
        $this->aArgs = $this->setupSuccessArgs();
    }

    public function refunded()
    {
        $this->aArgs['token']          = $this->oWooCommerceWebhook->token;
        $this->aArgs['orderID']        = $this->oWooCommerceWebhook->orderID;
        $this->aArgs['aInvoiceFormat'] = $this->oWooCommerceWebhook->aInvoiceFormat;

        /**
         * @hooked: PaymentController:'wilcity/wiloke-listing-tools/'.$billingType
         * .'/stripe/payment-disputed'
         */
        do_action(
            'wilcity/wiloke-listing-tools/'.$this->aArgs['billingType'].'/payment-gateway-refunded',
            $this->aArgs
        );
    }

    /**
     * PayPalProceededNonRecurringPayment constructor.
     *
     * @param WooCommerceWebhook $oPayPalExecution
     */
    public function __construct(WooCommerceWebhook $wooCommerceWebhook)
    {
        $this->oWooCommerceWebhook = $wooCommerceWebhook;

        $nextBillingDateGMT = '';
        if (isset($this->oWooCommerceWebhook->nextBillingDateGMT)) {
            $nextBillingDateGMT = $this->oWooCommerceWebhook->nextBillingDateGMT;
        } elseif (isset($this->oWooCommerceWebhook->aPaymentMeta['nextBillingDateGMT'])) {
            $nextBillingDateGMT = $this->oWooCommerceWebhook->aPaymentMeta['nextBillingDateGMT'];
        }

        parent::__construct(
            $this->oWooCommerceWebhook->paymentID,
            $nextBillingDateGMT
        );
        $this->getCommonArgs();
    }
}
