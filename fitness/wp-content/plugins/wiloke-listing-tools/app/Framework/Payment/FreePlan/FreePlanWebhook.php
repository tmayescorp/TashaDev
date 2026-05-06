<?php

namespace WilokeListingTools\Framework\Payment\FreePlan;

use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Controllers\RetrieveController;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Payment\ProceededPaymentHook;
use WilokeListingTools\Framework\Payment\WebhookInterface;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;

class FreePlanWebhook implements WebhookInterface
{
    private $gateway = 'free';
    private $newStatus;
    protected $oPaymentMeta;
    protected $oPaymentInfo;
    protected $aPaymentMetaInfo;
    protected $paymentID;
    protected $focusIncreaseNextBillingDate = false;
    
    public function observer()
    {
        if (isset($_REQUEST['wiloke-submission-listener']) &&
            $_REQUEST['wiloke-submission-listener'] == $this->gateway
        ) {
            $aStatus = $this->verify();
            if ($aStatus['status'] == 'success') {
                $this->handler();
            }
        }
    }
    
    public function verify()
    {
        $oRetrieve = new RetrieveController(new NormalRetrieve());
        if (
            !isset($_REQUEST['paymentID'])
            || empty($_REQUEST['paymentID'])
            || !PaymentModel::getField('ID', $_REQUEST['paymentID'])
        ) {
            return $oRetrieve->error(
                [
                    'msg' => esc_html__('The payment ID is required.', 'wiloke-listing-tools')
                ]
            );
        }
        
        if (!isset($_REQUEST['newStatus']) || empty($_REQUEST['newStatus'])) {
            return $oRetrieve->error(
                [
                    'msg' => esc_html__('The new status is required.', 'wiloke-listing-tools')
                ]
            );
        }
        
        $this->newStatus = sanitize_text_field(trim($_REQUEST['newStatus']));
        $this->paymentID = sanitize_text_field(trim($_REQUEST['paymentID']));
        
        $gateway = PaymentModel::getField('gateway', $_REQUEST['paymentID']);
        
//        if (!General::isAdmin()) {
//            if (GetWilokeSubmission::getField('approved_method') != 'auto_approved_after_payment') {
//                return $oRetrieve->error(
//                    [
//                        'msg' => 'Forbidden'
//                    ]
//                );
//            }
//        }
        
        if ($gateway !== 'free') {
            return $oRetrieve->error(
                [
                    'msg' => 'Forbidden'
                ]
            );
        }
        
        return $oRetrieve->success([]);
    }
    
    public function handler()
    {
        $oRetrieve = new RetrieveController(new NormalRetrieve());
        $this->billingType      = PaymentModel::getField('billingType', $this->paymentID);
        $this->aPaymentMetaInfo = PaymentMetaModel::getPaymentInfo($this->paymentID);
        $this->oPaymentInfo     = PaymentModel::getPaymentInfo($this->paymentID);
        
        $oProceedWebhook       = new ProceededPaymentHook(
            new FreePlanHook($this)
        );
        $oPrePareInvoiceFormat = new FreePlanPrepareInvoiceFormat($this);
        
        $this->token = PaymentMetaModel::getPaymentTokenByPaymentID($this->paymentID);
        switch ($this->newStatus) {
            case 'succeeded':
                $this->aInvoiceFormat = $oPrePareInvoiceFormat->prepareInvoiceParam()->getParams();
                $oProceedWebhook->doCompleted();
                break;
            case 'cancelled':
                $oProceedWebhook->doCancelled();
                break;
        }
        
        return $oRetrieve->success([
            'paymentID' => $this->paymentID,
            'status'    => $this->newStatus
        ]);
    }
    
    public function __isset($name)
    {
        return !empty($this->$name);
    }
    
    public function __get($name)
    {
        return $this->$name;
    }
}
