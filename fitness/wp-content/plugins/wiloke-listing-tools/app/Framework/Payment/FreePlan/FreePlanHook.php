<?php

namespace WilokeListingTools\Framework\Payment\FreePlan;

use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Payment\FreePlan\FreePlanWebhook;
use WilokeListingTools\Framework\Payment\FreePlan\FreeWebhook;
use WilokeListingTools\Framework\Payment\PaymentHook\ProceededNonRecurringPaymentHookAbstract;
use WilokeListingTools\Framework\Payment\PaymentHook\ProceededPaymentHookInterface;

final class FreePlanHook extends
    ProceededNonRecurringPaymentHookAbstract implements
    ProceededPaymentHookInterface
{
    protected $aArgs;
    private $oFreeWebhook;
    
    /**
     * DirectBankTransferWebhook constructor.
     *
     * @param FreePlan $oFreeWebhook
     */
    public function __construct(FreePlanWebhook $oFreeWebhook)
    {
        $this->oFreeWebhook = $oFreeWebhook;
        parent::__construct($this->oFreeWebhook->paymentID);
        $this->getCommonArgs();
    }
    
    private function getCommonArgs()
    {
        $this->aArgs = $this->setupSuccessArgs();
    }
    
    public function completed()
    {
        $this->aArgs['token']          = $this->oFreeWebhook->token;
        $this->aArgs['aInvoiceFormat'] = $this->oFreeWebhook->aInvoiceFormat;
     
        /**
         * @hooked: WilokeListingTools\Controllers\PaymentController:updatePaymentCompletedStatus 5
         */
        do_action('wilcity/wiloke-listing-tools/'.$this->aArgs['billingType'].'/payment-gateway-completed',
            $this->aArgs);
        
        /**
         * @hooked: SessionController:deletePaymentSessions
         */
        do_action('wiloke-submission/payment-succeeded-and-updated-everything');
    }
    
    public function failed()
    {
        /**
         * @hooked: WilokeListingTools\Controllers\PaymentController:updatePaymentFailedStatus 5
         * @hooked: SessionController:deletePaymentSessions
         */
        do_action('wilcity/wiloke-listing-tools/'.$this->aArgs['billingType'].'/payment-gateway-failed', $this->aArgs);
    }
    
    public function active()
    {
        // TODO: Implement active() method.
    }
    
    public function refunded()
    {
        $this->aArgs['token']          = $this->oFreeWebhook->token;
        $this->aArgs['aInvoiceFormat'] = $this->oFreeWebhook->aInvoiceFormat;
        
        /**
         * @hooked: PaymentController:'wilcity/wiloke-listing-tools/'.$billingType
         * .'/stripe/payment-disputed'
         */
        do_action(
            'wilcity/wiloke-listing-tools/'.$this->aArgs['billingType'].'/payment-gateway-refunded',
            $this->aArgs
        );
    }
    
    public function reactivate()
    {
        // TODO: Implement reactivate() method.
    }
}
