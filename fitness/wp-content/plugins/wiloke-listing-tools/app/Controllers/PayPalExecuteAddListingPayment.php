<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Payment\PayPal\PayPalExecutePaymentAbstract;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Models\PaymentMetaModel;

class PayPalExecuteAddListingPayment extends PayPalExecutePaymentAbstract
{
    public $token;
    private $paymentID;
    
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
        
        if (!isset($aPaymentMetaInfo['planID']) || empty($aPaymentMetaInfo['planID'])) {
            Session::setSession('payment_error', esc_html__('We found no Plan ID', 'wiloke-listing-tools'));
            FileSystem::logError('We found no Plan ID in the Payment Meta. Payment ID: '.$this->paymentID);
            
            return false;
        }
        
        if (get_post_status($aPaymentMetaInfo['planID']) !== 'publish') {
            Session::setSession('payment_error',
                esc_html__('The plan must be published first', 'wiloke-listing-tools'));
            FileSystem::logError(sprintf('This is not a publish plan id %d Payment ID %d', $aPaymentMetaInfo['planID'],
                $this->paymentID));
        }
        
        return true;
    }
}
