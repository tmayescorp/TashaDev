<?php
namespace WilokeListingTools\Framework\Payment\PayPal;

use PayPal\Api\ChargeModel;
use PayPal\Api\Details;
use PayPal\Api\Tax;
use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Controllers\RetrieveController;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Payment\CreatedPaymentHook;
use WilokeListingTools\Framework\Payment\PaymentMethodInterface;
use PayPal\Api\Currency;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\PaymentDefinition;
use PayPal\Api\Plan;
use PayPal\Api\Patch;
use PayPal\Api\PatchRequest;
use PayPal\Common\PayPalModel;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Exception\PayPalInvalidCredentialException;

use PayPal\Api\Agreement;
use PayPal\Api\Payer;
use WilokeListingTools\Framework\Payment\PayPalPayment;
use WilokeListingTools\Framework\Payment\Receipt;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;

final class PayPalRecurringPaymentMethod extends PayPalPayment implements PaymentMethodInterface
{
    /**
     * @var RetrieveController
     */
    protected $oRetrieve;
    
    public function getBillingType()
    {
        return wilokeListingToolsRepository()->get('payment:billingTypes', true)->sub('recurring');
    }
    
    /**
     * @param $oE \Exception
     *
     * @return mixed
     */
    public function getError($oE)
    {
        return $this->oRetrieve->error([
            'msg' => $oE->getMessage()
        ]);
    }
    
    protected function createPlan()
    {
        $this->oRetrieve = new RetrieveController(new NormalRetrieve());
        
        // Create a new billing plan
        $oPlan = new Plan();
        try {
            $oPlan->setName($this->oReceipt->getPlanName())
                  ->setDescription($this->paymentDescription)
                  ->setType('INFINITE')
            ;
        } catch (\Exception $oE) {
            FileSystem::logError($oE->getMessage());
            
            return $this->getError($oE);
        }
        
        // Set billing plan definitions
        try {
            $oCurrency = new Currency(
                [
                    'value'    => $this->oReceipt->getTotal(),
                    'currency' => $this->oReceipt->getCurrency()
                ]
            );
        } catch (\Exception $oE) {
            FileSystem::logError($oE->getMessage());
            
            return $this->getError($oE);
        }
        $aPaymentDefinitions = [];
        
        try {
            $paymentDefinition = new PaymentDefinition();
            $paymentDefinition->setName($this->oReceipt->getPlanName())
                              ->setType('REGULAR')
                              ->setFrequency('DAY')
                              ->setFrequencyInterval($this->oReceipt->getRegularPeriod())
                              ->setAmount($oCurrency)
            ;
        
            if ($tax = $this->oReceipt->getTax()) {
                try {
                    $taxAmount = new Currency(
                        [
                            'value'    => $tax,
                            'currency' => $this->oReceipt->getCurrency()
                        ]
                    );
                    
                    $chargeModelTax = new ChargeModel();
                    $chargeModelTax->setType('TAX');
                    $chargeModelTax->setAmount($taxAmount);
                    $paymentDefinition->addChargeModel($chargeModelTax);
                } catch (\Exception $oE) {
                    return $this->getError($oE);
                }
            }
            
            $aPaymentDefinitions[] = $paymentDefinition;
        } catch (\Exception $oE) {
            return $this->getError($oE);
        }
        
        // Trial
        if (!empty($this->oReceipt->getTrialPeriod())) {
            $oFreeCurrency = new Currency(
                [
                    'value'    => 0,
                    'currency' => $this->oReceipt->getCurrency()
                ]
            );
            
            try {
                $paymentTrialDefinition = new PaymentDefinition();
                $paymentTrialDefinition->setName(esc_html__('Trial', 'wiloke-listing-tools').' '.
                                                 $this->oReceipt->getPlanName())
                                       ->setType('TRIAL')
                                       ->setFrequency('DAY')
                                       ->setFrequencyInterval($this->oReceipt->getTrialPeriod())
                                       ->setCycles(1)
                                       ->setAmount($oFreeCurrency)
                ;
                $aPaymentDefinitions[] = $paymentTrialDefinition;
            } catch (\Exception $oE) {
                return $this->getError($oE);
            }
        }
        
        // Set merchant preferences
        try {
            $merchantPreferences = new MerchantPreferences();
            $merchantPreferences->setReturnUrl($this->thankyouUrl($this->getBillingType()))
                                ->setCancelUrl($this->cancelUrl($this->getBillingType()))
                                ->setNotifyUrl($this->notifyUrl())
                                ->setAutoBillAmount('YES')
                                ->setInitialFailAmountAction('CONTINUE')
                                ->setMaxFailAttempts($this->maxFailedPayments)
            ;
        } catch (\Exception $oE) {
            return $this->getError($oE);
        }
        
        if (!empty($this->aConfiguration['initial_fee'])) {
            try {
                $oCurrencyFee = new Currency([
                    'value'    => $this->aConfiguration['initial_fee'],
                    'currency' => $this->oReceipt->getCurrency()
                ]);
            } catch (\Exception $oE) {
                return $this->getError($oE);
            }
            
            try {
                $merchantPreferences->setSetupFee($oCurrencyFee);
            } catch (\Exception $oE) {
                return $this->getError($oE);
            }
        }
        
        $oPlan->setPaymentDefinitions($aPaymentDefinitions);
        $oPlan->setMerchantPreferences($merchantPreferences);
        //        $oPlan->($merchantPreferences);
        
        //create plan
        try {
            $createdPlan = $oPlan->create($this->oApiContext);
            
            try {
                $patch = new Patch();
                $value = new PayPalModel('{"state":"ACTIVE"}');
                $patch->setOp('replace')
                      ->setPath('/')
                      ->setValue($value)
                ;
                $patchRequest = new PatchRequest();
                $patchRequest->addPatch($patch);
                $createdPlan->update($patchRequest, $this->oApiContext);
                $oPlan = Plan::get($createdPlan->getId(), $this->oApiContext);
                
                // Output plan id
                $this->planID = $oPlan->getId();
                
                return $this->oRetrieve->success([
                    'msg'    => '',
                    'planID' => $oPlan->getId()
                ]);
            } catch (PayPalConnectionException $ex) {
                return $this->getError($ex);
            } catch (\Exception $ex) {
                return $this->getError($ex);
            }
        } catch (PayPalConnectionException $ex) {
            return $this->getError($ex);
        } catch (\Exception $ex) {
            return $this->getError($ex);
        }
    }
    
    protected function createBillingAgreement()
    {
        // Create new agreement
        $instAgreement = new Agreement();
        $instAgreement->setName($this->paymentDescription)
                      ->setDescription($this->paymentDescription)
                      ->setStartDate(Time::iso8601StartDate())
        ;
        // Set plan id
        $oPlan = new Plan();
        $oPlan->setId($this->planID);
        $instAgreement->setPlan($oPlan);
        
        // Add payer type
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');
        $instAgreement->setPayer($payer);
        // Adding shipping details
        //		if ( $instShippingAddress = $this->setShippingAddress() ){
        //			$instAgreement->setShippingAddress($instShippingAddress);
        //		}
        
        try {
            // Create agreement
            $instAgreement = $instAgreement->create($this->oApiContext);
            // Extract approval URL to redirect user
            
            $approvalUrl = $instAgreement->getApprovalLink();
            $this->parseTokenFromApprovalUrl($approvalUrl);
            
            $oAddPaymentHook = new CreatedPaymentHook(new PayPalRecurringCreatedPaymentHook($this));
            $oAddPaymentHook->doSuccess();
            
            $this->paymentID = PaymentMetaModel::getPaymentIDByToken($this->token);
            if (empty($this->paymentID)) {
                FileSystem::logError('We could not create payment id');
                
                return $this->oRetrieve->error([
                    'msg' => esc_html__('Could not insert Payment History', 'wiloke-listing-tools')
                ]);
            }
            
            return $this->oRetrieve->success([
                'paymentID'   => $this->paymentID,
                'gateway'     => $this->gateway,
                'billingType' => $this->getBillingType(),
                'msg'         => esc_html__('The payment has been created successfully. We will redirect to PayPal shortly',
                    'wiloke-listing-tools'),
                'redirectTo'  => $instAgreement->getApprovalLink()
            ]);
        } catch (PayPalConnectionException $ex) {
            return $this->oRetrieve->error([
                'code' => $ex->getCode(),
                'msg'  => $ex->getMessage()
            ]);
        } catch (PayPalInvalidCredentialException $ex) {
            return $this->oRetrieve->error([
                'code' => $ex->getCode(),
                'msg'  => $ex->errorMessage()
            ]);
        } catch (\Exception $ex) {
            return $this->oRetrieve->error([
                'msg' => $ex->getMessage()
            ]);
        }
    }
    
    /**
     * @param Receipt\ReceiptStructureInterface $oReceipt
     *
     * @return array
     */
    public function proceedPayment(Receipt\ReceiptStructureInterface $oReceipt)
    {
        $this->oReceipt = $oReceipt;
        $this->setup();
        
        $aResult = $this->createPlan();
        if ($aResult['status'] == 'success') {
            $aResult = $this->createBillingAgreement();
            Session::setSession('waiting_for_paypal_execution', 'yes');
            
            return $aResult;
        } else {
            return $aResult;
        }
    }
    
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        } else {
            FileSystem::logError('Stripe: The property '.$name.' does not exist');
            
            return false;
        }
    }
}
