<?php
namespace WilokeListingTools\Framework\Payment\PayPal;

use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;

use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Payment\PayPalPayment;
use WilokeListingTools\Framework\Payment\ProceededPaymentHook;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Models\PaymentMetaModel;

final class PayPalExecuteNonRecurringPayment extends PayPalPayment implements PayPalExecuteInterface
{
    public $payerID;
    protected $aPlan;
    protected $oExecutePayPalResult;
    protected $intentID;
    protected $aPaymentMeta;
    
    /**
     * @return array
     */
    public function execute()
    {
        if (!isset($_REQUEST['paymentId'])) {
            FileSystem::logError('Missed PayPal Payment ID');
            
            return [
                'status' => 'error',
                'msg'    => esc_html__('Missing PayPal Payment ID', 'wiloke-listing-tools')
            ];
        }
        
        $this->paypalPaymentID = trim($_REQUEST['paymentId']);
        
        $this->setup();
        
        $this->token     = trim($_REQUEST['token']);
        $this->paymentID = PaymentMetaModel::getPaymentIDByToken($this->token);
        /*
         * It's an array: token presents to key and planId presents to value
         */
        $this->paypalPaymentID = trim($_REQUEST['paymentId']);
        $this->payerID         = trim($_REQUEST['PayerID']);
        
        $instPayment = Payment::get($this->paypalPaymentID, $this->oApiContext);
        
        // Execute payment with payer id
        $instPaymentExecution = new PaymentExecution();
        $instPaymentExecution->setPayerId($this->payerID);
        $billingType = wilokeListingToolsRepository()->get('payment:billingTypes', true)->sub('nonrecurring');
        
        $this->aPaymentMeta                = PaymentMetaModel::getPaymentInfo($this->paymentID);
        $this->aPaymentMeta['paymentID']   = $this->paymentID;
        $this->aPaymentMeta['billingType'] = $billingType;
        
        $oProceedPaymentHook = new ProceededPaymentHook(new PayPalProceededNonRecurringPaymentHook($this));
        
        try {
            // Execute payment
            $this->oExecutePayPalResult     = $instPayment->execute($instPaymentExecution, $this->oApiContext);
            $this->aPaymentMeta['intentID'] = $this->oExecutePayPalResult->id;
            
            /**
             * @hooked: WilokeListingTools\Controllers\PaymentController:updatePaymentCompletedStatus 5
             * @hooked: SessionController:deletePaymentSessions
             */
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
            $oProceedPaymentHook->doFailed();
            
            return [
                'status' => 'error',
                'aInfo'  => $this->aPaymentMeta,
                'msg'    => $ex->getMessage()
            ];
        }
    }
}
