<?php

namespace WilokeListingTools\Framework\Payment\PaymentHook;

use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;

abstract class ProceededNonRecurringPaymentHookAbstract
{
    protected $paymentID;
    protected $aArgs;
    
    public function suspended()
    {
        // TODO: Implement suspended() method.
    }
    
    public function reactivate()
    {
        // TODO: Implement reactivate() method.
    }
    
    public function failed()
    {
        /**
         * @hooked: WilokeListingTools\Controllers\PaymentController:updatePaymentFailedStatus 5
         */
        do_action(
            'wilcity/wiloke-listing-tools/'.$this->aArgs['billingType'].'/stripe/payment-gateway-failed',
            $this->aArgs
        );
    }
    
    public function disputed()
    {
        /**
         * @hooked: PaymentController:'wilcity/wiloke-listing-tools/'.$billingType
         * .'/stripe/payment-disputed'
         */
        do_action(
            'wilcity/wiloke-listing-tools/'.$this->aArgs['billingType'].'/payment-gateway-disputed',
            $this->aArgs
        );
    }
    
    public function cancelled()
    {
        /**
         * @hooked: PaymentController:'wilcity/wiloke-listing-tools/'.$billingType
         * .'/stripe/payment-disputed'
         */
        do_action(
            'wilcity/wiloke-listing-tools/'.$this->aArgs['billingType'].'/payment-gateway-cancelled',
            $this->aArgs
        );
    }
    
    public function __construct($paymentID)
    {
        $this->paymentID = $paymentID;
    }
    
    /**
     *
     * @return array
     */
    public function setupSuccessArgs()
    {
        $billingType                 = PaymentModel::getField('billingType', $this->paymentID);
        $aPaymentMeta                = PaymentMetaModel::getPaymentInfo($this->paymentID);
        $aPaymentMeta['paymentID']   = $this->paymentID;
        $aPaymentMeta['billingType'] = $billingType;
        $aPaymentMeta['gateway']     = PaymentModel::getField('gateway', $this->paymentID);
        
        return $aPaymentMeta;
    }
}
