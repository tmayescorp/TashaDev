<?php

namespace WilokeListingTools\Middleware;

use WilokeListingTools\Framework\Routing\InterfaceMiddleware;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\PaymentModel;

class VerifyPaymentID implements InterfaceMiddleware
{
    public $msg;
    
    public function handle(array $aOptions)
    {
        if (!isset($aOptions['paymentID']) || empty($aOptions['paymentID'])) {
            $this->msg = esc_html__('The payment id is required', 'wiloke-listing-tools');
            return false;
        }
    
        if (!isset($aOptions['userID']) || empty($aOptions['userID'])) {
            $aOptions['userID'] = User::getCurrentUserID();
        }
        
        if (PaymentModel::getField('userID', $aOptions['paymentID']) != $aOptions['userID']) {
            $this->msg = esc_html__('You do not have permission to do this action', 'wiloke-listing-tools');
            return false;
        }
        
        return true;
    }
}
