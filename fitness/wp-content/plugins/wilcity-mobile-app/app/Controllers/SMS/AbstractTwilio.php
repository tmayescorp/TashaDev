<?php

namespace WILCITY_APP\Controllers\SMS;

use Twilio\Rest\Client;

class AbstractTwilio
{
    protected $sendToPhoneNumber;
    protected $receiverCountry;
    protected $msg;
    protected $oClient;
    protected $sendFromPhoneNumber;

    public function setAPI()
    {
	    if (!class_exists('\WilokeThemeOptions')) {
		    return '';
	    }


	    $sid       = \WilokeThemeOptions::getOptionDetail('wilcity_twilio_sid');
        $authToken = \WilokeThemeOptions::getOptionDetail('wilcity_twilio_auth_token');

        if (empty($sid) || empty($authToken)) {
            throw new \Exception('The Twilio setting is required');
        }

        $this->oClient = new Client($sid, $authToken);
    }

    public function setSendFrom()
    {
        $this->sendFromPhoneNumber = \WilokeThemeOptions::getOptionDetail('wilcity_twilio_yourphone_number');
        if (empty($this->sendFromPhoneNumber)) {
            throw new \Exception('The Phone Number setting is required: Appearance -> Theme Options -> Twilio Settings');
        }

        return trim($this->sendFromPhoneNumber);
    }

    public function autoAddDialCodeToPhoneNumber($phoneNumber, $country)
    {
        if (empty($country)) {
            return $phoneNumber;
        }

        $aDialCode = include WILCITY_APP_PATH.'configs/dialcode.php';

        if (!isset($aDialCode[$country])) {
            return $phoneNumber;
        }

        $dialCode = $aDialCode[$country]['code'];
        if (
            strpos($phoneNumber, $dialCode) === 0 ||
            strpos($phoneNumber, '+'.$dialCode) === 0
        ) {
            return $phoneNumber;
        }

        return '+'.$dialCode . $phoneNumber;
    }
}
