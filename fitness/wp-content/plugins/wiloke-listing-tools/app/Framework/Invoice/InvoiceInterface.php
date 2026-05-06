<?php

namespace WilokeListingTools\Framework\Invoice;

interface InvoiceInterface
{
    /**
     * @param $aInvoiceData
     * @param $aArgs
     *
     * @return $this
     */
    public function setup($aInvoiceData, $aArgs);
    
    /**
     * @return mixed
     */
    public function print();
}
