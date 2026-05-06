<?php
namespace WilokeListgoFunctionality\Framework\Payment\PayPal;

use PayPal\Api\Amount;
use PayPal\Api\RefundRequest;
use PayPal\Api\Sale;

use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Controllers\RetrieveController;
use WilokeListingTools\Framework\Payment\PayPalPayment;
use WilokeListingTools\Framework\Payment\RefundInterface;
use WilokeListingTools\Models\InvoiceModel;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;

class PayPalRefundNonRecurringPayment extends PayPalPayment implements RefundInterface
{
    protected $oApiContext;
    protected $chargedID;
    protected $oSale;
    protected $paymentID;
    /**
     * @var RetrieveController
     */
    protected $oRetrieve;
    
    protected function getSaleInfo()
    {
        try {
            $this->oSale = Sale::get($this->chargedID, $this->oApiContext);
            
            return $this->oRetrieve->success([]);
        } catch (\Exception $ex) {
            return $this->oRetrieve->error([
                'error' => esc_html__('This sale does not exist', 'wiloke-listing-tools')
            ]);
        }
    }
    
    protected function getSaleIDByPaymentID()
    {
        $this->chargedID = PaymentMetaModel::getPaymentIDByIntentID($this->paymentID);
        if (empty($this->chargedID)) {
            return $this->oRetrieve->error(
                [
                    'msg' => esc_html__('We could not find PayPal Sale ID', 'wiloke-listing-tools')
                ]
            );
        }
        
        return $this->oRetrieve->success([]);
    }
    
    public function getChargedID()
    {
        $aStatus = $this->getSaleIDByPaymentID();
    
        if ($aStatus['status'] == 'error') {
            return $this->oRetrieve->error($aStatus);
        }
    
        $aStatus = $this->getSaleInfo();
    
        if ($aStatus['status'] == 'error') {
            return $this->oRetrieve->error($aStatus);
        }
    }
    
    public function refund($paymentID)
    {
        $this->paymentID = $paymentID;
        $this->oRetrieve = new RetrieveController(new NormalRetrieve());
        
        $this->setupConfiguration();
        $invoiceID = InvoiceModel::getInvoiceIDByPaymentID($this->paymentID);
        
        if (empty($invoiceID)) {
            return $this->oRetrieve->error([
                'msg'    => esc_html__('We could not found the invoice of this session', 'wiloke-listing-tools')
            ]);
        }
        
        $amount   = InvoiceModel::getField('total', $invoiceID);
        $currency = InvoiceModel::getField('currency', $invoiceID);
        
        $oAmount = new Amount();
        $oAmount->setTotal($amount)
                ->setCurrency($currency)
        ;
        
        $oRefundRequest = new RefundRequest();
        $oRefundRequest->setAmount($oAmount);
        
        $oSale = new Sale();
        $oSale->setId($this->chargedID);
        
        try {
            $refundedSale = $oSale->refundSale($oRefundRequest, $this->oApiContext);
            PaymentModel::updatePaymentStatus('refunded', $paymentID);
            
            return $this->oRetrieve->success([
                'status' => 'success',
                'msg'    => $refundedSale
            ]);
        } catch (\Exception $oE) {
            return $this->oRetrieve->error([
                'status' => 'error',
                'msg'    => $oE->getMessage()
            ]);
        }
        
    }
}
