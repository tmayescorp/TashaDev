<?php
namespace WilokeListingTools\Framework\Payment\PayPal;

use PayPal\Api\Agreement;
use PayPal\Api\AgreementStateDescriptor;
use WilokeListingTools\Framework\Helpers\Message;
use WilokeListingTools\Framework\Payment\PayPalPayment;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;

class PayPalCancelRecurringPayment extends PayPalPayment
{
    protected $agreementID;
    protected $setNote;
    
    public function getAgreementIDByPaymentID($paymentID)
    {
        $this->agreementID = PaymentMetaModel::getSubscriptionID($paymentID);
        
        if (empty($this->agreementID)) {
            Message::error(esc_html__('We could not found PayPal Agreement ID of this payment id',
                'wiloke-listing-tools'));
        }
        
        return $this;
    }
    
    public function execute($paymentID)
    {
        $this->getAgreementIDByPaymentID($paymentID);
        $this->setup();
        
        $oAgreement = new Agreement();
        $oAgreement->setId($this->agreementID);
        
        $oAgreementStateDescriptor = new AgreementStateDescriptor();
        $oAgreementStateDescriptor->setNote(esc_html__('Cancel the agreement', 'wiloke-listing-tools'));
        
        try {
            $oAgreement->cancel($oAgreementStateDescriptor, $this->oApiContext);
            $cancelAgreementDetails = Agreement::get($oAgreement->getId(), $this->oApiContext);
            
            PaymentModel::updatePaymentStatus('cancelled', $paymentID);
            
            return [
                'status' => 'success',
                'msg'    => esc_html__('The payment has been cancelled successfully', 'wiloke-listing-tools')
            ];
        } catch (\Exception $oE) {
            return [
                'status' => 'error',
                'msg'    => $oE->getMessage()
            ];
        }
    }
}
