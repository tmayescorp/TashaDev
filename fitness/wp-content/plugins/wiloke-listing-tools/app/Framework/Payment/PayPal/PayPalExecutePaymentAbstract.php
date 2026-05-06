<?php

namespace WilokeListingTools\Framework\Payment\PayPal;

abstract class PayPalExecutePaymentAbstract
{
    /**
     * @var PayPalExecuteInterface
     */
    private $oPayPalExecute;
    
    public function __construct(PayPalExecuteInterface $oPayPalExecute)
    {
        $this->oPayPalExecute = $oPayPalExecute;
    }
    
    /**
     * @return bool
     */
    public function verify(){
    
    }
    
    /**
     * @return mixed
     */
    public function execute()
    {
        return $this->oPayPalExecute->execute();
    }
}
