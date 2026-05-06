<?php

namespace WilokeListingTools\Framework\Payment\PaymentHook;

use WilokeListingTools\Framework\Payment\PaymentMethodInterface;
use WilokeListingTools\Framework\Store\Session;

/**
 * Created Payment does not mean We created a WILOKE SUBMISSION PAYMENT ID, which means We created a payment ID on
 * the PAYMENT GATEWAY. EG: We created a Payment ID on Stripe / PayPal
 *
 * @package WilokeListingTools\Framework\Payment\PaymentHook
 */
abstract class NonRecurringCreatedPaymentHookAbstract
{
    /**
     * @var PaymentMethodInterface
     */
    protected $oPaymentInterface;

    /**
     * RecurringPaymentHookAbstract constructor.
     *
     * @param PaymentMethodInterface $oPaymentInterface
     */
    public function __construct(PaymentMethodInterface $oPaymentInterface)
    {
        $this->oPaymentInterface = $oPaymentInterface;
    }

    public function setupSuccessArgs()
    {
        return $this->oPaymentInterface->oReceipt->getPaymentData
        ([
                'ID'                 => isset($this->oPaymentInterface->ID) ? $this->oPaymentInterface->ID : $this->oPaymentInterface->paymentID,
                'gateway'            => $this->oPaymentInterface->gateway,
                'planRelationshipID' => Session::getSession(wilokeListingToolsRepository()->get('payment:sessionRelationshipStore'),
                    false),
                'claimID'            => Session::getSession(wilokeListingToolsRepository()->get('claim:sessionClaimID'),
                    false),
                'billingType'        => $this->oPaymentInterface->getBillingType(),
                'packageType'        => $this->oPaymentInterface->oReceipt->getPackageType(),
                'currency'           => $this->oPaymentInterface->oReceipt->getCurrency(),
                'userID'             => $this->oPaymentInterface->oReceipt->getUserID(),
                'category'           => Session::getSession(wilokeListingToolsRepository()->get('payment:category'),
                    false)
            ]
        );
    }

    public function setupErrorArgs()
    {

    }
}
