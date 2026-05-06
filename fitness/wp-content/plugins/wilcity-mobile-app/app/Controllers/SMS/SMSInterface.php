<?php

namespace WILCITY_APP\Controllers\SMS;

interface SMSInterface
{
    public function setAPI();
    public function setSendTo($phoneNumber);
    public function getSendTo();
    public function setSendFrom();
    public function setMessage($msg);
    public function send();
}
