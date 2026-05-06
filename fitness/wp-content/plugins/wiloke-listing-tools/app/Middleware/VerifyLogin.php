<?php

namespace WilokeListingTools\Middleware;

use WilokeListingTools\Framework\Routing\InterfaceMiddleware;

class VerifyLogin implements InterfaceMiddleware
{
    public $msg;
    
    public function handle(array $aOptions)
    {
        if (is_user_logged_in()) {
            $this->msg = esc_html__('You logged into the site already', 'wiloke-listing-tools');
            
            return false;
        }
        
        if (!isset($aOptions['user_login']) || empty($aOptions['user_login'])) {
            $this->msg = esc_html__('Invalid username', 'wiloke-listing-tools');
            
            return false;
        }
        
        if (!isset($aOptions['user_password']) || empty($aOptions['user_password'])) {
            $this->msg = esc_html__('Invalid password', 'wiloke-listing-tools');
            
            return false;
        }
        
        return true;
    }
}
