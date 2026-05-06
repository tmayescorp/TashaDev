<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Controllers\Retrieve\RetrieveFactory;
use WilokeListingTools\Framework\Helpers\DebugStatus;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Submission;
use WilokeListingTools\Framework\Helpers\WPML;
use WilokeListingTools\Framework\Payment\FreePlan\FreePlan;
use WilokeListingTools\Framework\Payment\Receipt\ReceiptStaticFactory;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\RemainingItems;
use WilokeListingTools\Models\UserModel;
use WilokeListingTools\Register\WilokeSubmission;

trait HandleSubmit
{
    private $oReceipt;
    private $isChangedPlan;
    private $aMaybeUserPlan = [];

    private function isSentEmailNotificationAboutSubmission($listingID)
    {
        return get_transient('wilcity_is_sent_email_nas_' . $listingID);
    }

    private function setSentEmailNotificationAboutSubmission($listingID)
    {
        set_transient('wilcity_is_sent_email_nas_' . $listingID, 'yes',
            apply_filters('wilcity/stop-sending-email-within', 60 * 24));
    }

    private function hookAfterListingSubmitted($isSubmit = false)
    {
        /**
         * @hooked WilokeListingTools\Controllers\EmailController::submittedListing 10
         * @hooked WilokeListingTools\Controllers\PlanRelationshipController::addPlanRelationshipUserPurchasedPlan 10
         * @hooked WilokeListingTools\Controllers\PostController::handleListingPlanAfterSubmitting 15
         * @hooked WilokeListingTools\Controllers\SessionController::maybeDeletePaymentSessions 15
         */
        do_action(
            'wiloke/submitted-listing',
            [
                'planID'         => $this->planID,
                'postID'         => $this->listingID,
                'postAuthor'     => get_post_field('post_author', $this->listingID),
                'isAutoApproved' => false,
                'isSubmit'       => $isSubmit,
                'isChangedPlan'  => $this->isChangedPlan,
                'aUserPlan'      => $this->aMaybeUserPlan
            ]
        );
    }

    private function hookBeforeSubmitListing()
    {
        /**
         * @hooked WilokeListingTools\Controllers\EmailController::submittedListing 10
         * @hooked WilokeListingTools\Controllers\PlanRelationshipController::addPlanRelationshipUserPurchasedPlan 10
         * @hooked WilokeListingTools\Controllers\PostController::handleListingPlanAfterSubmitting 15
         * @hooked WilokeListingTools\Controllers\SessionController::maybeDeletePaymentSessions 15
         */
        do_action(
            'wilcity/wiloke-listing-tools/before-submit-listing',
            [
                'planID'     => $this->planID,
                'postID'     => $this->listingID,
                'postAuthor' => get_post_field('post_author', $this->listingID),
            ]
        );
    }

    private function goToThankyouPage()
    {
        return RetrieveFactory::retrieve()->success([
            'redirectTo' => GetWilokeSubmission::getThankyouPageURL(
                [
                    'postID'   => $this->listingID,
                    'category' => 'addlisting'
                ],
                true
            )
        ]);
    }

    public function handleSubmit()
    {
        return $this->_handleSubmit();
    }

    private function _handleSubmit()
    {
        WPML::cookieCurrentLanguage();
        $aResponse = $this->middleware(
            [
                'isLockedAddListing',
                'sessionPathWriteable'
            ],
            [

            ],
            'normal'
        );

        if ($aResponse['status'] == 'error') {
            return (RetrieveFactory::retrieve()->error(
                [
                    'msg' => $aResponse['msg']
                ]
            ));
        }

        Session::setPaymentCategory('addlisting');

        $this->listingID = Session::getPaymentObjectID(false);
        Session::setSession(wilokeListingToolsRepository()->get('payment:listingType'),
            get_post_type($this->listingID));

        $this->postStatus = get_post_status($this->listingID);
        $this->planID = Session::getPaymentPlanID();

        $this->aPlanSettings = GetSettings::getPlanSettings($this->planID);

        $oldPlanID = GetSettings::getPostMeta($this->listingID, 'oldPlanID');
        $this->isChangedPlan = !empty($oldPlanID) && $oldPlanID != $this->planID;

        $aResponse = $this->middleware(
            [
                'isUserLoggedIn',
                'canSubmissionListing',
                'isPassedPostAuthor',
                'isPlanExists',
                'isExceededFreePlan'
            ],
            [
                'postID'      => $this->listingID,
                'planID'      => $this->planID,
                'listingID'   => $this->listingID,
                'userID'      => get_current_user_id(),
                'listingType' => get_post_type($this->listingID),
                'postType'    => get_post_type($this->listingID)
            ],
            'normal'
        );

        if ($aResponse['status'] == 'error') {
            return (RetrieveFactory::retrieve()->error(
                [
                    'msg' => $aResponse['msg']
                ]
            ));
        }

        $aUpdatePost = [
            'ID' => $this->listingID
        ];

        $this->hookBeforeSubmitListing();
        $this->postStatus = get_post_status($this->listingID);

        if (!defined('WILOKE_LISTING_TOOLS_CHECK_EVEN_ADMIN') && current_user_can('administrator')) {
            $aUpdatePost['post_status'] = 'publish';
            $this->setDuration(GetWilokeSubmission::getBillingType(), $this->listingID, $this->planID);
            wp_update_post($aUpdatePost);
            do_action('wilcity/wiloke-listing-tools/app/Controllers/HandleSubmit/admin/submitted',
                ['postID' => $this->listingID, 'planID' => $this->planID]
            );

            return $this->goToThankyouPage();
        }

        if ($this->postStatus == 'editing') {
            if (GetWilokeSubmission::getField('published_listing_editable') == 'allow_trust_approved') {
                $aUpdatePost['post_status'] = 'publish';
            } else {
                $oldPostStatus = GetSettings::getPostMeta($this->listingID, 'oldPostStatus');
                if (Submission::listingStatusWillPublishImmediately($oldPostStatus)) {
                    $aUpdatePost['post_status'] = 'publish';
                } else {
                    $aUpdatePost['post_status'] = 'pending';
                }
                SetSettings::deletePostMeta($oldPostStatus, 'oldPostStatus');
            }

            $aUpdatePost = apply_filters('wilcity/wiloke-listing-tools/src/Controllers/HandleSubmit/editing',
                $aUpdatePost);

            if ($aUpdatePost['post_status'] != 'publish' &&
                !$this->isSentEmailNotificationAboutSubmission($this->listingID)
            ) {
                $this->hookAfterListingSubmitted();
                $this->setSentEmailNotificationAboutSubmission($this->listingID);
            }

            wp_update_post($aUpdatePost);

            return $this->goToThankyouPage();
        }

        if (in_array($this->postStatus, ['unpaid', 'expired'])) {
            if ((UserModel::getRemainingItemsOfPlans($this->planID) > 0) &&
                (!defined('WILOKE_ALWAYS_PAY') || !WILOKE_ALWAYS_PAY)
            ) {
                $this->aMaybeUserPlan = UserModel::getSpecifyUserPlanID($this->planID, User::getCurrentUserID(), true);
                $isTrial = isset($this->aMaybeUserPlan['isTrial']) && $this->aMaybeUserPlan['isTrial'];
                $this->setDuration(GetWilokeSubmission::getBillingType(), $this->listingID, $this->planID,
                    $isTrial);
                $oldPostStatus = GetSettings::getPostMeta($this->listingID, 'oldPostStatus');

                if (
                    GetWilokeSubmission::getField('approved_method') == 'auto_approved_after_payment'
                    || Submission::listingStatusWillPublishImmediately($oldPostStatus)
                ) {
                    $aUpdatePost['post_status'] = 'publish';
                } else {
                    $aUpdatePost['post_status'] = 'pending';
                }

                wp_update_post($aUpdatePost);
                SetSettings::deletePostMeta($oldPostStatus, 'oldPostStatus');
                $this->hookAfterListingSubmitted();
                return $this->goToThankyouPage();
            }
        }
        // Free Add Listing
        if (empty($this->aPlanSettings['regular_price'])) {
            $this->oReceipt = ReceiptStaticFactory::get('addlisting', [
                'planID'     => $this->planID,
                'userID'     => User::getCurrentUserID(),
                'couponCode' => '',
                'aRequested' => $_REQUEST
            ]);
            $this->oReceipt->setupPlan();

            $oFreePlan = new FreePlan();
            $aStatus = $oFreePlan->proceedPayment($this->oReceipt);

            if ($aStatus['status'] == 'success') {
                if (empty($this->aMaybeUserPlan)) {
                    $this->aMaybeUserPlan = UserModel::getSpecifyUserPlanID(
                        $this->planID,
                        User::getCurrentUserID(),
                        true
                    );
                }

                $isTrial = isset($this->aMaybeUserPlan['isTrial']) && $this->aMaybeUserPlan['isTrial'];
                $this->setDuration(
                    wilokeListingToolsRepository()->get('payment:billingTypes', true)->sub('nonrecurring'),
                    $this->listingID, $this->planID, $isTrial
                );

                wp_update_post([
                    'ID'          => $this->listingID,
                    'post_status' => GetWilokeSubmission::getField('approved_method') != 'manual_review' ? 'publish' :
                        'pending'
                ]);
                do_action('wilcity/wiloke-listing-tools/NonRecurringPayment/payment-gateway-completed',
                    ['paymentID'   => $oFreePlan->paymentID,
                     'billingType' => wilokeListingToolsRepository()
                         ->get('payment:billingTypes', true)
                         ->sub('nonrecurring')
                    ]);
                $this->hookAfterListingSubmitted();
                return $this->goToThankyouPage();
            } else {
                return (RetrieveFactory::retrieve()->error(
                    [
                        'msg' => esc_html__('ERROR: We could not create Free Plan', 'wiloke-listing-tools')
                    ]
                ));
            }
        }

        $redirectTo = GetWilokeSubmission::getField('checkout', true);

        // If paying via WooCoomerce, we need to get rid of this product id from the cart
        if (GetWilokeSubmission::isGatewaySupported('woocommerce')) {
            $productID = GetSettings::getPostMeta($this->planID, 'woocommerce_association');
        }

        if (!empty($productID)) {
            $redirectTo = GetSettings::getCartUrl($this->planID);
            /*
            * @hooked WooCommerceController:removeProductFromCart
            */
            do_action('wiloke-listing-tools/before-redirecting-to-cart', $productID);
            //            Session::setSession(wilokeListingToolsRepository()->get('payment:associateProductID'), $productID);
            Session::setProductID($productID);
        }

        $this->hookAfterListingSubmitted(true);

        return (RetrieveFactory::retrieve()->success(
            [
                'redirectTo' => $redirectTo
            ]
        ));
    }
}
