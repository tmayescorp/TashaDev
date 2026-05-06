<?php

namespace WilokeListingTools\Frontend;

use WILCITY_SC\SCHelpers;
use WilokeHelpers;
use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Controllers\RetrieveController;
use WilokeListingTools\Framework\Helpers\GalleryHelper;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\Inc;
use WilokeListingTools\Framework\Helpers\PostSkeleton;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Submission;
use WilokeListingTools\Framework\Helpers\TermSetting;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Helpers\UserSkeleton;
use WilokeListingTools\Framework\Helpers\Validation;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Models\FavoriteStatistic;
use WilokeListingTools\Models\ReviewMetaModel;
use WilokeListingTools\Models\ReviewModel;
use WilokeListingTools\Models\SharesStatistic;
use WilokeListingTools\Models\ViewStatistic;
use WilokeThemeOptions;

class SingleListing extends Controller
{
    public static    $aSidebarOrder;
    public static    $aNavOrder;
    protected static $aGroupVal                = [];
    protected static $groupKey                 = '';
    private static   $aConvertNavKeysToListingPlanKeys
                                               = [
            'photos' => 'gallery',
            'tags'   => 'listing_tag'
        ];
    private static   $aDefaultSidebarKeys      = [];
    private static   $isTCountDownSC           = false;
    private static   $aAdminOnly
                                               = [
            'google_adsense',
            'google_adsense_2',
            'google_adsense_1'
        ];
    private static   $aListingPromotionShownUp = [];
    private          $post;
    private          $postID;
    private static   $aCache;

    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('wp_ajax_wilcity_save_single_sidebar_settings', [$this, 'saveSidebarSettings']);

        add_action('wp_ajax_wilcity_fetch_single_gallery', [$this, 'fetchGallery']);
        add_action('wp_ajax_nopriv_wilcity_fetch_single_gallery', [$this, 'fetchGallery']);
        add_action('wp_ajax_wilcity_fetch_content', [$this, 'fetchContent']);
        add_action('wp_ajax_nopriv_wilcity_fetch_content', [$this, 'fetchContent']);
        add_action('wp_ajax_nopriv_wilcity_get_restaurant_menu', [$this, 'fetchRestaurant']);
        add_action('wp_ajax_wilcity_get_restaurant_menu', [$this, 'fetchRestaurant']);

        add_action('wp_ajax_wilcity_fetch_custom_content', [$this, 'fetchCustomContent']);
        add_action('wp_ajax_nopriv_wilcity_fetch_custom_content', [$this, 'fetchCustomContent']);

        add_action('wp_ajax_wilcity_get_tags', [$this, 'fetchTags']);
        add_action('wp_ajax_nopriv_wilcity_get_tags', [$this, 'fetchTags']);

        add_filter('wilcity/nav-order', [$this, 'removeTagIfPlanIsDisabled'], 10, 1);
        add_filter('wilcity/single-listing/tabs', [$this, 'removeDisableNavigationByListingPlan'], 10, 2);
        //		add_action('wp_head', array($this, 'insertGoogleAds'));
        add_filter('the_content', [$this, 'insertGoogleAdsToContent']);
        add_filter('body_class', [$this, 'addPlanClassToSingleListing']);
        add_filter('wilcity/single-listing/fetchCustomContent', [$this, 'modifyColumnRelationshipShortcode']);
        add_filter('wilcity/filter/custom_login_page_url', [$this, 'modifyRedirectToSingleListingPage']);
    }

    public function modifyRedirectToSingleListingPage($url)
    {
        $aPostTypes = General::getPostTypeKeys(false, false);

        if (is_singular($aPostTypes)) {
            return get_permalink();
        }

        return $url;
    }

    private function getHighlightBoxes($post)
    {
        if (!is_user_logged_in() || $post->post_author != get_current_user_id()) {
            return [
                'items' => []
            ];
        }

        $aHighlightBoxes = GetSettings::getOptions(
            General::getSingleListingSettingKey('highlightBoxes', get_post_type($post->ID)), false, true
        );

        if (empty($aHighlightBoxes) || ($aHighlightBoxes['isEnable'] == 'no') || empty($aHighlightBoxes['aItems'])) {
            return [
                'items' => []
            ];
        }

        $aHighlightBoxes = General::unSlashDeep($aHighlightBoxes);
        $aPostTypeKeys = General::getPostTypeKeys(true, false);

        $aItems = [];

        foreach ($aHighlightBoxes['aItems'] as $aItem) {
            if (in_array($aItem['key'], $aPostTypeKeys)) {
                $aItems[] = [
                    'link'    => apply_filters('wilcity/add-new-event-url', '#', $post),
                    'icon'    => esc_html($aItem['icon']),
                    'bgColor' => empty($aItem['bgColor']) ? 'red' : esc_html($aItem['bgColor']),
                    'heading' => esc_html($aItem['name']),
                    'target'  => '_self'
                ];
            } else {
                if (isset($aItem['isPopup']) && $aItem['isPopup'] === 'yes') {
                    $popupTarget = esc_html('wil-' . $aItem['type']);
                } else {
                    $popupTarget = '#';
                }
                $aItems[] = [
                    'link'        => empty($aItem['linkTo']) ? '#' : $aItem['linkTo'],
                    'popupTarget' => $popupTarget,
                    'icon'        => esc_html($aItem['icon']),
                    'bgColor'     => empty($aItem['bgColor']) ? 'red' : esc_html($aItem['bgColor']),
                    'heading'     => esc_html($aItem['name']),
                    'target'      => empty($aItem['linkTargetType']) ? '_self' : esc_html($aItem['linkTargetType'])
                ];
            }
        }

        return [
            'items'       => $aItems,
            'itemsPerRow' => $aHighlightBoxes['itemsPerRow']
        ];
    }

    public static function hasCoupon($post)
    {
        $aCoupon = GetSettings::getPostMeta($post->ID, 'coupon');
        if (empty($aCoupon) || (empty($aCoupon['code']) && empty($aCoupon['redirect_to']))) {
            return false;
        }

        if (!isset($aCoupon['expiry_date'])) {
            $aCoupon['expiry_date'] = [];
        }

        $aCoupon['expiry_date']
            = !is_numeric($aCoupon['expiry_date']) ? strtotime((string)$aCoupon['expiry_date']) :
            $aCoupon['expiry_date'];

        if ($aCoupon['expiry_date'] < time()) {
            return false;
        }

        return true;
    }

    private function getCouponInfo($post)
    {
        $aCoupon = GetSettings::getPostMeta($post->ID, 'coupon');
        if (empty($aCoupon) || (empty($aCoupon['code']) && empty($aCoupon['redirect_to']))) {
            return [];
        }

        if (!isset($aCoupon['expiry_date'])) {
            $aCoupon['expiry_date'] = [];
        }

        $aCoupon['expiry_date']
            = !is_numeric($aCoupon['expiry_date']) ? strtotime((string)$aCoupon['expiry_date']) :
            $aCoupon['expiry_date'];

        if ($aCoupon['expiry_date'] < time()) {
            return [];
        }
        $aCoupon['postID'] = absint($post->ID);
        if (!isset($aCoupon['popup_image']) || empty($aCoupon['popup_image'])) {
            $aCoupon['popup_image'] = WilokeThemeOptions::getThumbnailUrl('listing_coupon_popup_img', 'large');
        }

        //        $aCoupon['expiry_date'] = date_i18n(get_option('date_format').' '.get_option('time_format'), $aCoupon['expiry_date']);
        return $aCoupon;
    }

    protected function getGallery($post)
    {
        $aRawGallery = GetSettings::getPostMeta($post->ID, 'gallery');
        if (empty($aRawGallery)) {
            return [
                'items' => []
            ];
        }

        $aGallery = GalleryHelper::gallerySkeleton($aRawGallery, 'large');

        return [
            'items' => $aGallery
        ];
    }

    private function getVideos()
    {
        $aRawVideos = GetSettings::getPostMeta($this->postID, 'video_srcs');

        if (empty($aRawVideos)) {
            return [];
        }

        return self::parseVideos($aRawVideos, $this->postID);
    }

    //    private function getSharingOn()
    //    {
    //        $socials = GetSettings::getOptions('sharing_on');
    //        return empty($socials) ? ['facebook', 'twitter', 'linkedin', 'whatsapp'] : $socials;
    //    }
    //
    private function isDiscussionAllowed()
    {
        return is_user_logged_in() && GetSettings::getOptions(General::getReviewKey('toggle_review_discussion',
            $this->post->post_type), false, true) === 'enable' ? 'yes' : 'no';
    }

    private function getMyInfo()
    {
        if (!is_user_logged_in()) {
            return [];
        }

        $oUser = new UserSkeleton(get_current_user_id());

        return $oUser->pluck([
            'displayName',
            'avatar',
            'authorLink'
        ]);
    }

    private function getRestaurant()
    {
        $aMenuGroups = self::getRestaurantMenu($this->postID);
        if (!is_array($aMenuGroups)) {
            return [];
        }

        $aResponse = [];
        foreach ($aMenuGroups as $groupKey => $aMenuGroup) {
            if (empty($aMenuGroup['items'])) {
                unset($aMenuGroups[$groupKey]);
                continue;
            }

            foreach ($aMenuGroup['items'] as $order => $aItem) {
                if (isset($aItem['price']) && is_numeric($aItem['price'])) {
                    $aMenuGroup['items'][$order]['price'] = GetWilokeSubmission::renderPrice($aItem['price']);
                }

                if (isset($aItem['gallery'])) {
                    $aMenuGroup['items'][$order]['gallery'] = GalleryHelper::gallerySkeleton($aItem['gallery']);
                }
            }

            $aResponse[] = $aMenuGroup;
        }

        return $aResponse;
    }

    private function getTaxonomies()
    {
        $aNavOrder = self::getNavOrder($this->post);
        $aNavOrder = array_filter($aNavOrder, function ($aItem) {
            if ($aItem['key'] === 'tags' ||
                $aItem['key'] === 'taxonomy' ||
                (isset($aItem['taxonomy']) && taxonomy_exists($aItem['taxonomy']))) {
                return true;
            }

            if (isset($aItem['category']) && $aItem['category'] === 'taxonomy') {
                return !empty($aItem['taxonomy']);
            }

            return false;
        });

        if (empty($aNavOrder)) {
            return [];
        }

        $aResponse = [];
        foreach ($aNavOrder as $aNavItem) {
            $aNavItem['taxonomy_post_type'] = 'currentPostType';
            if ($aNavItem['key'] === 'tags') {
                $aResponse['listing_tag'] = TermSetting::getTermBoxes($this->postID, 'listing_tag', $aNavItem);
            } else {
                $aResponse[$aNavItem['taxonomy']] = TermSetting::getTermBoxes(
                    $this->postID,
                    $aNavItem['taxonomy'],
                    $aNavItem
                );
            }
        }

        return $aResponse;
    }

    private function parseSidebarOrder()
    {
        $aSidebarItem = self::getSidebarOrder();

        if (!empty($aSidebarItem)) {
            if (!current_user_can('administrator')) {
                $aSidebarItem = array_filter((array)$aSidebarItem, function ($aSidebarItem) {
                    if (in_array($aSidebarItem['key'], self::getAdminOnly())) {
                        return false;
                    }

                    if (isset($aSidebarItem['adminOnly']) && $aSidebarItem['adminOnly'] == 'yes') {
                        return false;
                    }

                    return true;
                });
            }
        }

        return $aSidebarItem;
    }

    private function getListingRouter()
    {
        if (get_current_user_id() != $this->post->post_author && !current_user_can('administrator')) {
            return [];
        }

        return wilokeListingToolsRepository()->get('listing-settings:sidebars');
    }

    private function getButtonSettings()
    {
        $aResponse = [];
        $aResponse['button_link'] = GetSettings::getPostMeta($this->postID, 'button_link');
        $aResponse['button_icon'] = GetSettings::getPostMeta($this->postID, 'button_icon');
        $aResponse['button_name'] = GetSettings::getPostMeta($this->postID, 'button_name');

        return $aResponse;
    }

    private function getGeneralSettings()
    {
        $aGeneralSettings = GetSettings::getPostMeta(
            $this->postID,
            wilokeListingToolsRepository()->get('listing-settings:keys', true)->sub('general')
        );

        if (!is_array($aGeneralSettings)) {
            $aGeneralSettings = ['sidebarPosition' => 'right'];
        }

        return $aGeneralSettings;
    }

    private function getNavigationSettings()
    {
        $aDraggable = self::getNavOrder();
        $aFixed = wilokeListingToolsRepository()->get('single-nav:fixed', false);

        if (!current_user_can('administrator')) {
            $aDraggable = array_filter($aDraggable, function ($aItem) {
                if (in_array($aItem['key'], self::getAdminOnly()) ||
                    (isset($aItem['adminOnly']) && $aItem['adminOnly'] == 'yes')
                ) {
                    return false;
                }

                return true;
            });
        }

        $isUsingDefaultNav = GetSettings::getPostMeta(
            $this->postID,
            wilokeListingToolsRepository()->get('single-nav:keys', true)->sub('isUsedDefaultNav')
        );

        return [
            'settings'          => [
                'draggable' => $aDraggable,
                'fixed'     => $aFixed
            ],
            'isUsingDefaultNav' => empty($isUsingDefaultNav) ? "yes" : $isUsingDefaultNav
        ];
    }

    private function getSidebarSettings()
    {
        $aSidebarItem = self::getSidebarOrder($this->post);

        if (!empty($aSidebarItem)) {
            if (!current_user_can('administrator')) {
                $aSidebarItem = array_filter($aSidebarItem, function ($aSidebarItem) {
                    if (in_array($aSidebarItem['key'], SingleListing::getAdminOnly()) ||
                        (isset($aSidebarItem['adminOnly']) && $aSidebarItem['adminOnly'] == 'yes')
                    ) {
                        return false;
                    }

                    return true;
                });
            }
        }

        $isUsingDefaultSidebar = GetSettings::getPostMeta(
            $this->postID,
            wilokeListingToolsRepository()->get('listing-settings:keys', true)->sub('isUsedDefaultSidebar')
        );

        return [
            'isUsingDefaultSidebar' => empty($isUsingDefaultSidebar) ? 'yes' : $isUsingDefaultSidebar,
            'items'                 => is_array($aSidebarItem) ? array_values($aSidebarItem) : []
        ];
    }

    public function enqueueScripts()
    {
        if (!is_singular(General::getPostTypeKeys(false, true))) {
            return false;
        }

        global $post;
        $this->post = $post;
        $this->postID = absint($post->ID);

        wp_localize_script('wilcity-empty', 'WIL_SINGLE_LISTING', [
            'postID'           => absint($post->ID),
            'postType'         => $post->post_type,
            'userID'           => absint(get_current_user_id()),
            'currentTab'       => isset($_GET['tab']) ? stripslashes($_GET['tab']) : 'home',
            //            'reviews'             => [
            //                'statistic'  => ReviewMetaModel::getGeneralReviewData($post),
            //                'total'      => ReviewModel::countTotalReviews($post->ID),
            //                'isReviewed' => !ReviewModel::isEnabledReview($post->post_type) || ReviewModel::isUserReviewed
            //                ($post->ID) ? 'yes' : 'no'
            //            ],
            'compareStatistic' => [
                'views'     => ViewStatistic::compare($post->post_author, $post->ID),
                'favorites' => FavoriteStatistic::compare($post->ID),
                'shares'    => SharesStatistic::compare($post->post_author, $post->ID)
            ],
            'highlightBoxes'   => $this->getHighlightBoxes($post),
            'coupon'           => $this->getCouponInfo($post),
            'postUrl'          => get_permalink($post->ID),
            'gallery'          => $this->getGallery($post),
            'isAdministrator'  => current_user_can('administrator') ? 'yes' : 'no',
            'isAllowReported'  => GetSettings::getOptions('toggle_report', false, true),
            //            'isDiscussionAllowed' => $this->isDiscussionAllowed(),
            'myInfo'           => $this->getMyInfo(),
            'videos'           => [
                'items' => $this->getVideos()
            ],
            'restaurant'       => $this->getRestaurant(),
            'taxonomies'       => $this->getTaxonomies(),
            'listingSettings'  => [
                'router'          => $this->getListingRouter(),
                'buttonSettings'  => $this->getButtonSettings(),
                'generalSettings' => $this->getGeneralSettings(),
                'editNavigation'  => $this->getNavigationSettings(),
                'editSidebar'     => $this->getSidebarSettings(),
                'defines'         => wilokeListingToolsRepository()->get('listing-settings:defines')
            ]
        ]);
    }

    public static function getAdminOnly()
    {
        return self::$aAdminOnly;
    }

    public static function getRestaurantMenu($postID, $wrapperClass = '')
    {
        if (isset(self::$aCache[$postID . 'restaurant_menu']) && !empty(self::$aCache[$postID . 'restaurant_menu'])) {
            return self::$aCache[$postID . 'restaurant_menu'];
        }

        $numberOfMenus = GetSettings::getPostMeta($postID, 'number_restaurant_menus');

        $hasZero = GetSettings::getPostMeta($postID, 'restaurant_menu_group_0');
        if (empty($numberOfMenus) && !empty($hasZero)) {
            $numberOfMenus = 1;
        }

        if (empty($numberOfMenus)) {
            return '';
        }

        $aMenus = [];
        for ($i = 0; $i < $numberOfMenus; $i++) {
            $aMenu = GetSettings::getPostMeta($postID, 'restaurant_menu_group_' . $i);
            if (!empty($aMenu)) {
                foreach ($aMenu as $index => $aItem) {
                    if ((!isset($aItem['title']) || empty($aItem['title']))
                        && (!isset($aItem['description']) || empty($aItem['description']))) {
                        unset($aMenu[$index]);
                        continue;
                    }

                    if (isset($aItem['description'])) {
                        $aMenu[$index]['description'] = preg_replace("/\r\n|\r|\n/", '', $aItem['description']);
                    }
                }

                if ($aMenu) {
                    $aMenu = array_values($aMenu);
                    $aMenus['restaurant_menu_group_' . $i]['items'] = $aMenu;
                    $aMenus['restaurant_menu_group_' . $i]['wrapper_class']
                        = 'wilcity_restaurant_menu_group_' . $i . ' ' . $wrapperClass;
                    $aMenus['restaurant_menu_group_' . $i]['group_title']
                        = GetSettings::getPostMeta($postID, 'group_title_' . $i);
                    $aMenus['restaurant_menu_group_' . $i]['group_description']
                        = GetSettings::getPostMeta($postID, 'group_description_' . $i);
                    $aMenus['restaurant_menu_group_' . $i]['group_icon']
                        = GetSettings::getPostMeta($postID, 'group_icon_' . $i);
                }
            }
        }

        self::$aCache[$postID . 'restaurant_menu'] = $aMenus;

        return $aMenus;
    }

    /*
     * If this shortcode is under Listing Tab, We will modify its column
     *
     * @since 1.2.0
     */
    public function modifyColumnRelationshipShortcode($sc)
    {
        if (strpos($sc, 'wilcity_render_listing_type_relationships') !== false) {
            $scType = SCHelpers::getCustomSCClass($sc);
            $sc = str_replace(']',
                ' maximum_posts_on_lg_screen="col-md-4" maximum_posts_on_md_screen="col-md-4" extra_class="' . $scType .
                ' clearfix"]', $sc);
        }

        return $sc;
    }

    public static function isClaimedListing($listingID, $isFocus = false)
    {
        if (!$isFocus && !WilokeThemeOptions::isEnable('listing_toggle_contact_info_on_unclaim')) {
            return true;
        }

        return GetSettings::getPostMeta($listingID, 'claim_status') == 'claimed';
    }

    public static function setListingPromotionShownUp($listingID)
    {
        self::$aListingPromotionShownUp[] = $listingID;
    }

    public function addPlanClassToSingleListing($classes)
    {
        if (!class_exists('\WilokeThemeOptions')) {
            return $classes;
        }

        if (!is_array($classes)) {
            $aClasses = [$classes];
        } else {
            $aClasses = $classes;
        }

        if (is_singular()) {
            global $post;
            $belongsToPlanClass = GetSettings::getSingleListingBelongsToPlanClass($post->ID);
            if (!empty($belongsToPlanClass)) {
                $aClasses[] = $belongsToPlanClass;
            }

            $aClasses[] = 'wil-nav-bg-' . WilokeThemeOptions::getOptionDetail('general_listing_menu_background');
        }

        return $aClasses;
    }

    public function insertGoogleAdsToContent($content)
    {
        global $wiloke, $post;
        $aPostTypes = General::getPostTypeKeys(false, false);
        if (!is_singular($aPostTypes)) {
            return $content;
        }

        if (!isset($wiloke->aThemeOptions['google_adsense_client_id']) ||
            empty($wiloke->aThemeOptions['google_adsense_client_id']) ||
            empty($wiloke->aThemeOptions['google_adsense_slot_id']) ||
            ($wiloke->aThemeOptions['google_adsense_directory_content_position'] == 'disable')) {
            return $content;
        }

        if (!GetSettings::isPlanAvailableInListing($post->ID, 'toggle_google_ads')) {
            return $content;
        }

        if ($wiloke->aThemeOptions['google_adsense_directory_content_position'] == 'above') {
            $content = '[wilcity_in_article_google_adsense]' . $content;
        } else {
            $content .= '[wilcity_in_article_google_adsense]';
        }

        return $content;
    }

    public function removeDisableNavigationByListingPlan($aTabs, $post)
    {
        $planID = GetSettings::getPostMeta($post->ID, 'belongs_to');
        if (empty($planID)) {
            return $aTabs;
        }
        foreach ($aTabs as $order => $aTab) {
            $tabKey = isset(self::$aConvertNavKeysToListingPlanKeys[$aTab['key']]) ?
                self::$aConvertNavKeysToListingPlanKeys[$aTab['key']] : $aTab['key'];
            $tabKey = str_replace('wilcity_single_navigation_', '', $tabKey);
            if (!GetSettings::isPlanAvailableInListing($post->ID, $tabKey)) {
                unset($aTabs[$order]);
            }
        }

        return $aTabs;
    }

    public static function isElementorEditing(): bool
    {
        if (!current_user_can('edit_theme_options') || !is_admin()) {
            return false;
        }

        if (!isset($_REQUEST['action']) || ($_REQUEST['action'] != 'elementor')) {
            return false;
        }

        return true;
    }

    public static function parseCustomFieldSC($content, $key = '', $postID = '')
    {
        $rawContent = str_replace(['[', ']'], ['', ''], $content);
        if (strpos($rawContent, 'wilcity') !== 0) {
            preg_match('/(?:group_key={{)([^}]*)(?:}})/', $content, $aMatches);

            if (!empty($postID)) {
                $post = get_post($postID);
            } else {
                global $post;
            }
            $isGroupField = false;

            if (isset($aMatches[1])) {
                self::$groupKey = $aMatches[1];
                $isGroupField = true;
                if (!GetSettings::isPlanAvailableInListing($post->ID, self::$groupKey)) {
                    return '';
                }
            }

            if (!empty($key)) {
                if (!GetSettings::isPlanAvailableInListing($post->ID, $key)) {
                    return '';
                }
            }
            self::$isTCountDownSC = strpos($content, 'countdown') !== false;

            preg_match_all('/(?:{{)([^}]*)(?:}})/', $content, $aMatches);

            if (isset($aMatches[1]) && !empty($aMatches[1])) {
                if ($isGroupField) {
                    if (empty($aGroupVal)) {
                        $aGroupVal = GetSettings::getPostMeta($post->ID, 'wilcity_group_' . self::$groupKey);

                        if (!isset($aGroupVal['items']) || empty($aGroupVal['items'])) {
                            return '';
                        }

                        if (!isset($aGroupVal['items'][0]) || empty($aGroupVal['items'][0])) {
                            return '';
                        }
                    }

                    $originalShortcode = $content;
                    $shortcode = "";
                    $content = "";
                    foreach ($aGroupVal['items'] as $aItems) {
                        $shortcode = $originalShortcode;
                        foreach ($aMatches[1] as $keyVal) {
                            if (strpos($keyVal, 'is_content') !== false) {
                                $isContent = true;
                                $realKey = str_replace('is_content_', '', $keyVal);
                            } else {
                                $isContent = false;
                                $realKey = $keyVal;
                            }

                            if (isset($aItems[$realKey])) {
                                $getVal = is_array($aItems[$realKey]) ? implode(',', $aItems[$realKey]) :
                                    $aItems[$realKey];
                                if (!$isContent) {
                                    $getVal = '"' . $getVal . '"';
                                }
                                $shortcode = str_replace('{{' . $keyVal . '}}', $getVal, $shortcode);
                            }
                        }

                        $content .= $shortcode . ' ';
                    }

                    $content = trim($content);
                } else {
                    $getVal = GetSettings::getPostMeta($post->ID, 'custom_' . $aMatches[1][0]);
                    $getVal = is_array($getVal) ? implode(',', $getVal) : $getVal;

                    if (strpos($aMatches[1][0], 'is_content') !== false) {
                        $isContent = true;
                    } else {
                        $isContent = false;
                    }

                    if (self::$isTCountDownSC) {
                        $getVal = is_numeric($getVal) ? $getVal : strtotime($getVal);
                        $getVal = Time::dateDiff($getVal, \time());
                        $getVal = '+' . $getVal . ' hours';
                    }

                    if ($isContent) {
                        $getVal = '"' . $getVal . '"';
                    }
                    $content = str_replace($aMatches[1][0], $getVal, $content);
                }
            }
            $content = str_replace(['{{', '}}'], ['"', '" post_id="' . $postID . '"'], $content);

            self::$groupKey = '';
            self::$aGroupVal = '';
            self::$isTCountDownSC = false;

            return trim($content);
        }

        $content = str_replace(['{{', '}}', ']'], ['"', '"', ' post_id="' . $postID . '"]'], $content);

        return $content;
    }

    public function removeTagIfPlanIsDisabled($aTabs)
    {
        if (!is_single()) {
            return $aTabs;
        }
        global $post;

        if (!is_array($aTabs)) {
            return $aTabs;
        }

        foreach ($aTabs as $tab => $aSetting) {
            if ($tab == 'photos') {
                $key = 'gallery';
            } else {
                $key = $tab;
            }

            $key = str_replace('wilcity_single_navigation_', '', $key);

            if (!GetSettings::isPlanAvailableInListing($post->ID, 'toggle_' . $key)) {
                unset($aTabs[$tab]);
            }

            if (isset($aSetting['taxonomy'])) {
                if (!GetSettings::isPlanAvailableInListing($post->ID, 'toggle_' . $aSetting['taxonomy'])) {
                    unset($aTabs[$tab]);
                }
            }
        }

        return $aTabs;
    }

    public static function getMapIcon($post, $thumbnailSize = 'thumbnail')
    {
        $postID = is_numeric($post) ? $post : absint($post->ID);
        $oTerm = \WilokeHelpers::getTermByPostID($postID, 'listing_cat');
        $iconURL = get_template_directory_uri() . '/assets/img/map-icon.png';

        if ($oTerm) {
            $iconURL = TermSetting::getTermImageIcon($oTerm, $thumbnailSize);
        }

        return apply_filters('wiloke-listing-tools/map-icon-url', $iconURL, $postID, $oTerm);
    }

    public function fetchTags()
    {
        $listingID = $_GET['postID'];
        $post_type = get_post_field('post_type', $listingID);
        $aTags = GetSettings::getPostTerms($listingID, 'listing_tag');
        if ($aTags) {
            ob_start();
            ?>
            <div class="row">
                <?php
                foreach ($aTags as $oTag) :
                    if (!empty($oTag) && !is_wp_error($oTag)) :
                        ?>
                        <div class="col-sm-3 fix">
                            <div class="icon-box-1_module__uyg5F three-text-ellipsis mt-20 mt-sm-15">
                                <div class="icon-box-1_block1__bJ25J">
                                    <?php echo WilokeHelpers::getTermIcon($oTag,
                                        'icon-box-1_icon__3V5c0 rounded-circle', true, ['type' => $post_type]); ?>
                                </div>
                            </div>
                        </div>
                    <?php
                    endif;
                endforeach;
                ?>
            </div>
            <?php
            $content = ob_get_contents();
            ob_end_clean();
            wp_send_json_success([
                'content' => $content
            ]);
        } else {
            wp_send_json_error([
                'msg' => esc_html__('Whoops! We found no features of this listing', 'wiloke-listing-tools')
            ]);
        }
    }

    private function getCustomSectionSettings($key, $tabKey = '')
    {
        $aDefaultNavOrder = GetSettings::getOptions($key, false, true);

        if (empty($aDefaultNavOrder)) {
            return [];
        }

        if (isset($aDefaultNavOrder[$key])) {
            return $aDefaultNavOrder[$key];
        }

        if (!empty($tabKey)) {

            if (isset($aDefaultNavOrder[$tabKey])) {
                return $aDefaultNavOrder[$tabKey];
            }

            if (isset($aDefaultNavOrder['wilcity_single_navigation_' . $tabKey])) {
                return $aDefaultNavOrder['wilcity_single_navigation_' . $tabKey];
            }
        }

        if (isset($aDefaultNavOrder['wilcity_single_navigation_' . $key])) {
            return $aDefaultNavOrder['wilcity_single_navigation_' . $key];
        }

        return [];
    }

    public function fetchCustomContent()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $aSetting = $this->getCustomSectionSettings(
            General::getSingleListingSettingKey('navigation', get_post_type($_GET['postID'])),
            isset($_GET['tabKey']) ? $_GET['tabKey'] : ''
        );

        $errMsg = esc_html__('Whoops! We could not found the content of this content', 'wiloke-listing-tools');
        if (empty($aSetting)) {
            $oRetrieve->error([
                'msg' => $errMsg
            ]);
        }

        $content = self::parseCustomFieldSC(
            $aSetting['content'],
            str_replace('wilcity_single_navigation_', '', $_GET['tabKey']),
            $_GET['postID']
        );

        if (empty($content)) {
            $oRetrieve->error([
                'msg' => $errMsg
            ]);
        }

        $content = apply_filters('wilcity/single-listing/fetchCustomContent', $content);

        ob_start();
        echo do_shortcode(stripslashes($content));
        $content = ob_get_contents();
        ob_end_clean();
        if (empty($content)) {
            $oRetrieve->error([
                'msg' => $errMsg
            ]);
        }
        $oRetrieve->success([
            'content' => apply_filters
            ('wilcity/filter/wiloke-listing-tools/SingleListing/custom-content-response', $content, $_GET, $aSetting)
        ]);
    }

    public function fetchRestaurant()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $aMenus = self::getRestaurantMenu($_GET['postID']);
        if (!is_array($aMenus)) {
            $oRetrieve->error([
                'msg' => esc_html__('There are no menus', 'wiloke-listing-tools')
            ]);
        }

        $aResponse = [];
        foreach ($aMenus as $key => $aMenu) {
            $aResponse[] = wilcityRenderRestaurantListMenu($aMenu, $_GET['postID'], true);
        }

        $oRetrieve->success(['menus' => $aResponse]);
    }

    public function fetchContent()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $content = get_post_field('post_content', $_GET['postID']);
        $content = apply_filters('the_content', $content);

        if (empty($content)) {
            $oRetrieve->error([
                'msg' => esc_html__('The content is empty', 'wiloke-listing-tools')
            ]);
        }
        $oRetrieve->success(['content' => $content]);
    }

    public function successMsg()
    {
        wp_send_json_success(['msg' => esc_html__('The settings have been updated', 'wiloke-listing-tools')]);
    }

    public function saveSidebarSettings()
    {
        $this->middleware(['isPostAuthor'], [
            'postAuthor'    => get_current_user_id(),
            'postID'        => $_POST['postID'],
            'passedIfAdmin' => true
        ]);

        if ($_POST['isUsedDefaultSidebar'] == 'yes') {
            SetSettings::setPostMeta($_POST['postID'],
                wilokeListingToolsRepository()->get('listing-settings:keys', true)->sub('isUsedDefaultSidebar'), 'yes');
        } else {
            SetSettings::setPostMeta($_POST['postID'],
                wilokeListingToolsRepository()->get('listing-settings:keys', true)->sub('isUsedDefaultSidebar'), 'no');

            $aSidebarSections = [];
            foreach ($_POST['data'] as $aRawSection) {
                $aSection = [];
                foreach ($aRawSection as $key => $val) {
                    $aSection[sanitize_text_field($key)] = sanitize_text_field($val);
                }
                $aSidebarSections[sanitize_text_field($aSection['key'])] = $aSection;
            }

            SetSettings::setPostMeta($_POST['postID'], wilokeListingToolsRepository()
                ->get('listing-settings:keys', true)
                ->sub('sidebar', true)
                ->sub('settings'), $aSidebarSections);
        }
        $this->successMsg();
    }

    public static function parseVideos($aRawVideos, $postID)
    {
        global $wiloke;

        $defaultThumb = isset($wiloke->aThemeOptions['listing_video_thumbnail']['url']) ?
            $wiloke->aThemeOptions['listing_video_thumbnail']['url'] : '';

        if (empty($aRawVideos)) {
            return [];
        }

        foreach ($aRawVideos as $order => $aVideo) {
            if (!isset($aVideo['thumbnail']) || empty($aVideo['thumbnail'])) {
                if (strpos($aVideo['src'], 'youtube') !== false) {
                    $aParseVideo = explode('?v=', $aVideo['src']);
                    $videoID = end($aParseVideo);
                    $aRawVideos[$order]['thumbnail'] = 'https://img.youtube.com/vi/' . $videoID . '/hqdefault.jpg';
                } else if (strpos($aVideo['src'], 'vimeo') !== false) {
                    $aParseVideo = explode('/', $aVideo['src']);
                    $videoID = end($aParseVideo);
                    $aThumbnails = WilokeHelpers::getVimeoThumbnail($videoID);
                    $aRawVideos[$order] = array_merge($aRawVideos[$order], $aThumbnails);
                } else {
                    $aRawVideos[$order]['thumbnail'] = $defaultThumb;
                }
                SetSettings::setPostMeta($postID, 'video_srcs', $aRawVideos);
            }
        }

        return $aRawVideos;
    }

    public function fetchGallery()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $msg = esc_html__('There are no photos', 'wiloke-listing-tools');
        if (empty($_GET['postID'])) {
            $oRetrieve->error([
                'msg' => esc_html__('You do not have permission to access this page', 'wiloke-listing-tools')
            ]);
        }

        $oPostSkeleton = new PostSkeleton();
        $aGallery = $oPostSkeleton->getSkeleton($_GET['postID'], ['gallery']);

        if (empty($aGallery) || !isset($aGallery['gallery']) || empty($aGallery['gallery'])) {
            $oRetrieve->error([
                'msg' => $msg
            ]);
        }

        $oRetrieve->success($aGallery);
    }

    public function printContent()
    {
        Inc::file('single-listing:content');
    }

    public function printNavigation($post)
    {
        $aNavigationSettings = GetSettings::getPostMeta($post->ID,
            wilokeListingToolsRepository()->get('listing-settings:keys', true)->sub('navigation'));

        Inc::file('single-listing:navtop');
    }

    public static function getNavOrder($post = null)
    {
        if (empty($post)) {
            global $post;
        }

        if (!empty(self::$aNavOrder)) {
            return self::$aNavOrder;
        }

        $usingCustomSettings = GetSettings::getPostMeta($post->ID,
            wilokeListingToolsRepository()->get('listing-settings:keys', true)->sub('isUsedDefaultNav'));

        if ($usingCustomSettings == 'no') {
            $aIndividualNavOrder = GetSettings::getPostMeta($post->ID,
                wilokeListingToolsRepository()->get('listing-settings:keys', true)->sub('navigation'));
        }

        $aDefaultNavOrder = GetSettings::getOptions(
            General::getSingleListingSettingKey('navigation', $post->post_type), false, true
        );

        if (empty($aDefaultNavOrder) || !is_array($aDefaultNavOrder)) {
            return [];
        }

        if (empty($aIndividualNavOrder)) {
            self::$aNavOrder = apply_filters('wilcity/nav-order', $aDefaultNavOrder, $post);
        } else {
            self::$aNavOrder = $aIndividualNavOrder + $aDefaultNavOrder;

            foreach (self::$aNavOrder as $key => $aVal) {
                if (isset($aVal['maximumItemsOnHome'])) {
                    self::$aNavOrder[$key]['maximumItemsOnHome'] = absint($aVal['maximumItemsOnHome']);
                }

                if (isset($aDefaultNavOrder[$key]['name'])) {
                    self::$aNavOrder[$key] = $aDefaultNavOrder[$key];

                    if (isset($aIndividualNavOrder[$key])) {
                        self::$aNavOrder[$key]['isShowOnHome'] = $aIndividualNavOrder[$key]['isShowOnHome'];
                        self::$aNavOrder[$key]['status'] = $aIndividualNavOrder[$key]['status'];
                    }

                    if (!isset($aVal['isCustomSection']) || $aVal['isCustomSection'] == 'no') {
                        continue;
                    }
                    if (!isset($aDefaultNavOrder[$key])) {
                        unset(self::$aNavOrder[$key]);
                    } else {
                        self::$aNavOrder[$key]['content'] = $aDefaultNavOrder[$key]['content'];
                    }
                }

                if (!isset($aVal['baseKey'])) {
                    self::$aNavOrder[$key]['baseKey'] = $key;
                }
            }
            self::$aNavOrder = apply_filters('wilcity/nav-order', self::$aNavOrder, $post);
        }

        foreach (self::$aNavOrder as $sectionKey => $aNavOrder) {
            if (!isset($aNavOrder['baseKey'])) {
                self::$aNavOrder[$sectionKey]['baseKey'] = $sectionKey;
            }
        }

        if (isset(self::$aNavOrder['tags'])) {
            self::$aNavOrder['tags']['key'] = 'tags';
        }

        return self::$aNavOrder;
    }

    public static function getSidebarOrder($post = null)
    {
        if (empty($post)) {
            global $post;
        }

        $isUsedDefaultSidebar = GetSettings::getPostMeta(
            $post->ID,
            wilokeListingToolsRepository()->get('listing-settings:keys', true
            )->sub('isUsedDefaultSidebar'));

        if (!empty(self::$aSidebarOrder)) {
            return self::$aSidebarOrder;
        }

        $aGeneralSidebarSettings = GetSettings::getOptions(
            General::getSingleListingSettingKey('sidebar', $post->post_type), false, true
        );

        if (empty($isUsedDefaultSidebar) || $isUsedDefaultSidebar == 'yes') {
            self::$aSidebarOrder = $aGeneralSidebarSettings;
            self::$aSidebarOrder = apply_filters('wilcity/sidebar-order', self::$aSidebarOrder, $post);

            return self::$aSidebarOrder;
        }

        $aIndividualSidebarSettings = GetSettings::getPostMeta(
            $post->ID,
            wilokeListingToolsRepository()->get('listing-settings:keys', true)
                ->sub('sidebar', true)
                ->sub('settings')
        );

        if (!empty($aIndividualSidebarSettings)) {
            self::$aSidebarOrder = $aIndividualSidebarSettings;
            self::$aDefaultSidebarKeys = array_keys($aGeneralSidebarSettings);
            $aCustomSectionKeys = array_keys($aIndividualSidebarSettings);
            self::$aSidebarOrder = array_filter(self::$aSidebarOrder, function ($aSidebar) {
                return in_array($aSidebar['key'], self::$aDefaultSidebarKeys);
            });

            foreach ($aGeneralSidebarSettings as $key => $aSection) {
                if (!in_array($key, $aCustomSectionKeys)) {
                    self::$aSidebarOrder[$key] = $aSection;
                } else {
                    self::$aSidebarOrder[$key] = $aSection;
                    if (isset($aIndividualSidebarSettings[$key]['status'])) {
                        self::$aSidebarOrder[$key]['status'] = $aIndividualSidebarSettings[$key]['status'];
                    }
                }
                self::$aSidebarOrder[$key]['name'] = stripslashes($aSection['name']);
            }

        } else {
            self::$aSidebarOrder = $aGeneralSidebarSettings;
        }

        self::$aSidebarOrder = apply_filters('wilcity/sidebar-order', self::$aSidebarOrder, $post);

        return self::$aSidebarOrder;
    }

    public static function getDefaultNavKeys()
    {
        $aNavigation = wilokeListingToolsRepository()->get('listing-settings:navigation');
        $aFixedKeys = array_keys($aNavigation['fixed']);
        $aRest = array_keys($aNavigation['draggable']);

        $aRest[] = 'listing-settings';

        return array_merge($aFixedKeys, $aRest);
    }
}
