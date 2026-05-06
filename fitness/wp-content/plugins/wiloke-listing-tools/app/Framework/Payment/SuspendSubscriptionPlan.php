<?php

namespace WilokeListingTools\Framework\Payment;

use WilokeListingTools\Framework\Helpers\Submission;
use WilokeListingTools\Framework\Payment\DirectBankTransfer\DirectBankTransferSuspend;
use WilokeListingTools\Framework\Payment\PayPal\PayPalSuspendPlan;
use WilokeListingTools\Framework\Payment\Stripe\StripeSuspendPlan;
use WilokeListingTools\Framework\Payment\WooCommerce\WooCommerceSuspend;
use WilokeListingTools\Models\PaymentModel;

final class SuspendSubscriptionPlan implements SuspendInterface
{
    private $paymentID;
    
    public function setPaymentID($paymentID)
    {
        $this->paymentID = $paymentID;
        return $this;
    }
    
    private function isPaymentExists()
    {
        $aPaymentInfo = PaymentModel::getPaymentInfo($this->paymentID);
        
        return !empty($aPaymentInfo);
    }
    
    private function isRecurringPayment()
    {
        return PaymentModel::isRecurringPayment($this->paymentID);
    }
    
    private function getGateway()
    {
        return PaymentModel::getField('gateway', $this->paymentID);
    }
    
    /**
     * @throws \Exception | ['status' => 'success']
     */
    public function suspend()
    {
        if (!$this->isPaymentExists()) {
            throw new \Exception(esc_html__('The payment id does not exists', 'wiloke-listing-tools'));
        }
        
        if (!$this->isRecurringPayment()) {
            throw new \Exception(esc_html__('This payment is not a recurring payment', 'wiloke-listing-tools'));
        }
        
        switch ($gateway = $this->getGateway()) {
            case 'stripe':
                $oSuspendGateway = (new StripeSuspendPlan($this->paymentID));
                break;
            case 'woocommerce':
                $oSuspendGateway = (new WooCommerceSuspend($this->paymentID));
                break;
            case 'free':
            case 'banktransfer':
                $oSuspendGateway = (new DirectBankTransferSuspend($this->paymentID));
                break;
            case 'paypal':
                $oSuspendGateway = (new PayPalSuspendPlan($this->paymentID));
                break;
            default:
                if (has_filter('wilcity/filter/wiloke-listing-tools/suspend')) {
                    $oSuspendGateway = apply_filters('wilcity/filter/wiloke-listing-tools/suspend', null, $this);
                }
                if ($oSuspendGateway === null) {
                    throw new \Exception(sprintf(esc_html__('The gateway %s is not supported yet',
                        'wiloke-listing-tools')));
                }
                break;
        }
        
        return $oSuspendGateway->suspend();
    }
}
