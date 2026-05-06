<?php

namespace WilokeListingTools\Framework\Payment\Stripe;

use Stripe\Refund;
use Stripe\Stripe;
use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Controllers\RetrieveController;
use WilokeListingTools\Framework\Payment\PaymentMethodInterface;
use WilokeListingTools\Framework\Payment\Receipt\ReceiptStructureInterface;
use WilokeListingTools\Framework\Payment\RefundInterface;
use WilokeListingTools\Framework\Payment\StripePayment;
use WilokeListingTools\Models\InvoiceModel;
use WilokeListingTools\Models\PaymentMetaModel;

class StripeRefundNonRecurringPayment extends StripePayment implements RefundInterface
{
    protected $chargeID;
    private $paymentID;
    /**
     * @var RetrieveController
     */
    public $oRetrieve;
    
    public function getChargedID()
    {
        $this->chargeID = PaymentMetaModel::getStripeChargeID($this->paymentID);
        if (empty($this->chargeID)) {
            return $this->oRetrieve->error([
                'msg' => esc_html__('We could not find Stripe Charge ID', 'wiloke-listing-tools')
            ]);
        }
        
        return $this->oRetrieve->success([]);
    }
    
    public function refund($paymentID)
    {
        $this->paymentID = $paymentID;
        $this->oRetrieve = new RetrieveController(new NormalRetrieve());
        
        $this->getChargedID();
        $this->setApiContext();
        $invoiceID = InvoiceModel::getInvoiceIDByPaymentID($this->paymentID);
        
        if (empty($invoiceID)) {
            return $this->oRetrieve->error([
                'msg' => esc_html__('We could not found the invoice of this session', 'wiloke-listing-tools')
            ]);
        }
        $amount = InvoiceModel::getField('total', $invoiceID);
        
        try {
            Refund::create(
                [
                    'charge' => $this->chargeID,
                    'amount' => $amount * $this->oApiContext->zeroDecimal,
                    'reason' => 'requested_by_customer'
                ]
            );
            
            return $this->oRetrieve->success([
                'msg' => esc_html__('The refund has been proceeded', 'wiloke-listing-tools')
            ]);
        } catch (\Exception $oE) {
            return $this->oRetrieve->error([
                'msg' => $oE->getMessage()
            ]);
        }
    }
}
