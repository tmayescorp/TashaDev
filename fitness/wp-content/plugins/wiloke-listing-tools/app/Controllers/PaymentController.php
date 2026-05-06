<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\Message;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;

class PaymentController
{
    private $paymentID;

    public function __construct()
    {
        $aBillingTypes = wilokeListingToolsRepository()->get('payment:billingTypes', false);

        /**
         * It's different from payment-gateway-completed and payment-completed.
         * payment-completed means Wiloke Submission Payment completed.
         * payment-gateway-completed means the payment on gateway completed. EG: Stripe, PayPal
         */
        foreach ($aBillingTypes as $billingType) {
            add_action(
                'wilcity/wiloke-listing-tools/'.$billingType.'/payment-gateway-completed',
                [$this, 'updatePaymentCompletedStatus'],
                5
            );

            add_action(
                'wilcity/wiloke-listing-tools/'.$billingType.'/payment-gateway-disputed',
                [$this, 'updatePaymentDisputeStatus'],
                5
            );

            add_action(
                'wilcity/wiloke-listing-tools/'.$billingType.'/payment-gateway-failed',
                [$this, 'updatePaymentFailedStatus'],
                5
            );

            add_action(
                'wilcity/wiloke-listing-tools/'.$billingType.'/payment-gateway-suspended',
                [$this, 'updatePaymentSuspendedStatus'],
                5
            );

            add_action(
                'wilcity/wiloke-listing-tools/'.$billingType.'/payment-gateway-cancelled',
                [$this, 'updatePaymentCancelledStatus'],
                5
            );

            add_action(
                'wilcity/wiloke-listing-tools/'.$billingType.'/payment-gateway-refunded',
                [$this, 'updatePaymentRefundedStatus'],
                5
            );

            add_action(
                'wilcity/wiloke-listing-tools/'.$billingType.'/payment-gateway-reactivate',
                [$this, 'updatePaymentCompletedStatus'],
                5
            );

            //            add_action(
            //                'wilcity/wiloke-listing-tools/'.$billingType.'/paypal/payment-completed',
            //                [$this, 'updatePaymentCompletedStatus'],
            //                5
            //            );
            //
            //            add_action(
            //                'wilcity/wiloke-listing-tools/'.$billingType.'/paypal/payment-failed',
            //                [$this, 'updatePaymentFailedStatus'],
            //                5
            //            );
        }

        add_action('wilcity/wiloke-listing-tools/before/insert-payment', [$this, 'insertNewPayment']);
    }

    /**
     * @param $aData
     *
     * @throws \Exception
     */
    public function insertNewPayment($aData)
    {
        $aRequires = [
            'gateway',
            'billingType',
            'total',
            'currency',
            'userID',
            'packageType',
            'category'
        ];

        foreach ($aRequires as $required) {
            if (!isset($aData[$required]) || $aData[$required] === '') {
                $errMsg = sprintf(
                    esc_html__('%s Warning: The %s is required', 'wiloke-listing-tools'),
                    __METHOD__,
                    $required
                );

                if (wp_doing_ajax()) {
                    wp_send_json_error([
                        'status' => 'error',
                        'msg'    => $errMsg
                    ]);
                } else {
                    throw new \Exception($errMsg);
                }
            }
        }

        if (GetWilokeSubmission::isNonRecurringPayment($aData['billingType']) && $aData['gateway'] == 'stripe') {
            if (!isset($aData['token']) || empty($aData['token'])) {
                wp_send_json_error([
                    'status' => 'error',
                    'msg'    => esc_html__('The token is required', 'wiloke-listing-tools')
                ]);
            }
        }

        $aPaymentData = [
            'userID'      => $aData['userID'],
            'planID'      => $aData['planID'] ?? '',
            'packageType' => $aData['packageType'],
            'gateway'     => $aData['gateway'],
            'status'      => $aData['status'] ?? 'pending',
            'billingType' => $aData['billingType'],
            'planName'    => $aData['planName']
        ];

        $orderID = '';
        if ($aData['gateway'] == 'woocommerce') {
            if (empty($aData['orderID'])) {
                FileSystem::logError('The order id is required', __CLASS__, __METHOD__);

                return false;
            }

            $orderID = absint($aData['orderID']);
        }

        if (!empty($aData['paymentID'])) {
            $this->paymentID = $aData['paymentID'];
        } else {
            $this->paymentID = PaymentModel::insertPaymentHistory($aPaymentData, $orderID);
        }

        if (empty($this->paymentID)) {
            Message::error(esc_html__('Payment: We could not insert payment', 'wiloke-listing-tools'));
        }
        $aData['paymentID'] = $this->paymentID;

        FileSystem::logSuccess('Payment: Inserted Payment. Payment ID:'.$this->paymentID, __CLASS__);

        if (isset($aData['token']) && !empty($aData['token'])) {
            $paymentMetaID = PaymentMetaModel::setPaymentToken($this->paymentID, $aData['token']);

            if ($paymentMetaID) {
                FileSystem::logSuccess('Inserted Payment Token Relationship: '.$paymentMetaID);
            }
        }

        $aPaymentMetaData = $aData;
        unset($aPaymentMetaData['oEvent']);
        PaymentMetaModel::set(
            $this->paymentID,
            wilokeListingToolsRepository()->get('payment:paymentInfo'),
            $aPaymentMetaData
        );

        $aData['paymentID'] = $this->paymentID;

        /**
         * @hooked PromotionController@createPromotion
         */
        do_action('wilcity/wiloke-listing-tools/inserted-payment', $aData);

        /*
         * We will delete all sessions here
         *
         * @hooked SessionController:deletePaymentsSession 100
         */
        if ($aPaymentData['gateway'] !== 'woocommerce') {
            do_action('wilcity/wiloke-listing-tools/payment-succeeded-and-updated-everything');
        }
    }

    /**
     * @param $aInfo
     */
    public function updatePaymentFailedStatus($aInfo)
    {
        $billingType     = PaymentModel::getField('billingType', $aInfo['paymentID']);
        $aInfo['status'] = 'failed';
        PaymentModel::updatePaymentStatus($aInfo['status'], $aInfo['paymentID']);
        FileSystem::logSuccess('AddListing: Update Payment To failed Status because there was a dispute');

        do_action('wilcity/wiloke-listing-tools/'.$billingType.'/updated-payment', $aInfo);

        /*
         * hooked:  WilokeListingTools\Controllers\PostController:updatePostAfterPaymentFailed 10
         * hooked:  WilokeListingTools\Controllers\EmailController:sendFailedPaymentNotificationToAdmin 10
         */
        do_action('wilcity/wiloke-listing-tools/'.$billingType.'/payment-failed', $aInfo);
    }

    public function updatePaymentSuspendedStatus($aInfo)
    {
        $billingType     = PaymentModel::getField('billingType', $aInfo['paymentID']);
        $aInfo['status'] = 'suspended';

        PaymentModel::updatePaymentStatus($aInfo['status'], $aInfo['paymentID']);
        FileSystem::logSuccess('AddListing: Update Payment To Suspended Status');

        do_action('wilcity/wiloke-listing-tools/'.$billingType.'/updated-payment', $aInfo);

        /*
         * hooked:  WilokeListingTools\Controllers\PostController:afterUpdatingNonRecurringPaymentToFailed 10
         * hooked:  WilokeListingTools\Controllers\EmailController:sendSuspendedPaymentNotificationToAdmin 10
         */
        do_action('wilcity/wiloke-listing-tools/'.$billingType.'/payment-suspended', $aInfo);
    }

    /**
     * @param array $aInfo Required argument: paymentID
     */
    public function updatePaymentCancelledStatus($aInfo)
    {
        $billingType           = PaymentModel::getField('billingType', $aInfo['paymentID']);
        $aInfo['status']       = 'cancelled';
        $aInfo['beforeStatus'] = PaymentModel::getField('status', $aInfo['paymentID']);

        PaymentModel::updatePaymentStatus($aInfo['status'], $aInfo['paymentID']);
        FileSystem::logSuccess('AddListing: Update Payment To Cancelled Status');

        do_action('wilcity/wiloke-listing-tools/'.$billingType.'/updated-payment', $aInfo);

        /*
         * hooked:  WilokeListingTools\Controllers\PostController:updatePostAfterPaymentCancelled 10
         * hooked:  WilokeListingTools\Controllers\EmailController:sendCancelledPaymentNotificationToAdmin 10
         * hooked:  WilokeListingTools\Controllers\EmailController:sendCancelledPaymentNotificationToCustomer 10
         */
        do_action('wilcity/wiloke-listing-tools/'.$billingType.'/payment-cancelled', $aInfo);
    }

    /**
     * @param array $aInfo Required argument: paymentID
     */
    public function updatePaymentRefundedStatus(array $aInfo)
    {
        $billingType           = PaymentModel::getField('billingType', $aInfo['paymentID']);
        $aInfo['status']       = 'refunded';
        $aInfo['beforeStatus'] = PaymentModel::getField('status', $aInfo['paymentID']);

        PaymentModel::updatePaymentStatus($aInfo['status'], $aInfo['paymentID']);
        FileSystem::logSuccess('AddListing: Update Payment To Refunded Status');

        do_action('wilcity/wiloke-listing-tools/'.$billingType.'/updated-payment', $aInfo);

        /*
         * hooked:  WilokeListingTools\Controllers\UserPlanController:deleteUserPlan 10
         * hooked:  WilokeListingTools\Controllers\PostController:updatePostAfterPaymentRefunded 10
         * hooked:  WilokeListingTools\Controllers\EmailController:sendRefundedPaymentNotificationToCustomer 10
         */
        do_action('wilcity/wiloke-listing-tools/'.$billingType.'/payment-refunded', $aInfo);
    }

    /**
     * @param $aInfo
     */
    public function updatePaymentDisputeStatus($aInfo)
    {
        $billingType     = PaymentModel::getField('billingType', $aInfo['paymentID']);
        $aInfo['status'] = 'dispute';

        PaymentModel::updatePaymentStatus($aInfo['status'], $aInfo['paymentID']);
        FileSystem::logSuccess('AddListing: Update Payment To dispute Status because there was a dispute', __CLASS__);

        do_action('wilcity/wiloke-listing-tools/'.$billingType.'/updated-payment', $aInfo);

        /**
         * hooked:  WilokeListingTools\Controllers\PostController:afterUpdatingNonRecurringPaymentToDispute 10
         * hooked:  WilokeListingTools\Controllers\UserPlanController:lockAddListing 10
         * hooked:  WilokeListingTools\Controllers\EmailController:sendDisputeWarningToCustomer 10
         * hooked:  WilokeListingTools\Controllers\EmailController:sendDisputeWarningToAdmin 10
         */
        do_action('wilcity/wiloke-listing-tools/'.$billingType.'/payment-dispute', $aInfo);
    }

    protected function updateNextBillingDate($aInfo)
    {
        if (GetWilokeSubmission::isNonRecurringPayment($aInfo['billingType'])) {
            return false;
        }

        if (empty($aInfo['nextBillingDateGMT']) || empty($aInfo['paymentID'])) {
            FileSystem::logError('Missed PaymentID or nextBillingDateGMT. Info: '.json_encode($aInfo));

            return false;
        }

        PaymentMetaModel::setNextBillingDateGMT($aInfo['nextBillingDateGMT'], $aInfo['paymentID']);
        FileSystem::logSuccess('Updated Next Billing Date for:'.$aInfo['paymentID']);
    }

    /**
     * @param $aInfo
     *
     * @return bool
     */
    protected function setPaymentTokenID($aInfo)
    {
        if (!isset($aInfo['paymentID']) || !isset($aInfo['token'])) {
            $msg = 'Maybe Error: We could not set Token and Payment relationship.';
            if (isset($aInfo['token'])) {
                $msg .= ' Token:'.$aInfo['token'];
            }

            if (isset($aInfo['paymentID'])) {
                $msg .= ' paymentID:'.$aInfo['paymentID'];
            }

            FileSystem::logError($msg);

            return false;
        }

        if (PaymentMetaModel::getPaymentIDByToken($aInfo['token'])) {
            return false;
        }

        $relationshipID = PaymentMetaModel::setPaymentToken($aInfo['paymentID'], $aInfo['token']);
        FileSystem::logSuccess('Updated Payment Token relationship. ID: '.$relationshipID.', Payment ID: '.
                               $aInfo['paymentID']);
    }

    /**
     * @param $aInfo
     *
     * @return bool
     */
    protected function setSubscriptionID($aInfo)
    {
        if (!isset($aInfo['paymentID']) || !isset($aInfo['subscriptionID'])) {
            FileSystem::logError(
                'Maybe Error: Missing Payment ID | Subscription ID'
            );

            return false;
        }

        if (PaymentMetaModel::getPaymentIDBySubscriptionID($aInfo['subscriptionID'])) {
            return false;
        }

        $relationshipID = PaymentMetaModel::setPaymentSubscriptionID($aInfo['paymentID'], $aInfo['subscriptionID']);
        FileSystem::logSuccess('Updated Subscription ID: '.$relationshipID.', Payment ID: '.$aInfo['paymentID']);
    }

    /**
     * @param $aInfo
     *
     * @return bool
     */
    protected function setIntentID($aInfo)
    {
        if (!isset($aInfo['paymentID']) || !isset($aInfo['intentID'])) {
            FileSystem::logError(
                'Maybe Error: Missing Payment ID | intentID ID'
            );

            return false;
        }

        $relationshipID = PaymentMetaModel::setPaymentIntentID($aInfo['paymentID'], $aInfo['intentID']);
        FileSystem::logSuccess('Updated Intent ID: '.$relationshipID.', Payment ID: '.$aInfo['paymentID']);
    }

    /**
     * @param $aInfo
     */
    public function updatePaymentCompletedStatus($aInfo)
    {
        if (GetWilokeSubmission::isNonRecurringPayment($aInfo['billingType'])) {
            $aInfo['status'] = 'succeeded';
        } else {
            $aInfo['status'] = 'active';
        }

        PaymentModel::updatePaymentStatus($aInfo['status'], $aInfo['paymentID']);
        $aPaymentMeta = PaymentMetaModel::getPaymentInfo($aInfo['paymentID']);
        if (isset($aInfo['stripeEventID'])) {
            $aPaymentMeta['stripeEventID'] = $aInfo['stripeEventID'];
            PaymentMetaModel::updatePaymentInfo($aInfo['paymentID'], $aPaymentMeta);
        }

        $this->setPaymentTokenID($aInfo);

        if (GetWilokeSubmission::isNonRecurringPayment($aInfo['billingType'])) {
            $this->setIntentID($aInfo);
        } else {
            $this->updateNextBillingDate($aInfo);
            $this->setSubscriptionID($aInfo);
        }

        do_action('wilcity/wiloke-listing-tools/'.$aInfo['billingType'].'/updated-payment', $aInfo);
        /**
         * hooked:  WilokeListingTools\Controllers\UserPlanController:updateUserPlan 5
         * hooked:  WilokeListingTools\Controllers\PlanRelationshipController:updatePostPaymentRelationship 10
         * hooked:  WilokeListingTools\Controllers\PostController:updatePostAfterPaymentCompleted 15
         * hooked:  WilokeListingTools\Controllers\ClaimListingsController:paidClaimSuccessfully 12
         * hooked:  WilokeListingTools\Controllers\InvoiceController:prepareInsertInvoice 15
         * hooked:  WilokeListingTools\Controllers\PromotionController:updatePromotionAfterPaymentCompleted 15
         */
        do_action('wilcity/wiloke-listing-tools/'.$aInfo['billingType'].'/payment-completed', $aInfo);
    }
}
