<?php

namespace WilokeListingTools\Framework\Payment\DirectBankTransfer;

use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Controllers\Retrieve\RetrieveFactory;
use WilokeListingTools\Controllers\RetrieveController;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Payment\Receipt\ReceiptStructureInterface;
use WilokeListingTools\Framework\Store\Session;

abstract class DirectBankTransferPayment
{
    protected $aConfiguration;
    public    $gateway       = 'banktransfer';
    public    $aBankAccounts = [];
    protected $paymentID;
    protected $token;
    protected $postID;
    protected $category;
    protected $subscriptionID;
    protected $isRefunded;
    protected $nextBillingDateGMT;
    protected $isTrial       = false;
    /**
     * @var ReceiptStructureInterface
     */
    protected $oReceipt;

    protected function generateToken()
    {
        $this->token = md5($this->gateway . $this->category . time());
    }

    public function getToken()
    {
        return $this->token;
    }

    protected function generateSubscriptionID()
    {
        if (!empty($this->nextBillingDateGMT)) {
            $this->subscriptionID = md5($this->gateway . $this->paymentID . $this->nextBillingDateGMT);
        }

        return $this->subscriptionID;
    }

    protected function getCategory()
    {
        $this->category = Session::getPaymentCategory(false);

        return $this->category;
    }

    /**
     * @param $billingType
     *
     * @return string
     */
    protected function thankyouUrl()
    {
        return $this->oReceipt->getThankyouURL([
            [
                'planID'      => $this->oReceipt->getPlanID(),
                'postID'      => $this->getPostID(),
                'category'    => $this->getCategory(),
                'gateway'     => $this->gateway,
                'paymentID'   => $this->paymentID,
                'promotionID' => Session::getSession('promotionID', true)
            ]
        ]);
    }

    /**
     * @param $billingType
     *
     * @return string
     */
    protected function cancelUrl()
    {
        return $this->oReceipt->getCancelUrl([
            'planID'   => $this->oReceipt->getPlanID(),
            'postID'   => $this->getPostID(),
            'category' => $this->getCategory()
        ]);
    }

    public function getBankAccount()
    {
        $this->aConfiguration = GetWilokeSubmission::getAll();

        for ($i = 1; $i <= 4; $i++) {
            if (!empty($this->aConfiguration['bank_transfer_account_name_' . $i]) &&
                !empty($this->aConfiguration['bank_transfer_account_number_' . $i]) &&
                !empty($this->aConfiguration['bank_transfer_name_' . $i])
            ) {
                foreach (
                    [
                        'bank_transfer_account_name',
                        'bank_transfer_account_number',
                        'bank_transfer_name',
                        'bank_transfer_short_code',
                        'bank_transfer_iban',
                        'bank_transfer_swift'
                    ] as $bankInfo
                ) {
                    $this->aBankAccounts[$i][$bankInfo] = $this->aConfiguration[$bankInfo . '_' . $i];
                }
            }
        }
    }

    protected function getPostID()
    {
        $this->postID = Session::getPaymentObjectID(false);

        return $this->postID;
    }

    protected function setupConfiguration()
    {
        $oRetrieve = RetrieveFactory::retrieve('normal');

        $this->aConfiguration = GetWilokeSubmission::getAll();
        $msg = esc_html__('The Direct Bank Transfer has not configured yet!', 'wiloke-listing-tools');
        if (!GetWilokeSubmission::isGatewaySupported($this->gateway)) {
            return $oRetrieve->error([
                'msg' => $msg
            ]);
        }

        $this->getBankAccount();

        if (empty($this->aBankAccounts)) {
            return $oRetrieve->error([
                'msg' => esc_html__('You need provide one bank account at least: Wiloke Submission -> Direct Bank Transfer setting',
                    'wiloke-listing-tools')
            ]);
        }

        return $oRetrieve->success([
            'msg' => esc_html__('The bank accounts have been setup', 'wiloke-listing-tools')
        ]);
    }

    public function getConfiguration($field = '')
    {
        if (!empty($field)) {
            return $this->aConfiguration[$field];
        }

        return $this->aConfiguration;
    }

    public function __get($name)
    {
        return $this->$name;
    }

    public function __isset($name)
    {
        return !empty($this->$name);
    }
}
