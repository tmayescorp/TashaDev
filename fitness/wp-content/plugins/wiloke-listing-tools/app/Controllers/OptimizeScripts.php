<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\Firebase;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Frontend\SingleListing;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\UserModel;
use WilokeListingTools\Register\WilokeSubmission;
use WilokeListingTools\Framework\Helpers\WPML;

class OptimizeScripts extends Controller
{
    private $rawTranslation;
    private $rawGlobal;

    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'singleEnqueueScripts']);
        add_action('wp_enqueue_scripts', [$this, 'addCustomScripts'], 998);
        //        add_action('updated_option', [$this, 'rebuildCacheFiles'], 10);
        add_action('wp_head', [$this, 'inlineScripts'], 1);
        add_action('wp_print_styles', [$this, 'removeStyles'], 999);
        //        add_action('wp_print_scripts', [$this, 'removeScripts'], 999);
        add_filter('kc_enqueue_scripts', [$this, 'removeKCScripts'], 100);
        add_filter('kc_enqueue_styles', [$this, 'removeKCScripts'], 100);
    }

    public function removeKCScripts($aScripts)
    {
        if (!function_exists('wilcityIsSearchV2')) {
            return $aScripts;
        }

        if (!wilcityIsSearchV2()) {
            return $aScripts;
        }

        if (\WilokeThemeOptions::isEnable('remove_kc_from_search_page', false)) {
            return [];
        }

        return $aScripts;
    }

    public function removeStyles()
    {
        if (!function_exists('wilcityIsSearchWithoutMapPage')) {
            return false;
        }
        // Removing jquery smooth on Search Page to resolve conflict Event Calendar style issue
        if (wilcityIsSearchWithoutMapPage() || is_front_page()) {
            wp_dequeue_style('jquery-ui-style');
        }

        if (wilcityIsSearchV2()) {
            if (\WilokeThemeOptions::isEnable('remove_elementor_from_search_page')) {
                wp_dequeue_style('elementor-common');
                wp_dequeue_style('elementor-icons');
            }
        }
    }

    public function removeScripts()
    {
        if (!function_exists('wilcityIsSearchV2')) {
            return false;
        }

        if (!wilcityIsSearchV2()) {
            return false;
        }
    }

    private function restrictAdminOnlyItem($aItems)
    {
        if (!empty($aItems)) {
            if (!current_user_can('administrator')) {
                $aItems = array_filter($aItems, function ($aItem) {
                    if (in_array($aItem['key'],
                            SingleListing::getAdminOnly()) ||
                        (isset($aItem['adminOnly']) && $aItem['adminOnly'] == 'yes')
                    ) {
                        return false;
                    }

                    return true;
                });
            }
        }

        return $aItems;
    }

    public function inlineScripts()
    {
        if (!class_exists('\WilokeThemeOptions')) {
            return false;
        }

        $aTranslation = GetSettings::getTranslation();
        $promotionDesc = GetSettings::getOptions('promotion_description', false, true);
        if (!empty($promotionDesc)) {
            $aTranslation['selectAdsDesc'] = stripslashes($promotionDesc);
        }
        $productJS = trailingslashit(get_template_directory_uri() . '/assets/production/js');
        $mapTheme = \WilokeThemeOptions::getOptionDetail('map_theme', 'blurWater');
        if ($mapTheme == 'custom'):
            $theme = \WilokeThemeOptions::getOptionDetail('map_custom_theme', []);
            ?>
            <script style="text/javascript">
                window.WILCITY_CUSTOM_MAP = <?php echo $theme; ?>;
            </script>
        <?php
        endif;
        ?>
        <script>
            window.webpack_public_path__ = "<?php echo apply_filters('wilcity/filter/wiloke-listing-tools/app/Controllers/OptimizeScripts/inline-global/productJs',
                $productJS); ?>";
            window.WHITE_LABEL = "<?php echo esc_attr(WILCITY_WHITE_LABEL); ?>";
            window.wilI18 = '<?php echo base64_encode(json_encode($aTranslation,
                JSON_UNESCAPED_SLASHES | JSON_HEX_QUOT)); ?>';
        </script>
        <?php
    }

    private function removeElementorEditModeFromSearchPage()
    {
        $query = new \WP_Query([
            'post_type'   => 'page',//it is a Page right?
            'post_status' => 'publish',
            'meta_query'  => [
                [
                    'key'     => '_wp_page_template',
                    'value'   => ['templates/search-without-map.php', 'templates/new-search-without-map.php'],
                    'compare' => 'IN'
                ]
            ]
        ]);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                delete_post_meta($query->post->ID, '_elementor_edit_mode');
            }
        }

        wp_reset_postdata();;
    }

    public function singleEnqueueScripts()
    {
        global $post;
        $aSupportedPostTypes = GetSettings::getAllDirectoryTypes(true);

        if (!is_singular($aSupportedPostTypes)) {
            return false;
        }

        $isExternalSingleListing = false;
        if (FileSystem::isFileExists(WILCITY_WHITE_LABEL . '-single-listing-settings.js')) {
            $cacheVersion = GetSettings::getOptions('single_listing_settings_ver');
            if (version_compare($cacheVersion, WILOKE_LISTING_TOOL_VERSION, '>=')) {
                wp_enqueue_script(WILCITY_WHITE_LABEL . '-single-listing-settings.js',
                    FileSystem::getFileURI(WILCITY_WHITE_LABEL . '-single-listing-settings.js'), ['jquery'],
                    $cacheVersion, false);
                $isExternalSingleListing = true;
            }
        }

        if (!$isExternalSingleListing) {
            $internalSingleListingSettings
                = FileSystem::filePutContents(WILCITY_WHITE_LABEL . '-single-listing-settings.js',
                '/* <![CDATA[ */ window.' . $this->getDefineName('WILCITY_',
                    'SINGLE_LISTING') . '=' . json_encode(wilokeListingToolsRepository()->get('listing-settings')) .
                '; /* ]]> */');
            if ($internalSingleListingSettings) {
                SetSettings::setOptions('single_listing_settings_ver', WILOKE_LISTING_TOOL_VERSION);
                wp_enqueue_script(WILCITY_WHITE_LABEL . '-single-listing-settings.js',
                    FileSystem::getFileURI(WILCITY_WHITE_LABEL . '-single-listing-settings.js'), ['jquery'],
                    WILOKE_LISTING_TOOL_VERSION, false);
                $isExternalSingleListing = true;
            }
        }

        if (!$isExternalSingleListing) {
            wp_localize_script('wilcity-empty', $this->getDefineName('WILCITY_', 'SINGLE_LISTING'),
                wilokeListingToolsRepository()->get('listing-settings'));
        }

        wp_localize_script('wilcity-empty', $this->getDefineName('WILCITY_', 'SINGLE_LISTING_SETTINGS'), [
            'general' => GetSettings::getPostMeta($post->ID,
                wilokeListingToolsRepository()->get('listing-settings:keys', true)->sub('general')),
        ]);
    }

    private function getDefineName($prefix, $name)
    {
        $prefixToLower = strtolower($prefix);

        if ($prefixToLower == 'wiloke_') {
            return strtoupper(WILOKE_WHITE_LABEL) . '_' . $name;
        } else if ($prefixToLower == 'wilcity_') {
            return strtoupper(WILCITY_WHITE_LABEL) . '_' . $name;
        }

        return $prefix . $name;
    }

    private function buildScriptCode($isFocus = false)
    {
        global $wiloke, $post;
        if ($isFocus) {
            $aThemeOptions = \Wiloke::getThemeOptions(true);
        } else {
            $aThemeOptions = $wiloke->aThemeOptions;
        }

        $mapTheme = isset($aThemeOptions['map_theme']) ? esc_js($aThemeOptions['map_theme']) : 'blurWater';

        $role = '';
        $aRoles = [];
        $defaultPostType = 'listing';
        $postTypeDefaultExcerptEvent = 'listing';
        if (class_exists('\WilokeListingTools\Framework\Helpers\GetSettings')) {
            $aDefaultPostTypes = GetSettings::getFrontendPostTypes(true);
            $defaultPostType = $aDefaultPostTypes[0];
            $postTypeDefaultExcerptEvent = $defaultPostType == 'event' ? $aDefaultPostTypes[1] : $defaultPostType;
        }

        $mapboxStyle = \WilokeThemeOptions::getOptionDetail('mapbox_style');
        $mapboxStyle = empty($mapboxStyle) ? 'mapbox://styles/mapbox/streets-v9' : $mapboxStyle;

        if (isset($aThemeOptions['map_center']) && !empty($aThemeOptions['map_center'])) {
            $aParseLatLng = explode(',', $aThemeOptions['map_center']);
            $aLatLng['lat'] = floatval(trim($aParseLatLng[0]));
            $aLatLng['lng'] = floatval(trim($aParseLatLng[1]));
        } else {
            $aLatLng['lat'] = 21.027763;
            $aLatLng['lng'] = 105.834160;
        }

        $aGlobal = [
            'DEBUG_SCRIPT'                => !defined('WP_DEBUG_SCRIPT') || WP_DEBUG_SCRIPT ? 'yes' : 'no',
            'homeURL'                     => home_url('/'),
            'restAPI'                     => rest_url(WILOKE_PREFIX . '/v2/'),
            'dateFormat'                  => get_option('date_format'),
            'startOfWeek'                 => get_option('start_of_week'),
            'uploadType'                  => isset($aThemeOptions['addlisting_upload_img_via']) ?
                $aThemeOptions['addlisting_upload_img_via'] : '',
            'maxUpload'                   => (int)(ini_get('upload_max_filesize')) * 1024,
            'ajaxurl'                     => esc_url(admin_url('admin-ajax.php')),
            'isUseMapBound'               => 'no',
            'hasGoogleAPI'                => isset($aThemeOptions['general_google_api']) &&
            !empty($aThemeOptions['general_google_api']) ? 'yes' : 'no',
            'mapCenter'                   => isset($aThemeOptions['map_center']) ? $aThemeOptions['map_center'] : '',
            'defaultMapCenter'            => $aLatLng,
            'mapMaxZoom'                  => isset($aThemeOptions['map_max_zoom']) ?
                absint($aThemeOptions['map_max_zoom']) :
                7,
            'mapMinZoom'                  => isset($aThemeOptions['map_minimum_zoom']) ?
                absint($aThemeOptions['map_minimum_zoom']) : 1,
            'mapDefaultZoom'              => isset($aThemeOptions['map_default_zoom']) ?
                absint($aThemeOptions['map_default_zoom']) : 4,
            'mapTheme'                    => esc_js($mapTheme),
            'mapLanguage'                 => isset($aThemeOptions['general_google_language']) ?
                trim($aThemeOptions['general_google_language']) : '',
            'mapboxStyle'                 => esc_js($mapboxStyle),
            'isAddingListing'             => !class_exists('\WilokeListingTools\Framework\Store\Session') ||
            empty(Session::getSession(wilokeListingToolsRepository()->get('payment:storePlanID'))) ?
                'no' : 'yes',
            'aUsedSocialNetworks'         => \WilokeSocialNetworks::getUsedSocialNetworks(),
            'isPaidClaim'                 => !class_exists('\WilokeListingTools\Controllers\ClaimController') ||
            !\WilokeListingTools\Controllers\ClaimController::isPaidClaim() ? 'no' :
                'yes',
            'datePickerFormat'            => apply_filters('wilcity_date_picker_format', 'mm/dd/yy'),
            'timeZone'                    => get_option('timezone_string'),
            'defaultPostType'             => $defaultPostType,
            'defaultPostTypeExcerptEvent' => $postTypeDefaultExcerptEvent,
            'isUploadImgViaAjax'          => defined('WILCITY_BETA_UPLOAD_IMG_VIA_AJAX') ||
            $aThemeOptions['addlisting_upload_img_via'] == 'ajax' ? 'yes' : 'no',
            'oFirebaseConfiguration'      => class_exists('WilokeListingTools\Framework\Helpers\Firebase') &&
            Firebase::isFirebaseEnable() ? Firebase::getFirebaseChatConfiguration() :
                '',
            'localeCode'                  => isset($aThemeOptions['general_locale_code']) &&
            !empty($aThemeOptions['general_locale_code']) ?
                esc_attr($aThemeOptions['general_locale_code']) : 'en-US',
            'radius'                      => empty($aThemeOptions['default_radius']) ? 10 :
                absint($aThemeOptions['default_radius']),
            'unit'                        => empty($aThemeOptions['unit_of_distance']) ? 'km' :
                esc_js($aThemeOptions['unit_of_distance']),
            'postTypes'                   => General::getPostTypes(false, false, false)
        ];

        // Map
        $aGlobal['mapType'] = \WilokeThemeOptions::getOptionDetail('map_type');
        if ($aGlobal['mapType'] == 'mapbox') {
            $aGlobal['mapAPI'] = $aThemeOptions['mapbox_api'];
            $aGlobal['mapSizeIcon'] = isset($aThemeOptions['mapbox_iconsize']) ? $aThemeOptions['mapbox_iconsize'] : '';
        }

        $_SESSION['fbCSRF'] = wp_create_nonce('fbCSRF');
        $locale = get_locale();
        $locale = str_replace(['es_AR', 'ru_RU', 'fr_CA', 'fr_FR'], ['es', 'ru', 'fr', 'fr'], $locale);

        if (is_front_page()) {
            $postId = get_option('page_on_front');
        } else if (is_home()) {
            $postId = get_option('page_for_posts');
        } else if (is_single()) {
            $postId = $post->ID;
        } else {
            $postId = 0;
        }

        $aInlineGlobal = [
            'isRTL'            => is_rtl() ? 'yes' : 'no',
            'pluginVersion'    => WILOKE_LISTING_TOOL_VERSION,
            'timeFormat'       => get_option('time_format'),
            'security'         => wp_create_nonce('wilSecurity'),
            'timeZone'         => get_option('timezone_string'),
            'currency'         => GetWilokeSubmission::getSymbol(GetWilokeSubmission::getField('currency_code')),
            'currencyPosition' => GetWilokeSubmission::getField('currency_position'),
            'language'         => WPML::isActive() ? General::getCurrentLanguage() : '',
            'vee'              => [
                'locate' => apply_filters('wilcity/filter/wiloke-listing-tools/vee-language', $locale)
            ],
            'postID'           => absint($postId),
            'termID'           => is_tax() ? get_queried_object()->term_id : 0,
            'productionURL'    => trailingslashit(get_template_directory_uri() . '/assets/production/js'),
            'unit'             => \WilokeThemeOptions::getOptionDetail('unit_of_distance'),
            'fbState'          => $_SESSION['fbCSRF'],
            'homeURL'          => home_url('/'),
            'isUsingFirebase'  => class_exists('WilokeListingTools\Framework\Helpers\Firebase') &&
            Firebase::isFirebaseEnable() ? 'yes' : 'no',
            'postType'         => isset($post->post_type) ? $post->post_type : '',
            'hourFormat'       => \WilokeThemeOptions::getOptionDetail('timeformat'),
            'wpmlCurrentLang'  => defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : '',
        ];

        if (is_user_logged_in()) {
            $oUserMeta = get_userdata(get_current_user_id());
            $role = isset($oUserMeta->roles[0]) ? $oUserMeta->roles[0] : '';
            $aRoles = $oUserMeta->roles;
            if (method_exists('\WilokeListingTools\Register\WilokeSubmission', 'isDashboard') &&
                WilokeSubmission::isDashboard()
            ) {
                $aExternalLinks = DashboardController::getNavigation();

                if (!empty($aExternalLinks)) {
                    $aExternalLinks = array_filter($aExternalLinks, function ($aNav) {
                        return isset($aNav['redirect']) && !empty($aNav['redirect']);
                    });

                    $aInlineGlobal['oDashboardExternalLinks'] = $aExternalLinks;
                }

                $toggleDeleteAccount = isset($aThemeOptions['toggle_allow_customer_delete_account']) &&
                    $aThemeOptions['toggle_allow_customer_delete_account'] == 'enable';

                if ($toggleDeleteAccount) {
                    $aGlobal['oDashboardDeleteAccount'] = [
                        'warning' => $aThemeOptions['customer_delete_account_warning']
                    ];
                }

                $aGlobal['chartsColor'] = [
                    'favorite' => \WilokeThemeOptions::getColor('favorite_chart_color', '#f06292'),
                    'rating'   => \WilokeThemeOptions::getColor('rating_chart_color', '#f06292'),
                    'share'    => \WilokeThemeOptions::getColor('share_chart_color', '#f06292'),
                    'view'     => \WilokeThemeOptions::getColor('view_chart_color', '#f06292'),
                ];
            }

            $aInlineGlobal['oUserShortInfo'] = apply_filters(
                'wilcity/filter/wiloke-listing-tools/app/Controller/OptimizeScripts/buildScriptCode/user-short-info',
                [],
                get_current_user_id()
            );
        }

        $aInlineGlobal['userRole'] = $role;
        $aInlineGlobal['roles'] = $aRoles;

        $aInlineGlobal['becomeAnAuthor']
            = apply_filters('wilcity/filter/wiloke-listing-tools/app/Controller/OptimizeScripts/become-an-author',
            false);
        if ($aInlineGlobal['becomeAnAuthor']) {
            $aInlineGlobal['becomeAnAuthorPageUrl'] = GetWilokeSubmission::getField('become_an_author_page', true);
        }

        if (\WilokeThemeOptions::isEnable('map_bound_toggle')) {
            $aGlobal['isUseMapBound'] = 'yes';
            $aGlobal['mapBoundStart'] = $aThemeOptions['map_bound_start'];
            $aGlobal['mapBoundEnd'] = $aThemeOptions['map_bound_end'];
        }

        if (class_exists('\WilokeListingTools\Framework\Helpers\General')) {
            $aGlobal['oSingleMap']['maxZoom']
                = isset($aThemeOptions['single_map_max_zoom']) && !empty($aThemeOptions['single_map_max_zoom']) ?
                $aThemeOptions['single_map_max_zoom'] : 21;
            $aGlobal['oSingleMap']['minZoom']
                = isset($aThemeOptions['single_map_minimum_zoom']) &&
            !empty($aThemeOptions['single_map_minimum_zoom']) ?
                $aThemeOptions['single_map_minimum_zoom'] : 21;
            $aGlobal['oSingleMap']['defaultZoom']
                = isset($aThemeOptions['single_map_default_zoom']) &&
            !empty($aThemeOptions['single_map_default_zoom']) ?
                $aThemeOptions['single_map_default_zoom'] : 21;
        }

        $aInlineGlobal['datePickerFormat'] = apply_filters('wilcity_date_picker_format', 'mm/dd/yy');

        if (class_exists('WilokeListingTools\Frontend\User')) {
            if (is_user_logged_in()) {
                $userID = get_current_user_id();
                $aUser['displayName'] = User::getField('display_name', $userID);
                $aUser['avatar'] = User::getAvatar($userID);
                $aUser['position'] = User::getPosition($userID);
                $aInlineGlobal['user'] = $aUser;
                $aInlineGlobal['isUserLoggedIn'] = 'yes';
                $aInlineGlobal['userID'] = absint($userID);
            } else {
                $aInlineGlobal['isUserLoggedIn'] = 'no';
                $aInlineGlobal['canRegister'] = RegisterLoginController::canRegister() ? 'yes' : 'no';
            }
        }
        if (!is_user_logged_in() || $isFocus) {
            if (isset($aThemeOptions['toggle_google_recaptcha']) &&
                $aThemeOptions['toggle_google_recaptcha'] == 'enable') {
                $aGlobal['oGoogleReCaptcha']['siteKey'] = $aThemeOptions['recaptcha_site_key'];
                $aGlobal['oGoogleReCaptcha']['on'] = $aThemeOptions['using_google_recaptcha_on'];
            }

            $aGlobal['oFacebook'] = [
                'API'    => $aThemeOptions['fb_api_id'],
                'toggle' => \WilokeThemeOptions::isEnable('fb_toggle_login') ? 'yes' : 'no'
            ];
        }

        if (class_exists('WilokeSocialNetworks') && class_exists('WilokeListingTools\Frontend\User') &&
            User::canSubmitListing()) {
            $aGlobal['oSocialNetworks'] = \WilokeSocialNetworks::$aSocialNetworks;
        }

        if (isset($aThemeOptions['search_country_restriction']) &&
            !empty($aThemeOptions['search_country_restriction'])) {
            $aGlobal['countryRestriction'] = $aThemeOptions['search_country_restriction'];
        }

        if (isset($aThemeOptions['general_search_restriction']) &&
            !empty($aThemeOptions['general_search_restriction'])) {
            $aGlobal['searchCountryRestriction'] = $aThemeOptions['general_search_restriction'];
        }

        //        $aTranslation  = GetSettings::getTranslation();
        //        $promotionDesc = GetSettings::getOptions('promotion_description',false,true);
        //        if (!empty($promotionDesc)) {
        //            $aTranslation['selectAdsDesc'] = stripslashes($promotionDesc);
        //        }

        return apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Controllers/OptimizeScripts/inline-global/buildScriptCode',
            [
                'global' => $aGlobal,
                'inline' => $aInlineGlobal
            ]
        );
    }

    private function encodeData($aData)
    {

        $aGlobal = $aData['global'];
        $this->rawGlobal = $aGlobal;
        $globalJson = json_encode($aGlobal, JSON_UNESCAPED_UNICODE);

        return [
            'global' => !empty($globalJson) ? $globalJson : '',
            'inline' => $aData['inline']
        ];
    }

    public function addCustomScripts()
    {
        if (!defined('WILOKE_THEME_URI')) {
            return false;
        }


        //        $globalVersion = GetSettings::getOptions('global_js_version');
//        $jsURL = WILOKE_THEME_URI . 'assets/production/js/';
        $aBuiltScripts = $this->rebuildCacheFiles('wiloke_themeoptions', false);

//        $individualVersion = GetSettings::getOptions('individual_scripts_version');
        wp_localize_script('wilcity-empty', $this->getDefineName('WILOKE_', 'GLOBAL'), $this->rawGlobal);
        wp_localize_script('wilcity-empty', $this->getDefineName('WILOKE_', 'INLINE_GLOBAL'), $aBuiltScripts['inline']);

        $publishableKey = GetWilokeSubmission::getField('mode') == 'live' ?
            GetWilokeSubmission::getField('stripe_publishable_key') :
            GetWilokeSubmission::getField('stripe_sandbox_publishable_key');
        wp_localize_script('wilcity-empty', $this->getDefineName('WILCITY_', 'GLOBAL'), [
            'oStripe'  => [
                'publishableKey' => $publishableKey,
                'hasCustomerID'  => UserModel::getStripeID() ? 'yes' : 'no'
            ],
            'oGeneral' => [
                'brandName' => GetWilokeSubmission::getField('brandname')
            ]
        ]);

        do_action('wilcity/wiloke-listing-tools/after-added-addCustomScripts');
    }

    public function rebuildCacheFiles($options, $isRebuildCacheFile = true)
    {
        if ($options != 'wiloke_themeoptions' && $options != 'wiloke_themeoptions-transients' &&
            $options != 'firebase_chat_configuration') {
            return false;
        }

        // Because We get its value before changing
        if (!\WilokeThemeOptions::isEnable('remove_elementor_from_search_page', false)) {
            $this->removeElementorEditModeFromSearchPage();
        }

        $aBuiltScripts = $this->buildScriptCode(true);
        $aEncodeJson = $this->encodeData($aBuiltScripts);

        if ($isRebuildCacheFile) {
            if (empty($aEncodeJson['global'])) {
                SetSettings::setOptions('wilcity_global_error', 'yes');
            } else {
                $globalFile = FileSystem::filePutContents(WILCITY_WHITE_LABEL . '-global.js',
                    '/* <![CDATA[ */ window.' . $this->getDefineName('WILOKE_',
                        'GLOBAL') . '=' . $aEncodeJson['global'] . '; /* ]]> */');
                if ($globalFile) {
                    SetSettings::setOptions('individual_scripts_version', time());
                    SetSettings::deleteOption('wilcity_global_error');
                } else {
                    SetSettings::setOptions('wilcity_global_error', 'yes');
                }
            }
        }

        return $aEncodeJson;
    }
}
