<?php

namespace WilokeListingTools\Framework\Payment\PayPal;

use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Exception\PayPalConnectionException;

use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Controllers\Retrieve\RetrieveFactory;
use WilokeListingTools\Controllers\RetrieveController;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Payment\CreatedPaymentHook;
use WilokeListingTools\Framework\Payment\PaymentMethodInterface;
use WilokeListingTools\Framework\Payment\PayPalPayment;
use WilokeListingTools\Framework\Payment\Receipt;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;

class PayPalNonRecurringPaymentMethod extends PayPalPayment implements PaymentMethodInterface
{
    public function getBillingType()
    {
        return wilokeListingToolsRepository()->get('payment:billingTypes', true)->sub('nonrecurring');
    }

    public function getApprovalUrl()
    {
        return $this->oPayment->getApprovalLink();
    }

    /**
     * @return array
     */
    public function prepareSubmissionInfo(): array
    {
        // Create new payer and method
        $this->oPayer = new Payer();
        $this->oPayer->setPaymentMethod('paypal');
        $oRetrieve = RetrieveFactory::retrieve('normal');

        try {
            $oInstPlan = new Item();
            $oInstPlan->setName($this->oReceipt->getPlanName())
                ->setCurrency($this->oReceipt->getCurrency())
                ->setQuantity(1)
                ->setPrice($this->oReceipt->getSubTotal());
            $aItems[] = $oInstPlan;
        }
        catch (\Exception $oE) {
            return $oRetrieve->error($oE->getMessage());
        };

        $discountPrice = 0;
        if (!empty($this->oReceipt->getDiscount())) {
            $discountPrice = -$this->oReceipt->getDiscount();

            try {
                $oDiscount = new Item();
                $oDiscount->setName($this->oReceipt->getPlanName())
                    ->setCurrency($this->oReceipt->getCurrency())
                    ->setQuantity(1)
                    ->setPrice($discountPrice);
                $aItems[] = $oDiscount;
            }
            catch (\Exception $oE) {
                return $oRetrieve->error($oE->getMessage());
            }
        }

        try {
            $oItemList = new ItemList();
            $oItemList->setItems($aItems);
        }
        catch (\Exception $oE) {
            return $oRetrieve->error($oE->getMessage());
        }

        try {
            $instDetails = new Details();

            if (!empty($this->oReceipt->getTax())) {
                $instDetails->setTax($this->oReceipt->getTax());
                $instDetails->setSubtotal($this->oReceipt->getSubTotal() + $discountPrice);
            }
        }
        catch (\Exception $oE) {
            return $oRetrieve->error($oE->getMessage());
        }

        try {
            $instAmount = new Amount();
            $instAmount->setCurrency($this->oReceipt->getCurrency())
                ->setDetails($instDetails)
                ->setTotal($this->oReceipt->getTotal());
        }
        catch (\Exception $oE) {
            return $oRetrieve->error($oE->getMessage());
        }

        // Set transaction object
        try {
            $transaction = new Transaction();
            $transaction->setItemList($oItemList)
                ->setAmount($instAmount)
                ->setInvoiceNumber(uniqid())
                ->setDescription($this->paymentDescription);
        }
        catch (\Exception $oE) {
            return $oRetrieve->error($oE->getMessage());
        }

        // Set redirect urls
        try {
            $oRedirectUrls = new RedirectUrls();
            $oRedirectUrls->setReturnUrl($this->thankyouUrl($this->getBillingType()))
                ->setCancelUrl($this->cancelUrl($this->getBillingType()));
        }
        catch (\Exception $oE) {
            return $oRetrieve->error($oE->getMessage());
        }

        // Create the full payment object
        try {
            $this->oPayment = new Payment();
            $this->oPayment->setIntent('sale')
                ->setPayer($this->oPayer)
                ->setRedirectUrls($oRedirectUrls)
                ->setTransactions([$transaction]);
        }
        catch (\Exception $oE) {
            return $oRetrieve->error($oE->getMessage());
        }

        return $oRetrieve->success(['msg' => esc_html__('The data has been prepared successfully',
            'wiloke-listing-tools')]);
    }

    private function submitPayment()
    {
        $oRetrieve = RetrieveFactory::retrieve('normal');

        // Create payment with valid API context
        try {
            $this->oPayment->create($this->oApiContext);
            // Get PayPal redirect URL and redirect user
            $approvalUrl = $this->getApprovalUrl();
            $this->parseTokenFromApprovalUrl($approvalUrl);

            $oAddPaymentHook = new CreatedPaymentHook(new PayPalNonRecurringCreatedPaymentHook($this));
            $oAddPaymentHook->doSuccess();

            $this->paymentID = PaymentMetaModel::getPaymentIDByToken($this->getToken());
            if (empty($this->paymentID)) {
                return $oRetrieve->error([
                    'msg' => esc_html__('Could not insert Payment History', 'wiloke-listing-tools')
                ]);
            }

            Session::setSession('waiting_for_paypal_execution', 'yes');

            return $oRetrieve->success([
                'msg'        => esc_html__('The payment has been created successfully. We will redirect to PayPal shortly',
                    'wiloke-listing-tools'),
                'redirectTo' => $approvalUrl,
                'paymentID'  => $this->paymentID,
                'gateway'    => $this->gateway
            ]);
        }
        catch (PayPalConnectionException $ex) {
            return $oRetrieve->error([
                'code'   => $ex->getCode(),
                'status' => 'error',
                'msg'    => $ex->getMessage()
            ]);
        }
        catch (\Exception $ex) {
            return $oRetrieve->error([
                'status' => 'error',
                'msg'    => $ex->getMessage()
            ]);
        }
    }

    /**
     * @param Receipt\ReceiptStructureInterface $oReceipt
     *
     * @return mixed
     */
    public function proceedPayment(Receipt\ReceiptStructureInterface $oReceipt)
    {
        $this->oReceipt = $oReceipt;
        $this->setup();
        $oRetrieve = RetrieveFactory::retrieve('normal');

        $aPrepareStatus = $this->prepareSubmissionInfo();
        if ($aPrepareStatus['status'] == 'error') {
            return $oRetrieve->error($aPrepareStatus);
        }

        $aSubmitStatus = $this->submitPayment();
        if ($aSubmitStatus['status'] == 'error') {
            return $oRetrieve->error($aSubmitStatus);
        }

        return $oRetrieve->success($aSubmitStatus);
    }

    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        } else {
            return false;
        }
    }

    public function __isset($name)
    {
        return !empty($this->$name);
    }
}
