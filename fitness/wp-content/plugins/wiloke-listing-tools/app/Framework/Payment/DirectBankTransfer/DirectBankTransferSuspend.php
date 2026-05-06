<?php

namespace WilokeListingTools\Framework\Payment\DirectBankTransfer;

use WilokeListingTools\Framework\Payment\SuspendInterface;
use WilokeListingTools\Models\PaymentModel;

class DirectBankTransferSuspend
{
    protected $paymentID;
    protected $subscriptionID;
    
    public function __construct($paymentID)
    {
        $this->paymentID = $paymentID;
    }
    
    public function suspend()
    {
        $status = PaymentModel::getField('status', $this->paymentID);
        if ($status !== 'active') {
            return [
                'status' => 'success'
            ];
        }
        
        $status = PaymentModel::updatePaymentStatus('suspended', $this->paymentID);
        
        if ($status) {
            return [
                'status' => 'success'
            ];
        }
        
        return [
            'status' => 'error',
            'msg'    => esc_html__('We could not update suspended status to database', 'wiloke-listing-tools')
        ];
    }
}
