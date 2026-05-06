<?php

namespace WilokeListingTools\Framework\Payment;

use WilokeListingTools\Framework\Payment\PaymentHook\CreatedPaymentHookInterface;

class CreatedPaymentHook
{
    /**
     * @var CreatedPaymentHookInterface $oPaymentHookInterface
     */
    protected $oPaymentHookInterface;
    
    /**
     * AddPaymentHookAction constructor.
     *
     * @param CreatedPaymentHookInterface $oPaymentHookInterface
     */
    public function __construct(CreatedPaymentHookInterface $oPaymentHookInterface)
    {
        $this->oPaymentHookInterface = $oPaymentHookInterface;
    }
    
    public function doSuccess()
    {
        $this->oPaymentHookInterface->success();
    }
    
    public function doError()
    {
        $this->oPaymentHookInterface->error();
    }
}
