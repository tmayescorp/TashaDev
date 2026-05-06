<?php

namespace WilokeListingTools\Framework\Payment\PaymentHook;

use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;

abstract class ProceedRecurringPaymentHookAbstract
{
    protected $paymentID;
    protected $nextBillingDateGMT;
    protected $token;
    protected $aArgs;
    
    /**
     * ProceedRecurringPaymentHookAbstract constructor.
     *
     * @param $paymentID
     * @param $nextBillingDate
     */
    public function __construct($paymentID, $nextBillingDate)
    {
        $this->paymentID = $paymentID;
        if (!empty($nextBillingDate)) {
            $this->nextBillingDateGMT = is_numeric($nextBillingDate) ? $nextBillingDate : strtotime($nextBillingDate);
        }
    }
    
    /**
     *
     * @return array
     */
    public function setupSuccessArgs()
    {
        $billingType                        = PaymentModel::getField('billingType', $this->paymentID);
        $aPaymentMeta                       = PaymentMetaModel::getPaymentInfo($this->paymentID);
        $aPaymentMeta['paymentID']          = $this->paymentID;
        $aPaymentMeta['billingType']        = $billingType;
        $aPaymentMeta['gateway']            = PaymentModel::getField('gateway', $this->paymentID);
        $aPaymentMeta['nextBillingDateGMT'] = $this->nextBillingDateGMT;
        
        return $aPaymentMeta;
    }
    
    public function failed()
    {
        /**
         * @hooked: WilokeListingTools\Controllers\PaymentController:updatePaymentFailedStatus 5
         */
        do_action('wilcity/wiloke-listing-tools/'.$this->aArgs['billingType'].'/payment-gateway-failed', $this->aArgs);
        
        /**
         * @hooked: SessionController:deletePaymentSessions
         */
        do_action('wiloke-submission/payment-succeeded-and-updated-everything');
    }
    
    public function refunded()
    {
        // TODO: Implement refunded() method.
    }
    
    public function disputed()
    {
        // TODO: Implement disputed() method.
    }
    
    public function suspended()
    {
        /**
         * @hooked: WilokeListingTools\Controllers\PaymentController:updatePaymentCompletedStatus 5
         * @hooked: WilokeListingTools\Controllers\InvoiceController:prepareInsertInvoice 6
         */
        do_action(
            'wilcity/wiloke-listing-tools/'.$this->aArgs['billingType'].'/payment-gateway-suspended',
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
}
