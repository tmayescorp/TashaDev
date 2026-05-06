<?php

namespace WilokeListingTools\Framework\Payment\PaymentHook;

/**
 * Interface ProceededPaymentHookInterface
 * @package WilokeListingTools\Framework\Payment\PaymentHook
 */
interface ProceededPaymentHookInterface
{
    /**
     * @return void
     */
    public function active();
    
    /**
     * @return void
     */
    public function completed();
    
    /**
     * @return void
     */
    public function failed();
    
    /**
     * @return void
     */
    public function refunded();
    
    /**
     * @return void
     */
    public function disputed();
    
    /**
     * @return void
     */
    public function cancelled();
    
    /**
     * @return mixed
     */
    public function suspended();
    
    /**
     * @return mixed
     */
    public function reactivate();
}
