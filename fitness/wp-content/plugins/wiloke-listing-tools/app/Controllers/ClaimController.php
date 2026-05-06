<?php

namespace WilokeListingTools\Controllers;

use Stripe\Util\Set;
use WilcityPaidClaim\Register\RegisterClaimSubMenu;
use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Framework\Helpers\DebugStatus;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Payment\FreePlan\FreePlan;
use WilokeListingTools\Framework\Payment\Receipt\ReceiptStaticFactory;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PlanRelationshipModel;
use WilokeListingTools\Models\UserModel;
use WilokeListingTools\Framework\Helpers\Validation as ValidationHelper;

class ClaimController extends Controller
{
    use SetPlanRelationship;

    protected      $planID;
    protected      $listingID;
    protected      $claimerID;
    protected      $claimID;
    protected      $aClaimerInfo;
    protected      $oReceipt;
    private static $isPaidClaim = false;
    private        $logFile     = 'claim.log';

    public function __construct()
    {
        add_action('wp_ajax_wilcity_claim_request', [$this, 'handleClaimRequest']);
        add_action('wp_ajax_nopriv_wilcity_claim_request', [$this, 'handleClaimRequest']);
        add_action('wp_ajax_nopriv_wil_fetch_claim_fields', [$this, 'handleFetchClaimFields']);
        add_action('wp_ajax_wil_fetch_claim_fields', [$this, 'handleFetchClaimFields']);
        add_action('post_updated', [$this, 'parseClaimStatusAfterPostSaved'], 10, 3);

        $aBillingTypes = wilokeListingToolsRepository()->get('payment:billingTypes', false);
        foreach ($aBillingTypes as $billingType) {
            add_action(
                'wilcity/wiloke-listing-tools/' . $billingType . '/payment-completed',
                [$this, 'updateClaimIDToPaymentMeta'],
                12
            );
        }

        $aPostTypes = General::getPostTypeKeys(false);
        foreach ($aPostTypes as $postType) {
            add_filter(
                "bulk_actions-edit-" . $postType,
                [$this, "addBulkActionFilter"]
            );
        }
    }

    public function addBulkActionFilter($aActions) {
        $aActions["claimed"] = esc_html__("Claimed Listing", "wiloke-listing-tools");
        return $aActions;
    }

    public function updateClaimIDToPaymentMeta($aInfo)
    {
        if (
            !in_array($aInfo['status'], ['succeeded', 'active'])
            || empty($aInfo['postID'])
            || empty($aInfo['claimID'])
            || $aInfo['category'] != 'paidClaim'
        ) {
            return false;
        }

        SetSettings::setPostMeta($aInfo['claimID'], 'claim_status', 'approved');
        PaymentMetaModel::set($aInfo['paymentID'], 'claimID', $aInfo['claimID']);
    }

    /**
     * If a claim has been approved and then admin wants to reject it, this function will help to reset Listing
     * Author, Listing Claim status to the default status
     *
     * @param $metaID
     * @param $objectID
     * @param $metaKey
     * @param $metaValue
     *
     * @return bool
     */
    public function revertToDefaultStatus($metaID, $objectID, $metaKey, $metaValue)
    {
        _deprecated_function('revertToDefaultStatus', '1.2.5', 'parseClaimStatusAfterPostSaved');

        if (get_post_type($objectID) !== 'claim_listing') {
            return false;
        }

        if ($metaKey !== 'wilcity_claim_status' || $metaValue == 'approved') {
            return false;
        }

        if (!General::isAdmin() || !check_admin_referer('wilcity_admin_security', 'wilcity_admin_nonce_field')) {
            return false;
        }

        if (!isset($_POST['attribute_post_author']) || empty($_POST['attribute_post_author'])) {
            wp_die('"Attribute this listing to"" setting is required');
        }

        $author = absint($_POST['attribute_post_author']);
        $listingID = GetSettings::getPostMeta($objectID, 'claimed_listing_id');

        if (empty($listingID) || empty($author)) {
            return false;
        }

        SetSettings::setPostMeta($listingID, 'claim_status', 'not_claim');

        global $wpdb;

        $wpdb->update(
            $wpdb->posts,
            [
                'post_author' => $author
            ],
            [
                'ID' => $listingID
            ],
            [
                '%d'
            ],
            [
                '%d'
            ]
        );

        $claimerID = GetSettings::getPostMeta($objectID, 'claimer_id');

        do_action('wiloke/claim/' . $metaValue, $claimerID, $listingID);
    }

    /*
     * When a request has been approved, we will cancelled all other request and switch the post author of this listing
     */
    public function approvedClaimListing($metaID, $objectID, $metaKey, $metaValue)
    {
        _deprecated_function('approvedClaimListing', '1.2.5', 'parseClaimStatusAfterPostSaved');
        if ($metaKey !== 'wilcity_claim_status' || $metaValue != 'approved') {
            return false;
        }

        $this->listingID = GetSettings::getPostMeta($objectID, 'claimed_listing_id');
        $claimerID = GetSettings::getPostMeta($objectID, 'claimer_id');

        $formerlyAuthorID = get_post_field('post_author', $this->listingID);
        SetSettings::setPostMeta($objectID, 'formerly_post_author', $formerlyAuthorID);

        do_action('wiloke/claim/approved', $claimerID, $this->listingID, $objectID);
    }

    public function parseClaimStatusAfterPostSaved($claimID, $oPostAfter, $oPostBefore)
    {
        if ($oPostAfter->post_type !== 'claim_listing') {
            return false;
        }

        if ($oPostBefore->post_status == 'trash') {
            SetSettings::setPostMeta($claimID, 'claim_status', 'cancelled');

            return false;
        }

        $planID = GetSettings::getPostMeta($claimID, 'claim_plan_id');
        $listingID = GetSettings::getPostMeta($claimID, 'claimed_listing_id');
        $postAuthor = GetSettings::getPostMeta($claimID, 'claimer_id');
        //        var_export($planID);die;
        //        var_export(UserModel::getSpecifyUserPlanID($planID, $postAuthor));die;
        $aInfo = [
            'claimID'    => $claimID,
            'postID'     => $listingID,
            'planID'     => $planID,
            'claimerID'  => $postAuthor,
            'userID'     => $postAuthor,
            'freePlanID' => GetWilokeSubmission::getFreeClaimPlanID($listingID),
            'aUserPlan'  => UserModel::getSpecifyUserPlanID($planID)
        ];

        if ($oPostAfter->post_status == 'publish') {
            if (isset($_POST['wilcity_claim_status']) && !empty($_POST['wilcity_claim_status'])) {
                $claimStatus = sanitize_text_field($_POST['wilcity_claim_status']);
            } else {
                $claimStatus = GetSettings::getPostMeta($claimID, 'claim_status');
            }
            if ($claimStatus === 'approved') {
                /**
                 * hooked WilokeListingTools\Controllers\PostController:updatePostAfterClaimApproved
                 * hooked WilokeListingTools\Controllers\EmailController:claimApproved
                 * hooked WilokeListingTools\Controllers\NotificationsController:addClaimApproved
                 * hooked WilokeListingTools\Controllers\RegisterLoginController:addClaimerToWilokeSubmissionGroup
                 * hooked WilokeListingTools\Controllers\RegisterLoginController:autoSwitchConfirmationToApproved
                 */
                do_action('wilcity/wiloke-listing-tools/claim-approved', $aInfo);
            } else {
                if (
                    !General::isAdmin()
                    || !check_admin_referer('wilcity_admin_security', 'wilcity_admin_nonce_field')
                ) {
                    return false;
                }

                if ($claimStatus == 'pending') {
                    return false;
                }

                if (empty($listingID)) {
                    return false;
                }

                if (!isset($_POST['attribute_post_author']) || empty($_POST['attribute_post_author'])) {
                    if (get_post_status($listingID) !== 'publish') {
                        return false;
                    }
                    wp_die('"Attribute this listing to"" setting is required');
                }

                $aInfo['postAuthor'] = $_POST['attribute_post_author'];

                /**
                 * If a claim has been approved and then admin wants to reject it, this function will help to reset Listing
                 * Author, Listing Claim status to the default status
                 *
                 * @$metaValue: cancelled, pending
                 *
                 * @hooked: WilokeListingTools\Controllers\NotificationsController:addClaimCancelled
                 * @hooked: WilokeListingTools\Controllers\PostController:updatePostAfterClaimCancelled
                 */
                do_action('wilcity/wiloke-listing-tools/claim-' . $claimStatus, $aInfo);
            }
        } else {
            $aOriginalListingInfo = GetSettings::getPostMeta($claimID, 'listing_original_info');
            if (isset($aOriginalListingInfo['postAuthor'])) {
                $aInfo['postAuthor'] = $aOriginalListingInfo['postAuthor'];
            } else {
                $aInfo['postAuthor'] = User::getFirstSuperAdmin();
            }

            $aInfo['postStatusBefore'] = $oPostBefore->post_status;

            /**
             *
             * @hooked: WilokeListingTools\Controllers\NotificationsController:addClaimCancelled
             * @hooked: WilokeListingTools\Controllers\PostController:updatePostAfterClaimCancelled
             */
            do_action('wilcity/wiloke-listing-tools/claim-cancelled', $aInfo);
        }
    }

    private function isClaimerExisting($listingID, $claimerID)
    {
        global $wpdb;
        $tbl = $wpdb->postmeta;

        $aAllClaimedPostsByAuthor = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_id FROM $tbl WHERE meta_key=%s AND meta_value=%d",
                'wilcity_claimer_id', $claimerID
            ),
            ARRAY_A
        );

        if (empty($aAllClaimedPostsByAuthor)) {
            return false;
        }

        foreach ($aAllClaimedPostsByAuthor as $aData) {
            $claimedID = GetSettings::getPostMeta($aData['post_id'], 'claimed_listing_id');
            if ($claimedID == $listingID) {
                return $aData['post_id'];
            }
        }
    }

    private function storeOriginalListingInfo()
    {
        $aInfo['planID'] = GetSettings::getListingBelongsToPlan($this->listingID);
        $aInfo['postAuthor'] = get_post_field('post_author', $this->listingID);
        if (!empty($aInfo['planID'])) {
            $aInfo['planRelationship'] = PlanRelationshipModel::getIDByObjectID($this->listingID);
            $aInfo['listingExpiry'] = GetSettings::getPostMeta($this->listingID, 'post_expiry');
        }
        SetSettings::setPostMeta($this->claimID, 'listing_original_info', $aInfo);
    }

    private function updateClaimSettings()
    {
        SetSettings::setPostMeta($this->claimID, 'claimer_id', $this->claimerID);
        SetSettings::setPostMeta($this->claimID, 'claimed_listing_id', $this->listingID);
        SetSettings::setPostMeta($this->claimID, 'claim_status', 'pending');
        SetSettings::setPostMeta($this->claimID, 'claimer_info', $this->aClaimerInfo);
        $this->storeOriginalListingInfo();

        FileSystem::logSuccess('Claim: Updated Claim Info. Claim ID: ' . $this->claimerID);
    }

    protected function insertClaim()
    {
        $oUserInfo = get_user_by('id', $this->claimerID);
        $this->claimID = wp_insert_post([
            'post_type'   => 'claim_listing',
            'post_status' => 'draft',
            'post_title'  => $oUserInfo->user_login . ' ' . esc_html__('wants to claim ',
                    'wiloke-listing-tools') . ' ' . get_the_title($this->listingID)
        ]);

        $this->updateClaimSettings();
        FileSystem::logSuccess('Claim: Created Claim. Claim ID: ' . $this->claimerID);
    }

    protected function updateClaim()
    {
        $this->claimID = $this->isClaimerExisting($this->listingID, $this->claimerID);

        if (empty($this->claimID)) {
            $this->insertClaim();
        } else {
            $this->updateClaimSettings();
        }
    }

    public static function isPaidClaim()
    {
        if (DebugStatus::status('WILCITY_DISABLE_PAID_CLAIM') ||
            !class_exists('WilcityPaidClaim\Register\RegisterClaimSubMenu')
        ) {
            self::$isPaidClaim = false;

            return false;
        }

        $aStatus = GetSettings::getOptions(RegisterClaimSubMenu::$optionKey);
        if (isset($aStatus['toggle']) && $aStatus['toggle'] == 'enable') {
            self::$isPaidClaim = true;

            return true;
        }

        self::$isPaidClaim = false;

        return false;
    }

    public function handleClaimRequest()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        if (!isset($_POST['data']) || empty($_POST['data'])) {
            $oRetrieve->error(['msg' => esc_html__('The data is required', 'wiloke-listing-tools')]);
        }

        if (!ValidationHelper::isValidJson($_POST['data'])) {
            $oRetrieve->error(['msg' => esc_html__('Invalid json format', 'wiloke-listing-tools')]);
        }
        $aData = ValidationHelper::getJsonDecoded();

        $aStatus = $this->middleware([
            'isUserLoggedIn',
            'isLockedAddListing',
            'isListingPostType',
            'isClaimAvailable'
        ], [
            'postID' => $aData['postID']
        ]);

        if ($aStatus['status'] == 'error') {
            FileSystem::logError('Could not proceed this claim. Reason: ' . $aStatus['msg']);
            $oRetrieve->error([
                'msg' => $aStatus['msg']
            ]);
        }

        $userID = get_current_user_id();

        if (get_post_field('post_author', $aData['postID']) == $userID) {
            $oRetrieve->error([
                'msg' => esc_html__('You are the author of this post already.', 'wiloke-listing-tools')
            ]);
        }

        FileSystem::logSuccess('Claim: Starting Claim Listing ' . get_the_title($this->listingID), true);

        do_action('wiloke-listing-tools/before-handling-claim-request', $aData);

        $aClaimFields = GetSettings::getOptions('claim_settings', false, true);
        foreach ($aClaimFields as $key => $aField) {
            if ($aField['key'] == 'claimPackage') {
                continue;
            }

            if ($aField['isRequired'] == 'yes') {
                if (!isset($aData[$aField['key']]) || empty($aData[$aField['key']])) {
                    $oRetrieve->error(
                        [
                            'msg' => sprintf(esc_html__('We need your %s.', 'wiloke-listing-tools'), $aField['label'])
                        ]
                    );
                }
            }

            if ($aField['type'] == 'checkbox') {
                $values = $aData[$aField['key']];
                $aValues = array_map('sanitize_text_field', $values);
                $aData['value'] = $aValues;
            } else {
                $aData['value'] = isset($aData[$aField['key']]) ? sanitize_text_field($aData[$aField['key']]) : '';
            }
        }

        $this->listingID = $aData['postID'];

        if (self::isPaidClaim()) {
            $this->planID = isset($aData['claimPackage']) ? $aData['claimPackage'] : '';
        } else {
            $this->planID = GetWilokeSubmission::getFreeClaimPlanID($this->listingID);
        }

        $this->claimerID = $userID;
        $this->aClaimerInfo = $aData;
        $this->updateClaim();

        do_action('wilcity/handle-claim-request', [
            'planID'      => $this->planID,
            'postID'      => $this->listingID,
            'claimerID'   => $this->claimerID,
            'claimID'     => $this->claimID,
            'isPaidClaim' => self::isPaidClaim() ? 'yes' : 'no'
        ]);

        if (get_post_type($this->planID) !== 'listing_plan') {
            FileSystem::logError('Wrong Plan Type. Plan ID: ' . $this->planID . ' Plan Title: ' . get_the_title
                ($this->planID) . ' Post Type: ' . get_post_type($this->planID));
            $oRetrieve->error([
                'msg' => esc_html__('Invalid plan id', 'wiloke-listing-tools')
            ]);
        }

        Session::setPaymentPlanID($this->planID);
        Session::setPaymentObjectID($this->listingID);
        Session::setPaymentCategory('paidClaim');

        FileSystem::logSuccess('Claim: Paid claim is processing');

        $aUserPlan = UserModel::getSpecifyUserPlanID($this->planID, $userID, true);
        $aPlanSettings = GetSettings::getPlanSettings($this->planID);

        if (!empty($aUserPlan)) {
            FileSystem::logSuccess(
                'Claim: Customer purchased this plan and it is available. Plan ID: ' . $this->planID
            );

            if (empty($aPlanSettings['regular_price'])) {
                $this->middleware(['isExceededFreePlan'], [
                    'listingID' => $this->listingID,
                    'isClaim'   => true,
                    'planID'    => $this->planID
                ]);
            }

            if (!empty($aUserPlan['remainingItems']) && (absint($aUserPlan['remainingItems']) > 0)) {
                SetSettings::setPostMeta($this->claimID, 'claim_plan_id', $this->planID);
                FileSystem::logSuccess('Claim: Paid Claim was used a purchased plan. Info: ' .
                    json_encode($aUserPlan));

                $msg
                    = esc_html__('Thanks for your claiming! Our staff will review your request and contact you shortly',
                    'wiloke-listing-tools');
                if (!empty($aPlanSettings['regular_price'])) {
                    /**
                     * @hooked WilokeListingTools\Controllers\PlanRelationshipController:afterClaimApproved 10
                     * @hooked WilcityPaidClaim\Controllers\ClaimListingsController:paidClaimSuccessfully 99
                     */
                    do_action('wilcity/wiloke-listing-tools/claimed-listing-with-purchased-plan', [
                        'userID'    => get_current_user_id(),
                        'postID'    => $this->listingID,
                        'planID'    => $this->planID,
                        'status'    => 'succeeded',
                        'claimID'   => $this->claimID,
                        'aUserPlan' => $aUserPlan,
                        'category'  => 'paidClaim'
                    ]);

                    $msg = esc_html__('Congratulations! Your claim has been approved.', 'wiloke-listing-tools');
                }

                $oRetrieve->success([
                    'msg' => $msg
                ]);
            }
        }

        Session::setClaimID($this->claimID);

        if (empty($aPlanSettings['regular_price'])) {
            $this->oReceipt = ReceiptStaticFactory::get('addlisting', [
                'planID'     => $this->planID,
                'userID'     => User::getCurrentUserID(),
                'couponCode' => '',
                'aRequested' => $_REQUEST
            ]);
            $this->oReceipt->setupPlan();

            $oFreePlan = new FreePlan();
            $aStatus = $oFreePlan->proceedPayment($this->oReceipt);

            if ($aStatus['status'] != 'success') {
                $oRetrieve->error([
                    'msg' => esc_html__('ERROR: We could not create Free Plan', 'wiloke-listing-tools')
                ]);
            } else {
                do_action('wiloke/free-claim/submitted', $this->claimerID, $this->listingID, $this->planID);
                SetSettings::setPostMeta(
                    $this->claimID,
                    'claim_plan_id',
                    $this->planID
                );
                $oRetrieve->success([
                    'msg'        => esc_html__('Thanks for your claiming! Our staff will review your request and contact you shortly',
                        'wiloke-listing-tools'),
                    'redirectTo' => $aStatus['redirectTo']
                ]);
            }
        } else {
            $redirectTo = GetWilokeSubmission::getField('checkout', true);
            $productID = GetSettings::getPostMeta($this->planID, 'woocommerce_association');
            if (!empty($productID)) {
                $wooCommerceCartUrl = GetSettings::getCartUrl($this->planID);
                /*
                * @hooked WooCommerceController:removeProductFromCart
                */
                do_action('wiloke-listing-tools/before-redirecting-to-cart', $productID);
                $redirectTo = $wooCommerceCartUrl;
                Session::setProductID($productID);
            }

            SetSettings::setPostMeta($this->claimID, 'claim_plan_id', $this->planID);

            $oRetrieve->success([
                'redirectTo' => add_query_arg(
                    [
                        'planID' => $this->planID
                    ],
                    $redirectTo
                )
            ]);
        }
    }

    public function handleFetchClaimFields()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        if (empty($_GET['postID'])) {
            $oRetrieve->error(['msg' => esc_html__('The post ID is required', 'wiloke-listing-tools')]);
        }

        $postID = $_GET['postID'];
        $postType = get_post_type($postID);
        $aSupportedPostTypes = GetSettings::getFrontendPostTypes(true);

        if (!in_array($postType, $aSupportedPostTypes)) {
            $oRetrieve->error([
                'msg' => esc_html__('Oops! There are no claim fields.', 'wiloke-listing-tools')
            ]);
        }

        if (!self::isPaidClaim()) {
            if (empty(GetWilokeSubmission::getFreeClaimPlanID($postID))) {
                $oRetrieve->error([
                    'msg' => esc_html__('Please go to Wiloke Submission -> Set a Free Claim Plan of this post type',
                        'wiloke-listing-tools')
                ]);
            }
        }

        $post = get_post($postID);

        $aClaimSettings = GetSettings::getOptions('claim_settings', false, true);

        if (!empty($aClaimSettings)) {
            foreach ($aClaimSettings as $order => $aClaimSetting) {
                $aClaimSettings[$order]['label'] = stripslashes($aClaimSetting['label']);
                if ($aClaimSetting['type'] !== 'radio' && $aClaimSetting['type'] !== 'checkbox') {
                    continue;
                }

                $aOptions = General::parseSelectFieldOptions($aClaimSetting['options'], 'wil-select-tree');

                unset($aClaimSettings[$order]['options']);
                $aClaimSettings[$order]['options'] = $aOptions;
                $aClaimSettings[$order]['maximum'] = $aClaimSetting['type'] !== 'radio' ? 100 : 1;
            }
        }

        $aClaimSettings = apply_filters('wilcity/claim-field-settings', array_values($aClaimSettings), $post);
        $oRetrieve->success(['fields' => $aClaimSettings]);
    }
}
