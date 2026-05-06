<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Framework\Helpers\GalleryHelper;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\PlanHelper;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Submission;
use WilokeListingTools\Framework\Helpers\VideoHelper;
use WilokeListingTools\Framework\Helpers\WPML;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\PostModel;
use WilokeListingTools\Framework\Helpers\Validation as ValidationHelper;
use WilokeListingTools\Controllers\DashboardController;
use WilokeThemeOptions;

class ListingController extends Controller
{
    use SingleJsonSkeleton;
    use SetCustomButton;

    private static $aAdminOnly
        = [
            'google_adsense',
            'google_adsense_2',
            'google_adsense_1'
        ];

    public function __construct()
    {
        add_action('wp_ajax_wilcity_fetch_listings_json', [$this, 'fetchListingsJson']);
        add_action('wp_ajax_fetch_listings_statistics', [$this, 'fetchListingsJson']);
        add_action('wp_ajax_fetch_listing_statistic_general_data', [$this, 'fetchGeneralData']);
        add_action('wp_ajax_wilcity_load_more_listings', [$this, 'loadMoreListings']);
        add_action('wp_ajax_nopriv_wilcity_load_more_listings', [$this, 'loadMoreListings']);
        //		add_action('wp_ajax_wilcity_button_settings', array($this, 'fetchButtonSettings'));

        add_action('rest_api_init', function () {
            register_rest_route(WILOKE_PREFIX . '/v2', '/listings/(?P<postID>\d+)/button-settings', [
                'methods'             => 'GET',
                'callback'            => [$this, 'getButtonSettings'],
                'permission_callback' => '__return_true'
            ]);
        });

        add_action('wp_ajax_wilcity_save_page_button', [$this, 'handleSaveButtonSettings']);
        add_action('wp_ajax_wilcity_listing_settings', [$this, 'handleSaveListingSettings']);
        add_action('wp_ajax_wilcity_listing_settings_navigation_mode', [$this, 'handleSaveNavigationMode']);
        add_action('wp_ajax_wilcity_listing_settings_sidebar_mode', [$this, 'handleSaveSidebarMode']);
        add_action('wp_ajax_wil_fetch_videos', [$this, 'fetchVideos']);
        add_action('wp_ajax_wil_fetch_gallery', [$this, 'fetchGallery']);
        add_action('wp_ajax_wil_fetch_edit_gallery_video', [$this, 'fetchEditGalleryVideo']);
        add_action('wp_ajax_wil_update_gallery_videos', [$this, 'handleUpdateGalleryVideos']);

        if (defined('KC_URL')) {
            add_filter(KC_URL . '/assets/images/default.jpg', [$this, 'setDefaultFeaturedImage']);
        }
    }

    public function setDefaultFeaturedImage($url)
    {
        $default = WilokeThemeOptions::getThumbnailUrl('listing_featured_image');
        if (!empty($default)) {
            $url = $default;
        }
        return $url;
    }

    public function handleUpdateGalleryVideos()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        $this->verify($oRetrieve, $_POST['postID']);

        if (!isset($_POST['data']) || empty($_POST['data'])) {
            $oRetrieve->success(['msg' => esc_html__('You changed nothing', 'wiloke-listing-tools')]);
        }

        if (!ValidationHelper::isValidJson($_POST['data'])) {
            $oRetrieve->success(['msg' => esc_html__('Invalid JSON format', 'wiloke-listing-tools')]);
        }

        $aData = ValidationHelper::getJsonDecoded();
        $planID = GetSettings::getListingBelongsToPlan($_POST['postID']);
        $aPlanSettings = !empty($planID) ? GetSettings::getPlanSettings($planID) : [];

        $errMsg = __('This plan does not support %s, You should upgrade to higher plan to get this feature',
            'wiloke-listing-tools');
        $upgradePlanURL = add_query_arg(
            [
                'postID'       => $_POST['postID'],
                'listing_type' => get_post_type($_POST['postID'])
            ],
            GetWilokeSubmission::getPermalink('addlisting')
        );

        if (isset($aData['videos'])) {
            if (!empty($planID) && !Submission::isPlanSupported('toggle_videos', $planID)) {
                $oRetrieve->error(
                    [
                        'msg'             => sprintf($errMsg, esc_html__('video gallery', 'wiloke-listing-tools')),
                        'needUpgradePlan' => 'yes',
                        'upgradeUrl'      => $upgradePlanURL
                    ]);
            }

            if (empty($aData['videos'])) {
                SetSettings::deletePostMeta($_POST['postID'], 'video_srcs');
            } else {
                $aParseVideos = VideoHelper::parseVideoToDB($aData['videos']);
                $aParseVideos = VideoHelper::sureVideoDoesNotExceedPlan($aPlanSettings, $aParseVideos);
                SetSettings::setPostMeta($_POST['postID'], 'video_srcs', $aParseVideos);
            }
        }

        if (isset($aData['gallery'])) {
            if (!empty($planID) && !Submission::isPlanSupported('toggle_gallery', $planID)) {
                $oRetrieve->error(
                    [
                        'msg'             => sprintf($errMsg, esc_html__('images gallery', 'wiloke-listing-tools')),
                        'needUpgradePlan' => 'yes',
                        'upgradeUrl'      => $upgradePlanURL
                    ]);
            }

            if (empty($aData['gallery'])) {
                SetSettings::deletePostMeta($_POST['postID'], 'gallery');
            } else {
                $aParseGallery = GalleryHelper::parseGalleryToDB($aData['gallery']);
                $aParseGallery = GalleryHelper::sureGalleryDoesNotExceededPlan($aPlanSettings, $aParseGallery);
                SetSettings::setPostMeta($_POST['postID'], 'gallery', $aParseGallery);
            }
        }

        $oRetrieve->success([
            'msg' => esc_html__('Congratulations! Your update has been successfully', 'wiloke-listing-tools')
        ]);
    }

    private function verify(RetrieveController $oRetrieve, $postID)
    {
        $aStatus = $this->middleware(['isPostAuthor'], [
            'postAuthor'    => get_current_user_id(),
            'postID'        => $postID,
            'passedIfAdmin' => true
        ]);

        if ($aStatus['status'] === 'error') {
            $oRetrieve->error($aStatus);
        }

        return true;
    }

    private function sendSuccessMsg(RetrieveController $oRetrieve)
    {
        $oRetrieve->success(['msg' => esc_html__('The settings have been updated', 'wiloke-listing-tools')]);
    }

    public function getGallery($postID)
    {
        $aRawPhotos = GetSettings::getPostMeta($postID, 'gallery');

        if (empty($aRawPhotos)) {
            return [];
        }

        return GalleryHelper::gallerySkeleton($aRawPhotos);
    }

    public function getVideos($postID)
    {
        $aRawVideos = GetSettings::getPostMeta($postID, 'video_srcs');

        return VideoHelper::parseVideoToUpload($aRawVideos);
    }

    public function fetchEditGalleryVideo()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        $this->verify($oRetrieve, $_GET['postID']);

        $aVideos = $this->getVideos($_GET['postID']);
        $aGallery = $this->getGallery($_GET['postID']);
        $planID = GetSettings::getListingBelongsToPlan($_GET['postID']);
        $aPlanSettings = GetSettings::getPlanSettings($planID);

        $aResponse = [
            'canUploadGallery'     => 'yes',
            'canUploadVideo'       => 'yes',
            'maximumVideos'        => 100,
            'maximumGalleryImages' => 100
        ];

        if (!empty($aPlanSettings)) {
            $aResponse['canUploadGallery'] = $aPlanSettings['toggle_gallery'] === 'enable' ? 'yes' : 'no';
            $aResponse['canUploadVideo'] = $aPlanSettings['toggle_videos'] === 'enable' ? 'yes' : 'no';
            if ($aPlanSettings['maximumVideos'] != "") {
                $aResponse['maximumVideos'] = absint($aPlanSettings['maximumVideos']);
            }

            if ($aPlanSettings['maximumGalleryImages'] != "") {
                $aResponse['maximumGalleryImages'] = absint($aPlanSettings['maximumGalleryImages']);
            }
        }

        $oRetrieve->success(array_merge(['videos' => $aVideos, 'gallery' => $aGallery], $aResponse));
    }

    public function handleSaveNavigationMode()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        $this->verify($oRetrieve, $_POST['postID']);

        SetSettings::setPostMeta(
            $_POST['postID'],
            wilokeListingToolsRepository()->get('listing-settings:keys', true)->sub('isUsedDefaultNav'),
            sanitize_text_field($_POST['mode'])
        );
        $this->sendSuccessMsg($oRetrieve);
    }

    public function handleSaveSidebarMode()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        $this->verify($oRetrieve, $_POST['postID']);
        SetSettings::setPostMeta(
            $_POST['postID'],
            wilokeListingToolsRepository()->get('listing-settings:keys', true)->sub('isUsedDefaultSidebar'),
            sanitize_text_field($_POST['mode'])
        );

        $this->sendSuccessMsg($oRetrieve);
    }

    public function handleSaveListingSettings()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        $this->verify($oRetrieve, $_POST['postID']);

        if (!isset($_POST['value']) || empty($_POST['value'])) {
            $oRetrieve->error(['msg' => esc_html__('The value is required', 'wiloke-listing-tools')]);
        }

        if (!ValidationHelper::isValidJson($_POST['value'])) {
            $oRetrieve->error(['msg' => esc_html__('Invalid JSON format', 'wiloke-listing-tools')]);
        }

        $aValue = ValidationHelper::deepValidation(ValidationHelper::getJsonDecoded());

        switch ($_POST['mode']) {
            case 'navigation':
                $aNavOrder = [];
                foreach ($aValue as $aItem) {
                    if (!current_user_can('administrator') && in_array($aItem['key'], self::$aAdminOnly)) {
                        continue;
                    }
                    $aNavOrder[$aItem['key']] = $aItem;
                }
                SetSettings::setPostMeta(
                    $_POST['postID'],
                    wilokeListingToolsRepository()->get('listing-settings:keys', true)->sub('navigation'),
                    $aNavOrder
                );
                break;
            case 'sidebar':
                $aSidebarSections = [];
                foreach ($aValue as $aRawSection) {
                    $aSection = [];
                    foreach ($aRawSection as $key => $val) {
                        $aSection[$key] = $val;
                    }
                    $aSidebarSections[$aSection['key']] = $aSection;
                }
                SetSettings::setPostMeta($_POST['postID'], wilokeListingToolsRepository()
                    ->get('listing-settings:keys', true)
                    ->sub('sidebar', true)
                    ->sub('settings'), $aSidebarSections);
                break;
            case 'general':
                SetSettings::setPostMeta(
                    $_POST['postID'],
                    wilokeListingToolsRepository()->get('listing-settings:keys', true)->sub('general'),
                    $aValue
                );
                break;
        }

        $this->sendSuccessMsg($oRetrieve);
    }

    public function handleSaveButtonSettings()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $this->verify($oRetrieve, $_POST['postID']);

        if (ValidationHelper::isValidJson($_POST['value'])) {
            $aButtonSettings = ValidationHelper::getJsonDecoded();
        } else {
            $oRetrieve->error(['msg' => 'Invalid JSON format']);
        }

        $aButtonSettings = ValidationHelper::deepValidation($aButtonSettings);
        $aButtonSettings = wp_parse_args(
            $aButtonSettings,
            [
                'button_link' => '',
                'button_icon' => '',
                'button_name' => ''
            ]
        );
        $this->setCustomButtonToListing($_POST['postID'], $aButtonSettings);
        $this->sendSuccessMsg($oRetrieve);
    }

    public function getButtonSettings(\WP_REST_Request $oRequest)
    {
        $postID = $oRequest->get_param('postID');
        $aSettings['buttonName'] = GetSettings::getPostMeta($postID, 'button_name');
        $aSettings['websiteLink'] = GetSettings::getPostMeta($postID, 'button_link');
        $aSettings['icon'] = GetSettings::getPostMeta($postID, 'button_icon');

        return [
            'data' => $aSettings
        ];
    }

    public static function renderClaimStatus($postID)
    {
        return PostModel::isClaimed($postID) ? esc_html__('Claimed', 'wiloke-listing-tools') : '';
    }

    public function fetchGeneralData()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $aRawPostTypes = GetSettings::getFrontendPostTypes();

        $aPostTypes = [];
        $aPostStatus = General::getPostsStatus(true);

        $aCountPosts = [];
        foreach ($aRawPostTypes as $aOption) {
            if ($aOption['key'] == 'event') {
                continue;
            }
            $aPostTypes[] = [
                'name'  => $aOption['singular_name'],
                'value' => $aOption['key']
            ];

            $aCountPosts[$aOption['key']] = User::countUserPosts(get_current_user_id(), $aOption['key']);
        }

        $oRetrieve->success([
            'oPostTypes'  => $aPostTypes,
            'aCountPosts' => $aCountPosts,
            'aPostStatus' => $aPostStatus
        ]);
    }

    public function loadMoreListings()
    {
        $page = isset($_POST['page']) ? absint($_POST['page']) : 2;
        $aPostTypeKeys = General::getPostTypeKeys(true);

        if (!in_array($_POST['postType'], $aPostTypeKeys)) {
            wp_send_json_error([
                'msg' => esc_html__('You do not have permission to access this page', 'wiloke-listing-tools')
            ]);
        }

        $aData = [];
        foreach ($_POST as $key => $val) {
            $aData[$key] = sanitize_text_field($val);
        }

        $query = new \WP_Query(
            [
                'post_type'      => $aData['postType'],
                'posts_per_page' => $aData['postsPerPage'],
                'paged'          => $page,
                'post_status'    => 'publish'
            ]
        );

        if ($query->have_posts()) {
            ob_start();
            while ($query->have_posts()) {
                $query->the_post();
                wilcity_render_grid_item($query->post, [
                    'img_size'                   => $aData['img_size'],
                    'maximum_posts_on_lg_screen' => $aData['maximum_posts_on_lg_screen'],
                    'maximum_posts_on_md_screen' => $aData['maximum_posts_on_md_screen'],
                    'maximum_posts_on_sm_screen' => $aData['maximum_posts_on_sm_screen'],
                    'style'                      => 'grid',
                ]);
            }
            $contents = ob_get_contents();
            ob_end_clean();
            wp_send_json_success(['msg' => $contents]);
        } else {
            wp_send_json_error(
                [
                    'msg' => sprintf(esc_html__('Oops! Sorry, We found no %s', 'wiloke-listing-tools'),
                        $aData['postType'])
                ]
            );
        }
    }

    public function fetchListingsJson()
    {
        WPML::cookieCurrentLanguage();
        $aData = isset($_GET['postType']) ? $_GET : $_POST;
        $aArgs = [
            'post_type'      => $aData['postType'],
            'post_status'    => $aData['postStatus'],
            'posts_per_page' => 10,
            'paged'          => isset($aData['page']) ? absint($aData['page']) : 1,
            'author'         => User::getCurrentUserID()
        ];

        if ($aArgs['post_status'] == 'any') {
            $postStatus = ['temporary_close'];
            $aStatuses = DashboardController::getPostStatuses(false);
            foreach ($aStatuses as $aStatus) {
                $postStatus[] = $aStatus['id'];
            }

            $aArgs['post_status'] = $postStatus;
        }

        if (isset($aData['s']) && !empty($aData['s'])) {
            $aArgs['s'] = trim($aData['s']);
        }

        $query = new \WP_Query(WPML::addFilterLanguagePostArgs($aArgs));
        $aPostTypeInfo = General::getPostTypeSettings($aData['postType']);

        $addListingURL = apply_filters(
            'wilcity/wiloke-submission/box-listing-type-url',
            GetWilokeSubmission::getField('package', true),
            [
                'key' => $aData['postType']
            ]
        );

        if (!$query->have_posts()) {
            wp_reset_postdata();

            wp_send_json_error([
                'msg'               => esc_html__('You do not have any listing yet.', 'wiloke-listing-tools'),
                'addListingBtnName' => sprintf(esc_html__('Add %s', 'wiloke-listing-tools'),
                    $aPostTypeInfo['singular_name']),
                'addListingUrl'     => $addListingURL
            ]);
        }

        $aListings = [];

        $reviewMode = GetSettings::getOptions(General::getReviewKey('mode', $aData['postType']), false, true);
        $reviewMode = empty($reviewMode) ? 5 : floatval($reviewMode);

        while ($query->have_posts()) {
            $query->the_post();
            $aListing = $this->json($query->post);
            $aListing['oReview'] = [
                'mode'    => $reviewMode,
                'average' => GetSettings::getAverageRating($query->post->ID)
            ];
            $aListings[] = $aListing;
        }
        wp_reset_postdata();

        wp_send_json_success([
            'listings'          => $aListings,
            'maxPages'          => $query->max_num_pages,
            'addListingBtnName' => sprintf(esc_html__('Add %s', 'wiloke-listing-tools'),
                $aPostTypeInfo['singular_name']),
            'addListingUrl'     => $addListingURL
        ]);
    }
}
