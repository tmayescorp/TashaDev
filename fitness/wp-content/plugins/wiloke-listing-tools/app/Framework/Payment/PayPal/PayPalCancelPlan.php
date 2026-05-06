<?php
namespace WilokeListgoFunctionality\Framework\Payment\PayPal;

use PayPal\Api\Agreement;
use PayPal\Api\AgreementStateDescriptor;
use WilokeListingTools\Framework\Payment\PayPalPayment;

class PayPalCancelPlan extends PayPalPayment
{
    protected $descriptor = 'Reactivating the agreement';
    protected $agreementID;
    protected $oApiContext;
    
    public function __construct($agreementID)
    {
        $this->descriptor  =
            apply_filters('wiloke-submission/app/payment/paypal/PayPalRenewPlan/descriptor', $this->descriptor);
        $this->agreementID = $agreementID;
        $this->setup();
    }
    
    public function processRenewing()
    {
        //Create an Agreement State Descriptor, explaining the reason to suspend.
        $insAgreementStateDescriptor = new AgreementStateDescriptor();
        $insAgreementStateDescriptor->setNote($this->descriptor);
        
        try {
            Agreement::get($this->agreementID, $this->oApiContext);
            
            return [
                'status' => 'success'
            ];
        } catch (\Exception $ex) {
            return [
                'status' => 'error',
                'msg'    => $ex->getMessage()
            ];
        }
    }
}
