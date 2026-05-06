<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Helpers\DebugStatus;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\HTML;
use WilokeListingTools\Framework\Helpers\Submission;
use WilokeListingTools\Framework\Helpers\WooCommerce;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\UserModel;
use WilokeListingTools\Register\WilokeSubmission;

class AddListingButtonController extends Controller
{
    private $needBecomeAnAuthor = false;

    public function __construct()
    {
        add_filter('wilcity/single-listing/add-new-listing', [$this, 'addNewListing']);
        add_filter('wilcity/single-listing/edit-listing', [$this, 'editListing'], 10, 2);
        add_action('wilcity/single-listing/before/wrapper', [$this, 'maybeSubmitListingBtn'], 10, 1);
        add_action('wilcity/single-event/before/wrapper', [$this, 'maybeSubmitListingBtn'], 10, 1);
        add_filter(
            'wilcity/filter/wilcity-listing-tools/app/Controllers/AddListingButtonController/has-submit-btn',
            [$this, 'hasSubmitListingBtn'],
            10,
            2
        );
        add_action('wilcity/single-listing/wil-content', [$this, 'printEditButton'], 10, 2);
        add_action('wilcity/single-event/wil-content', [$this, 'printEditButton'], 10, 2);
//        add_action('wilcity/single-listing/after/wrapper', [$this, 'printAddListingButtonOnMobile']);
        add_action('wilcity/header/after-menu', [$this, 'printAddListingButton']);
        add_filter('wilcity/submission/pricingUrl', [$this, 'generatePricingUrl'], 10, 3);
        add_action('wp_ajax_wilcity_get_edit_url', [$this, 'ajaxGetEditUrl']);
        add_action('wp_ajax_wilcity_change_plan_for_post', [$this, 'ajaxChangePlanForThisPost']);
        add_filter('wilcity/add-new-event-url', [$this, 'addNewEventUrl'], 10, 2);
        add_filter('wilcity/wiloke-submission/box-listing-type-url', [$this, 'buildBoxUrl'], 10, 2);
        add_shortcode('wilcity_print_add_listing_shortcode', [$this, 'addListingShortcodeBtn']);
    }

    public function buildBoxUrl($url, $aInfo)
    {
        $aLinkArgs = [
            'listing_type' => $aInfo['key']
        ];

        if (GetWilokeSubmission::getField('add_listing_mode') == 'free_add_listing') {
            $addListingUrl = GetWilokeSubmission::getField('addlisting', true);
            $addListingUrl = add_query_arg(
                $aLinkArgs,
                $addListingUrl
            );

            return $addListingUrl;
        }

        return add_query_arg(
            $aLinkArgs,
            $url
        );
    }

    public function addNewEventUrl($addListingUrl, $post)
    {
        $addListingUrl = GetWilokeSubmission::getField('package', true);

        $addListingUrl = add_query_arg(
            [
                'listing_type' => 'event',
                'parentID'     => isset($post->ID) ? $post->ID : ''
            ],
            $addListingUrl
        );

        return $addListingUrl;
    }

    public function ajaxChangePlanForThisPost()
    {
        $this->middleware(['isPublishedPost'], [
            'postID' => $_POST['postID']
        ]);

        $addListingUrl = GetWilokeSubmission::getField('package', true);

        $addListingUrl = add_query_arg(
            [
                'postID'       => $_POST['postID'],
                'listing_type' => get_post_type($_POST['postID'])
            ],
            $addListingUrl
        );

        wp_send_json_success(['url' => $addListingUrl]);
    }

    public function ajaxGetEditUrl()
    {
        $this->middleware(['isPostAuthor'], [
            'postID'        => $_POST['postID'],
            'passedIfAdmin' => true
        ]);
        $postStatus = get_post_status($_POST['postID']);

        $planID = GetSettings::getListingBelongsToPlan($_POST['postID']);
        if (empty($planID) || $postStatus == 'expired') {
            $addListingUrl = GetWilokeSubmission::getField('package', true);
        } else {
            $addListingUrl = GetWilokeSubmission::getField('addlisting', true);
        }

        $addListingUrl = add_query_arg(
            [
                'postID'       => $_POST['postID'],
                'planID'       => $planID,
                'listing_type' => get_post_type($_POST['postID'])
            ],
            $addListingUrl
        );

        wp_send_json_success(['url' => $addListingUrl]);
    }

    public function addNewListing($postType)
    {
        $addListingUrl = GetWilokeSubmission::getField('addlisting', true);
        $addListingUrl = add_query_arg(
            [
                'listing_type' => $postType
            ],
            $addListingUrl
        );

        return $addListingUrl;
    }

    public function editListing($nothing, $post): string
    {
        $planID = GetSettings::getPostMeta($post->ID, 'belongs_to');
        if (!current_user_can('administrator') && (int)$post->post_author !== (int)User::getCurrentUserID()) {
            return '';
        }

        if (empty($planID)) {
            if (!GetWilokeSubmission::isFreeAddListing()) {
                $addListingUrl = GetWilokeSubmission::getField('package', true);
            } else {
                $addListingUrl = GetWilokeSubmission::getField('addlisting', true);
                $planID = GetWilokeSubmission::getFreePlan($post->post_type);
            }
        } else {
            $addListingUrl = GetWilokeSubmission::getField('addlisting', true);
        }

        $addListingUrl = add_query_arg(
            [
                'postID'       => $post->ID,
                'planID'       => $planID,
                'listing_type' => $post->post_type
            ],
            $addListingUrl
        );

        return $addListingUrl;
    }

    public function generatePricingUrl($planID, $postID, $aAtts)
    {
        $aArgs = [
            'planID'       => $planID,
            'listing_type' => $aAtts['listing_type']
        ];
        if (!empty($postID)) {
            $aArgs['postID'] = $postID;
        }

        if (isset($aAtts['parentID'])) {
            $aArgs['parentID'] = $aAtts['parentID'];
        }

        return add_query_arg(
            $aArgs,
            GetWilokeSubmission::getField('addlisting', true)
        );
    }

    public function addListingShortcodeBtn()
    {
        ob_start();
        $this->printAddListingButton();
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public function printAddListingButton()
    {
        ?>
        <div id="wil-add-listing-btn-wrapper" style="display: inline-block;"></div>
        <?php
    }

    public function hasSubmitListingBtn($status, $post, $isApp = false): bool
    {
        $aAllowedPostStatus = ['unpaid', 'expired', 'editing'];
        $category = Session::getPaymentCategory(false);
        if (!General::isPostTypeSubmission($post->post_type) ||
            !in_array($post->post_status, $aAllowedPostStatus) ||
            (!empty($category) && $category !== 'addlisting')
        ) {
            return $status;
        }

        ## Do not set session if We are in add listing step
        if (Session::getPaymentObjectID() == $post->ID && Session::getSession('test') == 'oke') {
            return $status;
        }

        $belongsTo = GetSettings::getListingBelongsToPlan($post->ID);

        if (empty($belongsTo) || get_post_status($belongsTo) !== 'publish') {
            return $status;
        }

        do_action('wilcity/wiloke-listing-tools/payment-succeeded-and-updated-everything');

        Session::setPaymentPlanID($belongsTo);
        Session::setPaymentObjectID($post->ID);

        $productId = GetSettings::getPostMeta($post->ID, 'woocommerce_association');
        if (!empty($productId)) {
            Session::setProductID($productId);
        }

        Session::setPaymentCategory('addlisting');
        Session::destroySession('waiting_for_paypal_execution');
        Session::setSession('test', 'oke');
        return true;
    }

    public function maybeSubmitListingBtn($post)
    {
        if (in_array($post->post_type, General::getPostTypeKeys(false, false))) {
            $this->hasSubmitListingBtn(false, $post);
        }
    }

    public function printAddListingButtonOnMobile($post)
    {
        if (isset($_GET['hide_body']) && $_GET['hide_body'] == 'listing_details') {
            echo '<button>'.Session::getSession('test').'</button>';
        }
    }

    public function printEditButton($post, $isFocused = false)
    {
        if (
            !is_user_logged_in() ||
            DebugStatus::status('WILCITY_DISABLE_EDIT_BUTTON') ||
            (!$isFocused && ((Session::getPaymentObjectID() != $post->ID))) ||
            (!current_user_can('administrator') && (User::getCurrentUserID() != $post->post_author))
        ) {
            return '';
        }

        global $post;

        $planID = Session::getPaymentPlanID();
        $objectID = Session::getPaymentObjectID();

        if ((empty($planID) || ($objectID !== $post->ID)) && $isFocused) {
            $planID = GetSettings::getPostMeta($post->ID, 'belongs_to');
        }

        $aAddListingArgs['postID'] = $post->ID;
        if (empty($planID)) {
            if (!GetWilokeSubmission::isFreeAddListing()) {
                $addListingUrl = GetWilokeSubmission::getField('package', true);
            } else {
                $addListingUrl = GetWilokeSubmission::getField('addlisting', true);
                $planID = GetWilokeSubmission::getFreePlan($post->post_type);
                $aAddListingArgs['planID'] = $planID;
            }
        } else {
            $addListingUrl = GetWilokeSubmission::getField('addlisting', true);
            $aAddListingArgs['planID'] = $planID;
        }

        if (isset($post->ID)) {
            $aAddListingArgs['listing_type'] = get_post_type($post->ID);
        }

        $addListingUrl = add_query_arg(
            $aAddListingArgs,
            $addListingUrl
        );
        $aPlanSettings = GetSettings::getPlanSettings($planID);

        $remainingItems = UserModel::getRemainingItemsOfPlans($planID);
        if (empty($aPlanSettings['regular_price']) || $remainingItems > 0) {
            $btnName = __('Submit Listing', 'wiloke-listing-tools');
        } else {
            $btnName = __('Pay & Publish', 'wiloke-listing-tools');
        }

        echo '<div class="btn-group-fixed_module__3qULF pos-f-right-bottom text-right">';
        HTML::renderLink(
            'wil-btn--secondary wil-btn--round wil-btn--md wil-edit-listing-btn',
            esc_html__('Edit Listing', 'wiloke-listing-tools'),
            $addListingUrl,
            apply_filters(
                'wilcity/filter/wiloke-listing-tools/app/AddListingButtonController/edit-icon',
                'la la-edit'
            ),
            'wil-edit-listing-btn'
        );

        echo '<div class="mb-10"></div>';
        if ($post->post_status != 'publish' && Session::getPaymentObjectID() == $post->ID &&
            Session::getPaymentPlanID()) {
            $checkoutUrl = GetWilokeSubmission::getField('checkout', true);
            HTML::renderLink(
                'wil-btn--primary2 wil-btn--round wil-btn--md disable wil-submit-listing-btn',
                esc_html($btnName),
                $checkoutUrl,
                'la la-send',
                'wilcity-submit',
                true
            );
        }
        echo '</div>';
    }
}
