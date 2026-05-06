<?php

namespace WilokeListingTools\Framework\Payment;

interface ChangePlanInterface
{
    public function setNewPlan($newPlanID);
    
    public function setOldPlan($oldPlanID);
    
    public function setOldPaymentID($oldPaymentID);
    
    public function suspendOldPlan();
    
    public function createNewPayment();
}
