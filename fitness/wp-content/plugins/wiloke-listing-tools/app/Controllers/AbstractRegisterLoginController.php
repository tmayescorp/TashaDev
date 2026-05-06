<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Helpers\GetSettings;

abstract class AbstractRegisterLoginController
{
    protected function getRegisterRedirectTo($redirectTo = '')
    {
        return apply_filters(
            'wilcity/filter/wiloke-listing-tools/register-redirect-to',
            empty($redirectTo) ? GetSettings::redirectToAfterRegister() : esc_url($redirectTo)
        );
    }
    
    protected function getLoginRedirectTo($redirectTo = '')
    {
        return apply_filters(
            'wilcity/filter/wiloke-listing-tools/login-redirect-to',
            empty($redirectTo) ? GetSettings::redirectToAfterLogin() : esc_url($redirectTo)
        );
    }
}
