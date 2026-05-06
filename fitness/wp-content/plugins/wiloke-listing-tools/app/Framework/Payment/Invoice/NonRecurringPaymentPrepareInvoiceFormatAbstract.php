<?php

namespace WilokeListingTools\Framework\Payment\Invoice;

use WilokeListingTools\Framework\Helpers\FileSystem;

abstract class NonRecurringPaymentPrepareInvoiceFormatAbstract
{
    protected $aRequires = [
        'currency',
        'total',
        'subTotal'
    ];
    
    protected $aParams = [];
    
    protected function verifyInvoice()
    {
        foreach ($this->aRequires as $required) {
            if (empty($this->getParam($required))) {
                FileSystem::logError(
                    'We could not insert Invoice because you missed ' . $required,
                    __CLASS__,
                    __METHOD__
                );
                return false;
            }
        }
        
        return true;
    }
    
    private function getParam($param)
    {
        if (empty($this->aParams[$param])) {
            return null;
        }
        
        return $this->aParams[$param];
    }
    
    public function getParams()
    {
        return $this->aParams;
    }
}
