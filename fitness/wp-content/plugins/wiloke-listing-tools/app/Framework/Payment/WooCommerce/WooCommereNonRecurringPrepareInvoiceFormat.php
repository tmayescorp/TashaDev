<?php

namespace WilokeListingTools\Framework\Payment\WooCommerce;

use WilokeListingTools\Framework\Payment\Invoice\NonRecurringPaymentPrepareInvoiceFormatAbstract;
use WilokeListingTools\Framework\Payment\Invoice\PrePareInvoiceFormatInterface;

final class WooCommereNonRecurringPrepareInvoiceFormat extends
    NonRecurringPaymentPrepareInvoiceFormatAbstract implements
    PrePareInvoiceFormatInterface
{
    /**
     * @var $oWooCommerceWebhook
     */
    private $oWooCommerceWebhook;
    
    /**
     * StripeNonRecurringInvoice constructor.
     *
     * @param WooCommerceWebhook $oWooCommerceWebhook
     */
    public function __construct(WooCommerceWebhook $oWooCommerceWebhook)
    {
        $this->oWooCommerceWebhook = $oWooCommerceWebhook;
    }
    
    public function setCurrency()
    {
        $this->aParams['currency'] = get_woocommerce_currency();
        
        return $this;
    }
    
    public function setTotal()
    {
        $this->aParams['total'] = $this->oWooCommerceWebhook->oOrder->get_total();
        
        return $this;
    }
    
    public function setTax()
    {
        $this->aParams['tax'] = $this->oWooCommerceWebhook->oOrder->get_total_tax();
        
        return $this;
    }
    
    public function setSubTotal()
    {
        $this->aParams['subTotal'] = $this->oWooCommerceWebhook->oOrder->get_subtotal();
        
        return $this;
    }
    
    public function setDiscount()
    {
        $this->aParams['discount'] = $this->oWooCommerceWebhook->oOrder->get_discount_total();
        
        return $this;
    }
    
    public function setToken()
    {
        $this->aParams['token'] = $this->oWooCommerceWebhook->orderID;
        
        return $this;
    }
    
    public function setIsRefunded()
    {
        $this->aParams['isRefunded'] = $this->oWooCommerceWebhook->isRefunded;
        
        return $this;
    }
    
    public function prepareInvoiceParam()
    {
        $this->setCurrency()->setToken()->setTax()->setDiscount()->setTotal()->setSubTotal()->setIsRefunded();
        
        return $this;
    }
}
