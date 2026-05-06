<?php

namespace WilokeListingTools\Framework\Helpers;

class SQLEscape
{
    public static function realEscape($info)
    {
        global $wpdb;
        if (!is_array($info)) {
            return $wpdb->_real_escape($info);
        }
        
        return array_map([__CLASS__, 'realEscape'], $info);
    }
}
