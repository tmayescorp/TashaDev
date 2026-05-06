<?php
namespace WilokeListingTools\Framework\Payment\DirectBankTransfer;

use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Controllers\RetrieveController;
use WilokeListingTools\Framework\Payment\CreatedPaymentHook;
use WilokeListingTools\Framework\Payment\PaymentMethodInterface;
use WilokeListingTools\Framework\Payment\Receipt\ReceiptStructureInterface;
use WilokeListingTools\Models\PaymentMetaModel;

class DirectBankTransferRecurringPayment extends DirectBankTransferPayment implements PaymentMethodInterface
{
    protected $oReceipt;
    
    public function getBillingType()
    {
        return wilokeListingToolsRepository()->get('payment:billingTypes', true)->sub('recurring');
    }
    
    /**
     * @param ReceiptStructureInterface $oReceipt
     *
     * @return array
     */
    public function proceedPayment(ReceiptStructureInterface $oReceipt)
    {
        $this->oReceipt = $oReceipt;
        $aStatus        = $this->setupConfiguration();
        
        $oRetrieve = new RetrieveController(new NormalRetrieve());
        if ($aStatus['status'] == 'error') {
            return $aStatus;
        }
        
        $this->getPostID();
        $this->generateToken();
        
        $oAddPaymentHook = new CreatedPaymentHook(new DirectBankTransferRecurringCreatedPaymentHook($this));
        $oAddPaymentHook->doSuccess();
        
        $this->paymentID = PaymentMetaModel::getPaymentIDByToken($this->token);
        
        return $oRetrieve->success(
            [
                'msg'        => esc_html__('The payment has been created successfully', 'wiloke-listing-tools'),
                'redirectTo' => $this->thankyouUrl(),
                'paymentID'  => $this->paymentID,
                'gateway'    => $this->gateway
            ]
        );
    }
}
