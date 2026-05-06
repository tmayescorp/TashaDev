<?php
namespace WilokeListingTools\Framework\Payment\Stripe;

use WilokeListingTools\Framework\Payment\Invoice\PrePareInvoiceFormatInterface;
use WilokeListingTools\Framework\Payment\Invoice\RecurringPaymentPrepareInvoiceFormatAbstract;

final class StripeRecurringPrepareInvoiceFormat extends RecurringPaymentPrepareInvoiceFormatAbstract implements
    PrePareInvoiceFormatInterface
{
    /**
     * @var StripeWebhook
     */
    private $oStripeWebhook;
    
    /**
     * StripeNonRecurringInvoice constructor.
     *
     * @param StripeWebhook $oStripeWebhook
     */
    public function __construct(StripeWebhook $oStripeWebhook)
    {
        $this->oStripeWebhook = $oStripeWebhook;
    }
    
    /**
     * @param $price
     *
     * @return float
     */
    private function reConvertZeroDecimal($price)
    {
        return empty($price) ? 0 : floatval($price / $this->oStripeWebhook->getZeroDecimal());
    }
    
    public function setCurrency()
    {
        $this->aParams['currency'] = strtolower($this->oStripeWebhook->oEvent->data->object->currency);
        
        return $this;
    }
    
    public function setTotal()
    {
        $this->aParams['total'] = $this->reConvertZeroDecimal($this->oStripeWebhook->oEvent->data->object->total);
        
        return $this;
    }
    
    public function setTax()
    {
        $this->aParams['tax'] = $this->reConvertZeroDecimal($this->oStripeWebhook->oEvent->data->object->tax);
        
        return $this;
    }
    
    public function setSubTotal()
    {
        $this->aParams['subTotal'] = $this->reConvertZeroDecimal($this->oStripeWebhook->oEvent->data->object->subtotal);
        
        return $this;
    }
    
    public function setDiscount()
    {
        $this->aParams['discount'] = $this->reConvertZeroDecimal($this->oStripeWebhook->oEvent->data->object->discount);
        
        return $this;
    }
    
    public function setToken()
    {
        $this->aParams['token'] = $this->oStripeWebhook->oEvent->id; // event ID => It helps to resolve double invoice
        
        return $this;
    }
    
    public function prepareInvoiceParam()
    {
        $this->setCurrency()->setToken()->setTotal()->setDiscount()->setTax()->setSubTotal();
        
        return $this;
    }
}
