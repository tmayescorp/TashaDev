<?php

namespace WilokeListingTools\Framework\Payment\DirectBankTransfer;

use WilokeListingTools\Framework\Payment\Invoice\PrePareInvoiceFormatInterface;
use WilokeListingTools\Framework\Payment\Invoice\RecurringPaymentPrepareInvoiceFormatAbstract;

final class DirectBankTransferNonRecurringPrepareInvoiceFormat extends
    RecurringPaymentPrepareInvoiceFormatAbstract implements
    PrePareInvoiceFormatInterface
{
    /**
     * @var DirectBankTransferWebhook
     */
    private $oDirectBankTransferWebhook;
    
    /**
     * PayPalNonRecurringPrepareInvoiceFormat constructor.
     *
     * @param DirectBankTransferWebhook $oDirectBankTransferWebhook
     */
    public function __construct(DirectBankTransferWebhook $oDirectBankTransferWebhook)
    {
        $this->oDirectBankTransferWebhook = $oDirectBankTransferWebhook;
    }
    
    public function setCurrency()
    {
        $this->aParams['currency'] = strtolower($this->oDirectBankTransferWebhook->aPaymentMetaInfo['currency']);
        
        return $this;
    }
    
    public function setTotal()
    {
        $this->aParams['total'] = floatval($this->oDirectBankTransferWebhook->aPaymentMetaInfo['total']);
        
        return $this;
    }
    
    public function setTax()
    {
        $this->aParams['tax'] = $this->oDirectBankTransferWebhook->aPaymentMetaInfo['tax'];
        
        return $this;
    }
    
    public function setSubTotal()
    {
        $this->aParams['subTotal'] = floatval($this->oDirectBankTransferWebhook->aPaymentMetaInfo['subTotal']);
        
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
        $this->aParams['discount'] = floatval($this->oDirectBankTransferWebhook->aPaymentMetaInfo['discount']);
        return $this;
    }
    
    public function setToken()
    {
        $this->aParams['token'] = $this->oDirectBankTransferWebhook->subscriptionID;
        
        return $this;
    }
    
    public function setIsRefunded()
    {
        $this->aParams['isRefunded'] = $this->oDirectBankTransferWebhook->isRefunded;
        
        return $this;
    }
    
    public function prepareInvoiceParam()
    {
        $this->setCurrency()->setToken()->setTotal()->setTax()->setDiscount()->setSubTotal()->setIsRefunded();
        return $this;
    }
}
