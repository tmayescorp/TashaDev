<?php

namespace WilokeListingTools\Framework\Payment;

use WilokeListingTools\Framework\Payment\PaymentHook\ProceededPaymentHookInterface;

class ProceededPaymentHook
{
    /**
     * AddPaymentHookAction constructor.
     *
     * @param ProceededPaymentHookInterface $oPaymentHookInterface
     */
    public function __construct(ProceededPaymentHookInterface $oPaymentHookInterface)
    {
        $this->oPaymentHookInterface = $oPaymentHookInterface;
    }
    
    public function doSuspended()
    {
        $this->oPaymentHookInterface->suspended();
    }
    
    public function doReactive()
    {
        $this->oPaymentHookInterface->reactivate();
    }
    
    public function doActive()
    {
        $this->oPaymentHookInterface->active();
    }
    
    public function doCompleted()
    {
        $this->oPaymentHookInterface->completed();
    }
    
    public function doFailed()
    {
        $this->oPaymentHookInterface->failed();
    }
    
    public function doDisputed()
    {
        $this->oPaymentHookInterface->disputed();
    }
    
    public function doRefunded()
    {
        $this->oPaymentHookInterface->refunded();
    }
    
    public function doCancelled()
    {
        $this->oPaymentHookInterface->cancelled();
    }
}
