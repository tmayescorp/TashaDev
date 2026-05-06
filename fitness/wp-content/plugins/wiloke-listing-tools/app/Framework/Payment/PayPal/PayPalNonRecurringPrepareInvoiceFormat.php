<?php
namespace WilokeListingTools\Framework\Payment\PayPal;

use WilokeListingTools\Framework\Payment\Invoice\NonRecurringPaymentPrepareInvoiceFormatAbstract;
use WilokeListingTools\Framework\Payment\Invoice\PrePareInvoiceFormatInterface;

final class PayPalNonRecurringPrepareInvoiceFormat extends NonRecurringPaymentPrepareInvoiceFormatAbstract implements
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
        $this->aParams['tax'] = isset($this->oPayPalWebhook->oEvent->resource->amount->details->tax) ? floatval
    ($this->oPayPalWebhook->oEvent->resource->amount->details->tax) : 0;
        
        return $this;
    }
    
    public function setSubTotal()
    {
        $this->aParams['subTotal'] = floatval($this->oPayPalWebhook->oEvent->resource->amount->details->subtotal);
        
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
        $this->aParams['discount'] = isset($this->oPayPalWebhook->aPaymentMeta['discount']) ?
            floatval($this->oPayPalWebhook->aPaymentMeta['discount']) : 0;
        return $this;
    }
    
    public function setToken()
    {
        $this->aParams['token'] = $this->oPayPalWebhook->oEvent->resource->id;
        
        return $this;
    }
    
    public function prepareInvoiceParam()
    {
        $this->setCurrency()->setToken()->setTotal()->setTax()->setDiscount()->setSubTotal();
        return $this;
    }
}
