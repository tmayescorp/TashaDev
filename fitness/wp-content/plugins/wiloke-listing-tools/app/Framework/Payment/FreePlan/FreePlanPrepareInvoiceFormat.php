<?php

namespace WilokeListingTools\Framework\Payment\FreePlan;

use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Payment\FreePlan\FreePlanWebhook;
use WilokeListingTools\Framework\Payment\Invoice\NonRecurringPaymentPrepareInvoiceFormatAbstract;
use WilokeListingTools\Framework\Payment\Invoice\PrePareInvoiceFormatInterface;

final class FreePlanPrepareInvoiceFormat extends NonRecurringPaymentPrepareInvoiceFormatAbstract implements
    PrePareInvoiceFormatInterface
{
    /**
     * @var FreePlan
     */
    private $oFreePlanWebhook;
    
    /**
     * PayPalNonRecurringPrepareInvoiceFormat constructor.
     *
     * @param FreePlan $oFreePlanWebhook
     */
    public function __construct(FreePlanWebhook $oFreePlanWebhook)
    {
        $this->oFreePlanWebhook = $oFreePlanWebhook;
    }
    
    public function setCurrency()
    {
        $this->aParams['currency'] = GetWilokeSubmission::getField('currency_code');
        
        return $this;
    }
    
    public function setTotal()
    {
        $this->aParams['total'] = $this->oFreePlanWebhook->aPaymentMetaInfo['total'];
        
        return $this;
    }
    
    public function setTax()
    {
        $this->aParams['tax'] = $this->oFreePlanWebhook->aPaymentMetaInfo['tax'];
        
        return $this;
    }
    
    public function setSubTotal()
    {
        $this->aParams['subTotal'] = $this->oFreePlanWebhook->aPaymentMetaInfo['subTotal'];
        
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
        $this->aParams['discount'] = $this->oFreePlanWebhook->aPaymentMetaInfo['discount'];;
        
        return $this;
    }
    
    public function setToken()
    {
        $this->aParams['token'] = $this->oFreePlanWebhook->token;
        
        return $this;
    }
    
    public function prepareInvoiceParam()
    {
        $this->setCurrency()->setToken()->setTotal()->setTax()->setDiscount()->setSubTotal();
        
        return $this;
    }
}
