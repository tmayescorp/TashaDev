<?php

namespace WilokeListingTools\Controllers;

use Wiloke;
use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Controllers\Retrieve\RetrieveFactory;
use WilokeListingTools\Framework\Helpers\DebugStatus;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\HTML;
use WilokeListingTools\Framework\Helpers\Request;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Submission;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Helpers\WPML;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Frontend\SingleListing;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\MetaBoxes\Listing;
use WilokeListingTools\Models\PromotionModel;
use WilokeThemeOptions;

class AddListingController extends Controller
{
    use Validation;
    use TraitSetEventData;
    use TraitHostedBy;
    use GetWilokeToolSettings;
    use SetPostDuration;
    use SetCustomSections;
    use SetProductsToListing;
    use SetMyRoom;
    use SetListingBelongsToPlanID;
    use SetListingRelationship;

    protected $isJustInserted = true;
    protected $aData;
    protected $listingID;
    protected $listingType;
    protected $prevPlanID;
    protected $planID;
    protected $aSections;
    protected $postStatus;
    protected $isUpdateListing;
    protected $aSectionSettings;
    protected $aGetTerms      = [];
    public    $aConvertToRealBusinessHourKeys
                              = [
            [
                'from' => 'firstOpenHour',
                'to'   => 'firstCloseHour'
            ],
            [
                'from' => 'secondOpenHour',
                'to'   => 'secondCloseHour'
            ]
        ];
    protected $aPlanSettings  = [];
    use PrintSidebarItems;
    use SetCustomButton;
    use InsertGallery;
    use SetSinglePrice;
    use InsertLogo;
    use InsertImg;
    use InsertCoverImage;
    use PrintAddListingSettings;
    use PrintAddListingFields;
    use MergingSettingValues;
    use BelongsToCategories;
    use BelongsToCustomTaxonomies;
    use BelongsToTags;
    use SetPriceRange;
    use SetVideo;
    use SetGeneral;
    use SetContactInfo;
    use SetSocialNetworks;
    use InsertAddress;
    use BelongsToLocation;
    use SetPlanRelationship;
    use InsertFeaturedImg;
    use SetCustomGroup;
    use AddBookingComBannerCreator;
    use HandleSubmit;
    use SetCoupon;
    use SetMyPosts;
    use SetRestaurantMenu;

    public function __construct()
    {
        add_action('wiloke/wilcity/addlisting/print-fields', [$this, 'printAddListingFields']);
        add_action('wiloke/wilcity/addlisting/print-sidebar-items', [$this, 'printSidebarItems']);
//        if (!defined('WILOKE_OPTIMIZATION_PATH')) {
//            add_action('wp_enqueue_scripts', [$this, 'printAddListingSettings']);
//        } else {
        add_action('wp_enqueue_scripts', [$this, 'printAddListingSettingPlaceholder']);
        add_action('wp_ajax_wilcity_fetch_listing_settings', [$this, 'fetchListingSettings']);
//        }
        add_action('wp_ajax_wilcity_handle_review_listing', [$this, 'handlePreview']);
        //        add_action('wp_ajax_nopriv_wilcity_handle_review_listing', [$this, 'handlePreview']);
        add_action('wp_ajax_wilcity_handle_submit_listing', [$this, 'handleSubmit']);
        add_action('wp_ajax_nopriv_wilcity_select2_fetch_term', [$this, 'fetchTerms']);
        add_action('wp_ajax_wilcity_fetch_my_posts', [$this, 'findMyPosts']);
        add_action('wp_ajax_wilcity_select2_fetch_term', [$this, 'fetchTerms']);
        add_filter('woocommerce_add_to_cart_redirect', [$this, 'straightGoToCheckoutPage']);
        //        add_action('woocommerce_order_status_completed', [$this, 'paymentCompleted'], 10, 1);
        add_action('wp_ajax_fetch_tags_of_listing_cat', [$this, 'fetchTagsOfListingCat'], 10, 1);
        add_action('wp_ajax_nopriv_fetch_tags_of_listing_cat', [$this, 'fetchTagsOfListingCat'], 10, 1);
        add_action('wp_ajax_wilcity_fetch_post', [$this, 'fetchPosts'], 10, 1);
        add_action('wp_ajax_nopriv_wilcity_fetch_post', [$this, 'fetchPosts'], 10, 1);
        add_action('wilcity/can-not-submit-listing', [$this, 'printCannotSubmitListingMsg']);
        add_filter('the_content', [$this, 'modifyAddListingContent']);
        add_action('wp_ajax_wilcity_fetch_listing_type', [$this, 'fetchListingByPostType']);
        add_filter('wilcity/thankyou-content', [$this, 'replacePlaceHolderWithRealTextOnThankyouPage'], 10, 2);
        add_filter('wilcity/filter/custom_login_page_url', [$this, 'modifyRedirectToAddListingPlan']);
        add_action('wp_head', [$this, 'addEditListingAndSubmitURLToPrefetch']);
        add_action('update_postmeta', [$this, 'changePostStatusDirectly'], 10, 4);
        add_filter('wilcity/header/data-posttype', [$this, 'renderCurrentPostType']);
        remove_filter('the_content', 'wpautop');
        add_action('admin_menu', [$this, 'addPendingCountToMenu']);
    }

    public function addPendingCountToMenu()
    {
        global $menu;
        if (function_exists('add_user_menu_bubble_event')) {
            return false;
        }

        $aListingTypes = General::getPostTypeKeys(false);
        $aListingTypes[] = "claim_listing";
        foreach ($aListingTypes as $postTypeKey) {
            $totalPending = wp_count_posts($postTypeKey)->pending;
            if ($totalPending) {
                foreach ($menu as $key => $value) {
                    if ($menu[$key][2] == 'edit.php?post_type=' . $postTypeKey) {
                        $menu[$key][0] .= ' <span class="update-plugins">' .
                            $totalPending .
                            '</span>';
                        return;
                    }
                }
            }
        }
    }

    public function fetchListingSettings()
    {
        $aParsedURL = parse_url($_POST['url']);
        if (empty($aParsedURL['query'])) {
            wp_send_json_error(['msg' => esc_html__('Missing required information', 'wiloke-listing-tools')]);
        }

        $aParsedQueryString = explode('&', $aParsedURL['query']);
        $aParsedAddListingInfo = [];
        foreach ($aParsedQueryString as $rawInfo) {
            $aParsedKeyValue = explode('=', $rawInfo);
            $aParsedAddListingInfo[trim($aParsedKeyValue[0])] = trim($aParsedKeyValue[1]);
        }

        $listingType = '';
        $listingId = 0;
        $planId = 0;

        if (isset($aParsedAddListingInfo['listing_type'])) {
            $listingType = $aParsedAddListingInfo['listing_type'];
        }

        if (isset($aParsedAddListingInfo['planID'])) {
            $planId = (int)$aParsedAddListingInfo['planID'];
        }

        if (isset($aParsedAddListingInfo['postID'])) {
            $listingId = (int)$aParsedAddListingInfo['postID'];
        }

        wp_send_json_success([
            'info' => $this->getAddListingSettings($listingId, $planId, $listingType)
        ]);
    }

    public function renderCurrentPostType($postType): string
    {
        if (isset($_GET['listing_type'])) {
            return $_GET['listing_type'];
        }

        return $postType;
    }

    public function changePostStatusDirectly($metaId, $postId, $metaKey, $metaValue): bool
    {
        if (!current_user_can('administrator') || !isset($_POST['action']) || $_POST['action'] !== 'editpost') {
            return false;
        }

        if (isset($_POST['wilcity_listing_status']) && !empty($_POST['wilcity_listing_status'])) {
            $postStatus = $_POST['wilcity_listing_status'];
            unset($_POST['wilcity_listing_status']);
            $post = get_post($postId);

            global $wpdb;
            $wpdb->update(
                $wpdb->posts,
                [
                    'post_status' => $wpdb->_real_escape($postStatus)
                ],
                [
                    'ID' => $postId
                ],
                [
                    '%s'
                ],
                [
                    '%d'
                ]
            );
            delete_post_meta($postId, 'wilcity_listing_status');
            clean_post_cache($postId);
            $oPostAfter = get_post($postId);

            do_action('wilcity_after_reupdated_post', $postId, $oPostAfter, $post);
        }

        return true;
    }

    public function addEditListingAndSubmitURLToPrefetch()
    {
        if (is_singular(General::getPostTypeKeys(false, false))) {
            global $post;
            ?>
            <link id="link-edit-listing" ref="prefetch" as="document"
                  href="<?php echo esc_url(apply_filters('wilcity/single-listing/edit-listing', '', $post)); ?>"/>
            <link id="link-submit-listing" ref="prefetch" as="document"
                  href="<?php echo esc_url(add_query_arg(
                      [
                          'action' => 'wilcity_handle_submit_listing'
                      ],
                      admin_url('admin-ajax.php')
                  )); ?>"/>
            <?php
        }
    }

    public function getData()
    {
        return $this->aData;
    }

    public function modifyRedirectToAddListingPlan($url)
    {
        global $post;

        if (empty($post)) {
            return $url;
        }

        if (GetWilokeSubmission::getField('package') === $post->ID) {
            return GetWilokeSubmission::getField('package', true);
        }

        if (GetWilokeSubmission::getField('addlisting') == $post->ID) {
            if (isset($_GET['planID']) && isset($_GET['listing_type'])) {
                return add_query_arg(
                    [
                        'planID'       => $_GET['planID'],
                        'listing_type' => $_GET['listing_type']
                    ],
                    get_permalink($post->ID)
                );
            }
        }

        return $url;
    }

    /**
     * Resolving Event Date on Add Listing
     * @return mixed
     */
    public function getEventDateFormat()
    {
        return str_replace(
                ['j. F Y', 'F j, Y', 'j F, Y', 'j F Y'],
                ['d-m-Y', 'm-d-Y', 'd-m-Y', 'd-m-Y'],
                get_option('date_format')
            ) . ' ' . Time::getPHPHourFormat();
    }

    public function replacePlaceHolderWithRealTextOnThankyouPage($content, $aArgs)
    {
        if ($error = Session::getSession('payment_error')) {
            return \WilokeMessage::message([
                'status'       => 'error',
                'hasRemoveBtn' => false,
                'hasMsgIcon'   => false,
                'msgIcon'      => 'la la-frown',
                'msg'          => $error
            ]);
        }

        $postExpiry = GetSettings::getPostMeta($aArgs['postID'], 'post_expiry');
        $postExpiry = empty($postExpiry) ? esc_html__('Forever', 'wiloke-listing-tools') :
            Time::toDateFormat($postExpiry) . ' ' . Time::toTimeFormat($postExpiry);
        $planID = GetSettings::getListingBelongsToPlan($aArgs['postID']);
        $planName = get_the_title($planID);

        $promotion = '';
        if (isset($aArgs['promotionID']) || !empty($aArgs['promotionID'])) {
            $aRawPromotions = PromotionModel::getListingPromotions($aArgs['promotionID']);
            $aRawPromotionPlans = GetSettings::getPromotionPlans();

            $aPromotionPlans = [];
            foreach ($aRawPromotionPlans as $promotionKey => $aPlan) {
                $aPromotionPlans[$promotionKey] = $aPlan;
            }

            $promotion = '<table
class="listing-table_table__2Cfzq wil-table-responsive-lg table-module__table thankyoupage-promotion-table mt-20 mb-20">';
            $promotion .= '<thead><tr>';
            $promotion .= '<th style="background-color: #f3f3f6">' . esc_html__('Plan Name', 'wiloke-listing-tools') .
                '</th>';
            $promotion .= '<th style="background-color: #f3f3f6">' . esc_html__('Position', 'wiloke-listing-tools') .
                '</th>';
            $promotion .= '<th style="background-color: #f3f3f6">' . esc_html__('Expiry Date', 'wiloke-listing-tools') .
                '</th>';
            $promotion .= '</tr></thead>';

            $promotion .= '<tbody>';
            foreach ($aRawPromotions as $aPromotion) {
                $position = str_replace('wilcity_promote_', '', $aPromotion['meta_key']);

                $promotion .= '<tr>';
                $promotion .= '<td>' . $aPromotionPlans[$position]['name'] . '</td>';
                $promotion .= '<td>' . $position . '</td>';
                $promotion .= '<td>' . date_i18n(get_option('date_format'), $aPromotion['meta_value']) . '</td>';
                $promotion .= '</tr>';
            }
            $promotion .= '</tbody>';

            $promotion .= '</table>';
        }

        $aReplacedPostTitles = [];
        $aParsePostIDs = explode(',', $aArgs['postID']);
        foreach ($aParsePostIDs as $postID) {
            $aReplacedPostTitles[]
                = '<a target="_blank" href="' . get_permalink($postID) . '">' . get_the_title($postID) . '</a>';
        }

        if (count($aReplacedPostTitles) > 1) {
            $replacedPostTitles = implode(', ', $aReplacedPostTitles);
        } else {
            $replacedPostTitles = implode(' ', $aReplacedPostTitles);
        }

        return str_replace(
            [
                '%postTitle%',
                '%postUrl%',
                '%adminEmail%',
                '%userName%',
                '%postExpiry%',
                '%planTitle%',
                '%planName%',
                '%billingUrl%',
                '%billingDashboardUrl%',
                '%listingDashboardUrl%',
                '%promotionSelected%'
            ],
            [
                $replacedPostTitles,
                get_permalink($aArgs['postID']),
                GetSettings::adminEmail(),
                User::getField(
                    'display_name',
                    get_post_field('post_author', $aArgs['postID'])
                ),
                $postExpiry,
                $planName,
                $planName,
                GetWilokeSubmission::getDashboardUrl('dashboard_page', 'billings'),
                GetWilokeSubmission::getDashboardUrl('dashboard_page', 'billings'),
                GetWilokeSubmission::getDashboardUrl('dashboard_page', 'listings') . '?s=' .
                urlencode(get_the_title($aArgs['postID'])),
                $promotion
            ],
            $content
        );
    }

    /*
     * @var $_GET => Required postType
     * @since 1.2.0
     */
    public function fetchListingByPostType()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        if (!isset($_GET['post_types']) || empty($_GET['post_types'])) {
            wp_send_json_error();
        }

        $s = '';
        if (isset($_GET['search'])) {
            $s = $_GET['search'];
        } else if (isset($_GET['q'])) {
            $s = $_GET['q'];
        }

        $aPostTypes = explode(',', trim($_GET['post_types']));

        $aOptions = User::getMyPosts($aPostTypes, ['s' => $s]);

        if (empty($aOptions)) {
            return $oRetrieve->error([
                'msg' => esc_html__('We found not posts that matched your query', 'wiloke-listing-tools')
            ]);
        }

        return $oRetrieve->success(['results' => $aOptions]);
    }

    public function findMyPosts()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $s = '';
        if (isset($_GET['search'])) {
            $s = $_GET['search'];
        } else if (isset($_GET['q'])) {
            $s = $_GET['q'];
        }

        $aOptions = User::getMyPosts('post', ['s' => $s]);

        if (empty($aOptions)) {
            $oRetrieve->error(['msg' => esc_html__('We found no products', 'wiloke-listing-tools')]);
        }

        if (isset($_GET['mode']) && $_GET['mode'] == 'select') {
            $oRetrieve->success([
                'results' => $aOptions
            ]);
        };

        $oRetrieve->success(
            [
                'msg' => [
                    'results' => $aOptions
                ]
            ]
        );
    }

    public function renderAddListingButton()
    {
        HTML::renderPaymentButtons();
    }

    public function modifyAddListingContent($content)
    {
        if (!is_page_template() || SingleListing::isElementorEditing()) {
            return $content;
        }

        global $post;
        if (is_page_template('wiloke-submission/pricing.php')) {
            if ($reason = User::isLockedAddListing()) {
                switch ($reason) {
                    case 'payment_dispute':
                        return \WilokeMessage::message([
                            'msg'        => esc_html__(
                                'We regret to inform you that your account has been locked the Add Listing feature because there was a dispute issue in the previous payment',
                                'wiloke-listing-tools'
                            ),
                            'msgIcon'    => 'la la-bullhorn',
                            'status'     => 'danger',
                            'hasMsgIcon' => true
                        ], true);
                        break;
                    default:
                        return \WilokeMessage::message([
                            'msg'        => esc_html__(
                                'We regret to inform you that your account has been locked the Add Listing feature.',
                                'wiloke-listing-tools'
                            ),
                            'msgIcon'    => 'la la-bullhorn',
                            'status'     => 'danger',
                            'hasMsgIcon' => true
                        ], true);
                        break;
                }
            }

            $aPostTypeSupported = Submission::getSupportedPostTypes();
            if (!User::canSubmitListing()) {
                ob_start();
                do_action('wilcity/can-not-submit-listing');
                $content = ob_get_contents();
                ob_end_clean();

                return $content;
            } else {
                if (!isset($_REQUEST['listing_type']) || empty($_REQUEST['listing_type'])) {
                    $aCustomPostTypes = GetSettings::getFrontendPostTypes();
                    $packageURL = GetWilokeSubmission::getField('package', true);
                    $additionalClass = '';
                    $order = 1;

                    $boxClass = count($aPostTypeSupported) < 3 ? 'col-md-6 col-lg-6' : 'col-md-4 col-ld-4';
                    $aCustomPostTypes
                        = apply_filters('wilcity/wiloke-listing-tools/filter/add-listing-boxes', $aCustomPostTypes);

                    ob_start();
                    foreach ($aCustomPostTypes as $aInfo) :
                        if (!in_array($aInfo['key'], $aPostTypeSupported)) {
                            continue;
                        }

                        $url = apply_filters('wilcity/wiloke-submission/box-listing-type-url', $packageURL, $aInfo);
                        $postTypeBg = isset($aInfo['bgImg']) && isset( $aInfo['bgImg']['url']) ?  $aInfo['bgImg']['url'] : false;
                        ?>
                        <div class="<?php echo esc_attr(apply_filters('wilcity/filter/listing-type-box-classes',
                            $additionalClass . ' ' . $boxClass, $aInfo));
                        ?>">
                            <div class="icon-box-2_module__AWd3Y wil-text-center"
                                 style="background-color: <?php echo esc_attr($aInfo['addListingLabelBg']); ?>">
                                <a href="<?php echo esc_url($url); ?>">
                                    <?php if ($postTypeBg) : ?>
                                        <div class="icon-box-2_icon__background"
                                             style="background-image: url(<?php echo esc_url($postTypeBg) ?>)">
                                        </div>
                                    <?php endif; ?>


                                    <?php if (isset($aInfo['icon'])) : ?>
                                        <div class="icon-box-2_icon__ZqobK"><i
                                                class="<?php echo esc_attr($aInfo['icon']); ?>"></i>
                                        </div>
                                    <?php endif; ?>

                                    <h2 class="icon-box-2_title__2cgba"><?php echo esc_html($aInfo['addListingLabel']); ?></h2>
                                </a>
                            </div>
                        </div>
                        <?php
                        $order++;
                    endforeach;
                    $listingType = ob_get_contents();
                    ob_end_clean();

                    return apply_filters(
                        'wilcity/filter/wiloke-submission/addlisting/listing-boxes',
                        '<div class="wilcity-choose-listing-types">' . $listingType . '</div>',
                        $listingType,
                        $post
                    );
                } else {
                    $isPrintPricing = true;

                    if (!in_array($_REQUEST['listing_type'], $aPostTypeSupported)) {
                        return \WilokeMessage::message([
                            'msg'        => sprintf(
                                __('Oops! %s type is not supported.', 'wiloke-listing-tools'),
                                $_REQUEST['listing_type']
                            ),
                            'msgIcon'    => 'la la-bullhorn',
                            'status'     => 'danger',
                            'hasMsgIcon' => true
                        ], true);
                    } else {
                        if ($isPrintPricing || DebugStatus::status('WILOKE_ALWAYS_PAY')) {
                            return $content;
                        } else {
                            $errMsg = \WilokeMessage::message([
                                'msg'        => sprintf(__(
                                    'You have exceeded your number of items quota in this plan. Please go to <a href="%s">Dashboard</a>, then click on Billings to upgrade to higher plan',
                                    'wiloke-listing-tools'
                                ), GetWilokeSubmission::getField('dashboard_page', true)),
                                'msgIcon'    => 'la la-bullhorn',
                                'status'     => 'info',
                                'hasMsgIcon' => true
                            ], true);

                            return $errMsg;
                        }
                    }
                }
            }
        } else if (is_page_template('templates/confirm-account.php')) {
            if (!isset($_REQUEST['action']) || $_REQUEST['action'] != 'confirm_account') {
                return \WilokeMessage::message(
                    [
                        'status'  => 'danger',
                        'msgIcon' => 'la la-frown-o',
                        'msg'     => __(
                            'Invalid confirmation link. <a href="#" class="wil-js-send-confirmation-code">Resend confirmation code</a>',
                            'wiloke-listing-tools'
                        )
                    ],
                    true
                );
            } else {
                $userName = urldecode($_REQUEST['userName']);
                $oUser = get_user_by('login', $userName);
                if (empty($oUser) || is_wp_error($oUser)) {
                    return \WilokeMessage::message(
                        [
                            'status'  => 'danger',
                            'msgIcon' => 'la la-frown-o',
                            'msg'     => esc_html__('This account does not exist.', 'wiloke-listing-tools')
                        ],
                        true
                    );
                } else {
                    $activationKey = urldecode($_REQUEST['activationKey']);
                    $userActivationKey = User::getField('user_activation_key', $oUser->ID);
                    if ($activationKey != $userActivationKey) {
                        return \WilokeMessage::message(
                            [
                                'status'  => 'danger',
                                'msgIcon' => 'la la-frown-o',
                                'msg'     => esc_html__('Invalid activation key.', 'wiloke-listing-tools')
                            ],
                            true
                        );
                    } else {
                        SetSettings::setUserMeta($oUser->ID, 'confirmed', true);
                    }
                }
            }
        }

        return $content;
    }

    public static function saveListingIDToSession()
    {
        if (isset($_GET['postID']) && !empty($_GET['postID'])) {
            Session::setPaymentObjectID($_GET['postID']);
        }
    }

    public function printCannotSubmitListingMsg()
    {
        ?>
        <div class="col-md-12">
            <?php if (User::isUserLoggedIn()) : ?>
                <?php if (!User::isAccountConfirmed()) : ?>
                    <?php do_action('wilcity/print-need-to-verify-account-message'); ?>
                <?php elseif (GetWilokeSubmission::isEnable('toggle_become_an_author')) : ?>
                    <div class="alert_module__Q4QZx alert_success__1nkos">
                        <div class="alert_icon__1bDKL"><i class="la la-smile-o"></i></div>
                        <div class="alert_content__1ntU3"><?php Wiloke::ksesHTML(
                                sprintf(__(
                                    'Just one more step to submit the listing. Please click on <a href="%s">Become An Author</a> to complete it.',
                                    'wiloke-listing-tools'
                                ), GetWilokeSubmission::getField('become_an_author_page', true)),
                                false
                            ); ?></div>
                    </div>
                <?php else : ?>
                    <div class="alert_module__Q4QZx alert_danger__2ajVf">
                        <div class="alert_icon__1bDKL"><i class="la la-frown-o"></i></div>
                        <div class="alert_content__1ntU3"><?php esc_html_e(
                                'You do not have permission to access this page!',
                                'wiloke-listing-tools'
                            ); ?></div>
                    </div>
                <?php endif; ?>
            <?php else : ?>
                <div class="alert_module__Q4QZx alert_success__1nkos">
                    <div class="alert_content__1ntU3 align-center">
                        <i class="la la-smile-o"></i> <?php esc_html_e(
                            'You must be logged in to submit a listing',
                            'wiloke-listing-tools'
                        ); ?>
                        <div class="mt-20 wilcity-trigger-login-register-wrapper">
                            <?php if (!GetSettings::isEnableCustomLoginPage()) : ?>
                                <a href="#"
                                   class="wilcity-trigger-login-button wil-btn wil-btn--primary2 wil-btn--round wil-btn--xs"
                                   style="color: #fff"><?php esc_html_e('Login', 'wiloke-listing-tools'); ?></a>
                                <a href="#"
                                   class="wilcity-trigger-register-button wil-btn wil-btn--secondary wil-btn--round wil-btn--xs"
                                   style="color: #fff"><?php esc_html_e('Register', 'wiloke-listing-tools'); ?></a>
                            <?php else : ?>
                                <a href="<?php echo esc_url(add_query_arg(
                                    [
                                        'action'            => 'login',
                                        'redirect_to'       => Request::currentPage(),
                                        'is_focus_redirect' => 'yes'
                                    ],
                                    GetSettings::getCustomLoginPage()
                                )); ?>"
                                   class="wil-btn wil-btn--primary2 wil-btn--round wil-btn--xs"
                                   style="color: #fff"><?php esc_html_e('Login', 'wiloke-listing-tools'); ?></a>
                                <a href="<?php echo esc_url(add_query_arg(
                                    ['action' => 'register'],
                                    GetSettings::getCustomLoginPage()
                                )); ?>" class="wil-btn wil-btn--secondary wil-btn--round wil-btn--xs"
                                   style="color: #fff"><?php esc_html_e('Register', 'wiloke-listing-tools'); ?></a>
                            <?php endif;
                            do_action('wilcity/add-listing/can-not-submit/not-login-in/after');
                            ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function fetchTagsOfListingCat()
    {

        $aTags = [];
        if (is_array($_POST['termID'])) {
            foreach ($_POST['termID'] as $termID) {
                $aMaybeTag = maybe_unserialize(GetSettings::getTermMeta($termID, 'tags_belong_to'));
                if (!empty($aMaybeTag)) {
                    $aTags = array_merge($aMaybeTag, $aTags);
                }
            }
        } else {
            $aTags = maybe_unserialize(GetSettings::getTermMeta($_POST['termID'], 'tags_belong_to'));
        }
        if (empty($aTags)) {
            wp_send_json_error();
        }

        $argsTag = [
            'taxonomy'   => 'listing_tag',
            'slug'       => array_unique($aTags),
            'hide_empty' => false
        ];

        $sectionTagSettings = General::findField($_POST['listingType'], 'listing_tag');

        if (isset($sectionTagSettings) && isset($sectionTagSettings['fields']['listing_tag'])) {
            $argsTag['orderBy'] = $sectionTagSettings['fields']['listing_tag']['orderBy'];
            $argsTag['order'] = $sectionTagSettings['fields']['listing_tag']['order'];
        }

        $terms = get_terms($argsTag);

        if (empty($terms) || is_wp_error($terms)) {
            wp_send_json_error();
        }

        $aTerms = [];

        foreach ($terms as $term) {
            if (!empty($listingType)) {
                $aBelongsTo = GetSettings::getTermMeta($term->term_id, 'belongs_to');
                if (!empty($aBelongsTo) && !in_array($listingType, $aBelongsTo)) {
                    continue;
                }
            }

            $aTerm['name'] = $term->name;
            $aTerm['label'] = $term->name;
            $aTerm['value'] = $term->term_id;
            $aTerms[] = $aTerm;
        }
        wp_send_json_success($aTerms);
    }

    public function generatePayAndPublishURL()
    {
        $planID = Session::getSession(wilokeListingToolsRepository()->get('payment:storePlanID'));
        $productID = GetSettings::getPostMeta($planID, 'product_alias');

        if (!empty($productID)) {
            /*
            * @hooked: WooCommerceController:preparePayment
            */
            do_action('wiloke-listing-tools/payment-via-woocommerce', $planID, $productID);
            $redirectTo = GetWilokeSubmission::getAddToCardUrl($productID);
        } else {
            $redirectTo = GetWilokeSubmission::getField('checkout', true);
        }

        wp_send_json_success(
            [
                'redirectTo' => $redirectTo
            ]
        );
    }

    public function straightGoToCheckoutPage($url)
    {
        if (isset($_POST['add-to-cart'])) {
            $product_id = (int)apply_filters('woocommerce_add_to_cart_product_id', $_POST['add-to-cart']);
            //Check if product ID is in the proper taxonomy and return the URL to the redirect product
            if (has_term('posters', 'product_cat', $product_id)) {
                return get_permalink(83);
            }
        }

        return $url;
    }

    public function fetchPosts()
    {
        if (!is_user_logged_in() || !isset($_GET['postTypes']) || empty($_GET['postTypes'])) {
            wp_send_json_error();
        }
        $aPostTypes = explode(',', $_GET['postTypes']);
        foreach ($aPostTypes as $key => $postType) {
            $aPostTypes[$key] = esc_sql(trim($postType));
        }

        $postTypes = implode("','", $aPostTypes);
        global $wpdb;

        if (current_user_can('edit_theme_options')) {
            $aResults = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT ID, post_title FROM $wpdb->posts WHERE post_title LIKE %s AND post_type In ('" .
                    $postTypes .
                    "') AND post_status='publish' ORDER BY ID DESC LIMIT 50",
                    '%' . esc_sql($_GET['search']) . '%'
                )
            );
        } else {
            $aResults = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT ID, post_title FROM $wpdb->posts WHERE post_title LIKE %s AND post_type IN ('" .
                    $postTypes .
                    "') AND post_author=" . esc_sql(get_current_user_id()) .
                    " AND post_status IN ('publish', 'pending') ORDER BY ID DESC LIMIT 50",
                    '%' . esc_sql($_GET['search']) . '%'
                )
            );
        }

        if (empty($aResults)) {
            wp_send_json_error();
        }

        $aResponse = [];
        foreach ($aResults as $oResult) {
            $aTemporary['id'] = $oResult->ID;
            $aTemporary['text'] = $oResult->post_title;
            $aResponse['results'][] = $aTemporary;
        }

        wp_send_json_success(
            [
                'msg' => $aResponse
            ]
        );
    }

    public function buildTermItemInfo($oTerm)
    {
        $aTerm['value'] = $oTerm->slug;
        $aTerm['name'] = $oTerm->name;
        $aTerm['parent'] = $oTerm->parent;
        $aIcon = \WilokeHelpers::getTermOriginalIcon($oTerm);
        if ($aIcon) {
            $aTerm['oIcon'] = $aIcon;
        } else {
            $featuredImgID = GetSettings::getTermMeta($oTerm->term_id, 'featured_image_id');
            $featuredImg = wp_get_attachment_image_url($featuredImgID, [32, 32]);
            $aTerm['oIcon'] = [
                'type' => 'image',
                'url'  => $featuredImg
            ];
        }

        return $aTerm;
    }

    public function fetchTerms()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        $aArgs = [];
        if (isset($_GET['search'])) {
            $aArgs = [
                'name__like' => $_GET['search'],
                'taxonomy'   => $_GET['taxonomy'],
                'hide_empty' => false
            ];
        }

        if (isset($_GET['postType']) && !empty($_GET['postType'])) {
            $aArgs['meta_query'] = [
                'relation' => 'OR',
                [
                    'key'     => 'wilcity_belongs_to',
                    'compare' => 'NOT EXISTS'
                ],
                [
                    'key'     => 'wilcity_belongs_to',
                    'compare' => 'LIKE',
                    'value'   => $_GET['postType']
                ]
            ];
        }

        $aRawTerms = GetSettings::getTerms($aArgs);

        if (!$aRawTerms) {
            return $oRetrieve->error(['msg' => esc_html__('Nothing found', 'wiloke-listing-tools')]);
        } else {
            $aTerms = [];
            foreach ($aRawTerms as $oTerm) {
                $aTerm = $this->buildTermItemInfo($oTerm);
                $aTerm['id'] = isset($_GET['get']) && $_GET['get'] == 'slug' ? $oTerm->slug : $oTerm->term_id;
                $aTerm['text'] = $oTerm->name;
                $aTerms['results'][] = $aTerm;
            }

            return $oRetrieve->success($aTerms);
        }
    }

    protected function updateBusinessHours()
    {
        if (!empty($this->aBusinessHours)) {
            Listing::saveBusinessHours($this->listingID, $this->aBusinessHours);
        }
    }

    protected function setMenuOrder()
    {
        if (!$this->isUpdateListing) {
            $this->aListingData['menu_order']
                = !empty($this->aPlanSettings['menu_order']) ? absint($this->aPlanSettings['menu_order']) :
                apply_filters(
                    'wilcity/filter/wiloke-listing-tools/app/Controllers/AddListingControllers/default-menu-order',
                    1
                );
        } else {
            if ($this->prevPlanID != $this->planID) {
                if (!empty($this->aPlanSettings['menu_order'])) {
                    $this->aListingData['menu_order'] = $this->aPlanSettings['menu_order'];
                }
            }
        }
    }

    public function verifyCSRF(): bool
    {
        return check_ajax_referer('wilcity-submit-listing', 'wilcityAddListingCsrf', false);
    }

    private function _handlePreview()
    {
        WPML::cookieCurrentLanguage();
        $isPassedCSRF = $this->verifyCSRF();
        Session::setSession('test', 'oke');

        if (!$isPassedCSRF) {
            return RetrieveFactory::retrieve()->error(
                [
                    'msg' => esc_html__('Forbidden', 'wiloke-listing-tools')
                ]
            );
        }

        do_action('wilcity/wiloke-listing-tools/before-add-listing', $_POST);

        $this->listingID = $_POST['listingID'];
        $listingType = $_POST['listingType'] ?? '';
        if (empty($_POST['planID'])) {
            if (!empty($this->listingID)) {
                $this->planID = GetSettings::getListingBelongsToPlan($this->listingID);
                $listingType = get_post_type($this->listingID);
            }

            if (empty($this->planID) && GetWilokeSubmission::isFreeAddListing()) {
                $this->planID = GetWilokeSubmission::getFreePlan($listingType);
            }
        } else {
            $this->planID = $_POST['planID'];
        }

        $this->planID = GetWilokeSubmission::getOriginalPlanId($this->planID);

        $this->isUpdateListing = !empty($this->listingID);
        if ($this->isUpdateListing) {
            $this->prevPlanID = GetSettings::getListingBelongsToPlan($this->listingID);
        }

        $aResponse = $this->middleware([
            'isLockedAddListing',
            'isSupportedPostTypeAddListing',
            'isAccountConfirmed',
            'canSubmissionListing',
            'isExceededFreePlan'
        ], [
            'userID'    => get_current_user_id(),
            'planID'    => $this->planID,
            'listingID' => $this->listingID,
            'postType'  => $listingType
        ], 'normal');

        if ($aResponse['status'] == 'error') {
            return RetrieveFactory::retrieve()->error($aResponse);
        }

        if (!empty($this->listingID)) {
            $aResponse = $this->middleware(
                ['isPostAuthor'],
                [
                    'postID'        => $this->listingID,
                    'passedIfAdmin' => 'yes'
                ],
                'normal'
            );

            if ($aResponse['status'] == 'error') {
                return RetrieveFactory::retrieve()->error($aResponse);
            }
        }

        $this->aData = !empty($_POST['data']) ? json_decode(stripslashes($_POST['data']), true) : [];
        $this->listingType = $_POST['listingType'];

        $aMiddleware = $aMiddlewareArgs = [];
        $aMiddleware[] = 'verifyDirectBankTransfer';
        $aMiddleware[] = 'isSupportedPostTypeAddListing';

        $aMiddlewareArgs = [
            'userID'   => User::getCurrentUserID(),
            'planID'   => $this->planID,
            'postType' => $this->listingType
        ];

        if (!empty($this->listingID)) {
            $this->postStatus = get_post_status($this->listingID);
            $aMiddleware[] = 'isListingBeingReviewed';
            $aMiddlewareArgs['postStatus'] = $this->postStatus;
        }

        $this->aPlanSettings = GetSettings::getPlanSettings($this->planID);

        $aResponse = $this->middleware($aMiddleware, $aMiddlewareArgs, 'normal');
        if ($aResponse['status'] == 'error') {
            return RetrieveFactory::retrieve()->error($aResponse);
        }

        $this->processHandleData();
        Session::setPaymentPlanID($this->planID);

        if (General::getPostTypeGroup($this->listingType) === 'event') {
            if (!empty($this->parentListingID)) {
                $this->aListingData['post_parent'] = $this->parentListingID;
            }
        }

        if (empty($this->listingID)) {
            $this->isJustInserted = true;
            $this->aListingData['post_type'] = $this->listingType;
            $this->setMenuOrder();
            $this->listingID = wp_insert_post($this->aListingData);

            if (empty($this->listingID)) {
                return (RetrieveFactory::retrieve()->error([
                    'msg' => esc_html__(
                        'Oops! Something went wrong. We could not create a new listing',
                        'wiloke-listing-tools'
                    )
                ]));
            }
        } else {
            $this->isJustInserted = false;
            $this->aListingData['ID'] = $this->listingID;
            SetSettings::setPostMeta($this->listingID, 'oldPostStatus', $this->postStatus);
            SetSettings::setPostMeta(
                $this->listingID,
                'oldPlanID',
                GetSettings::getListingBelongsToPlan($this->listingID)
            );

            if ($this->postStatus == 'expired') {
                $this->setMenuOrder();
                $this->aListingData['post_status'] = 'expired';
            } else {
                if ((GetSettings::getListingBelongsToPlan($this->listingID) == $this->planID)) {
                    if (in_array($this->postStatus, ['pending', 'editing', 'publish', 'temporary_close'])) {
                        $this->aListingData['post_status'] = 'editing';
                    }
                }
            }

            if (isset($this->aListingData['post_title']) && !empty($this->aListingData['post_title'])) {
                $this->aListingData['post_name'] = sanitize_text_field($this->aListingData['post_title']);
            }
            wp_update_post($this->aListingData);
        }

        $this->belongsToCategories();
        $this->belongsToLocation();
        $this->belongsToCustomTaxonomies();
        $this->belongsToTags();
        $this->insertAddress();
        $this->setSocialNetworks();
        $this->setListingRelationship();
        $this->setContactInfo();
        $this->setPriceRange();
        $this->setCustomGroup();
        $this->setVideos();
        $this->setSinglePrice();
        $this->insertLogo();
        $this->insertFeaturedImg();
        $this->insertCoverImg();
        $this->insertGallery();
        $this->setGeneralSettings();
        $this->updateBusinessHours();
        $this->setCustomSections();
        $this->setListingBelongsTo();
        $this->addBookingComBannerCreator();
        $this->setProductsToListing();
        $this->setMyRoom();
        $this->setMyPosts();
        $this->setCoupon();
        $this->setRestaurantMenu();
        $this->setCustomButtonToListing($this->listingID, $this->aCustomButton);
        $status = $this->updateEventData();

        if (!$status) {
            return RetrieveFactory::retrieve()->error(
                [
                    'msg' => esc_html__(
                        'Invalid Event Data',
                        'wiloke-listing-tools'
                    )
                ]
            );
        }
        $this->setHostedBy();

        do_action('wiloke-listing-tools/addlisting', $this, $this->listingID, $this->planID);

        // Save Session
        Session::setPaymentPlanID($this->planID);
        Session::setPaymentObjectID($this->listingID);

        if (isset($this->aData['claim_listing_status']['listing_claim_status'])) {
            if (!empty($this->aData['claim_listing_status']['listing_claim_status'])) {
                SetSettings::setPostMeta($this->listingID, 'claim_status',
                    $this->aData['claim_listing_status']['listing_claim_status']);
            } else {
                SetSettings::setPostMeta($this->listingID, 'claim_status', 'claimed');
            }
        } else {
            if (isset($this->aPlanSettings["toggle_claim"])) {
                $claimStatus = $this->aPlanSettings["toggle_claim"] == "yes" ? 'claimed' : 'not_claim';
            } else {
                $claimStatus = "claimed";
            }

            SetSettings::setPostMeta(
                $this->listingID,
                'claim_status',
                apply_filters(
                    'wilcity/filter/wiloke-listing-tools/addlisting/claim-status',
                    $claimStatus,
                    $this->listingID
                )
            );
        }

        do_action('wiloke-listing-tools/passed-preview-step', $this->listingID, $this->planID, $this);

        // Maybe Skip Preview Step
        if (WilokeThemeOptions::isEnable('addlisting_skip_preview_step', false)) {
            $this->_handleSubmit();
        }

        return RetrieveFactory::retrieve()->success(
            [
                'redirectTo' => add_query_arg(
                    [
                        'mode' => 'preview'
                    ],
                    get_permalink($this->listingID)
                ),
                'listingID'  => $this->listingID,
                'planID'     => $this->planID
            ]
        );
    }

    public function handlePreview()
    {
        return $this->_handlePreview();
    }

    final public function getPartial($fileName, $isRequired = false)
    {
        if (!is_file(WILOKE_LISTING_TOOL_DIR . 'views/addlisting/' . $fileName)) {
            if (WP_DEBUG) {
                return new \WP_Error('broke', 'The {' . $fileName . '} does not exits');
            } else {
                return '';
            }
        }

        if ($isRequired) {
            include WILOKE_LISTING_TOOL_DIR . 'views/addlisting/' . $fileName . '.php';
        } else {
            require WILOKE_LISTING_TOOL_DIR . 'views/addlisting/' . $fileName . '.php';
        }
    }
}
