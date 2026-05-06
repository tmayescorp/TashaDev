<?php

namespace WilokeListingTools\Framework\Helpers;

class Request
{
    public static function currentPage()
    {
        return esc_url((is_ssl() ? "https" : "http")."://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
    }
}
