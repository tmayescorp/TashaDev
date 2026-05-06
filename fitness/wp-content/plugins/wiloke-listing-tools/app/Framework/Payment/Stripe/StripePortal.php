<?php

namespace WilokeListingTools\Framework\Payment\Stripe;

use Stripe\BillingPortal\Configuration;
use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Controllers\RetrieveController;
use WilokeListingTools\Framework\Payment\StripePayment;

class StripePortal extends StripePayment
{
    private RetrieveController $oRetrieve;

    public function __construct()
    {
        $oNormalRetrieve = new NormalRetrieve();
        $this->oRetrieve = new RetrieveController($oNormalRetrieve);
    }

    public function setupPortal($privacyUrl, $termsServiceUrl)
    {
        $aStatus = $this->setApiContext();
        if ($aStatus['status'] != 'success') {
            return $aStatus;
        }

        try {
            Configuration::create([
                'business_profile' => [
                    'privacy_policy_url'   => $privacyUrl,
                    'terms_of_service_url' => $termsServiceUrl
                ],
                'features'         => [
                    'invoice_history' => ['enabled' => true],
                ],
            ]);

            return $this->oRetrieve->success([
                'msg' => 'Success'
            ]);

        }
        catch (\Exception $oException) {
            return $this->oRetrieve->error([
                'msg' => $oException->getMessage()
            ]);
        }
    }
}
