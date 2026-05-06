<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;
use WilokeListingTools\Models\UserModel;

class UserPlanController extends Controller
{
    public function __construct()
    {
        add_filter('get_avatar', [$this, 'wilcityAvatar'], 1, 5);
        $aBillingTypes = wilokeListingToolsRepository()->get('payment:billingTypes', false);
        foreach ($aBillingTypes as $billingType) {
            add_action(
                'wilcity/wiloke-listing-tools/' . $billingType . '/payment-completed', [
                $this, 'updateUserPlan'],
                5
            );

            add_action(
                'wilcity/wiloke-listing-tools/' . $billingType . '/payment-refunded',
                [$this, 'deleteUserPlan'],
                5
            );
        }

        add_action('wiloke-listing-tools/app/Controllers/after/ChangePlanStatusController',
            [$this, 'updateUserPlanAfterPlanChanged']);

        add_action('wilcity/wiloke-listing-tools/RecurringPayment/stripe/payment-completed',
            [$this, 'setStripeCustomerID']);
        add_action(
            'wilcity/wiloke-listing-tools/NonRecurringPayment/payment-dispute',
            [$this, 'lockAddListingBecausePaymentDispute'],
            10
        );

        add_action('wilcity/wiloke-listing-tools/updated-plan-relationship', [$this, 'updateRemainingItems'], 10, 2);
        add_action('wilcity/wiloke-listing-tools/added-plan-relationship', [$this, 'updateRemainingItems'], 10, 2);
        add_action('wilcity/wiloke-listing-tools/deleted-plan-relationship', [$this, 'updateRemainingItems'], 10, 2);
    }

    public function setStripeCustomerID($aInfo)
    {
        if (!isset($aInfo['stripeCustomerID']) || empty($aInfo['stripeCustomerID'])) {
            FileSystem::logError('We could not set stripe customer id');
        }

        UserModel::setStripeID($aInfo['stripeCustomerID'], $aInfo['userID']);
        FileSystem::logSuccess('Inserted Stripe Customer ID. Stripe Customer:' . $aInfo['stripeCustomerID'] .
            ' UserID' .
            $aInfo['userID']);
    }

    private function lockedAddListing($userID, $aDetails)
    {
        SetSettings::setUserMeta($userID, 'locked_addlisting', $aDetails['reason']);
        unset($aDetails['reason']);
        SetSettings::setUserMeta($userID, 'locked_addlisting_reason', $aDetails);

        FileSystem::logSuccess('Payment: Locked Add Listing. Details: ' . json_encode($aDetails));
    }

    public function lockAddListingBecausePaymentDispute($aInfo)
    {
        $userID = PaymentModel::getField('userID', $aInfo['paymentID']);

        $aData['reason'] = 'payment_dispute';
        $aData['paymentID'] = $aInfo['paymentID'];

        $this->lockedAddListing($userID, $aData);
    }

    /**
     * @param      $aInfo
     * @param bool $mustUpdate
     *
     * @return bool
     */
    public function updateRemainingItems($aInfo, $logIfError = false): bool
    {
        if (empty($aInfo['userID'])) {
            if ($logIfError) {
                FileSystem::logError('We could not update remaining items because the user id was emptied',
                    __CLASS__, __METHOD__);
            }

            return false;
        }

        if (empty($aInfo['planID'])) {
            if ($logIfError) {
                FileSystem::logError('We could not update remaining items because the user plan was emptied',
                    __CLASS__, __METHOD__);
            }

            return false;
        }

        $instUserModel = new UserModel();
        $instUserModel->updateRemainingItemsUserPlan($aInfo['planID'], $aInfo['userID'], $logIfError);

        return true;
    }

    public function deleteUserPlan($aInfo)
    {
        if (empty($aInfo['paymentID'])) {
            FileSystem::logError('The payment id is required', __CLASS__, __METHOD__);


            return false;
        }

        $aPaymentMetaInfo = PaymentMetaModel::getPaymentInfo($aInfo['paymentID']);
        if (!isset($aPaymentMetaInfo['category']) || !in_array($aPaymentMetaInfo['category'], [
                'addlisting',
                'paidClaim'
            ])
        ) {
            return false;
        }


        $instUserModel = new UserModel();
        $userID = PaymentModel::getField('userID', $aInfo['paymentID']);

        $instUserModel->setUserID($userID)
            ->setPaymentID($aInfo['paymentID'])
            ->setPlanID(PaymentModel::getField('planID', $aInfo['paymentID']));

        $status = $instUserModel->removeUserPlan();
    }

    public function updateUserPlanAfterPlanChanged($aInfo)
    {
        $this->updateUserPlan($aInfo);
    }

    public function updateUserPlan($aInfo)
    {
        if ($aInfo['status'] !== 'succeeded' && $aInfo['status'] !== 'active') {
            return false;
        }

        if (empty($aInfo['paymentID'])) {
            FileSystem::logError('The payment id is required', __CLASS__, __METHOD__);

            return false;
        }

        $aPaymentMetaInfo = PaymentMetaModel::getPaymentInfo($aInfo['paymentID']);
        $aAllowedPaymentCategories = apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Controllers/UserPlanController/updateUserPlan/allowedCategory',
            [
                'addlisting',
                'paidClaim'
            ]
        );

        if (!isset($aPaymentMetaInfo['category']) ||
            !in_array($aPaymentMetaInfo['category'], $aAllowedPaymentCategories)
        ) {
            return false;
        }

        $postType  = null;
        if (empty($aInfo['postType'])) {
            if (!empty($aPaymentMetaInfo['postID'])) {
                $aPostIDs = explode(',', $aPaymentMetaInfo['postID']);

                $postType = get_post_type($aPostIDs[0]);
            } else {
                if (!empty($aPaymentMetaInfo['postType'])) {
                    $postType = $aPaymentMetaInfo['postType'];
                }
            }
        } else {
            $postType = $aInfo['postType'];
        }

        if (empty($postType)) {
            FileSystem::logError(sprintf(
                'We could not detect post type of Payment Id', __CLASS__, __METHOD__,
                $aInfo['paymentID']
            ));
        }

        $userID = PaymentModel::getField('userID', $aInfo['paymentID']);
        $aPaymentInfo = PaymentModel::getPaymentInfo($aInfo['paymentID']);

        if (empty($aPaymentInfo)) {
            FileSystem::logError(sprintf(
                'The payment %s does not exist', __CLASS__, __METHOD__,
                $aInfo['paymentID']
            ));

            return false;
        }

        $instUserModel = new UserModel();
        $instUserModel->setUserID($userID)
            ->setBillingType($aPaymentInfo['billingType'])
            ->setGateway($aPaymentInfo['gateway'])
            ->setPaymentID($aPaymentInfo['ID'])
            ->setPlanID($aPaymentInfo['planID'])
            ->setPostType($postType);

        if (!GetWilokeSubmission::isNonRecurringPayment($aPaymentInfo['billingType'])) {
            $instUserModel->setNextBillingDateGMT($aInfo['nextBillingDateGMT']);
        }

        $isPassed = $this->middleware(['validateBeforeSetUserPlan'], [
            'instUserModel' => $instUserModel,
            'billingType'   => $aInfo['billingType'],
            'isBoolean'     => true
        ]);

        if (!$isPassed) {
            FileSystem::logError(
                'We could not update user plan because the info wont passed validation. ' . json_encode($aInfo),
                __CLASS__,
                __METHOD__
            );

            return false;
        }

        $status = $instUserModel->setUserPlan();
        if (!$status) {
            FileSystem::logError('We could not set User Plan', __CLASS__, __METHOD__);

            return false;
        }
        FileSystem::logSuccess('Update User Plan - UserID: ' . $userID . ' Payment Info: ' .
            json_encode($aPaymentInfo));
    }

    public function fixUserPlanIssue()
    {
        if (!is_user_logged_in()) {
            return false;
        }

        $userID = get_current_user_id();
        $fixKey = 'fixed_user_plan_v2_' . $userID;

        if (GetSettings::getOptions($fixKey)) {
            return false;
        }

        $aAllPlans = UserModel::getAllPlans($userID);
        if (empty($aAllPlans)) {
            SetSettings::setOptions($fixKey, true);

            return false;
        }

        SetSettings::setUserMeta($userID, 'backup_userplan', $aAllPlans);

        $aRebuildPlans = [];

        foreach ($aAllPlans as $planType => $aPlans) {
            foreach ($aPlans as $planID => $aPlanInfo) {
                if (!isset($aPlanInfo['postType']) || empty($aPlanInfo['postType'])) {
                    continue;
                }
                $aRebuildPlans[get_post_type($planID)][$planID] = $aPlanInfo;
                //                $aRebuildPlans[$aPlanInfo['postType'].'_plan'][$planID] = $aPlanInfo;
            }
        }

        if (!empty($aRebuildPlans)) {
            SetSettings::setUserMeta($userID, wilokeListingToolsRepository()->get('user:userPlans'), $aRebuildPlans);
        }

        SetSettings::setOptions($fixKey, true);
    }

    public function wilcityAvatar($avatar, $id_or_email, $size, $default, $alt)
    {
        if (is_object($id_or_email)) {
            if (!empty($id_or_email->user_id)) {
                $id = (int)$id_or_email->user_id;
            }
        } else if (!is_numeric($id_or_email)) {
            $user = get_user_by('email', $id_or_email);
            if (!empty($user)) {
                $id = $user->user_id;
            }
        } else {
            $id = $id_or_email;
        }

        if (isset($id)) {
            $url = GetSettings::getUserMeta($id, 'avatar');

            if (!empty($url)) {
                $avatar
                    = "<img alt='{$alt}' src='{$url}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
            }
        }

        return $avatar;
    }
}
