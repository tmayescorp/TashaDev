<?php

namespace WilokeListingTools\Middleware;

use WilokeListingTools\Framework\Routing\InterfaceMiddleware;
use WilokeListingTools\Models\PaymentModel;

class VerifyCartKey implements InterfaceMiddleware
{
    public $msg;
    
    public function handle(array $aOptions)
    {
        if (!isset($aOptions['cartKey']) || !isset($aOptions['cartKey'])) {
            $this->msg = esc_html__('The cart key is required', 'wiloke-listing-tools');
            
            return false;
        }
        
        return true;
    }
}
