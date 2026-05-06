<?php

namespace WilokeListingTools\Framework\Payment\Stripe;

use Stripe\Coupon;
use Stripe\Stripe;
use WilokeListingTools\Framework\Payment\StripePayment;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;

class StripeSuspendPlan extends StripePayment
{
    protected $paymentID;
    protected $subscriptionID;

    public function __construct($paymentID)
    {
        $this->paymentID = $paymentID;
    }

    public function suspend()
    {
        $status = PaymentModel::getField('status', $this->paymentID);
        if ($status !== 'active') {
            return true;
        }
        $this->setApiContext();
        $this->subscriptionID = PaymentMetaModel::getSubscriptionID($this->paymentID);

        if (empty($this->subscriptionID)) {
            return true;
        }

        if ($this->createFreeForeverCoupon()) {
            try {
                $oSubscription         = \Stripe\Subscription::retrieve($this->subscriptionID);
                $oSubscription->coupon = wilokeListingToolsRepository()->get('payment:stripeForeverCoupon');
                $oSubscription->save();

                PaymentModel::updatePaymentStatus('suspended', $this->paymentID);

                return [
                    'status' => 'success'
                ];
            } catch (\Exception $e) {
                return [
                    'status' => 'error',
                    'msg'    => $e->getMessage()
                ];
            }
        }

        return [
            'status' => 'error',
            'msg'    => esc_html__('Could not create free forever coupon', 'wiloke-listing-tools')
        ];
    }

    protected function createFreeForeverCoupon(): bool
    {
        Stripe::setApiKey($this->oApiContext->secretKey);

        try {
            Coupon::retrieve(wilokeListingToolsRepository()->get('payment:stripeForeverCoupon'));
        } catch (\Exception $oE) {
            try {
                Coupon::create([
                    'id'          => wilokeListingToolsRepository()->get('payment:stripeForeverCoupon'),
                    'duration'    => 'forever',
                    'percent_off' => 100,
                ]);

                return true;

            } catch (\Exception $oE) {
                return false;
            }
        }

        return true;
    }
}
