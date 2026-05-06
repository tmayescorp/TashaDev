<?php
namespace WilokeListingTools\Framework\Payment\PayPal;

use PayPal\Api\Agreement;
use PayPal\Api\AgreementStateDescriptor;
use WilokeListingTools\Framework\Payment\PayPalPayment;
use WilokeListingTools\Framework\Payment\SuspendInterface;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;

final class PayPalSuspendPlan extends PayPalPayment
{
    private $description;
    private $agreementID;
    
    public function __construct($paymentID)
    {
        $this->paymentID = $paymentID;
    }
    
    public function suspend()
    {
        $this->setup();
        $this->suspendDescrition = esc_html__('Suspended the Agreement', 'wiloke-listing-tools');
        $status                  = PaymentModel::getField('status', $this->paymentID);
        $this->description       = esc_html__('Suspended the Agreement', 'wiloke-listing-tools');
        
        if ($status !== 'active') {
            return [
                'status' => 'success'
            ];
        }
        
        $this->agreementID = PaymentMetaModel::getSubscriptionID($this->paymentID);
    
        if (empty($this->agreementID)) {
            return [
                'status' => 'success'
            ];
        }
        
        $agreementStateDescriptor = new AgreementStateDescriptor();
        $agreementStateDescriptor->setNote($this->description);
        $createdAgreement = null;
        
        $oAgreementInfo = Agreement::get($this->agreementID, $this->oApiContext);
        
        try {
            $oAgreementInfo->suspend($agreementStateDescriptor, $this->oApiContext);
            $this->oNewAgreement = Agreement::get($this->agreementID, $this->oApiContext);
            PaymentModel::updatePaymentStatus('suspended', $this->paymentID);
            
            return [
                'status' => 'success'
            ];
        } catch (\Exception $oE) {
            return [
                'status' => 'error',
                'msg'    => $oE->getMessage()
            ];
        }
    }
}
