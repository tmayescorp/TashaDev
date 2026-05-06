<?php
namespace WilokeListingTools\Framework\Payment\Stripe;

use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Payment\Invoice\NonRecurringPaymentPrepareInvoiceFormatAbstract;
use WilokeListingTools\Framework\Payment\Invoice\PrePareInvoiceFormatInterface;

final class StripeNonRecurringIPreparenvoiceFormat extends NonRecurringPaymentPrepareInvoiceFormatAbstract implements
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
    
    private function reConvertZeroDecimal($price)
    {
        return floatval($price/$this->oStripeWebhook->getZeroDecimal());
    }
    
    public function setCurrency()
    {
        $this->aParams['currency'] = strtolower($this->oStripeWebhook->oEvent->data->object->currency);
        
        return $this;
    }
    
    public function setTotal()
    {
        $this->aParams['total'] = $this->reConvertZeroDecimal($this->oStripeWebhook->oEvent->data->object->amount);
        
        return $this;
    }
    
    public function setTax()
    {
        $this->aParams['tax'] = $this->oStripeWebhook->aPaymentMeta['tax'];
        
        return $this;
    }
    
    public function setSubTotal()
    {
        $this->aParams['subTotal'] = $this->aParams['total'] - $this->aParams['tax'] + $this->aParams['discount'];
        
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
        $this->aParams['discount'] = isset($this->oStripeWebhook->aPaymentMeta['discount']) ?
            floatval($this->oStripeWebhook->aPaymentMeta['discount']) : 0;
        return $this;
    }
    
    public function setToken()
    {
        return $this;
    }
    
    public function prepareInvoiceParam()
    {
        $this->setCurrency()->setToken()->setTotal()->setTax()->setDiscount()->setSubTotal();
        return $this;
    }
}
