<?php

namespace WILCITY_APP\Controllers;

class SMSMessageController
{
    public function __construct()
    {
        add_filter('wilcity/filter/wilcity-advanced-woocommerce/configs/themeoptions', [$this, 'twilioSMSSettings']);
    }
    
    public function twilioSMSSettings($aOptions)
    {
        if (!class_exists('Twilio\Rest\Client') || !defined('WILCITY_MOBILE_APP_USING_TWILIO')) {
            return $aOptions;
        }
        
        $aConfigs           = require_once WILCITY_APP_PATH.'configs/twiliooptions.php';
        $aOptions['fields'] = array_merge($aOptions['fields'], $aConfigs);
        
        return $aOptions;
    }
}
