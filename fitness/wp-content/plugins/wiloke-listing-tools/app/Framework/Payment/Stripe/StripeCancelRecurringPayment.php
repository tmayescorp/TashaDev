<?php

namespace WilokeListingTools\Framework\Payment\Stripe;

use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Controllers\RetrieveController;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\Message;
use WilokeListingTools\Framework\Payment\CancelSubscriptionInterface;
use WilokeListingTools\Framework\Payment\StripePayment;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;

final class StripeCancelRecurringPayment extends StripePayment implements CancelSubscriptionInterface
{
    private $subscriptionID;
    public $errMsg;
    
    public function getSubscriptionIDByPaymentID($paymentID)
    {
        $this->subscriptionID = PaymentMetaModel::getSubscriptionID($paymentID);
        
        if (empty($this->subscriptionID)) {
            return false;
        }
        
        return true;
    }
    
    public function execute($paymentID)
    {
        $status    = $this->getSubscriptionIDByPaymentID($paymentID);
        $oRetrieve = new RetrieveController(new NormalRetrieve());
        
        if (!$status) {
            FileSystem::logError('We could not find subscription id of '.$paymentID);
            
            return $oRetrieve->error([
                'msg' => esc_html__('Invalid payment id',
                    'wiloke-listing-tools')
            ]);
        }
        
        $this->setApiContext();
        
        try {
            $oSubscription = \Stripe\Subscription::retrieve($this->subscriptionID);
            $oSubscription->cancel();
            
            /**
             * PaymentController:updatePaymentCancelledStatus 5
             */
            do_action(
                'wilcity/wiloke-listing-tools/'.
                wilokeListingToolsRepository()->get('payment:billingTypes', true)->sub('recurring').
                '/payment-gateway-cancelled',
                [
                    'paymentID' => $paymentID
                ]
            );
            
            return $oRetrieve->success([
                'oSubscription' => $oSubscription,
                'msg'           => sprintf(esc_html__('The payment has been cancelled. Gateway: %s, Payment ID: %d',
                    'wiloke-listing-tools'), 'Stripe', $paymentID)
            ]);
        } catch (\Exception $oE) {
            FileSystem::logError('We could not cancel '.$paymentID.' plan. Stripe Msg:'.$oE->getMessage());
            
            return $oRetrieve->error([
                'msg' => esc_html__('Unfortunately, We could not cancel this subscription. Possible reason: Invalid subscription ID',
                    'wiloke-listing-tools')
            ]);
        }
    }
}
