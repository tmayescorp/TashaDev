<?php
namespace WilokeListingTools\Framework\Payment\PayPal;

use PayPal\Api\Agreement;

use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Payment\PayPalPayment;
use WilokeListingTools\Framework\Payment\ProceededPaymentHook;
use WilokeListingTools\Models\PaymentMetaModel;

final class PayPalExecuteRecurringPayment extends PayPalPayment implements PayPalExecuteInterface
{
    protected $oExecutePayPalResult;
    protected $aPaymentMeta;
    
    public function execute()
    {
        $this->setup();
        $this->token                       = $_GET['token'];
        $this->paymentID                   = PaymentMetaModel::getPaymentIDByToken($this->token);
        $this->aPaymentMeta                = PaymentMetaModel::getPaymentInfo($this->paymentID);
        $billingType                       =
            wilokeListingToolsRepository()->get('payment:billingTypes', true)->sub('recurring');
        $this->aPaymentMeta['paymentID']   = $this->paymentID;
        $this->aPaymentMeta['billingType'] = $billingType;
        
        $oAgreement = new Agreement();
        try {
            // Execute agreement
            $this->oExecutePayPalResult               = $oAgreement->execute($this->token, $this->oApiContext);
            $this->aPaymentMeta['nextBillingDateGMT'] =
                Time::timestampUTCNow($this->oExecutePayPalResult->agreement_details->next_billing_date);
            $this->aPaymentMeta['subscriptionID']     = $this->oExecutePayPalResult->id;
            
            /**
             * @hooked: WilokeListingTools\Controllers\PaymentController:updatePaymentCompletedStatus 5
             * @hooked: SessionController:deletePaymentSessions
             */
            $oProceedPaymentHook = new ProceededPaymentHook(new PayPalProceededRecurringPaymentHook($this));
            $oProceedPaymentHook->doCompleted();
            
            return [
                'status' => 'success',
                'aInfo'  => $this->aPaymentMeta
            ];
        } catch (\Exception $ex) {
            /**
             * @hooked: WilokeListingTools\Controllers\PaymentController:updatePaymentFailedStatus 5
             * @hooked: SessionController:deletePaymentSessions
             */
            $oProceedPaymentHook = new ProceededPaymentHook(new PayPalProceededRecurringPaymentHook($this));
            $oProceedPaymentHook->doFailed();
            
            FileSystem::logError(json_encode([
                'paymentID' => $this->paymentID,
                'date'      => current_time('timestamp', true),
                'msg'       => $ex->getMessage(),
                'gateway'   => $this->gateway
            ]));
            
            return [
                'status' => 'error',
                'msg'    => $ex->getMessage(),
                'aInfo'  => $this->aPaymentMeta
            ];
        }
    }
}
