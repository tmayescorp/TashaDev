<?php

namespace WilokeListingTools\Controllers;

use Exception;
use Stripe\Util\Set;
use WC_Order;
use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Payment\PaymentGatewayStaticFactory;
use WilokeListingTools\Framework\Payment\PayPal\PayPalExecuteNonRecurringPayment;
use WilokeListingTools\Framework\Payment\PayPal\PayPalExecutePromotionPayment;
use WilokeListingTools\Framework\Payment\Receipt\ReceiptStaticFactory;
use WilokeListingTools\Framework\Payment\WooCommerce\WooCommerceNonRecurringPaymentMethod;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;
use WilokeListingTools\Models\PostModel;
use WilokeListingTools\Models\PromotionModel;

class PromotionController extends Controller
{
    protected $aPromotionPlans;
    protected $aWoocommercePlans;
    protected $expirationHookName            = 'expiration_promotion';
    private   $belongsToPromotionKey         = 'belongs_to_promotion';
    private   $category                      = 'promotion';
    protected $logFileName                   = 'promotion-success.log';
    private   $oReceipt;
    private   $isNonRecurringPayment         = true;
    private   $gateway;
    private   $aSelectedPlans;
    private   $aSelectedPlanKeys;
    private   $handlePromotionEvent          = 'wilcity_handle_promotion_event';
    private   $handlePromotionPositionExpiry = 'wilcity_handle_promotion_position_expiry';

    public function __construct()
    {
        add_action('wp_ajax_wilcity_fetch_promotion_plans', [$this, 'fetchPromotions']);
        add_action('wp_ajax_wilcity_get_payment_gateways', [$this, 'getPaymentGateways']);
        add_action('wp_ajax_wilcity_boost_listing', [$this, 'boostListing']);

        add_action('updated_post_meta', [$this, 'updatedListingPromotionMeta'], 999, 2);
        add_action('added_post_meta', [$this, 'updatedListingPromotionMeta'], 999, 2);
        add_action('save_post_promotion', [$this, 'updatePromotion'], 10);
        add_action('trashed_post', [$this, 'updatePromotion'], 10);
        add_action('before_delete_post', [$this, 'deletePromotion'], 100);

        add_action('wilcity/single-listing/sidebar-promotion', [$this, 'printOnSingleListing'], 10, 2);
        add_action('wp_ajax_wilcity_fetch_listing_promotions', [$this, 'fetchPromotionDetails']);
        add_action('wilcity/wiloke-listing-tools/inserted-payment', [$this, 'createPromotion']);

        $aBillingTypes = wilokeListingToolsRepository()->get('payment:billingTypes');
        foreach ($aBillingTypes as $billingType) {
            add_action(
                'wilcity/wiloke-listing-tools/' . $billingType . '/payment-completed',
                [$this, 'updatePromotionAfterPaymentCompleted'],
                15
            );

            add_action(
                'wilcity/wiloke-listing-tools/' . $billingType . '/payment-refunded',
                [$this, 'removePromotionAfterPaymentRefunded'],
                15
            );
        }

        add_action('init', [$this, 'paymentExecution'], 1);
        add_action('woocommerce_checkout_order_processed', [$this, 'purchasePromotionPlansThroughWooCommerce'], 5, 1);

        add_action($this->handlePromotionEvent, [$this, 'handlePromotion']);
        add_action($this->handlePromotionPositionExpiry, [$this, 'clearPromotionPositionAfterExpirationDate'], 10, 3);
    }

    /**
     * @param $aPromotion
     * @return string
     */
    private function generatePositionMetaKey(array $aPromotion): string
    {
        return 'promote_' . GetSettings::generateSavingPromotionDurationKey($aPromotion);
    }

    private function getPromotionInfoByMetaKey($metaKey)
    {
        $metaKey = str_replace('promote_', '', $metaKey);
        $aPromotions = $this->getPromotionPlans();
        foreach ($aPromotions as $aPromotion) {
            if (GetSettings::generateSavingPromotionDurationKey($aPromotion) == $metaKey) {
                return $aPromotion;
            } else if ($aPromotion['position'] == $metaKey) {
                return $aPromotion;
            }
        }

        return [];
    }

    public function paymentExecution(): bool
    {
        if (
            !isset($_GET['category']) || !in_array($_GET['category'], ['promotion'])
            || !isset($_GET['token']) || empty($_GET['token'])
            || Session::getSession('waiting_for_paypal_execution') !== 'yes'
        ) {
            return false;
        }

        $oPayPalMethod = new PayPalExecutePromotionPayment(new PayPalExecuteNonRecurringPayment());

        if (!$oPayPalMethod) {
            return false;
        }

        if ($oPayPalMethod->verify()) {
            $aResponse = $oPayPalMethod->execute();
            if ($aResponse['status'] == 'error') {
                Session::setSession('payment_error', $aResponse['msg']);
                FileSystem::logError($aResponse['msg'], __CLASS__, __METHOD__);
            }
        }

        return true;
    }

    public function fetchPromotionDetails()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $aData = isset($_GET['postID']) ? $_GET : $_POST;

        if (!isset($aData['postID']) || empty($aData['postID'])) {
            $oRetrieve->error([
                'msg' => esc_html__('The post id is required.', 'wiloke-listing-tools'),
            ]);
        }

        $aRawPromotions = PromotionModel::getListingPromotions($aData['postID']);
        if (empty($aRawPromotions)) {
            $oRetrieve->error([
                'msg' => esc_html__('There are no promotions.', 'wiloke-listing-tools'),
            ]);
        }

        $aPromotions = [];
        $aRawPromotionPlans = GetSettings::getPromotionPlans();

        $aPromotionPlans = [];
        foreach ($aRawPromotionPlans as $promotionKey => $aPlan) {
            $aPromotionPlans[$promotionKey] = $aPlan;
        }

        foreach ($aRawPromotions as $aPromotion) {
            $position = str_replace('wilcity_promote_', '', $aPromotion['meta_key']);
            $aPromotions[] = [
                'name'     => $aPromotionPlans[$position]['name'],
                'position' => $position,
                'preview'  => $aPromotionPlans[$position]['preview'],
                'expiryOn' => date_i18n(get_option('date_format'), $aPromotion['meta_value'])
            ];
        }

        $oRetrieve->success($aPromotions);
    }

    public function printOnSingleListing($post, $aSidebarSetting)
    {
        $aSidebarSetting = wp_parse_args($aSidebarSetting, [
            'name'        => '',
            'conditional' => '',
            'promotionID' => '',
            'style'       => 'slider'
        ]);

        $belongsTo = GetSettings::getPostMeta($post->ID, 'belongs_to');

        if (!empty($belongsTo) && !GetSettings::isPlanAvailableInListing($post->ID, 'toggle_promotion')) {
            return '';
        }

        $aPromotionSettings = GetSettings::getPromotionSetting($aSidebarSetting['promotionID']);

        if (!is_array($aPromotionSettings)) {
            return $aPromotionSettings;
        }

        if ($aPromotionSettings['name']) {
            unset($aPromotionSettings['name']);
        }

        $aSidebarSetting = array_merge($aSidebarSetting, $aPromotionSettings);

        $aSidebarSetting['orderby'] = 'rand';
        $aSidebarSetting['order'] = 'DESC';
        $aSidebarSetting['postType'] = General::getPostTypeKeys(false, false);
        $aSidebarSetting['aAdditionalArgs']['meta_query'] = [
            [
                'key'     => GetSettings::generateListingPromotionMetaKey($aSidebarSetting, true),
                'compare' => 'EXISTS',
            ]
        ];

        $aAtts = [
            'atts' => $aSidebarSetting
        ];

        echo wilcitySidebarRelatedListings($aAtts);
    }

    public function deletePromotion($postID): bool
    {
        if (get_post_type($postID) != 'promotion') {
            return false;
        }

        $listingID = GetSettings::getPostMeta($postID, 'listing_id');
        if (empty($listingID)) {
            return false;
        }

        $this->deleteAllPlansOfListing($listingID);

        return true;
    }

    /**
     * @param $promotionId
     * @return bool
     */
    private function movePromotionToTrashAfterAllPlansExpirationDate($promotionId): bool
    {
        $aPromotionPlans = $this->getPromotionPlans();

        $isExpiredAll = true;
        $now = current_time('timestamp');

        foreach ($aPromotionPlans as $aPlanSetting) {
            $val = GetSettings::getPostMeta($promotionId, $this->generatePositionMetaKey($aPlanSetting), '', '', true);
            if (!empty($val)) {
                $val = absint($val);
                if ($val > $now) {
                    $isExpiredAll = false;
                }
            }
        }

        if ($isExpiredAll) {
            PostModel::updatePostStatus($promotionId, 'trash');
            $listingId = GetSettings::getPostMeta($promotionId, 'listing_id');

            if (!empty($listingId)) {
                SetSettings::deletePostMeta($listingId, $this->belongsToPromotionKey);
            }

            do_action(
                'wilcity/wiloke-listing-tools/app/Controllers/PromotionController/movePromotionToTrashAfterAllPlansExpirationDate/after-promotion-expired',
                [
                    'listingId'   => $listingId,
                    'promotionId' => $promotionId
                ]
            );
        }

        return true;
    }

    /**
     * @param $position
     * @return bool
     */
    private function isTopOfSearch($position): bool
    {
        return strpos($position, 'top_of_search') !== false;
    }

    /**
     * @param int $listingId
     * @param int $promotionId
     * @throws Exception
     */
    private function addPromotionPlansToListing(int $listingId, int $promotionId)
    {
        $aPromotions = $this->getPromotionPlans();

        if ($aPromotions) {
            foreach ($aPromotions as $aPromotion) {
                $metaKey = $this->generatePositionMetaKey($aPromotion);
                $val = GetSettings::getPostMeta($promotionId, $metaKey);

                if (!empty($val)) {
                    if ($this->isTopOfSearch($aPromotion['position'])) {
                        $this->updateMenuOrder($listingId, $metaKey);
                    } else {
                        SetSettings::setPostMeta($listingId, $metaKey, $val);
                    }
                    $this->setPromotionPositionExpiryEvent($promotionId, $listingId, $metaKey, absint($val), true);
                } else {
//                    if (!$this->isTopOfSearch($aPromotion['position'])) {
//                        $this->updateMenuOrder($listingId, $metaKey, false);
//                    } else {
//                        SetSettings::deletePostMeta($listingId, $metaKey);
//                    }
//                    $this->setPromotionPositionExpiryEvent($promotionId, $listingId, $metaKey, absint($val), true);
                }
            }

            $isUpdated = GetSettings::getPostMeta($promotionId, 'is_boosted_promotion') === 'yes';
            SetSettings::setPostMeta($promotionId, 'is_boosted_promotion', 'yes');
            SetSettings::setPostMeta($listingId, $this->belongsToPromotionKey, $promotionId);

            do_action(
                'wilcity/wiloke-listing-tools/app/Controllers/PromotionController/addPromotionPlansToListing/after-added',
                [
                    'isUpdated'   => $isUpdated,
                    'listingId'   => $listingId,
                    'promotionId' => $promotionId
                ]
            );
        }
    }

    private function deleteAllPlansOfListing($listingId): bool
    {
        $aPromotionPlans = $this->getPromotionPlans();
        foreach ($aPromotionPlans as $aPromotionPlan) {
            if ($this->isTopOfSearch($aPromotionPlan['position'])) {
                $val = GetSettings::getPostMeta($listingId, $this->generatePositionMetaKey($aPromotionPlan));
                if (!empty($val)) {
                    $this->updateMenuOrder($listingId, $aPromotionPlan['position'], false);
                }
            }
            SetSettings::deletePostMeta($listingId, $this->generatePositionMetaKey($aPromotionPlan));
        }

        $promotionId = GetSettings::getPostMeta($listingId, $this->belongsToPromotionKey);
        SetSettings::deletePostMeta($listingId, $this->belongsToPromotionKey);

        do_action(
            'wilcity/wiloke-listing-tools/app/Controllers/PromotionController/deleteAllPlansOfListing/after-promotion-expired',
            [
                'listingId'   => $listingId,
                'promotionId' => $promotionId
            ]
        );
        return true;
    }

    private function generateScheduleKey($position): string
    {
        return 'trigger_promote_' . $position . '_expired';
    }

    protected function getPromotionPlans(): ?array
    {
        $this->aPromotionPlans = GetSettings::getPromotionPlans();

        return $this->aPromotionPlans;
    }

    protected function getPromotionPlanKeys(): array
    {
        return is_array($this->getPromotionPlans()) ? array_keys($this->aPromotionPlans) : [];
    }

    public function getPromotionField($field)
    {
        $this->getPromotionPlans();

        return $this->aPromotionPlans[$field] ?? false;
    }

    /*
     * Updating Listing Order
     *
     * @since 1.0
     */
    private function updateMenuOrder($listingID, $promotionKey, $isPlus = true)
    {
        $promotionKey = str_replace('promote_', '', $promotionKey);

        $aTopOfSearchSettings = $this->getPromotionField($promotionKey);

        if ($aTopOfSearchSettings) {
            $menuOrder = get_post_field('menu_order', $listingID);
            if ($isPlus) {
                $menuOrder = absint($menuOrder) + absint($aTopOfSearchSettings['menu_order']);
            } else {
                $menuOrder = absint($menuOrder) - absint($aTopOfSearchSettings['menu_order']);
            }

            global $wpdb;
            $status = $wpdb->update(
                $wpdb->posts,
                [
                    'menu_order' => absint($menuOrder)
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
        }
    }

    /**
     * @throws Exception
     */
    public function handlePromotion($promotionId): bool
    {
        $listingID = GetSettings::getPostMeta($promotionId, 'listing_id');
        if (empty($listingID)) {
            return false;
        }

        $listingID = absint($listingID);
        $promotionId = absint($promotionId);

        wp_clear_scheduled_hook($this->handlePromotionEvent, [$promotionId]);
        $promotionStatus = get_post_status($promotionId);

        if ($promotionStatus !== 'publish') {
            $this->deleteAllPlansOfListing($listingID);
        } else {
            $this->addPromotionPlansToListing($listingID, $promotionId);
        }

        return true;
    }

    /**
     * Set a expiry promotion cron job
     *
     * $position This var contains promotion ID already
     * @return bool
     * @since 1.2.0
     */
    public function updatedListingPromotionMeta($metaID, $objectID): bool
    {
        if ((get_post_type($objectID) !== 'promotion')) {
            return false;
        }

        $this->setupHandlePromotionEvent(absint($objectID));

        return true;
    }

    /**
     * @param int $promotionId
     */
    private function setupHandlePromotionEvent(int $promotionId): void
    {
        $listingId = GetSettings::getPostMeta(
            $promotionId,
            'listing_id',
            '',
            'int',
            true
        );

        if (empty($listingId)) {
            return;
        }

        wp_clear_scheduled_hook($this->handlePromotionEvent, [$promotionId]);

        if (get_post_status($promotionId) == 'publish') {
            wp_schedule_single_event(\time() + 30, $this->handlePromotionEvent, [$promotionId]);
        } else {
            wp_schedule_single_event(\time() + 60 * 10, $this->handlePromotionEvent, [$promotionId]);
        }

    }

    /**
     * @param int $promotionId
     * @param int $listingId
     * @param string $positionMetaKey It follows this structure: promote_[position]_[id]
     * @param int $expiryTimestamp
     * @param false $isClearOnly
     * @throws Exception
     */
    private function setPromotionPositionExpiryEvent(
        $promotionId,
        $listingId,
        $positionMetaKey,
        $expiryTimestamp,
        $isClearOnly = false
    )
    {
        $expiryTimestamp = absint($expiryTimestamp);
        if (get_post_type($promotionId) !== 'promotion') {
            throw new Exception('The $promotionId param must be a promotion id');
        }

        if (!in_array(get_post_type($listingId), General::getPostTypeKeys(false))) {
            throw new Exception('The $listingId param must be a listing type id');
        }

        wp_clear_scheduled_hook(
            $this->handlePromotionPositionExpiry,
            [
                $promotionId,
                $listingId,
                $positionMetaKey
            ]
        );

        if (!$isClearOnly) {
            wp_schedule_single_event(
                $expiryTimestamp,
                $this->handlePromotionPositionExpiry,
                [
                    $promotionId,
                    $listingId,
                    $positionMetaKey
                ]
            );
        }
    }

    /**
     * @param $promotionId
     * @param $listingId
     * @param $positionMetaKey
     * @return bool
     */
    public function clearPromotionPositionAfterExpirationDate($promotionId, $listingId, $positionMetaKey): bool
    {
        $oPost = get_post($listingId);

        if (empty($oPost) || is_wp_error($oPost)) {
            return false;
        }

        $promotionId = absint($promotionId);
        $listingId = absint($listingId);
        $promotionStatus = get_post_status($promotionId);

        if ($promotionStatus != 'publish') {
            SetSettings::deletePostMeta($listingId, $positionMetaKey);
        } else {
            if ($this->isTopOfSearch($positionMetaKey)) {
                $this->updateMenuOrder($listingId, $positionMetaKey, false);
            } else {
                SetSettings::deletePostMeta($listingId, $positionMetaKey);
            }
            $this->movePromotionToTrashAfterAllPlansExpirationDate($promotionId);
        }

        $aPromotionPlan = $this->getPromotionInfoByMetaKey($positionMetaKey);
        $aPromotionPlan = empty($aPromotionPlan) ? ['name' => $positionMetaKey] : $aPromotionPlan;

        wp_clear_scheduled_hook(
            $this->handlePromotionPositionExpiry,
            [
                $promotionId,
                $listingId,
                $positionMetaKey
            ]
        );

        do_action(
            'wilcity/wiloke-listing-tools/app/Controllers/PromotionController/after-promotion-position-expired',
            [
                'promotionInfo' => $aPromotionPlan,
                'promotionId'   => $promotionId,
                'listingId'     => $listingId
            ]
        );

        return true;
    }

    public function updatePromotion($promotionId): bool
    {
        if (get_post_status($promotionId) == 'draft') {
            return false;
        }

        $this->setupHandlePromotionEvent(absint($promotionId));

        return true;
    }

    protected function getWooCommercePlanSettings($productID)
    {
        $aPromotionPlans = $this->getPromotionPlans();
        foreach ($aPromotionPlans as $aPromotion) {
            if ($aPromotion['productAssociation'] == $productID) {
                return $aPromotion;
            }
        }
    }

    protected function getWooCommercePlans()
    {
        if (!empty($this->aWoocommercePlans)) {
            return $this->aWoocommercePlans;
        }

        $aPromotionPlans = $this->getPromotionPlans();
        foreach ($aPromotionPlans as $aPromotion) {
            $this->aWoocommercePlans[] = $aPromotion['productAssociation'];
        }

        return $this->aWoocommercePlans;
    }

    public function cancelPostPromotion($aInfo): bool
    {
        $aBoostPostData = PaymentMetaModel::get($aInfo['paymentID'], 'boost_post_data');
        if (empty($aBoostPostData)) {
            return true;
        }
        $this->decreasePostPromotion($aBoostPostData);

        return true;
    }

    protected function clearExpirationPromotion($position, $postID)
    {
        wp_clear_scheduled_hook($this->generateScheduleKey($position), [$postID, $position]);
        wp_clear_scheduled_hook($this->generateScheduleKey($position), ["$postID", "$position"]);
    }

    public function decreasePostPromotion($aBoostPostData)
    {
        foreach ($aBoostPostData['plans'] as $aInfo) {
            $this->clearExpirationPromotion($aInfo['position'], $aBoostPostData['postID']);
            SetSettings::deletePostMeta($aBoostPostData['postID'], $aInfo['position']);
        }
    }

    private function isPromotionProduct($productID)
    {
        $aPromotionPlans = $this->getPromotionPlans();

        // If $planID is not empty, which means it's Add Listing Plan Submission
        foreach ($aPromotionPlans as $aPromotionPlan) {
            if ($aPromotionPlan['productAssociation'] == $productID) {
                $this->aSelectedPlans[] = $aPromotionPlan;
                $this->aSelectedPlanKeys[] = GetSettings::generateSavingPromotionDurationKey($aPromotionPlan);

                return true;
            }
        }

        return false;
    }

    public function purchasePromotionPlansThroughWooCommerce($orderID)
    {
        $oOrder = new WC_Order($orderID);
        $aItems = $oOrder->get_items();

        $aPromotionProductIDs = [];

        foreach ($aItems as $aItem) {
            $productID = $aItem['product_id'];
            if ($this->isPromotionProduct($productID)) {
                $aPromotionProductIDs[] = $productID;
            }
        }

        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        if (!empty($aPromotionProductIDs)) {
            $this->gateway = 'woocommerce';
            $postID = Session::getPaymentObjectID();

            $aMiddleware = ['isGatewaySupported', 'isPublishedPost'];
            $aStatus = $this->middleware($aMiddleware, [
                'postID'  => $postID,
                'gateway' => $this->gateway
            ]);

            if ($aStatus['status'] == 'error') {
                return $oRetrieve->error($aStatus);
            }

            $this->oReceipt = ReceiptStaticFactory::get($this->category, [
                'userID'            => User::getCurrentUserID(),
                'couponCode'        => isset($aData['couponCode']) ? $aData['couponCode'] : '',
                'productID'         => $aPromotionProductIDs,
                'orderID'           => $orderID,
                'aRequested'        => $_REQUEST,
                'aSelectedPlanKeys' => $this->aSelectedPlanKeys,
                'planName'          => sprintf(
                    esc_html__('Promote %s', 'wiloke-listing-tools'),
                    get_the_title($postID)
                ),
                'gateway'           => $this->gateway,
                'aProductIDs'       => $aPromotionProductIDs
            ]);
            $this->oReceipt->setupPlan();

            $aResponse = $this->createSession();
            $oRetrieve = new RetrieveController(new AjaxRetrieve());
            if ($aResponse['status'] != 'success') {
                return $oRetrieve->error($aResponse);
            }
        }
    }

    public function createPromotion($aInfo): bool
    {
        if (!isset($aInfo['category']) || $aInfo['category'] != $this->category) {
            return false;
        }

        $promotionID = GetSettings::getPostMeta($aInfo['postID'], $this->belongsToPromotionKey);

        if (empty($promotionID)) {
            $promotionID = wp_insert_post([
                'post_title'  => sprintf(esc_html__('Promote %s', 'wiloke-listing-tools'),
                    get_the_title($aInfo['postID'])),
                'post_type'   => 'promotion',
                'post_status' => 'draft',
                'post_author' => $aInfo['userID']
            ]);
        } else {
            wp_update_post(
                [
                    'ID'          => $promotionID,
                    'post_status' => 'draft'
                ]
            );
        }

        SetSettings::setPostMeta($promotionID, 'listing_id', $aInfo['postID']);
        foreach ($aInfo['aSelectedPlans'] as $aPlan) {
            SetSettings::setPostMeta(
                $promotionID,
                $this->generatePositionMetaKey($aPlan),
                strtotime('+ ' . $aPlan['duration'] . ' days')
            );
        }

        PaymentMetaModel::setPromotionID($aInfo['paymentID'], $promotionID);
        Session::setSession('promotionID', $promotionID);

        do_action('wiloke/promotion/submitted', $aInfo['userID'], $aInfo['postID'], $promotionID);

        return true;
    }

    public function updatePromotionAfterPaymentCompleted($aInfo)
    {
        if (!isset($aInfo['category']) || $aInfo['category'] != $this->category) {
            return false;
        }

        $promotionID = PaymentMetaModel::getPromotionID($aInfo['paymentID']);

        wp_update_post([
            'ID'          => $promotionID,
            'post_status' => 'publish'
        ]);

        FileSystem::logPayment(
            'stripe-webhook.log',
            json_encode(
                [
                    'promotionID' => $promotionID,
                    'postID'      => $aInfo['postID'],
                    'userID'      => $aInfo['userID']
                ]
            )
        );

        do_action(
            'wilcity/wiloke-listing-tools/app/PromotionController/updatePromotionAfterPaymentCompleted',
            $promotionID,
            get_post_status($promotionID),
            $aInfo
        );

        return $promotionID;
    }

    public function removePromotionAfterPaymentRefunded($aInfo): bool
    {
        if (!isset($aInfo['category']) || $aInfo['category'] != $this->category) {
            return false;
        }

        $promotionID = PaymentMetaModel::getPromotionID($aInfo['paymentID']);

        if (!empty($promotionID)) {
            wp_update_post([
                'ID'          => $promotionID,
                'post_status' => 'trash'
            ]);
        }
        return true;
    }

    /**
     * Using Stripe API v3: It's required in EU
     *
     * @see   https://stripe.com/docs/payments/checkout/server#create-one-time-payments
     * @since 1.1.7.6
     */
    public function createSession()
    {
        $aPaymentMethod = PaymentGatewayStaticFactory::get($this->gateway, $this->isNonRecurringPayment);
        if ($aPaymentMethod['status'] == 'success') {
            return $aPaymentMethod['oPaymentMethod']->proceedPayment($this->oReceipt);
        }

        return $aPaymentMethod;
    }

    public function boostListing()
    {
        Session::destroySession(wilokeListingToolsRepository()->get('payment:associateProductID'));
        Session::setPaymentCategory($this->category);

        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        $this->gateway = isset($_POST['gateway']) && !empty($_POST['gateway']) ? $_POST['gateway'] : '';
        if (GetWilokeSubmission::isGatewaySupported("woocommerce")) {
            $this->gateway = 'woocommerce';
        }

        if (empty($this->gateway)) {
            $this->gateway = GetWilokeSubmission::getDefaultGateway();
            if (empty($this->gateway)) {
                return $oRetrieve->error(['msg' => esc_html__("Please select a payment gateway",
                    'wiloke-listing-tools')]);
            }
        }

        $aMiddleware = ['isGatewaySupported', 'isSetupThankyouCancelUrl', 'isPublishedPost'];
        $status = $this->middleware($aMiddleware, [
            'postID'    => $_POST['postID'],
            'gateway'   => $this->gateway,
            'isBoolean' => true
        ]);

        if (!$status) {
            return $oRetrieve->error(['msg' => esc_html__('You can promote a published listing only',
                'wiloke-listing-tools')]);
        }

        $noPlanMsg = esc_html__('You have to select 1 plan at least', 'wiloke-listing-tools');
        if (!isset($_POST['aPlans']) || empty($_POST['aPlans'])) {
            wp_send_json_error([
                'msg' => $noPlanMsg
            ]);
        }

        $aSelectedPlanKeys = [];
        $aSelectedPlans = [];
        $aPlans = [];

        if (is_array($_POST['aPlans'])) {
            $aPlans = $_POST['aPlans'];
        } else {
            if (\WilokeListingTools\Framework\Helpers\Validation::isValidJson($_POST['aPlans'])) {
                $aPlans = \WilokeListingTools\Framework\Helpers\Validation::getJsonDecoded();
            }
        }

        if (empty($aPlans)) {
            wp_send_json_error([
                'msg' => $noPlanMsg
            ]);
        }

        foreach ($aPlans as $aPlan) {
            if (isset($aPlan['value']) && $aPlan['value'] == 'yes') {
                $aSelectedPlanKeys[] = GetSettings::generateSavingPromotionDurationKey($aPlan);
                $aSelectedPlans[] = $aPlan;
            }
        }

        if (empty($aSelectedPlanKeys)) {
            return $oRetrieve->error([
                'msg' => $noPlanMsg
            ]);
        }

        Session::setPaymentObjectID($_POST['postID']);

        if ($this->gateway == 'woocommerce') {
            $aProductIDs = [];

            foreach ($aSelectedPlans as $aPlan) {
                if (in_array(GetSettings::generateSavingPromotionDurationKey($aPlan), $aSelectedPlanKeys)) {
                    $aProductIDs[] = $aPlan['productAssociation'];
                }
            }

            if (empty($aProductIDs)) {
                return $oRetrieve->error(
                    [
                        'msg' => esc_html__('The product id is required', 'wiloke-listing-tools')
                    ]
                );
            }

            /*
             * @WooCommerceController:removeProductFromCart
             */
            do_action('wiloke-listing-tools/before-redirecting-to-cart', $aProductIDs);

            return $oRetrieve->success([
                'productIDs' => $aProductIDs,
                'cartUrl'    => wc_get_cart_url()
            ]);
        } else {
            $planName = sprintf(
                esc_html__('Promotion - %s', 'wiloke-listing-tools'),
                get_the_title($_POST['postID'])
            );

            $this->oReceipt = ReceiptStaticFactory::get($this->category, [
                'userID'            => User::getCurrentUserID(),
                'aSelectedPlanKeys' => $aSelectedPlanKeys,
                'planName'          => $planName,
                'gateway'           => $this->gateway,
                'couponCode'        => ''
            ]);

            $aStatus = $this->oReceipt->setupPlan();
            if ($aStatus['status'] == 'error') {
                return $oRetrieve->error($aStatus['msg']);
            }

            $aResponse = $this->createSession();

            if ($aResponse['status'] == 'error') {
                return $oRetrieve->error($aResponse);
            }

            return $oRetrieve->success($aResponse);
        }
    }

    public function getPaymentGateways()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $aPromotions = GetSettings::getOptions('promotion_plans', false, true);

        if (empty($aPromotions)) {
            $oRetrieve->error(['msg' => esc_html__('There is no promotion plan', 'wiloke-listing-tools')]);
        }

        foreach ($aPromotions as $aPromotion) {
            if (isset($aPromotion['productAssociation']) && !empty($aPromotion['productAssociation'])) {
                $oRetrieve->error([
                    'msg'              => esc_html__('It is using WooCommerce Payment Gateway',
                        'wiloke-listing-tools'),
                    'doNotShowMessage' => true
                ]);
            }
        }

        $gateways = GetWilokeSubmission::getField('payment_gateways');
        if (empty($gateways)) {
            $oRetrieve->error([
                'msg' => esc_html__('You do not have any gateways. Please go to Wiloke Submission to set one.')
            ]);
        }

        $aGatewayKeys = explode(',', $gateways);
        $aGatewayNames = GetWilokeSubmission::getGatewaysWithName();

        $aGateways = [];
        foreach ($aGatewayKeys as $gateway) {
            $aGateways[$gateway] = $aGatewayNames[$gateway];
        }

        $oRetrieve->success($aGateways);
    }

    public function fetchPromotions()
    {
        $aPromotions = GetSettings::getPromotionPlans();
        $currency = GetWilokeSubmission::getField('currency_code');
        $symbol = GetWilokeSubmission::getSymbol($currency);
        $position = GetWilokeSubmission::getField('currency_position');

        $promotionID = GetSettings::getPostMeta($_GET['postID'], $this->belongsToPromotionKey);
        if (!empty($promotionID) && get_post_status($promotionID) === 'publish') {
            $now = current_time('timestamp');
            $order = 0;
            foreach ($aPromotions as $key => $aPlanSetting) {
                // Listing Sidebar without id won't display
                if ($aPlanSetting['position'] == 'listing_sidebar' && empty($aPlanSetting['id'])) {
                    continue;
                }
                $aReturnPromotions[$order] = $aPlanSetting;
                $promotionExpiry = GetSettings::getPostMeta($promotionID, 'promote_' . $key);
                if (!empty($promotionExpiry)) {
                    $promotionExpiry = Time::convertUTCTimestampToLocalTimestamp(absint($promotionExpiry));
                    if ($now < $promotionExpiry) {
                        $aReturnPromotions[$order]['isUsing'] = 'yes';
                        $convertHumanReadAble
                            = Time::toDateFormat($promotionExpiry) . ' ' . Time::toTimeFormat($promotionExpiry);
                        $aReturnPromotions[$order]['expiry'] = sprintf(
                            esc_html__('Your Promotion will expiry on: %s', 'wiloke-listing-tools'),
                            $convertHumanReadAble
                        );
                    }
                }
                $order++;
            }
        } else {
            $aReturnPromotions = array_values($aPromotions);
            foreach ($aReturnPromotions as $key => $aPlanSetting) {
                // Listing Sidebar without id won't display
                if ($aPlanSetting['position'] == 'listing_sidebar' && empty($aPlanSetting['id'])) {
                    unset($aReturnPromotions[$key]);
                }
            }
        }

        $taxRate = 0;
        if (GetWilokeSubmission::isTax()) {
            $taxRate = GetWilokeSubmission::getTaxRate();
        }

        wp_send_json_success(
            [
                'plans'            => $aReturnPromotions,
                'position'         => $position,
                'symbol'           => $symbol,
                'numberOfDecimals' => apply_filters('wilcity/filter/wiloke-listing-tools/fetchPromotion/numberOfDecimals',
                    2),
                'taxRate'          => $taxRate,
                'taxTitle'         => GetWilokeSubmission::getTaxTitle()
            ]
        );
    }
}
