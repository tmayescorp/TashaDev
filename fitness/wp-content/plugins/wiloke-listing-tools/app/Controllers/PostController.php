<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Submission;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Helpers\WooCommerce as WooCommerceHelpers;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;
use WilokeListingTools\Models\PlanRelationshipModel;
use WilokeListingTools\Models\PostMetaModel;

class PostController extends Controller
{
    use SetPostDuration;

    public    $expiredAt                     = '';
    public    $needUpdateScheduleKey         = 'need_update_schedule';
    public    $expirationKey                 = 'post_expiry';
    public    $almostExpiredKey              = 'post_almost_expiry';
    public    $deleteUnpaidListing           = 'delete_unpaid_listing';
    public    $fNotificationAlmostDeletePost = 'f_notice_delete_unpaid_listing';
    public    $sNotificationAlmostDeletePost = 's_notice_delete_unpaid_listing';
    public    $tNotificationAlmostDeletePost = 't_notice_delete_unpaid_listing';
    public    $updatedExpirationTime         = false;
    public    $test                          = 1;
    public    $directlyUpdatedExpirationDate = false;
    public    $skipUpdateExpiry              = false;
    public    $aObjectApprovedImmediately    = [];
    protected $setExpiryPostValueSchedule    = 'wilcity_set_expiry_post_value_schedule';
    protected $setExpiryPostEventSchedule    = 'wilcity_set_expiry_post_event_schedule';

    public function __construct()
    {
        $aBillingTypes = wilokeListingToolsRepository()->get('payment:billingTypes', false);

        /*
         * It's different from wiloke-listing-tools/payment-return-cancel-page and wiloke-listing-tools/payment-cancelled
         * wiloke-listing-tools/payment-cancelled means Subscription was cancelled
         * wiloke-listing-tools/payment-return-cancel-page means Custom click Cancel button and do not purchase plan
         */
        add_action('wiloke-listing-tools/payment-return-cancel-page', [$this, 'rollupListingToPreviousStatus']);

        add_action(
            'wiloke-listing-tools/woocommerce/after-order-succeeded',
            [
                $this, 'migrateAllListingsBelongsToWooCommerceToPublish'
            ],
            20
        );

        add_filter('wilcity/ajax/post-comment/post', [$this, 'insertComment'], 10, 2);

        add_action('wp_ajax_wilcity_hide_listing', [$this, 'hideListing']);
        add_action('wp_ajax_wilcity_republish_listing', [$this, 'rePublishPost']);
        add_action('wp_ajax_wilcity_delete_listing', [$this, 'deleteListing']);

        //        add_action('wiloke-listing-tools/on-changed-user-plan', [$this, 'updatePostToNewPlan'], 20, 1);
        add_action('wp_ajax_wilcity_fetch_posts', [$this, 'fetchPosts']);
        add_action('wp_ajax_nopriv_wilcity_fetch_posts', [$this, 'fetchPosts']);

        // Post Expired
        add_action($this->expirationKey, [$this, 'postExpired']);

        // Delete Expired Event
        add_action($this->deleteUnpaidListing, [$this, 'focusDeletePost']);

//        add_action('after_delete_post', [$this, 'clearAllSchedules']);
        add_action('edit_form_after_title', [$this, 'addNonceToAdmin']);
        add_action('wiloke-submission/app/session-detail/after_order_information_open', [$this, 'addNonceToAdmin']);

        // Since 1.1.7.6, We will use post_updated instead of post_updated
//        add_action('added_post_meta', [$this, 'changedListingPlan'], 999, 4);
//        add_action('deleted_post_meta', [$this, 'changedListingPlan'], 999, 4);
//        add_action('updated_postmeta', [$this, 'changedListingPlan'], 999, 4);

        add_action('added_post_meta', [$this, 'maySetExpiryPostValueSchedule'], 999, 4);
        add_action('updated_postmeta', [$this, 'maySetExpiryPostValueSchedule'], 999, 4);

        add_action('added_post_meta', [$this, 'maybeSetPostEventSchedule'], 999, 4);
        add_action('updated_postmeta', [$this, 'maybeSetPostEventSchedule'], 999, 4);

        add_action('deleted_post_meta', [$this, 'clearAllSchedulesAfterDeletingPostExpiry'], 999, 4);

        add_action('wp_insert_post', [$this, 'maybeSetSchedulePostEventAfterPostUpdated'], 10, 2);
        add_action('wilcity_after_reupdated_post', [$this, 'maybeSetSchedulePostEventAfterPostUpdated'], 10, 2);
        add_action('after_delete_post', [$this, 'setSchedulePostEventAfterDeletePost']);

        add_action($this->setExpiryPostValueSchedule, [$this, 'maybeUpdatePostExpiryValue']);
        add_action($this->setExpiryPostEventSchedule, [$this, 'setNextRecheckPostExpiryEvent']);

        add_action('wilcity/wiloke-listing-tools/claim-approved', [$this, 'updatePostAfterClaimApproved'], 10, 1);
//        add_action('wilcity/wiloke-listing-tools/claim-cancelled', [$this, 'updatePostAfterClaimCancelled'], 10, 1);

//        add_action('wiloke/submitted-listing', [$this, 'handleListingPlanAfterSubmitting'], 15);
//        add_action('wiloke/submitted-listing', [$this, 'clearScheduleAfterSubmittingListing']);

        foreach ($aBillingTypes as $billingType) {
            add_action(
                'wilcity/wiloke-listing-tools/' . $billingType . '/payment-completed',
                [$this, 'updatePostAfterPaymentCompleted'],
                15
            );

            add_action(
                'wilcity/wiloke-listing-tools/' . $billingType . '/payment-refunded',
                [$this, 'updatePostAfterPaymentRefunded'],
                10
            );
        }

        add_filter(
            'wilcity/wiloke-listing-tools/change-listings-to-another-purchased-plan',
            [$this, 'changedListingToAnotherPurchasedPlan'],
            10,
            2
        );

        //        add_action('init', [$this, 'testInfo']);
        add_action(
            'wilcity/wiloke-listing-tools/app/Controllers/HandleSubmit/admin/submitted',
            [$this, 'adminSubmittedListing']
        );
        add_action(
            'wilcity/wiloke-listing-tools/app/Controllers/DirectBankTransferPaymentScheduleController/cancelSubscription',
            [$this, 'changeBankTransferPostStatus']
        );

        add_action(
            'wiloke/submitted-listing',
            [$this, 'setDeleteUnpaidListingSchedule'],
            10,
            3
        );

        add_action('wilcity/single-listing/home/before/content', [$this, 'validateExpiredListing']);
    }

    public function validateExpiredListing($post)
    {
        $listingExpired = GetSettings::getListingExpiryTimestamp($post->ID);
        $isExpiredListing = $post->post_status == 'expired';
        if ($post->post_status == 'publish' &&
            !Session::getPaymentPlanID(false) &&
            Time::compareTwoTimes(current_time('timestamp'), $listingExpired, 1)
        ) {
            wp_update_post([
                'ID'          => $post->ID,
                'post_status' => 'expired'
            ]);
            $isExpiredListing = true;
        }

        if ($isExpiredListing && !Session::getPaymentPlanID(false) && ($post->post_author == get_current_user_id()
                || current_user_can('administrator'))) {
            echo (
                    sprintf(
                        __('<div class="wil-listing-expired-notice">The listing has been expired on %s. Click <a style="color: red; font-weight: bold" href="%s">here</a> to republish the listing</div>',  'wiloke-listing-tools'),
                        date_i18n(get_option('date_format', $listingExpired)),
                        add_query_arg([
                            [
                                'postID'       => $post->ID,
                                'listing_type' => $post->post_type
                            ]
                        ], get_permalink(GetWilokeSubmission::getField('package')))
                    )
            );
        }
    }

    public function isContributor($postAuthorId): bool
    {
        $oUser = new \WP_User($postAuthorId);

        return in_array('contributor', $oUser->roles);
    }

    /**
     * @param $aParams
     */
    public function changeBankTransferPostStatus($aParams)
    {
        $paymentID = $aParams['paymentID'];
        $postID = PlanRelationshipModel::getObjectIDsByPaymentID($paymentID)[0]['objectID'];
        if (PaymentModel::getField('status', $paymentID) == 'active') {
            PaymentModel::updatePaymentStatus('cancelled', $paymentID);
        }
        if (get_post_status($postID) == 'publish') {
            $update = wp_update_post(
                [
                    'ID'          => $postID,
                    'post_status' => 'expired'
                ]
            );
            if ($update) {
                FileSystem::logSuccess('Cancel Supcription BankTransfer successful');
            } else {
                FileSystem::logSuccess('Cancel Supcription BankTransfer fail');
            }
        }
    }

    protected function isEmptyListingExpired()
    {
        if (!isset($_POST['wilcity_post_expiry']) || empty($_POST['wilcity_post_expiry'])) {
            return true;
        }

        if (!isset($_POST['wilcity_post_expiry']['date']) || empty($_POST['wilcity_post_expiry']['date'])) {
            return true;
        }

        return false;
    }

    public function updatePostAfterClaimApproved($aInfo)
    {
        $formerlyAuthorID = get_post_field('post_author', $aInfo['postID']);
        wp_update_post([
            'ID'          => $aInfo['postID'],
            'post_author' => $aInfo['claimerID'],
            'post_status' => 'publish'
        ]);

        SetSettings::setPostMeta($aInfo['postID'], 'claim_status', 'claimed');
        SetSettings::setPostMeta($aInfo['postID'], 'attribute_post_author', $formerlyAuthorID);
        SetSettings::setPostMeta($aInfo['postID'], 'belongs_to', $aInfo['planID']);
    }

    public function addNonceToAdmin()
    {
        wp_nonce_field('wilcity_admin_security', 'wilcity_admin_nonce_field');
    }

    private function verifyPaymentBeforeUpdatingPost($aPaymentInfo, $aPaymentMetaInfo)
    {
        if (!isset($aPaymentInfo['paymentID']) || !isset($aPaymentInfo['status'])) {
            FileSystem::logError('The payment id and status are required', __CLASS__,
                __METHOD__);

            return false;
        }

        if (empty($aPaymentMetaInfo) || !isset($aPaymentMetaInfo['category'])) {
            FileSystem::logError('The payment info is empty or the category is empty ' . $aPaymentInfo['paymentID'],
                __CLASS__,
                __METHOD__);

            return false;
        }

        // If it's not add listing, We can ignore it
        if (!in_array($aPaymentMetaInfo['category'], ['addlisting', 'paidClaim'])) {
            return false;
        }

        $aRequires = ['planID'];

        if (GetWilokeSubmission::isNonRecurringPayment(PaymentModel::getField('billingType',
            $aPaymentInfo['paymentID']))
        ) {
            $aRequires[] = 'postID';
        }

        foreach ($aRequires as $required) {
            if (!isset($aPaymentMetaInfo[$required]) || empty($aPaymentMetaInfo[$required])) {
                FileSystem::logError(sprintf('The %s is required', $required));

                return false;
            }
        }

        return true;
    }

    private function parsePostIDs($postIDs)
    {
        $aPostIDs = explode(',', $postIDs);
        $aPostIDs = array_map(function ($postID) {
            return trim($postID);
        }, $aPostIDs);

        return $aPostIDs;
    }

    /**
     * If the beforeStatus is active, We won't move Listings that belongs to this plan to unpaid immediately.
     *
     * @param $aInfo
     *
     * @return bool
     */
    public function updatePostAfterPaymentFailed($aInfo)
    {
        $aPaymentMetaInfo = PaymentMetaModel::getPaymentInfo($aInfo['paymentID']);
        $verifyStatus = $this->verifyPaymentBeforeUpdatingPost($aInfo, $aPaymentMetaInfo);
        if (!$verifyStatus) {
            return false;
        }

        if (isset($aInfo['beforeStatus']) && $aInfo['beforeStatus'] == 'active') {
            return false;
        }

        wp_update_post(
            [
                'ID'          => $aPaymentMetaInfo['postID'],
                'post_status' => 'unpaid'
            ]
        );

        $this->updateListingScheduleExpiration(get_post($aPaymentMetaInfo['postID']),
            $aPaymentMetaInfo['planID']);
    }

    public function updatePostAfterPaymentRefunded($aInfo): bool
    {
        $aPaymentMetaInfo = PaymentMetaModel::getPaymentInfo($aInfo['paymentID']);
        $verifyStatus = $this->verifyPaymentBeforeUpdatingPost($aInfo, $aPaymentMetaInfo);

        if (!$verifyStatus) {
            return false;
        }

        $aObjectIDs = PlanRelationshipModel::getObjectIDsByPaymentID($aInfo['paymentID']);
        if (empty($aObjectIDs)) {
            return false;
        }

        foreach ($aObjectIDs as $aObject) {
            wp_update_post(
                [
                    'ID'          => $aObject['objectID'],
                    'post_status' => 'unpaid'
                ]
            );
        }
        return true;
    }

    public function updatePostAfterPaymentCancelled($aInfo)
    {
        $aPaymentMetaInfo = PaymentMetaModel::getPaymentInfo($aInfo['paymentID']);
        $verifyStatus = $this->verifyPaymentBeforeUpdatingPost($aInfo, $aPaymentMetaInfo);

        if (!$verifyStatus) {
            return false;
        }

        $aObjectIDs = PlanRelationshipModel::getObjectIDsByPaymentID($aInfo['paymentID']);
        if (empty($aObjectIDs)) {
            return false;
        }
        $aPostIDs = [];

        foreach ($aObjectIDs as $aObject) {
            if (!in_array(get_post_status($aObject['objectID']), ['unpaid', 'expired'])) {
                wp_update_post(
                    [
                        'ID'          => $aObject['objectID'],
                        'post_status' => 'unpaid'
                    ]
                );
                $this->updateListingScheduleExpiration(get_post($aPaymentMetaInfo['postID']),
                    $aPaymentMetaInfo['planID']);
                $aPostIDs[] = $aObject['objectID'];
            }
        }

        FileSystem::logSuccess(
            'AddListing: Update to trash status because the payment was an cancelled. Post IDs: ' . implode(',',
                $aPostIDs)
        );
    }

    /**
     * @param $aInfo
     *
     * @return bool
     */
    public function updatePostAfterPaymentDispute($aInfo)
    {
        $aPaymentMetaInfo = PaymentMetaModel::getPaymentInfo($aInfo['paymentID']);
        $verifyStatus = $this->verifyPaymentBeforeUpdatingPost($aInfo, $aPaymentMetaInfo);

        if (!$verifyStatus) {
            FileSystem::logSuccess('There is a dispute but We can not do anything because wrong verify status',
                __CLASS__);

            return false;
        }

        $aObjectIDs = PlanRelationshipModel::getObjectIDsByPaymentID($aInfo['paymentID']);
        foreach ($aObjectIDs as $aObject) {
            wp_update_post(
                [
                    'ID'          => $aObject['objectID'],
                    'post_status' => 'unpaid'
                ]
            );
        }

        FileSystem::logSuccess(
            'AddListing: Update to pending status because there was an dispute. Post ID: ' .
            $aPaymentMetaInfo['postID'],
            __CLASS__
        );

        $this->updateListingScheduleExpiration(get_post($aPaymentMetaInfo['postID']),
            $aPaymentMetaInfo['planID']);
    }

    private function determinePostNewStatus($postID)
    {
        if (in_array($postID, $this->aObjectApprovedImmediately)) {
            return 'publish';
        }

        return GetWilokeSubmission::getNewPostStatus($postID);
    }

    private function toNewStatus($postID, $planID): string
    {
        $postStatus = $this->determinePostNewStatus($postID);
        wp_update_post(
            [
                'ID'          => $postID,
                'post_status' => $postStatus
            ]
        );

        switch ($postStatus) {
            case 'pending':
            case 'publish':
                SetSettings::deletePostMeta($postID, 'post_expiry');
                SetSettings::deletePostMeta($postID, 'belongs_to');
                SetSettings::setPostMeta($postID, 'belongs_to', $planID);
                break;
        }

        return $postStatus;
    }

    public function testInfo()
    {
        $aInfo = [
            'status'    => 'succeeded',
            'gateway'   => 'free',
            'paymentID' => 15,
            'postID'    => 13345,
            'planID'    => 3207,
            'category'  => 'addlisting'
        ];

        $this->updatePostAfterPaymentCompleted($aInfo);
    }

    /**
     * @param $aInfo
     *
     * @return bool
     */
    public function updatePostAfterPaymentCompleted($aInfo)
    {
        $aPaymentMetaInfo = PaymentMetaModel::getPaymentInfo($aInfo['paymentID']);
        $verifyStatus = $this->verifyPaymentBeforeUpdatingPost($aInfo, $aPaymentMetaInfo);

        if (!$verifyStatus) {
            return false;
        }

        $this->aObjectApprovedImmediately = Session::getFocusObjectsApprovedImmediately();

        switch ($aInfo['status']) {
            case 'succeeded':
                $aPostIDs = $this->parsePostIDs($aPaymentMetaInfo['postID']);
                foreach ($aPostIDs as $postID) {
                    $this->toNewStatus($postID, $aPaymentMetaInfo['planID']);
                }
                break;
            case 'active':
                $aPostIDs = PlanRelationshipModel::getObjectIDsByPaymentID($aInfo['paymentID']);
                foreach ($aPostIDs as $aPost) {
                    $this->toNewStatus($aPost['objectID'], $aPaymentMetaInfo['planID']);
                }
                break;
        }
    }

    public function changedListingToAnotherPurchasedPlan($aResponse, $aInfo)
    {
        $oRetrieve = new RetrieveController(new NormalRetrieve());
        if ($aResponse['status'] == 'error') {
            return $oRetrieve->error($aResponse);
        }

        $aRequires = [
            'postIDs',
            'paymentID',
            'planID'
        ];

        foreach ($aRequires as $required) {
            if (!isset($aInfo[$required]) || empty($aInfo[$required])) {
                return $oRetrieve->error([
                    'msg' => sprintf(esc_html__('The %s is required', 'wiloke-listing-tools'), $required)
                ]);
            }
        }

        $aPostIDs = $aInfo['postIDs'];
        $planID = $aInfo['planID'];

        foreach ($aPostIDs as $postID) {
            $this->toNewStatus($postID, $planID);
        }

        $oRetrieve->success([]);
    }

    /*
     * The Expired Listing = Real Expired Time + Move listing to expired store after x days (You can find this setting under Wiloke Submission)
     *
     * @since 1.1.7.3
     * @return int
     */
    private function getExpiredListingTime($timestamp)
    {
        $plusExpiredTime = GetWilokeSubmission::getField('move_listing_to_expired_store_after');
        if (empty($plusExpiredTime)) {
            return $timestamp;
        }

        $oDT = new \DateTime();
        $oDT->setTimestamp($timestamp);
        $oDT->modify('+' . $plusExpiredTime . ' day');

        return strtotime($oDT->format('Y-m-d H:i:s'));
    }

    /*
     * Get Almost expired Listing Time. We will set a schedule and send an email to customer
     *
     * @since 1.1.7.3
     * return int
     */
    private static function getAlmostExpiredDate($timestamp, $beforeXday = 1)
    {
        $oDT = new \DateTime();
        $oDT->setTimestamp($timestamp);
        $oDT->modify('-' . $beforeXday . ' day');

        return strtotime($oDT->format('Y-m-d H:i:s'));
    }

    public function postExpired($postID)
    {
        $downgradeTo = GetWilokeSubmission::getField('downgrade_expired_premium_listing_to');
        $postStatus = 'expired';
        if ($downgradeTo == 'default_plan') {
            $key = 'free_claim_' . get_post_type($postID) . '_plan';
            $defaultPlan = GetWilokeSubmission::getField($key);

            if (!empty($defaultPlan) &&
                get_post_type($defaultPlan) === 'listing_plan' &&
                get_post_status($defaultPlan) === 'publish') {
                $postStatus = 'publish';
                SetSettings::deletePostMeta($postID, $this->expirationKey);
            }
        }

        $oldPlanId = GetSettings::getListingBelongsToPlan($postID);
        $aPlanSettings = GetSettings::getPlanSettings($oldPlanId);
        $post = get_post($postID);
        $menuOrder = absint($post->menu_order);

        if (isset($aPlanSettings['wilcity_belongs_to']) && !empty($aPlanSettings['wilcity_belongs_to'])) {
            $menuOrder = $menuOrder - absint($aPlanSettings['wilcity_belongs_to']);
            $menuOrder = $menuOrder > 0 ? $menuOrder : 0;
        }

        wp_update_post([
            'ID'          => $postID,
            'post_status' => $postStatus,
            'menu_order'  => $menuOrder
        ]);

        if ($postStatus === 'publish') {
            SetSettings::setPostMeta($postID, 'belongs_to', $defaultPlan, '', true);
        }
    }

    public function focusDeletePost($postID)
    {
        if (GetWilokeSubmission::getField('delete_listing_conditional')) {
            return false;
        }

        if (get_post_status($postID) != 'expired' && get_post_status($postID) != 'unpaid') {
            return false;
        }

        wp_delete_post($postID, true);
    }

    public function fetchPosts()
    {
        $aPostIDs = GetSettings::getPostsBelongToListing($_GET['parentID']);

        $aPostIDs = apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Controllers/PostControllers/fetchPosts',
            $aPostIDs
        );

        if (empty($aPostIDs)) {
            wp_send_json_error([
                'isLoaded' => 'yes',
                'msg'      => esc_html__('There are no posts', 'wiloke-listing-tools'),
            ]);
        }

        $aArgs = [
            'post_type'      => 'post',
            'posts_per_page' => 10,
            'post_status'    => 'publish',
            'post__in'       => $aPostIDs
        ];

        if ($aPostIDs == 'post_author') {
            unset($aArgs['post__in']);
        }

        if (isset($_GET['page']) && !empty($_GET['page'])) {
            $aArgs['paged'] = $_GET['page'];
        }

        if (isset($_GET['postNotIn']) && !empty($_GET['postNotIn'])) {
            if (isset($aArgs['post__in'])) {
                $post__not_in = $_GET['postNotIn'];
                $post__not_in = is_array($post__not_in) ? array_map('intval', $post__not_in) : array_map('intval',
                    explode(',', $post__not_in));
                $aArgs['post__in'] = array_diff($aArgs['post__in'], $post__not_in);
            }
        }

        if (isset($aArgs['post__in']) && empty($aArgs['post__in'])) {
            wp_send_json_error(['isLoaded' => 'yes']);
        }

        $query = new \WP_Query(apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Controllers/PostControllers/fetchPosts/query',
            $aArgs
        ));
        if ($query->have_posts()) {
            ob_start();
            while ($query->have_posts()) {
                $query->the_post();
                ?>
                <div class="col-sm-6">
                    <?php wilcity_render_grid_post($query->post); ?>
                </div>
                <?php
            }
            wp_reset_postdata();
            $content = ob_get_contents();
            ob_end_clean();
            wp_send_json_success([
                'args'     => $aArgs,
                'content'  => $content,
                'maxPages' => absint($query->max_num_pages),
                'maxPosts' => absint($query->found_posts)
            ]);
        } else {
            if (isset($_GET['postNotIn']) && !empty($_GET['postNotIn'])) {
                wp_send_json_error(['isLoaded' => 'yes']);
            }
            wp_send_json_error([
                'msg'      => esc_html__('There are no posts', 'wiloke-listing-tools'),
                'maxPages' => 0,
                'maxPosts' => 0
            ]);
        }
    }

    /**
     * Re-update Listing Order
     *
     * @since 1.2.0
     */
    private function reUpdateListingOrder($listingID, $newPlanID, $oldPlanID)
    {
        $listingOrder = get_post_field('menu_order', $listingID);
        $aNewPlanSettings = GetSettings::getPlanSettings($newPlanID);
        $aOldPlanSettings = GetSettings::getPlanSettings($oldPlanID);

        if (isset($aOldPlanSettings['menu_order']) && !empty($aOldPlanSettings['menu_order'])) {
            $listingOrder = absint($listingOrder) - absint($aOldPlanSettings['menu_order']);
            $listingOrder = $listingOrder > 0 ? $listingOrder : 0;
        }

        if (isset($aNewPlanSettings['menu_order']) && !empty($aNewPlanSettings['menu_order'])) {
            $listingOrder = absint($listingOrder) + absint($aNewPlanSettings['menu_order']);
            $listingOrder = $listingOrder > 0 ? $listingOrder : 0;
        }

        wp_update_post([
            'ID'         => $listingID,
            'menu_order' => $listingOrder
        ]);
    }

    /*
     * Updating Listing Information Like Expiry Date, Belongs To after Plan was Changed
     *
     * @since 1.2.0
     */
    private function onChangePlan($aInfo, $paymentID)
    {
        global $wpdb;
        $postMetaTbl = $wpdb->postmeta;
        $postTbl = $wpdb->posts;

        $aRawPostMetaIDs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT $postMetaTbl.meta_id, $postTbl.ID FROM $postMetaTbl LEFT JOIN $postTbl ON($postMetaTbl.post_id=$postTbl.ID) WHERE $postTbl.post_author=%d AND $postMetaTbl.meta_key=%s AND meta_value=%d AND post_status IN ('publish', 'pending')",
                $aInfo['userID'], General::generateMetaKey('belongs_to'), $aInfo['oldPlanID']
            ),
            ARRAY_A
        );

        if (empty($aRawPostMetaIDs)) {
            return false;
        }

        $aPostIDs = $aPostMetaIDs = [];
        foreach ($aRawPostMetaIDs as $aData) {
            $aPostMetaIDs[] = absint($aData['meta_id']);
            $aPostIDs[] = [
                'objectID' => $aData['ID']
            ];
        }

        $wpdb->query($wpdb->prepare(
            "UPDATE $postMetaTbl SET $postMetaTbl.meta_value = %d WHERE $postMetaTbl.meta_key=%s AND $postMetaTbl.meta_id IN (" .
            implode(',',
                $aPostMetaIDs) . ")",
            absint($aInfo['planID']), General::generateMetaKey('belongs_to')
        ));

        $this->expiredAt = PaymentMetaModel::getNextBillingDateGMT($paymentID);
        if (!empty($this->expiredAt)) {
            $this->inCaseToPublish($aPostIDs, [
                'nextBillingDateGMT' => $this->expiredAt,
                'oldPlanID'          => $aInfo['oldPlanID'],
                'planID'             => $aInfo['planID']
            ], __METHOD__);
        }
    }

    public function updatePostToNewPlan($aInfo)
    {
        if (!isset($aInfo['userID']) || !isset($aInfo['planID']) || !isset($aInfo['oldPlanID']) ||
            empty($aInfo['planID']) || empty($aInfo['userID']) || empty($aInfo['oldPlanID'])
        ) {
            return false;
        }

        $this->onChangePlan($aInfo, $aInfo['paymentID']);
    }

    public function deleteListing()
    {
        $this->middleware(['isPostAuthor'], [
            'postID'        => $_POST['postID'],
            'passedIfAdmin' => true
        ]);
        $postType = get_post_type($_POST['postID']);
        $postAuthor = get_post_field('post_author', $_POST['postID']);
        $planID = GetSettings::getListingBelongsToPlan($_POST['postID']);

        wp_delete_post($_POST['postID'], apply_filters('wilcity/app/Controllers/PostController/deleteListing/isFocus', true));
        do_action('wilcity/deleted/listing', $_POST['postID'], $postType, $postAuthor, $planID);

        wp_send_json_success([
            'msg' => esc_html__('Congrats! The listing has been deleted successfully', 'wiloke-listing-tools')
        ]);
    }

    public function rePublishPost()
    {
        $this->middleware(['isPostAuthor', 'isTemporaryHiddenPost'], [
            'postID'        => $_POST['postID'],
            'passedIfAdmin' => true
        ]);

        wp_update_post([
            'ID'          => $_POST['postID'],
            'post_status' => 'publish'
        ]);

        wp_send_json_success([
            'msg' => esc_html__('Congrats! The listing has been re-published successfully', 'wiloke-listing-tools')
        ]);
    }

    public function hideListing()
    {
        $this->middleware(['isPostAuthor', 'isPublishedPost'], [
            'postID'        => $_POST['postID'],
            'passedIfAdmin' => true
        ]);

        wp_update_post([
            'ID'          => $_POST['postID'],
            'post_status' => 'temporary_close'
        ]);

        wp_send_json_success([
            'msg' => esc_html__('Congrats! The listing has been hidden successfully', 'wiloke-listing-tools')
        ]);
    }

    public function insertComment($aResponse, $aData)
    {
        $commentID = wp_insert_comment([
            'user_id'         => get_current_user_id(),
            'comment_content' => $aData['content']
        ]);

        global $oReview, $wiloke;
        $wiloke->aThemeOptions = \Wiloke::getThemeOptions();
        $wiloke->aConfigs['translation'] = wilcityGetConfig('translation');

        $aReview = get_comment($commentID, ARRAY_A);
        $aReview['ID'] = $aReview['comment_ID'];
        $aReview['post_content'] = $aReview['comment_content'];
        $oReview = (object)$aReview;

        ob_start();
        get_template_part('reviews/item');
        $html = ob_get_contents();
        ob_end_clean();

        wp_send_json_success([
            'html'      => $html,
            'commentID' => $commentID
        ]);
    }

    private function renewListingExpired($listingID)
    {
        $durationTimestampUTC = GetSettings::getPostMeta($listingID, 'durationTimestampUTC');
        $isNextBillingDate = false;
        if (!empty($durationTimestampUTC)) {
            $timestampUTCToLocalTime = Time::convertUTCTimestampToLocalTimestamp($durationTimestampUTC);
            $duration = $this->getExpiredListingTime($timestampUTCToLocalTime);
            $isNextBillingDate = true;
        } else {
            $duration = GetSettings::getPostMeta($listingID, 'duration');
        }

        $this->setExpiration($listingID, $duration, $isNextBillingDate);
    }

    /*
     * Set schedules for a provided listing ID. We will send email before listing is expired and when the listing is expired
     *
     * @since 1.1.7.3
     */
    protected function setScheduleExpiration($postID, $expirationTimestamp)
    {
        $postID = absint($postID);

        $this->clearScheduled($postID);
        $postID = absint($postID);
        $expirationTimestamp
            = is_numeric($expirationTimestamp) ? $expirationTimestamp : strtotime($expirationTimestamp);
        $now = current_time('timestamp');

        $beforeOneWeek = $this->getAlmostExpiredDate($expirationTimestamp, 4);
        if (Time::compareTwoTimes($beforeOneWeek, $now, 7)) {
            wp_schedule_single_event($beforeOneWeek, $this->almostExpiredKey, [$postID]);
        }

        $beforeThreeDays = $this->getAlmostExpiredDate($expirationTimestamp, 3);
        if (Time::compareTwoTimes($beforeThreeDays, $now, 6)) {
            wp_schedule_single_event($beforeThreeDays, $this->almostExpiredKey, [$postID]);
        }

        $beforeOneDay = $this->getAlmostExpiredDate($expirationTimestamp, 2);
        if ($beforeOneDay > $now) {
            wp_schedule_single_event($beforeOneDay, $this->almostExpiredKey, [$postID]);
        }

        wp_schedule_single_event($expirationTimestamp, $this->expirationKey, [$postID]);
    }

    private function clearAutoDeleteUnpaidListing($postID)
    {
        $postID = absint($postID);

        wp_clear_scheduled_hook($this->deleteUnpaidListing, [$postID]);
        wp_clear_scheduled_hook($this->fNotificationAlmostDeletePost, [$postID]);
        wp_clear_scheduled_hook($this->sNotificationAlmostDeletePost, [$postID]);
        wp_clear_scheduled_hook($this->tNotificationAlmostDeletePost, [$postID]);

        $postIDString = strval($postID);
        wp_clear_scheduled_hook($this->deleteUnpaidListing, [$postIDString]);
        wp_clear_scheduled_hook($this->fNotificationAlmostDeletePost, [$postIDString]);
        wp_clear_scheduled_hook($this->sNotificationAlmostDeletePost, [$postIDString]);
        wp_clear_scheduled_hook($this->tNotificationAlmostDeletePost, [$postIDString]);

        SetSettings::deletePostMeta($postID, 'fwarning_delete_listing');
        SetSettings::deletePostMeta($postID, 'swarning_delete_listing');
        SetSettings::deletePostMeta($postID, 'twarning_delete_listing');

        FileSystem::logSuccess('AddListing: Cleared auto delete unpaid listing for ' . $postID);
    }

    public function clearAllSchedules($postId)
    {
        $this->clearAutoDeleteUnpaidListing($postId);
        $this->clearScheduled($postId);
    }

    /**
     * Sometimes, an administrator may add a listing and assign a plan to that listing via back-end
     *
     * @param $postID
     * @param $planID
     *
     * @return bool|mixed
     */
    private function isBackendEditing($postID, $planID)
    {
        if (
            !current_user_can('administrator')
            || (General::isAdmin() && !check_admin_referer('wilcity_admin_security', 'wilcity_admin_nonce_field'))
        ) {
            return false;
        }

        return apply_filters('wilcity/wiloke-listing-tools/filter/auto-set-expiration-via-admin', true, $postID,
            $planID);
    }

    public function setExpiration($postID, $duration, $isNextBillingDateVal = false)
    {
        if (empty($duration)) {
            // forever
            SetSettings::deletePostMeta($postID, $this->expirationKey);

            return true;
        }

        if ($duration < \time()) {
            $expirationTimestamp = strtotime('+' . $duration . ' days');
        } else {
            $expirationTimestamp = $duration;
        }


        /**
         * Solved_Set_Expiration_After_ADDING_LISTING: If a customer purchased a plan and the remaining items is not
         * empty,
         * adding a new
         * listing
         * this
         * plan, We will have to update that listing status after switching from Pending to Publish through save_post
         * action
         *
         * There is a problem in this action: save_post always be proceeded before update_post_meta,
         * so when updating post_expiry, update_post_meta will override this value. EG: the post_expiry is empty,
         * after We updated this value post_expiry_empty != post_expiry_updated
         *
         */

        if (GetSettings::getPostMeta($postID, $this->needUpdateScheduleKey) == 'yes') {
            SetSettings::setPostMeta($postID, $this->expirationKey, $expirationTimestamp);
            PostMetaModel::setExpiryTimeTemporary($postID, $expirationTimestamp);
        } else {
            SetSettings::setPostMeta($postID, $this->expirationKey, $expirationTimestamp, '', true);
        }

        $this->setScheduleExpiration($postID, $expirationTimestamp);
    }

    /**
     * @param $updated
     * @param $action
     * @param $that
     */
    public function reUpdateCorrectListingExpiry($updated, $action, $that)
    {
        if (GetSettings::getPostMeta($that->object_id, $this->needUpdateScheduleKey) == 'yes') {
            PostMetaModel::updateListingExpiration($that->object_id,
                PostMetaModel::getExpiryTimeTemporary($that->object_id));
            SetSettings::deletePostMeta($that->object_id, $this->needUpdateScheduleKey);
            FileSystem::logSuccess('Reupdate listing expiry for ' . $that->object_id);
        }
    }

    public function setDeleteUnpaidListingSchedule($aInfo): bool
    {
        ## isSubmit means a new submission
        if (!$this->isContributor($aInfo['postAuthor']) || $aInfo['isChangedPlan'] || !$aInfo['isSubmit']) {
            return false;
        }

        // May be customer edit listing
        if (get_post_status($aInfo['postID']) !== 'unpaid') {
            return false;
        }

        return $this->setAutoDeleteUnpaidListing($aInfo['postID']);
    }

    /**
     * @param $oPost
     * @param $planID
     * @return bool
     */
    private function updateListingScheduleExpiration($oPost, $planID): bool
    {
        # Set Auto Delete If it's Unpaid or Expired Listing
        if (in_array($oPost->post_status, ['unpaid', 'expired'])) {
            $this->setAutoDeleteUnpaidListing($oPost->ID);
        } else {
            # Clear Auto Delete If it's Pending or Publish status
            switch ($oPost->post_status) {
                case 'pending':
                    $this->clearAllSchedules($oPost->ID);
                    break;
                case 'publish':
                    if (empty($planID)) {
                        $this->clearAllSchedules($oPost->ID);

                        return false;
                    }
                    $this->clearAutoDeleteUnpaidListing($oPost->ID);

                    $paymentID = PlanRelationshipModel::getPaymentIDByPlanIDAndObjectID($planID, $oPost->ID);
                    $durationGMT = '';
                    if (!empty($paymentID)) {
                        $billingType = PaymentModel::getField('billingType', $paymentID);
                        $isNonRecurringPayment = GetWilokeSubmission::isNonRecurringPayment($billingType);
                    } else {
                        if ((GetWilokeSubmission::getDefaultPlanID($oPost->ID) != $planID) &&
                            !$this->isBackendEditing($oPost->ID, $planID)) {
                            if ($paymentID === 0) {
                                FileSystem::logError('Missing payment id in the plan relationship table. Object ID: '
                                    . $oPost->ID);
                            }

                            return false;
                        }
                        $isNonRecurringPayment = true;
                    }

                    if ($isNonRecurringPayment) {
                        $aPlanSettings = GetSettings::getPlanSettings($planID);
                        if (!empty($aPlanSettings['regular_period'])) {
                            $durationGMT = strtotime('+' . $aPlanSettings['regular_period'] . ' days');
                        }
                    } else {
                        $durationGMT = PaymentMetaModel::getNextBillingDateGMT($paymentID);

                        if (empty($durationGMT) || $durationGMT < \time()) {
//                            $defaultPlan = GetWilokeSubmission::getDefaultPlanID($oPost->ID);
//                            FileSystem::logSuccess("Default Plan " . $defaultPlan);
//                            if ($defaultPlan === $planID) {
                            $aPlanSettings = GetSettings::getPlanSettings($planID);
                            if (!empty($aPlanSettings['regular_period'])) {
                                $aPlanSettings['regular_period'] = absint($aPlanSettings['regular_period']) + 6;
                                $durationGMT = strtotime('+' . $aPlanSettings['regular_period'] . ' days');
                            }
//                            }
                        }
                    }

                    if (!empty($durationGMT)) {
                        $msg = 'AddListing: Added / Changed post expired and post belongs to plan. Duration GMT: ';
                        $msg .= ' Duration GMT:' . $durationGMT . ' - Payment ID:' . $paymentID . ' - Post ID:' .
                            $oPost->ID;
                        $msg .= ' - Expiry On: ' . date(get_option('date_format'), $durationGMT);
                        $msg .= ' - Timestamp Now: ' . current_time('timestamp');

                        $this->setExpiration($oPost->ID, $durationGMT, $isNonRecurringPayment);
                        FileSystem::logSuccess($msg);
                    } else {
                        FileSystem::logSuccess('Plan Settings ' . json_encode($aPlanSettings));
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Customer submitted a listing through front-end and it's keeping pending status. There are 2 scenarios:
     * Listing has been approved immediately => updateListingScheduleExpiration handles it
     * Listing must be waited for reviewing  => maybeUpdateListingSchedule handles it
     *
     * @param $aInfo
     *
     * @return bool
     */
    public function handleListingPlanAfterSubmitting($aInfo)
    {
        if (!empty($aInfo['aUserPlan'])) {
            if (get_post_status($aInfo['postID']) == 'publish') {
                $this->updateListingScheduleExpiration(get_post($aInfo['postID']), $aInfo['planID']);
            } else {
                SetSettings::setPostMeta($aInfo['postID'], $this->needUpdateScheduleKey, 'yes');
            }
        }
    }

    /**
     * Customer submitted a listing through front-end and it's keeping pending status. This function helps to setup
     * Listing Schedule after Admin approved this listing via admin
     *
     * @param $postID
     * @param $oPostAfter
     *
     * @return bool
     */
    public function maybeUpdateListingSchedule($postID, $oPostAfter): bool
    {
        if ($oPostAfter->post_status !== 'publish' || !in_array($oPostAfter->post_type, General::getPostTypeKeys
            (false, false))
        ) {
            return false;
        }

        if (!$this->checkAdminReferrer()) {
            return false;
        }

        if (GetSettings::getPostMeta($postID, $this->needUpdateScheduleKey) === 'yes') {
            $this->updateListingScheduleExpiration($oPostAfter, GetSettings::getListingBelongsToPlan($postID));
        } else {
            if (in_array($oPostAfter->post_status, ['publish', 'pending'])) {
                $this->clearAutoDeleteUnpaidListing($postID);
            }
        }

        return true;
    }

    public function clearScheduleAfterSubmittingListing($aInfo)
    {
        if (!in_array(get_post_status($aInfo['postID']), ['publish', 'pending'])) {
            return false;
        }

        $this->updateListingScheduleExpiration(get_post($aInfo['postID']), $aInfo['planID']);
    }

    public function handleListingPlanAfterUpdatedRecurringPayment($aInfo)
    {
        if (isset($aInfo['postID']) && !empty($aInfo['postID']) && $aInfo['category'] == 'addlisting') {
            $this->updateListingScheduleExpiration(get_post($aInfo['postID']), $aInfo['planID']);
        }
    }

    private function setPostExpiryValueSchedule(int $postId)
    {
        $this->clearAllSchedules($postId);
        wp_schedule_single_event(current_time('timestamp', false) + 120, $this->setExpiryPostValueSchedule, [$postId]);
    }

    private function setPostExpiryEventSchedule(int $postId)
    {
        wp_clear_scheduled_hook($this->setExpiryPostValueSchedule, [$postId]);
        wp_clear_scheduled_hook($this->setExpiryPostEventSchedule, [$postId]);
        wp_schedule_single_event(current_time('timestamp', false) + 120, $this->setExpiryPostEventSchedule, [$postId]);
    }

    public function setSchedulePostEventAfterDeletePost($postId)
    {
        if (!in_array(get_post_type($postId), General::getPostTypeKeys(false, false))) {
            return false;
        }

        $this->setPostExpiryEventSchedule(absint($postId));
    }

    public function maybeSetSchedulePostEventAfterPostUpdated($postId, $post): bool
    {
        if (!in_array($post->post_type, General::getPostTypeKeys(false, false))) {
            return true;
        }

        $postId = absint($postId);

        if ($post->post_status != 'publish') {
            $this->clearAllSchedules($postId);
        } else {
            $this->setPostExpiryValueSchedule($postId);
        }

        return true;
    }

    public function clearAllSchedulesAfterDeletingPostExpiry($metaId, $postId, $metaKey, $metaVal)
    {
        if (!in_array($metaKey, ['wilcity_post_expiry']) ||
            !in_array(get_post_type($postId), General::getPostTypeKeys(false, false))
        ) {
            return false;
        }

        $postId = absint($postId);
        $this->clearAllSchedules($postId);
    }

    public function maybeSetPostEventSchedule($metaId, $postId, $metaKey, $metaVal): bool
    {
        if (!in_array($metaKey, ['wilcity_post_expiry']) ||
            !in_array(get_post_type($postId), General::getPostTypeKeys(false))
        ) {
            return false;
        }

        $postId = absint($postId);
        $this->clearAllSchedules($postId);
        $this->setPostExpiryEventSchedule($postId);

        return true;
    }

    public function maySetExpiryPostValueSchedule($metaId, $postId, $metaKey, $planId): bool
    {
        if (!in_array($metaKey, ['wilcity_belongs_to']) ||
            !in_array(get_post_type($postId), General::getPostTypeKeys(false)) ||
            get_post_status($postId) !== 'publish'
        ) {
            return false;
        }

        $postId = absint($postId);
        $this->setPostExpiryValueSchedule($postId);

        return true;
    }

    public function setNextRecheckPostExpiryEvent($postId): bool
    {
        $this->clearAllSchedules($postId);
        if (get_post_status($postId) !== 'publish') {
            return false;
        }

        $expiryTimestamp = GetSettings::getPostMeta($postId, 'post_expiry');
        if (!empty($expiryTimestamp) && $expiryTimestamp > current_time('timestamp')) {
            $this->setScheduleExpiration($postId, $expiryTimestamp);
        }

        return true;
    }

    public function maybeUpdatePostExpiryValue($postId): bool
    {
        if (!in_array(get_post_type($postId), General::getPostTypeKeys(false))) {
            return false;
        }

        $postId = absint($postId);
        wp_clear_scheduled_hook($this->setExpiryPostValueSchedule, [$postId]);
        $expiryTimestamp = GetSettings::getListingExpiryTimestamp($postId);
        if ($expiryTimestamp < current_time('timestamp')) {
            $belongsTo = GetSettings::getListingBelongsToPlan($postId);
            if (!empty($belongsTo)) {
                $aPlanSettings = GetSettings::getPlanSettings($belongsTo);

                if (isset($aPlanSettings['regular_period']) && !empty($aPlanSettings['regular_period'])) {
                    $expiryTimestamp = strtotime('+ ' . $aPlanSettings['regular_period'] . ' days');
                    SetSettings::setPostMeta($postId, 'post_expiry', $expiryTimestamp);
                }
            }
        }

        return true;
    }

    /*
     * This function is very important. It will setup expiration of Listing after it was updated
     *
     * @since 1.2.0
     *
     */
    public function changedListingPlan($metaId, $postID, $metaKey, $planID)
    {
        if ($metaKey !== 'wilcity_belongs_to' ||
            !in_array(get_post_type($postID), General::getPostTypeKeys(false, false))
        ) {
            return false;
        }

        FileSystem::logSuccess('The listing plan has been changed. Listing ID: ' . $postID . ' Plan ID:' . $planID);
        $oPost = get_post($postID);
        $this->updateListingScheduleExpiration($oPost, $planID);
    }

    public function testUpdatePostMeta($postID, $metakey, $metaValue, $prevValue)
    {
        if ($metakey !== 'wilcity_belongs_to') {
            return false;
        }
        FileSystem::logSuccess('Oke ' . get_the_title($metaValue));
    }

    private function clearScheduled($postId)
    {
        $postId = absint($postId);
        $postIdToString = strval($postId);

        wp_clear_scheduled_hook($this->expirationKey, [$postIdToString]);
        wp_clear_scheduled_hook($this->almostExpiredKey, [$postIdToString]);
        wp_clear_scheduled_hook($this->expirationKey, [$postId]);
        wp_clear_scheduled_hook($this->almostExpiredKey, [$postId]);
        wp_clear_scheduled_hook($this->setExpiryPostValueSchedule, [$postId]);
        wp_clear_scheduled_hook($this->setExpiryPostEventSchedule, [$postId]);
    }

    private function setAutoDeleteUnpaidListing($postID): bool
    {
        $postID = absint($postID);

        $this->clearAutoDeleteUnpaidListing($postID);
        $postID = absint($postID);
        $duration = GetWilokeSubmission::getField('delete_listing_conditional');

        if (empty($duration)) {
            return false;
        }

        $oneDayToTimeStamp = 3600 * 24;
        $now = current_time('timestamp');
        $deleteAt = absint($duration) * $oneDayToTimeStamp;
        $deleteAt = $now + $deleteAt;
        wp_schedule_single_event($deleteAt, $this->deleteUnpaidListing, [$postID]);

        $fDuration = $duration - 1;
        if ($fDuration > 0) {
            $fDuration = absint($fDuration) * $oneDayToTimeStamp;
            $fNotification = $now + $fDuration;
            wp_schedule_single_event($fNotification, $this->fNotificationAlmostDeletePost, [$postID]);
            SetSettings::setPostMeta($postID, 'fwarning_delete_listing', $fNotification);

            $sDuration = $duration - 2;
            if ($sDuration > 0) {
                $sDuration = absint($sDuration) * $oneDayToTimeStamp;
                $sNotification = $now + $sDuration;
                wp_schedule_single_event($sNotification, $this->sNotificationAlmostDeletePost, [$postID]);
                SetSettings::setPostMeta($postID, 'swarning_delete_listing', $sNotification);
            }

            $tDuration = $duration - 3;
            if ($tDuration > 0) {
                $tDuration = absint($tDuration) * $oneDayToTimeStamp;
                $tNotification = $now + $tDuration;
                wp_schedule_single_event($tNotification, $this->tNotificationAlmostDeletePost, [$postID]);
                SetSettings::setPostMeta($postID, 'twarning_delete_listing', $tNotification);
            }
        }

        return true;
    }

    public function focusPostExpiration($postID)
    {
        $duration = GetSettings::getPostMeta($postID, $this->expirationKey);
        if (!empty($duration)) {
            $this->setScheduleExpiration($postID, $duration);
        }
    }

    public function changePostsStatusByPaymentID($paymentID, $status)
    {
        $aPostIDs = PlanRelationshipModel::getObjectIDsByPaymentID($paymentID);

        if (empty($aPostIDs)) {
            return false;
        }

        foreach ($aPostIDs as $aPost) {
            SetSettings::setPostMeta($aPost['objectID'], 'old_status', get_post_status($aPost['objectID']));
            wp_update_post(
                [
                    'ID'          => $aPost['objectID'],
                    'post_status' => $status
                ]
            );
        }
    }

    protected function migratePostAfterRenewPayment($paymentID, $nextBillingDateGMT)
    {
        $aPostIDs = PlanRelationshipModel::getObjectIDsByPaymentID($paymentID);

        if (empty($aPostIDs)) {
            return false;
        }

        foreach ($aPostIDs as $aPost) {
            $oldStatus = GetSettings::getPostMeta($aPost['objectID'], 'oldPostStatus');
            SetSettings::deletePostMeta($aPost['objectID'], 'oldPostStatus');
            SetSettings::setPostMeta($aPost['objectID'], 'durationTimestampUTC', $nextBillingDateGMT);

            if (empty($oldStatus)) {
                $oldStatus = get_post_status($aPost['objectID']);
            }

            if ($oldStatus == 'publish' || $oldStatus == 'expired') {
                $status = 'publish';
            } else {
                if ($oldStatus != 'pending') {
                    $approvalMethod = GetWilokeSubmission::getField('approved_method');
                    $status = $approvalMethod == 'manual_review' ? 'pending' : 'publish';
                } else {
                    $status = 'pending';
                }
            }

            wp_update_post(
                [
                    'ID'          => $aPost['objectID'],
                    'post_status' => $status
                ]
            );
        }
    }

    public function migratePostsToExpiredStatus($paymentID)
    {
        $this->changePostsStatusByPaymentID($paymentID, 'expired');
    }

    public function migratePostsToDraftStatus($paymentID)
    {
        $this->changePostsStatusByPaymentID($paymentID, 'draft');
    }

    public function migratePostsToPublishStatus($paymentID)
    {
        $this->changePostsStatusByPaymentID($paymentID, 'publish');
    }

    public function migratePostsToPendingStatus($paymentID)
    {
        $this->changePostsStatusByPaymentID($paymentID, 'pending');
    }

    protected function detectNewPostStatus($postID)
    {
        $postStatus = get_post_status($postID);
        if (Submission::listingStatusWillPublishImmediately($postStatus)) {
            return 'publish';
        } else {
            $oldPostStatus = GetSettings::getPostMeta($postID, 'oldPostStatus');

            return Submission::listingStatusWillPublishImmediately($oldPostStatus) ? 'publish' :
                Submission::detectPostStatus();
        }
    }

    /*
     * Upgrading all listings belong to previous plan to new plan (Change Plan session)
     *
     * @since 1.2.0
     */
    public function upgradeAllListingsBelongsToOldOldPlanToNewPlan(\WC_Subscription $that)
    {
        $orderID = $that->get_parent_id();
        $lastPaymentID = PaymentModel::getPaymentIDsByWooOrderID($orderID, true);

        if (!empty($lastPaymentID)) {
            $oldOrderID = PaymentMetaModel::get($lastPaymentID, 'oldOrderID');
            if (!empty($oldOrderID)) {
                $aOldPaymentIDs = PaymentModel::getPaymentIDsByWooOrderID($oldOrderID);
                PaymentMetaModel::delete($lastPaymentID, 'oldOrderID');
                if (empty($aOldPaymentIDs)) {
                    return false;
                }
            } else {
                $oldPaymentID = PaymentMetaModel::get($lastPaymentID, 'oldPaymentID');
                if (empty($aOldObjectIDs)) {
                    return false;
                }

                $aOldPaymentIDs = [
                    [
                        'ID' => $oldPaymentID
                    ]
                ];
            }

            $this->expiredAt = strtotime($that->get_date('next_payment'));
            $planID = PaymentModel::getField('planID', $lastPaymentID);

            foreach ($aOldPaymentIDs as $aOldPaymentID) {
                $aOldObjectIDs = PlanRelationshipModel::getObjectIDsByPaymentID($aOldPaymentID['ID']);
                if (empty($aOldObjectIDs)) {
                    continue;
                }
                $oldPlanID = PaymentModel::getField('planID', $aOldPaymentID['ID']);
                $this->inCaseToPublish($aOldObjectIDs, [
                    'planID'  => $planID,
                    'oldPlan' => $oldPlanID,
                    'orderID' => $orderID
                ], __METHOD__);
            }
        }
    }

    /*
     * Updating Expiry date for listing and post status after the payment has been completed successfully.
     * Note that We only process this task if it's not subscription
     *
     * We are using woocommerce_subscription_payment_complete https://docs.woocommerce.com/document/subscriptions/develop/action-reference/
     * This hook runs after wiloke-listing-tools/woocommerce/after-order-succeeded
     *
     * @since 1.1.7.3
     */
    public function afterSubscriptionPaymentComplete(\WC_Subscription $that)
    {
        $nextPayment = $that->get_date('next_payment');

        $aPaymentIDs = PaymentModel::getPaymentIDsByWooOrderID($that->get_parent_id());
        if (empty($aPaymentIDs)) {
            return false;
        }

        $this->expiredAt = strtotime($nextPayment);

        foreach ($aPaymentIDs as $aPaymentID) {
            PaymentModel::updatePaymentStatus('succeeded', $aPaymentID['ID']);
            $aObjectIDs = PlanRelationshipModel::getObjectIDsByPaymentID($aPaymentID['ID']);
            if (empty($aObjectIDs)) {
                continue;
            }

            $planID = PaymentModel::getField('planID', $aPaymentID['ID']);
            $this->inCaseToPublish($aObjectIDs, [
                'orderID' => $that->get_parent_id(),
                'planID'  => $planID
            ], __METHOD__);
        }
    }

    /*
     * After the subscription changed status https://docs.woocommerce.com/document/subscriptions/develop/action-reference/
     * We will change listings status that belong to order
     *
     * @since 1.1.7.3
     */
    public function moveAllListingToDraftAfterSubscriptionChangedStatus(\WC_Subscription $that, $newStatus, $oldStatus)
    {
        if ($oldStatus == $newStatus || $oldStatus !== 'active') {
            return false;
        }

        switch ($newStatus) {
            case 'pending-cancel':
            case 'cancelled':
            case 'on-hold':
                $orderID = $that->get_parent_id();
                $aSessionIDs = PaymentModel::getPaymentIDsByWooOrderID($orderID);

                if (empty($aSessionIDs)) {
                    return false;
                }

                foreach ($aSessionIDs as $aSession) {
                    $aPaymentIDs[] = $aSession['ID'];
                    $this->migratePostsToExpiredStatus($aSession['ID']);
                }
                break;
        }
    }

    /*
     * After the subscription re-activated https://docs.woocommerce.com/document/subscriptions/develop/action-reference/
     * We will change listings status to Pending / Publish status
     *
     * @since 1.1.7.3
     */
    public function moveAllListingToPendingOrPublishStatus(\WC_Subscription $that, $newStatus, $oldStatus)
    {
        if ($newStatus != 'active' || $oldStatus == $newStatus || !in_array($oldStatus, [
                'on-hold',
                'pending-cancel',
                'expired',
                'pending'
            ])
        ) {
            return false;
        }

        $aPaymentIDs = PaymentModel::getPaymentIDsByWooOrderID($that->get_parent_id());
        if (empty($aPaymentIDs)) {
            return false;
        }

        $nextBillingDateGMT = $that->get_date('next_payment');
        $nextBillingDateGMT = strtotime($nextBillingDateGMT);
        $nextBillingDateGMT = $this->getExpiredListingTime($nextBillingDateGMT);

        foreach ($aPaymentIDs as $aPaymentID) {
            $this->migratePostAfterRenewPayment($aPaymentID['ID'], $nextBillingDateGMT);
        }
    }

    /*
     *  After a Subscription is changed its status, We will change Listings that belongs to this order as well
     *
     * @var dateTime: It's next billing date. It's not timestamp, It's date time with human friendly format.
     * @since 1.1.7.3
     */
    public function afterUpdatedSubscriptionNextPayment(\WC_Subscription $that, $dateType, $dateTime)
    {
        if ($dateType == 'next_payment') {
            $nextBillingDateGMT = strtotime($dateTime);
            $nextBillingDateGMT = $this->getExpiredListingTime($nextBillingDateGMT);

            $aPaymentIDs = PaymentModel::getPaymentIDsByWooOrderID($that->get_parent_id());
            if (empty($aPaymentIDs)) {
                return false;
            }

            foreach ($aPaymentIDs as $aPaymentID) {
                $this->migratePostAfterRenewPayment($aPaymentID['ID'], $nextBillingDateGMT);
            }
        }
    }

    /*
     * Updating Expiry date for listing and post status after the payment has been completed successfully.
     * Note that We only process this task if it's not subscription
     *
     * @var PostController $callFromWhere it's for debug
     * @var PostController $aData Required: contains orderID, planID Maybe: oldPlanID
     * @since 1.0
     */
    protected function inCaseToPublish($aObjectIDs, $aData, $callFromWhere = '')
    {
        if (!is_array($aObjectIDs)) {
            return false;
        }

        foreach ($aObjectIDs as $aObjectID) {
            if (!empty($aObjectID['objectID'])) {
                $postStatus = $this->detectNewPostStatus($aObjectID['objectID']);
                $aPlanSettings = GetSettings::getPlanSettings($aData['planID']);

                if (!isset($aData['orderID']) || empty($aData['orderID']) ||
                    !WooCommerceHelpers::isSubscription($aData['orderID'])
                ) {
                    $duration = '';
                    if (isset($aData['nextBillingDateGMT']) && !empty($aData['nextBillingDateGMT'])) {
                        $duration = $aData['nextBillingDateGMT'];
                        $isBillingDate = true;
                    } else {
                        if (isset($aData['isTrial']) && !empty($aData['isTrial'])) {
                            $duration = $aPlanSettings['trial_period'];
                        }

                        if (empty($duration)) {
                            $duration = $aPlanSettings['regular_period'];
                        }
                        $isBillingDate = false;
                    }
                } else {
                    $isBillingDate = true;
                    $duration = $this->expiredAt;
                }

                if ($isBillingDate) {
                    SetSettings::setPostMeta($aObjectID['objectID'], 'durationTimestampUTC', $duration);
                } else {
                    SetSettings::setPostMeta($aObjectID['objectID'], 'duration', $duration);
                }

                $listingOrder = 0;
                if (isset($aData['objectID'])) {
                    $listingOrder = get_post_field('menu_order', $aData['objectID']);
                    $listingOrder = empty($listingOrder) ? 0 : absint($listingOrder);

                    if (isset($aData['oldPlanID']) && !empty($aData['oldPlanID'])) {
                        $oldPlanID = $aData['oldPlanID'];
                    } else {
                        $oldPlanID = GetSettings::getPostMeta($aObjectID['objectID'], 'oldPlanID');
                        SetSettings::deletePostMeta($aObjectID['objectID'], 'oldPlanID');
                    }

                    if (!empty($oldPlanID)) {
                        $aOldPlanSettings = GetSettings::getPlanSettings($oldPlanID);
                        if (!empty($aOldPlanSettings)) {
                            $oldPlanOrder
                                = isset($aOldPlanSettings['menu_order']) && !empty($aOldPlanSettings['menu_order']) ?
                                absint($aOldPlanSettings['menu_order']) : 0;
                            $listingOrder = $listingOrder - $oldPlanOrder;
                            $listingOrder = $listingOrder > 0 ? $listingOrder : 0;
                        }
                    }
                }

                if (isset($aPlanSettings['menu_order']) && !empty($aPlanSettings['menu_order'])) {
                    $listingOrder = empty($listingOrder) ? absint($aPlanSettings['menu_order']) :
                        $listingOrder + absint($aPlanSettings['menu_order']);
                }

                $this->updatedExpirationTime = true;

                $aPostData = [
                    'ID'          => $aObjectID['objectID'],
                    'post_status' => $postStatus,
                    'menu_order'  => $listingOrder
                ];
                wp_update_post($aPostData);
            }
        }
    }

    /*
     * Changing all listings belong to this plan to publish status
     * This is for non recurring payment only
     *
     * @since 1.2.0
     */
    public function migrateAllListingsBelongsToWooCommerceToPublish($aResponse)
    {
        if (!GetWilokeSubmission::isNonRecurringPayment()) {
            return false;
        }

        $aPaymentIDs = PaymentModel::getPaymentIDsByWooOrderID($aResponse['orderID']);
        if (empty($aPaymentIDs)) {
            return false;
        }

        foreach ($aPaymentIDs as $aPaymentID) {
            PaymentModel::updatePaymentStatus('succeeded', $aPaymentID['ID']);
            $aObjectIDs = PlanRelationshipModel::getObjectIDsByPaymentID($aPaymentID['ID']);
            if (empty($aObjectIDs)) {
                continue;
            }
            $this->inCaseToPublish($aObjectIDs, $aResponse, __METHOD__);
        }
    }

    public function migratePostsToPendingOrPublishStatus($paymentID)
    {
        $aPostIDs = PlanRelationshipModel::getObjectIDsByPaymentID($paymentID);

        if (empty($aPostIDs)) {
            return false;
        }

        foreach ($aPostIDs as $aPost) {
            $newStatus = $this->detectNewPostStatus($aPost['objectID']);
            wp_update_post(
                [
                    'ID'          => $aPost['objectID'],
                    'post_status' => $newStatus
                ]
            );
        }
    }

    public function moveAllPostsToUnPaid($aData)
    {
        $aObjectIDs = PlanRelationshipModel::getObjectIDsByPaymentID($aData['paymentID']);
        if (empty($aObjectIDs)) {
            return false;
        }
        foreach ($aObjectIDs as $aObjectID) {
            if (!empty($aObjectID['objectID'])) {
                wp_update_post(
                    [
                        'ID'          => $aObjectID['objectID'],
                        'post_status' => 'unpaid'
                    ]
                );
            }
        }
    }

    public function moveAllPostsToTrash($aData)
    {
        $aObjectIDs = PlanRelationshipModel::getObjectIDsByPaymentID($aData['paymentID']);
        if (empty($aObjectIDs)) {
            return false;
        }
        foreach ($aObjectIDs as $aObjectID) {
            if (!empty($aObjectID['objectID'])) {
                wp_update_post(
                    [
                        'ID'          => $aObjectID['objectID'],
                        'post_status' => 'expired'
                    ]
                );
            }
        }
    }

    public function migrateToPublish($aData)
    {
        if (GetWilokeSubmission::getField('approved_method') == 'manual_review' && isset($aData['postID'])) {
            $oldPostStatus = GetSettings::getPostMeta($aData['postID'], 'oldPostStatus');
            if (GetWilokeSubmission::isNonRecurringPayment($aData) && !empty($oldPostStatus)) {
                $this->migrateListingAfterUpgrading($aData, $oldPostStatus);

                return true;
            }
        }

        $aObjectIDs = PlanRelationshipModel::getObjectIDsByPaymentID($aData['paymentID']);
        if (empty($aObjectIDs)) {
            return false;
        }

        $this->inCaseToPublish($aObjectIDs, $aData, __METHOD__);
    }

    /**
     * If it's upgraded plan, We need to change that Listing status and Listing Expired
     *
     * @since 1.2.0
     */
    public function migrateListingAfterUpgrading($aData, $oldPostStatus)
    {
        if (!isset($aData['postID']) || empty($aData['postID'])) {
            return false;
        }

        $aPostTypeKeys = Submission::getAddListingPostTypeKeys();

        if (!in_array(get_post_type($aData['postID']), $aPostTypeKeys)) {
            return false;
        }

        if ($oldPostStatus == 'publish') {
            $this->inCaseToPublish([
                'objectID' => $aData['postID']
            ], $aData, __METHOD__);
        }
    }

    public function moveAllPostsToExpiry($aData)
    {
        $aObjectIDs = PlanRelationshipModel::getObjectIDsByPaymentID($aData['paymentID']);
        if (empty($aObjectIDs)) {
            return false;
        }

        foreach ($aObjectIDs as $aObjectID) {
            if (!empty($aObjectID['objectID'])) {
                wp_update_post(
                    [
                        'ID'          => $aObjectID['objectID'],
                        'post_status' => 'expired'
                    ]
                );
            }
        }
    }

    /*
     * Upgrading Listing and the payment was failed
     *
     * @since 1.2.0
     */
    public function rollupListingToPreviousStatus($aData)
    {
        if (isset($aData['postID']) && !empty($aData['postID'])) {
            $oldPostStatus = GetSettings::getPostMeta($aData['postID'], 'oldPostStatus');
            if (!empty($oldPostStatus)) {
                $oldPlanID = GetSettings::getPostMeta($aData['postID'], 'oldPlanID');
                SetSettings::setPostMeta($aData['postID'], 'belongs_to', $oldPlanID);
                wp_update_post(
                    [
                        'ID'          => $aData['postID'],
                        'post_status' => $oldPostStatus
                    ]
                );

                SetSettings::deletePostMeta($aData['postID'], 'oldPostStatus');
                SetSettings::deletePostMeta($aData['postID'], 'oldPlanID');
            }
        }
    }

    /*
     * Updating Expiry Schedule if Administrator changed Expiration value via back-end
     *
     *
     * @since 1.0
     */
    public function updateExpirationViaAdmin($metaID, $postID, $metaKey, $expirationTimestamp)
    {
        General::deprecatedFunction(__METHOD__, 'PostController:updatePostExpiration', '1.2.5');

        $this->directlyUpdatedExpirationDate = false;
        if (!General::isAdmin() || get_post_status($postID) != 'publish' || $metaKey != 'wilcity_post_expiry') {
            return false;
        }

        if (!check_admin_referer('wilcity_admin_security', 'wilcity_admin_nonce_field')) {
            return false;
        }

        if (empty($expirationTimestamp)) {
            return true;
        }
        $this->directlyUpdatedExpirationDate = true;

        $this->setScheduleExpiration($postID, $expirationTimestamp);
    }

    public function adminSubmittedListing($aInfo)
    {
        $aPlanSettings = GetSettings::getPlanSettings($aInfo['planID']);
        if (isset($aPlanSettings['regular_period']) && !empty($aPlanSettings['regular_period'])) {
            SetSettings::setPostMeta($aInfo['postID'], $this->expirationKey,
                strtotime('+' . $aPlanSettings['regular_period'] . ' days'));
        }
    }

    /**
     * Auto Add Listing Expiry if customer set Listing belongs to an external plan
     *
     * @since 1.0
     */
    public function autoSetExpirationViaAdmin($metaID, $postID, $metaKey, $planID)
    {
        General::deprecatedFunction(__METHOD__, 'PostController:updatePostExpiration', '1.2.5');

        if ($metaKey != 'wilcity_belongs_to' || !General::isAdmin()) {
            return false;
        }

        if ($this->directlyUpdatedExpirationDate || get_post_status($postID) != 'publish') {
            return false;
        }

        if (!check_admin_referer('wilcity_admin_security', 'wilcity_admin_nonce_field')) {
            return false;
        }

        $status = apply_filters('wilcity/wiloke-listing-tools/filter/auto-set-expiration-via-admin', true, $postID,
            $planID);

        if (!$status) {
            return false;
        }

        $aPlanSettings = GetSettings::getPlanSettings($planID);
        if (isset($aPlanSettings['regular_period']) && !empty($aPlanSettings['regular_period'])) {
            SetSettings::setPostMeta($postID, $this->expirationKey,
                strtotime('+' . $aPlanSettings['regular_period'] . ' days'));
        }
    }
}
