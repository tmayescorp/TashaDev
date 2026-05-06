<?php

namespace WilokeListingTools\Framework\Payment\DirectBankTransfer;

use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Payment\PaymentHook\ProceededNonRecurringPaymentHookAbstract;
use WilokeListingTools\Framework\Payment\PaymentHook\ProceededPaymentHookInterface;

final class DirectBankTransferProceededNonRecurringPaymentHook extends
    ProceededNonRecurringPaymentHookAbstract implements
    ProceededPaymentHookInterface
{
    protected $aArgs;
    private $oBankTransferWebhook;
    
    /**
     * DirectBankTransferWebhook constructor.
     *
     * @param DirectBankTransferWebhook $aBankTransferWebhook
     */
    public function __construct(DirectBankTransferWebhook $aBankTransferWebhook)
    {
        $this->oBankTransferWebhook = $aBankTransferWebhook;
        parent::__construct($this->oBankTransferWebhook->paymentID);
        $this->getCommonArgs();
    }
    
    private function getCommonArgs()
    {
        $this->aArgs = $this->setupSuccessArgs();
    }
    
    public function completed()
    {
        $this->aArgs['token']          = $this->oBankTransferWebhook->token;
        $this->aArgs['aInvoiceFormat'] = $this->oBankTransferWebhook->aInvoiceFormat;
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
        $this->aArgs['token']          = $this->oBankTransferWebhook->token;
        $this->aArgs['aInvoiceFormat'] = $this->oBankTransferWebhook->aInvoiceFormat;
        
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
