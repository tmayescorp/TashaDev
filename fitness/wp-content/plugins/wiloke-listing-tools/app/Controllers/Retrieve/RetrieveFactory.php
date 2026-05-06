<?php


namespace WilokeListingTools\Controllers\Retrieve;


use WilokeListingTools\Controllers\RetrieveController;

class RetrieveFactory
{
    public static function isAjax(): bool
    {
        return wp_doing_ajax();
    }

    public static function isRest(): bool
    {
        $prefix = rest_get_url_prefix();
        if ((defined('REST_REQUEST') && REST_REQUEST) // (#1)
            || (isset($_GET['rest_route']) // (#2)
                && strpos(trim($_GET['rest_route'], '\\/'), $prefix, 0) === 0)
            || (isset($_SERVER['REQUEST_URI']) && (strpos($_SERVER['REQUEST_URI'], $prefix) !== false) && strpos
                ($_SERVER['REQUEST_URI'], 'wiloke') !== false)
        ) {
            return true;
        } else {
            return false;
        }
    }

    public static function getRequestType(): string
    {
        if (self::isAjax()) {
            return 'ajax';
        }

        if (self::isRest()) {
            return 'rest';
        }

        return 'normal';
    }

    public static function retrieve($determine = ''): RetrieveController
    {
        $oRetrieveType = null;
        if (empty($determine)) {
            $determine = self::getRequestType();
        }

        switch ($determine) {
            case 'rest':
                $oRetrieveType = new RestRetrieve();
                break;
            case 'ajax':
                $oRetrieveType = new AjaxRetrieve();
                break;
            default:
                $oRetrieveType = new NormalRetrieve();
                break;
        }

        return (new RetrieveController($oRetrieveType));
    }
}
