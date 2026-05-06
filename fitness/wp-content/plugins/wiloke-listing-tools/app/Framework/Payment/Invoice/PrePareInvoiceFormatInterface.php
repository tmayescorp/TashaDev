<?php

namespace WilokeListingTools\Framework\Payment\Invoice;

interface PrePareInvoiceFormatInterface
{
    /**
     * @return $this
     */
    public function setCurrency();
    
    /**
     * @return $this
     */
    public function setTotal();
    
    /**
     * @return $this
     */
    public function setTax();
    
    /**
     * @return $this
     */
    public function setSubTotal();
    
    /**
     * @return $this
     */
    public function setDiscount();
    
    /**
     * @return $this
     */
    public function setToken();
    
    /**
     * @return array
     */
    public function getParams();
    
    /**
     * @return $this
     */
    public function prepareInvoiceParam();
}
