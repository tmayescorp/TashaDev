<?php

namespace WilokeListingTools\Framework\Payment\WooCommerce;

use WilokeListingTools\Controllers\WooCommerceController;
use WilokeListingTools\Framework\Payment\PaymentHook\ProceededNonRecurringPaymentHookAbstract;
use WilokeListingTools\Framework\Payment\PaymentHook\ProceededPaymentHookInterface;

final class WooCommerceProceededNonRecurringPaymentHook extends ProceededNonRecurringPaymentHookAbstract implements
    ProceededPaymentHookInterface
{
    private $oWooCommerceWebhook;
    
    public function active()
    {
        // TODO: Implement active() method.
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
    public function __construct(WooCommerceWebhook $oWooCommerceController)
    {
        $this->oWooCommerceWebhook = $oWooCommerceController;
        parent::__construct(
            $this->oWooCommerceWebhook->paymentID
        );
        $this->getCommonArgs();
    }
}
