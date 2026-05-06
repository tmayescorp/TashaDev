<?php

namespace WilokeListingTools\Framework\Payment\PayPal;

use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Models\PaymentMetaModel;

class PayPalExecutePromotionPayment extends PayPalExecutePaymentAbstract
{
    public $token;
    protected $paymentID;
    
    public function verify()
    {
        $this->token     = $_GET['token'];
        $this->paymentID = PaymentMetaModel::getPaymentIDByToken($this->token);
        
        if (empty($this->paymentID)) {
            Session::setSession('payment_error', esc_html__('Invalid PayPal Token', 'wiloke-listing-tools'));
            FileSystem::logError('We could not found payment id by this token '.$_GET['token']);
            
            return false;
        }
        
        $aPaymentMetaInfo = PaymentMetaModel::getPaymentInfo($this->paymentID);
        
        if (!isset($aPaymentMetaInfo['postID']) || empty($aPaymentMetaInfo['postID'])) {
            Session::setSession('payment_error', esc_html__('We found no Post ID', 'wiloke-listing-tools'));
            FileSystem::logError('We found no Post ID in the Payment Meta. Payment ID: '.$this->paymentID);
            
            return false;
        }
        
        return true;
    }
}
