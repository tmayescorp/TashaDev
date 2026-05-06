<?php

namespace WILCITY_APP\Controllers\SMS;

class SMSFactory
{
    /**
     * @param string $service
     * @param  array $aInfo . msg is required. userID|phoneNumber is required
     *
     * @return mixed|TwilioSMS
     * @throws \Exception
     */
    public static function getService(ARRAY $aInfo, $service = 'twilio')
    {
        switch ($service) {
            case 'twilio':
                $oService = new TwilioSMS($aInfo);
                break;
            default:
                $oService = apply_filters('wilcity/filter/wilcity-mobile-app/app/controller/sms/service', null,
                    $service);
                break;
        }
        
        if ($oService == null) {
            throw new \Exception('A SMS service is required');
        }
        
        return $oService;
    }
}
