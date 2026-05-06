<?php

namespace WilokeListingTools\Framework\Payment;

use Exception;
use Stripe\Stripe;
use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Controllers\RetrieveController;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Payment\Receipt\ReceiptStructureInterface;
use WilokeListingTools\Models\UserModel;

abstract class StripePayment
{
    /**
     * @var ReceiptStructureInterface
     */
    protected $oReceipt;
    protected $gateway = 'stripe';
    protected $aConfiguration;
    protected $oApiContext;
    protected $customerID;

    protected function setApiContext()
    {
        $oRetrieve = new RetrieveController(new NormalRetrieve());
        try {
            $this->aConfiguration = GetWilokeSubmission::getAll();
            $msg = esc_html__('The Stripe has not configured yet!', 'wiloke-listing-tools');

            if (!GetWilokeSubmission::isGatewaySupported($this->gateway)) {
                return $oRetrieve->error(
                    [
                        'msg' => $msg
                    ]
                );
            }

            if ($this->isLiveMode()) {
                $this->oApiContext['secretKey'] = $this->aConfiguration['stripe_secret_key'];
            } else {
                $this->oApiContext['secretKey'] = $this->aConfiguration['stripe_sandbox_secret_key'];
            }
            $this->oApiContext['zeroDecimal'] = $this->getZeroDecimal();
            settype($this->oApiContext, 'object');

            Stripe::setApiKey($this->oApiContext->secretKey);
            $this->getCustomerID();

            return $oRetrieve->success([]);
        }
        catch (Exception $oException) {
            return $oRetrieve->error(
                [
                    'msg' => $oException->getMessage()
                ]
            );
        }

    }

    /**
     * If user has already executed a session before, We will have his/her customer id
     *
     * @return void
     */
    protected function getCustomerID()
    {
        $this->customerID = UserModel::getStripeID();
    }

    protected function isLiveMode(): bool
    {
        return $this->aConfiguration['mode'] == 'live';
    }

    protected function getEndpointSecret(): string
    {
        $this->aConfiguration = GetWilokeSubmission::getAll();

        if ($this->isLiveMode()) {
            if (!isset($this->aConfiguration['stripe_endpoint_secret'])) {
                return '';
            }

            return trim($this->aConfiguration['stripe_endpoint_secret']);
        }

        return isset($this->aConfiguration['stripe_sandbox_endpoint_secret']) ? trim($this->aConfiguration['stripe_sandbox_endpoint_secret']) : '';
    }

    public function getConfiguration($field)
    {
        return $this->aConfiguration[$field];
    }

    public function getZeroDecimal()
    {
        return !isset($this->aConfiguration['stripe_zero_decimal']) ||
        empty($this->aConfiguration['stripe_zero_decimal']) ? 1 :
            absint($this->aConfiguration['stripe_zero_decimal']);
    }

    protected function setup()
    {
        $this->userID = $this->oReceipt->getUserID();
        $this->setApiContext();
    }
}
