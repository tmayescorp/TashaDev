<?php

namespace WilokeListingTools\Framework\Payment\PayPal;

use WilokeListingTools\Framework\Payment\Invoice\PrePareInvoiceFormatInterface;
use WilokeListingTools\Framework\Payment\Invoice\RecurringPaymentPrepareInvoiceFormatAbstract;

final class PayPalRecurringPrepareInvoiceFormat extends RecurringPaymentPrepareInvoiceFormatAbstract implements
    PrePareInvoiceFormatInterface
{
    /**
     * @var PayPalWebhook
     */
    private $oPayPalWebhook;
    
    /**
     * PayPalNonRecurringPrepareInvoiceFormat constructor.
     *
     * @param PayPalWebhook $oPayPalWebhook
     */
    public function __construct(PayPalWebhook $oPayPalWebhook)
    {
        $this->oPayPalWebhook = $oPayPalWebhook;
    }
    
    public function setCurrency()
    {
        $this->aParams['currency'] = strtolower($this->oPayPalWebhook->oEvent->resource->amount->currency);
        
        return $this;
    }
    
    public function setTotal()
    {
        $this->aParams['total'] = floatval($this->oPayPalWebhook->oEvent->resource->amount->total);
        
        return $this;
    }
    
    public function setTax()
    {
        $this->aParams['tax'] = floatval($this->oPayPalWebhook->aPaymentMeta['tax']);
        
        return $this;
    }
    
    public function setSubTotal()
    {
        $total = floatval($this->oPayPalWebhook->oEvent->resource->amount->details->subtotal);
        if ($total === 0) {
            $this->aParams['subTotal'] = 0;
        } else {
            $this->aParams['subTotal'] = floatval($this->oPayPalWebhook->aPaymentMeta['subTotal']);
        }
        
        return $this;
    }
    
    /**
     * We would not put discount price to Stripe directly, We save to Payment Meta instead. This is also reason
     * why we won't have to reConvertedZeroDecimal
     *
     * @return $this
     */
    public function setDiscount()
    {
        $this->aParams['discount'] = 0;
        
        return $this;
    }
    
    public function setToken()
    {
        $this->aParams['token'] = $this->oPayPalWebhook->oEvent->id;
        
        return $this;
    }
    
    public function prepareInvoiceParam()
    {
        $this->setCurrency()->setToken()->setTotal()->setTax()->setDiscount()->setSubTotal();
        
        return $this;
    }
}
