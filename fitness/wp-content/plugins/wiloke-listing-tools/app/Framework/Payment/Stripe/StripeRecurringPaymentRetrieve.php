<?php

namespace WilokeListingTools\Framework\Payment\Stripe;

use Stripe\Subscription;
use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Controllers\RetrieveController;
use WilokeListingTools\Framework\Payment\StripePayment;

class StripeRecurringPaymentRetrieve extends StripePayment
{
    private RetrieveController $oRetrieve;

    public function __construct()
    {
        $oNormalRetrieve = new NormalRetrieve();
        $this->oRetrieve = new RetrieveController($oNormalRetrieve);
    }

    public function findCustomerID($subscriptionID)
    {
        $aStatus = $this->setApiContext();
        if ($aStatus['status'] == 'error') {
            return $aStatus;
        }

        try {
            $oSubscription = Subscription::retrieve($subscriptionID);
            return $this->oRetrieve->success([
                'msg' => 'Success',
                'id'  => $oSubscription->customer
            ]);
        }
        catch (\Exception $oException) {
            return $this->oRetrieve->error([
                'msg' => $oException->getMessage()
            ]);
        }
    }
}
