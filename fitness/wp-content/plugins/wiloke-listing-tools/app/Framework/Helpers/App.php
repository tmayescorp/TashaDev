<?php
namespace WilokeListingTools\Framework\Helpers;

class App
{
    protected static $aApps = [];
    
    /**
     * @param $key
     * @param $val
     */
    public static function bind($key, $val)
    {
        self::$aApps[$key] = $val;
    }
    
    /**
     * @param $key
     *
     * @return bool|mixed
     */
    public static function get($key)
    {
        return array_key_exists($key, self::$aApps) ? self::$aApps[$key] : false;
    }
}
