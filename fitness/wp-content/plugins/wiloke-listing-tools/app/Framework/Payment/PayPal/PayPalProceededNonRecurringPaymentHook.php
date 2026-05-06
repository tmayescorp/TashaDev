<?php

namespace WilokeListingTools\Framework\Payment\PayPal;

use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Payment\PaymentHook\ProceededNonRecurringPaymentHookAbstract;
use WilokeListingTools\Framework\Payment\PaymentHook\ProceededPaymentHookInterface;
use WilokeListingTools\Framework\Payment\PayPalPayment;

final class PayPalProceededNonRecurringPaymentHook extends ProceededNonRecurringPaymentHookAbstract implements
    ProceededPaymentHookInterface
{
    protected $aArgs;
    /**
     * @var PayPalPayment $oPayPalExecution
     */
    private $oPayPalExecution;

    /**
     * PayPalProceededNonRecurringPayment constructor.
     *
     * @param PayPalPayment $oPayPalExecution
     */
    public function __construct(PayPalPayment $oPayPalExecution)
    {
        $this->oPayPalExecution = $oPayPalExecution;
        parent::__construct($this->oPayPalExecution->getPaymentID());
        $this->getCommonArgs();
    }

    private function getCommonArgs()
    {
        $this->aArgs = $this->setupSuccessArgs();
    }

    public function completed(): bool
    {
        if (isset($this->oPayPalExecution->intentID)) {
            $this->aArgs['intentID'] = $this->oPayPalExecution->intentID;
        } elseif (isset($this->oPayPalExecution->aPaymentMeta['intentID'])) {
            $this->aArgs['intentID'] = $this->oPayPalExecution->aPaymentMeta['intentID'];
        }

        $this->aArgs['aInvoiceFormat'] = $this->oPayPalExecution->aInvoiceFormat;
        if (empty($this->aArgs['intentID'])) {
            FileSystem::logError('Missing Intent ID. Payment ID: ' . $this->oPayPalExecution->getPaymentID());

            return false;
        }

        /**
         * @hooked: WilokeListingTools\Controllers\PaymentController:updatePaymentCompletedStatus 5
         */
        do_action('wilcity/wiloke-listing-tools/' . $this->aArgs['billingType'] . '/payment-gateway-completed',
            $this->aArgs);

        /**
         * @hooked: SessionController:deletePaymentSessions
         */
        do_action('wiloke-submission/payment-succeeded-and-updated-everything');

        return true;
    }

    public function failed()
    {
        /**
         * @hooked: WilokeListingTools\Controllers\PaymentController:updatePaymentFailedStatus 5
         * @hooked: SessionController:deletePaymentSessions
         */
        do_action('wilcity/wiloke-listing-tools/' . $this->aArgs['billingType'] . '/payment-gateway-failed',
            $this->aArgs);
    }

    public function active()
    {
        // TODO: Implement active() method.
    }

    public function refunded()
    {
        if (isset($this->oPayPalExecution->intentID)) {
            $this->aArgs['intentID'] = $this->oPayPalExecution->intentID;
        } elseif (isset($this->oPayPalExecution->aPaymentMeta['intentID'])) {
            $this->aArgs['intentID'] = $this->oPayPalExecution->aPaymentMeta['intentID'];
        }

        $this->aArgs['token'] = $this->oPayPalExecution->token;
        $this->aArgs['aInvoiceFormat'] = $this->oPayPalExecution->aInvoiceFormat;

        /**
         * @hooked: PaymentController:'wilcity/wiloke-listing-tools/'.$billingType
         * .'/stripe/payment-disputed'
         */
        do_action(
            'wilcity/wiloke-listing-tools/' . $this->aArgs['billingType'] . '/payment-gateway-refunded',
            $this->aArgs
        );
    }

    public function reactivate()
    {
        // TODO: Implement reactivate() method.
    }
}
