<?php

namespace WilokeListingTools\Framework\Payment\Stripe;

use Exception;
use Stripe\Price;
use Stripe\Product;
use WilokeListingTools\Controllers\Receipt\ReceiptInterface;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Payment\AddPaymentHookAction;
use WilokeListingTools\Framework\Payment\CreatedPaymentHook;
use WilokeListingTools\Framework\Payment\PaymentMethodInterface;
use WilokeListingTools\Framework\Payment\Receipt\ReceiptStructureInterface;
use WilokeListingTools\Framework\Payment\StripePayment;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Frontend\User;

final class StripeSCANonRecurringPaymentMethod extends StripePayment implements PaymentMethodInterface
{
    public function getBillingType()
    {
        return wilokeListingToolsRepository()->get('payment:billingTypes', true)->sub('nonrecurring');
    }

    protected $aPaymentInfo;
    protected $oCharge;
    protected $relationshipID;
    protected $userID;
    protected $token;
    protected $postID;
    protected $paymentID;

    /**
     * @return array
     */
    private function createSession()
    {
        try {
            $postID = Session::getPaymentObjectID();

            $aProduct = [
                'name' => $this->oReceipt->getPlanName()
            ];

            if ($desc = $this->oReceipt->getPlanDescription()) {
                $aProduct['description'] = $desc;
            }

            $featuredImg = $this->oReceipt->getPlanFeaturedImg();

            if (!empty($featuredImg)) {
                $aProduct['images'][] = $featuredImg;
            }

//            https://wilcityservice.com/support/ticket/51494
//            https://caffspot.com/check-out/
            $aLineItem = [
                'price_data' => [
                    'currency'     => strtolower($this->oReceipt->getCurrency()),
                    'unit_amount'  => $this->oApiContext->zeroDecimal * $this->oReceipt->getTotal(),
                    'product_data' => $aProduct
                ],
                'quantity'   => 1
            ];

            $oSession = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items'           => [
                    $aLineItem
                ],
                'success_url'          => $this->oReceipt->getThankyouURL([
                    'postID'      => $postID,
                    'category'    => Session::getPaymentCategory(),
                    'promotionID' => Session::getSession('promotionID', true)
                ]),
                'metadata'             => [
                    'userID' => $this->oReceipt->getUserID(),
                ],
                'cancel_url'           => $this->oReceipt->getCancelUrl(),
                'mode'                 => 'payment'
            ]);

            FileSystem::logSuccess('AddListing: ' . General::getDebugAddListingStep('Created Strip Session'));
            $this->token = $oSession->payment_intent;
            $this->postID = $postID;
//            $this->paymentID = $oSession->payment_intent;

            $oAddPaymentHook = new CreatedPaymentHook(new StripeNonRecurringCreatedPaymentHook($this));
            $oAddPaymentHook->doSuccess();

            return [
                'status'    => 'success',
                'sessionID' => $oSession->id,
//                'sessionID' => $oSession->id,
                'gateway'   => $this->gateway
            ];
        }
        catch (Exception $oException) {
            return [
                'status' => 'error',
                'msg'    => $oException->getMessage()
            ];
        }
    }

    /**
     * @param ReceiptStructureInterface $oReceipt
     *
     * @return array
     */
    public function proceedPayment(ReceiptStructureInterface $oReceipt)
    {
        $this->oReceipt = $oReceipt;
        $this->setup();

        try {
            return $this->createSession();
        }
        catch (Exception $oE) {
            return [
                'status' => 'error',
                'msg'    => $oE->getMessage()
            ];
        }
    }

    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        } else {
            FileSystem::logError('Stripe: The property ' . $name . ' does not exist');

            return false;
        }
    }
}
