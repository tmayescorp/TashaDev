<?php

namespace WilokeListingTools\Middleware;

use WilokeListingTools\Framework\Routing\InterfaceMiddleware;

class VerifyNonce implements InterfaceMiddleware
{
    public $msg;

    public function handle(array $aOptions)
    {
        $status = check_ajax_referer('wilSecurity', 'security', 0);

        if (!$status) {
            $aHeaderRequest = apache_request_headers();
            if (isset($aHeaderRequest['security'])) {
                $status = wp_verify_nonce($aHeaderRequest['security'], 'wilSecurity');
                if ($status) {
                    return true;
                }
            }
            $this->msg = esc_html__('Invalid security code', 'wiloke-listing-tools');

            return false;
        }

        return true;
    }
}
