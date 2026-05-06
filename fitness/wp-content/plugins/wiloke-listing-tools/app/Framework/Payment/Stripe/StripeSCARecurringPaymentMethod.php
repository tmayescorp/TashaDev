<?php

namespace WilokeListingTools\Framework\Payment\Stripe;

use Exception;
use Stripe\Plan;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Payment\CreatedPaymentHook;
use WilokeListingTools\Framework\Payment\PaymentMethodInterface;
use WilokeListingTools\Framework\Payment\Receipt\ReceiptStructureInterface;
use WilokeListingTools\Framework\Payment\StripePayment;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\PaymentModel;

final class StripeSCARecurringPaymentMethod extends StripePayment implements PaymentMethodInterface
{
    protected $aPaymentInfo;
    protected $oCharge;
    protected $relationshipID;
    protected $userID;
    protected $paymentID;
    public    $errMsg;
    private   $oStripePlanInfo;
    protected $token;
    protected $postID;
    /**
     * @var ReceiptStructureInterface
     */
    protected $oReceipt;

    public function getBillingType()
    {
        return wilokeListingToolsRepository()->get('payment:billingTypes', true)->sub('recurring');
    }

    protected function setup()
    {
        $this->userID = $this->oReceipt->getUserID();
        $this->setApiContext();
    }

    private function retrievePlan()
    {
        try {
            $this->oStripePlanInfo = Plan::retrieve($this->oReceipt->getPlanSlug());
            FileSystem::logSuccess(sprintf('Stripe: The Plan %s has been created before',
                $this->oReceipt->getPlanSlug()));

            return !empty($this->oStripePlanInfo);
        }
        catch (Exception $oException) {
            FileSystem::logSuccess(sprintf('Stripe: The Plan %s does not exist', $this->oReceipt->getPlanSlug()));

            return false;
        }
    }

    /**
     * @return bool
     */
    private function createPlan(): bool
    {
        $aArgs = [
            'amount'         => $this->oApiContext->zeroDecimal *
                ($this->oReceipt->getTotal() - $this->oReceipt->getTax()),
            'interval'       => 'day',
            'interval_count' => $this->oReceipt->getRegularPeriod(),
            'product'        => [
                'name' => $this->oReceipt->getPlanName()
            ],
            'currency'       => $this->oReceipt->getCurrency(),
            'id'             => $this->oReceipt->getPlanSlug()
        ];

        try {
            $this->oStripePlanInfo = Plan::create($aArgs);
            FileSystem::logSuccess(
                sprintf('Created Plan. Plan Info: %s', json_encode($aArgs))
            );

            return true;
        }
        catch (Exception $oException) {
            $this->errMsg = $oException->getMessage();
            FileSystem::logError(
                sprintf(
                    'We could not create plan because %s. Plan Info: %s',
                    $oException->getMessage(),
                    json_encode($aArgs)
                )
            );

            return false;
        }
    }

    /**
     * We have to create payment id before creating session, and this id will be used in the payment
     * subscription_data.metadata
     */
    private function createPaymentID()
    {
        $aPaymentData = [
            'userID'      => $this->oReceipt->getUserID(),
            'planID'      => $this->oReceipt->getPlanID(),
            'packageType' => $this->oReceipt->getPackageType(),
            'gateway'     => $this->gateway,
            'status'      => 'pending',
            'billingType' => $this->getBillingType(),
            'planName'    => $this->oReceipt->getPlanName()
        ];

        $this->paymentID = PaymentModel::insertPaymentHistory($aPaymentData);
    }

    /**
     * @return array
     */
    private function createSession()
    {
        /**
         * If the plan does not exist, We should create plan
         *
         * @see https://stripe.com/docs/api/plans/retrieve
         */
        if (!$this->retrievePlan()) {
            $createPlanStatus = $this->createPlan();
            if (!$createPlanStatus) {
                return [
                    'status' => 'error',
                    'msg'    => $this->errMsg
                ];
            }
        }

        $this->createPaymentID();
        if (!$this->paymentID) {
            FileSystem::logError('We could not create payment id');

            return [
                'status' => 'error',
                'msg'    => esc_html__('We could not create payment id', 'wiloke-listing-tools')
            ];
        }

        try {
            $postID = Session::getPaymentObjectID(false);

//            $aSubscriptionData = [
//                'items'    => [
//                    [
//                        'plan' => $this->oReceipt->getPlanSlug()
//                    ]
//                ],
//                'metadata' => [
//                    'userID'          => $this->oReceipt->getUserID(),
//                    'paymentID'       => $this->paymentID,
//                    'trialPeriodDays' => $this->oReceipt->getTrialPeriod()
//                ],
//            ];

            $aLineItem = [
                'price'    => $this->oReceipt->getPlanSlug(),
                'quantity' => 1,
            ];

            $aSubscriptionData = [];
            if ($this->oReceipt->getTax()) {
                $taxRateID = (new StripeTax())->getTaxRateID();
                if (!empty($taxRateID)) {
                    $aSubscriptionData['default_tax_rates'] = [$taxRateID];
                }
            }

            if (!empty($this->oReceipt->getTrialPeriod())) {
                $aSubscriptionData['trial_period_days'] = $this->oReceipt->getTrialPeriod();
            }

//            https://stripe.com/docs/payments/checkout/migrating-prices
            $aArgs = [
                'payment_method_types' => ['card'],
                'line_items'           => [$aLineItem],
                'success_url'          => $this->oReceipt->getThankyouURL([
                    'postID'      => $postID,
                    'category'    => Session::getPaymentCategory(false),
                    'promotionID' => Session::getSession('promotionID', true)
                ]),
                'metadata'             => [
                    'userID'          => $this->oReceipt->getUserID(),
                    'paymentID'       => $this->paymentID,
                    'trialPeriodDays' => $this->oReceipt->getTrialPeriod(),
                ],
                'cancel_url'           => $this->oReceipt->getCancelUrl(),
                'mode'                 => 'subscription'
            ];

            if (!empty($aSubscriptionData)) {
                $aArgs['subscription_data'] = $aSubscriptionData;
            }

            $oSession = \Stripe\Checkout\Session::create($aArgs);
            FileSystem::logSuccess('Stripe: Created Stripe Session. Information: ' . json_encode($oSession));
            $this->token = $oSession->id;
            $this->postID = $postID;

            $oAddPaymentHook = new CreatedPaymentHook(new StripeRecurringCreatedPaymentHook($this));

            $oAddPaymentHook->doSuccess();

            return [
                'status'    => 'success',
                'sessionID' => $oSession->id,
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
