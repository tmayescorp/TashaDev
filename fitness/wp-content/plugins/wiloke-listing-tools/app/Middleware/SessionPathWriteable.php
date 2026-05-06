<?php

namespace WilokeListingTools\Middleware;

use WilokeListingTools\Framework\Routing\InterfaceMiddleware;
use WilokeListingTools\Framework\Store\Session;

class SessionPathWriteable implements InterfaceMiddleware
{
    public function handle(array $aOptions)
    {
        if (Session::getSession('test', true) != 'oke') {
            $this->msg = esc_html__(
                sprintf(
                    'Session path %s is not writable for PHP! Please contact your hosting provider to report this issue',
                    session_save_path()
                ),
                'wiloke-listing-tools'
            );
            
            return false;
        }
        
        return true;
    }
}
