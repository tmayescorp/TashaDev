<?php

namespace WILCITY_APP\Controllers\SMS;

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Frontend\User;

/**
 * Class TwilioSMS
 * @package WILCITY_APP\Controllers\SMS
 */
class TwilioSMS extends AbstractTwilio implements SMSInterface
{
    public function __construct($aInfo)
    {
        $this->setAPI();
        $this->setSendFrom();
        $this->setUserCountry($aInfo['country']);
        
        if (isset($aInfo['phoneNumber'])) {
            $this->setSendTo($aInfo['phoneNumber']);
        } else if (isset($aInfo['userID'])) {
            $this->setUserID($aInfo['userID']);
        }
        
        $this->setMessage($aInfo['msg']);
    }
    
    public function setSendTo($phoneNumber)
    {
        $this->sendToPhoneNumber = $phoneNumber;
        
        $this->sendToPhoneNumber = $this->autoAddDialCodeToPhoneNumber(
            $this->sendToPhoneNumber,
            $this->receiverCountry
        );
    }
    
    public function getSendTo()
    {
        return $this->sendToPhoneNumber;
    }
    
    public function setUserCountry($country)
    {
        if (empty($country)) {
            throw new \Exception('The receiver country is required');
        }
        
        $this->receiverCountry = $country;
    }
    
    public function setUserID($userID)
    {
        $phoneNumber = User::getPhone($userID);
        if (empty($phoneNumber)) {
            throw new \Exception('The receiver phone number is required');
        }
        
        $this->setSendTo($phoneNumber);
    }
    
    public function setMessage($message)
    {
        if (empty($message)) {
            throw new \Exception('The message is required');
        }
        
        $this->msg = $message;
    }
    
    public function send()
    {
        $this->oClient->messages->create(
            $this->sendToPhoneNumber,
            [
                'from' => $this->sendFromPhoneNumber,
                'body' => $this->msg
            ]
        );
    }
}
