<?php

namespace WilokeListingTools\Framework\Helpers;

use Elementor\Plugin;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use Wiloke;
use WilokeThemeOptions;

class General
{
    public static    $aBusinessHours         = null;
    private static   $aFieldSettings         = [];
    public static    $isBookingFormOnSidebar = false;
    protected static $aThemeOptions          = [];
    public static    $step                   = 1;
    private static   $aCachePostType;

    public static function calculateDistance($lat1, $lon1, $lat2, $lon2, $unit = ""): float
    {
        $unit = empty($unit) ? WilokeThemeOptions::getOptionDetail("unit_of_distance", "km") : $unit;
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "km") {
            return ($miles * 1.609344);
        }

        return $miles;
    }

    public static function generateCallbackFunction($key, $prefix = 'get')
    {
        $func = preg_replace_callback(
            '/_([a-zA-Z])/',
            function ($aMatch) {
                return ucfirst($aMatch[1]);
            },
            $key
        );

        return $prefix . ucfirst($func);
    }

    /**
     * @param string $option Option Name|icon_color_rgba(235, 40, 40, 0.48)|icon_la_la-adjust
     *
     * @return array ['name' => 'Option Name', 'color' => rgba(235, 40, 0.47), 'icon' => 'la la-adjust']
     */
    private static function parseOptionParam($option)
    {
        $option = trim($option);
        if (strpos($option, '|') === false) {
            return [
                'name' => $option
            ];
        }
        $aRawOption = explode('|', $option);
        $aOption['name'] = trim($aRawOption[0]);
        unset($aRawOption[0]);

        if (!empty($aRawOption)) {
            foreach ($aRawOption as $val) {
                if (strpos($val, 'icon_color') !== false || strpos($val, 'rgba') !== false) {
                    $aOption['color'] = str_replace(['icon_color_', '---'], ['', ' '], $val);
                } elseif (strpos($val, 'icon_') !== false) {
                    $aOption['icon'] = str_replace(['icon_', '_'], ['', ' '], $val);
                }
            }
        }

        return $aOption;
    }

    public static function parseCustomSelectOption($option)
    {
        $option = trim($option);
        if (strpos($option, ':') !== false) {
            $aParseOption = explode(':', $option);
            $aOption = self::parseOptionParam($aParseOption[1]);
            $aOption['key'] = trim($aParseOption[0]);
        } else {
            $aOption['name'] = $option;
            $aOption['key'] = $option;
        }

        return $aOption;
    }

    public static function parseSelectFieldOptions($options, $type = 'select')
    {
        if (strpos($options, 'rgba') !== false) {
            $options = preg_replace_callback("/rgb([^|]+)/", function ($aMatches) {
                return str_replace(',', '---', $aMatches[0]);
            }, $options);
        }

        $aRawOptions = explode(',', $options);
        $aRawOptions = array_map('trim', $aRawOptions);
        foreach ($aRawOptions as $rawOption) {
            $aOption = self::parseCustomSelectOption($rawOption);
            switch ($type) {
                case 'wil-select-tree':
                    $aOptions[] = [
                        'id'    => $aOption['key'],
                        'label' => $aOption['name']
                    ];
                    break;
                case 'full':
                    $aOptions[] = wp_parse_args($aOption, ['color' => '', 'name' => '', 'icon' => '', 'key' => '']);
                    break;
                default:
                    $aOptions[$aOption['key']] = $aOption['name'];
                    break;
            }
        }

        return $aOptions;
    }

    public static function deprecatedFunction($function, $replacement, $version)
    {
        // @codingStandardsIgnoreStart
        if (is_ajax()) {
            do_action('deprecated_function_run', $function, $replacement, $version);
            $logString = "The {$function} function is deprecated since version {$version}.";
            $logString .= $replacement ? " Replace with {$replacement}." : '';
            FileSystem::logError($logString);
        } else {
            _deprecated_function($function, $version, $replacement);
        }
    }

    public static function getCurrentLanguage()
    {
        return defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : get_locale();
    }

    public static function renderRel($url)
    {
        $homeurl = home_url();
        if (strpos($url, $homeurl) === false) {
            return 'nofollow';
        }

        return 'dofollow';
    }

    public static function loginRedirectTo()
    {
        $type = WilokeThemeOptions::getOptionDetail('login_redirect_type');
        if ($type == 'self_page') {
            global $wp;

            return add_query_arg($wp->query_vars, home_url());
        }

        return get_permalink(WilokeThemeOptions::getOptionDetail('login_redirect_to'));
    }

    public static function isAdmin()
    {
        if (!wp_doing_ajax() && is_admin()) {
            if (!isset($_POST['template']) || $_POST['template'] != 'templates/mobile-app-homepage.php') {
                return true;
            }

            return false;
        }

        if (wp_doing_ajax()) {
            $action = '';

            if (isset($_POST['action'])) {
                $action = $_POST['action'];
            } else if (isset($_GET['action'])) {
                $action = $_GET['action'];
            }

            if (empty($action)) {
                return true;
            }

            if (strpos($action, 'wilcity') === false && strpos($action, 'wiloke') === false) {
                return true;
            }
        }

        return false;
    }

    public static function isElementorPreview()
    {
        if (
            class_exists('\Elementor\Plugin') && Plugin::$instance->editor->is_edit_mode() ||
            (isset($_REQUEST['elementor-preview']) && !empty($_REQUEST['elementor-preview']))
        ) {
            return true;
        }

        return false;
    }

    public static function unSlashDeep($aVal)
    {
        if (!is_array($aVal)) {
            return stripslashes($aVal);
        }

        return array_map([__CLASS__, 'unSlashDeep'], $aVal);
    }

    public static function getOptionField($key = '')
    {
        if (!empty(self::$aThemeOptions)) {
            return isset(self::$aThemeOptions[$key]) ? self::$aThemeOptions[$key] : '';
        }

        self::$aThemeOptions = Wiloke::getThemeOptions(true);

        return isset(self::$aThemeOptions[$key]) ? self::$aThemeOptions[$key] : '';
    }

    public static function getSecurityAuthKey()
    {
        return self::getOptionField('wilcity_security_authentication_key');
    }

    /**
     * Get Client IP
     * @since 1.0.1
     */
    public static function clientIP()
    {
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = sanitize_text_field($_SERVER['HTTP_X_FORWARDED']);
        } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = sanitize_text_field($_SERVER['HTTP_FORWARDED_FOR']);
        } else if (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = sanitize_text_field($_SERVER['HTTP_FORWARDED']);
        } else if (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = sanitize_text_field($_SERVER['REMOTE_ADDR']);
        } else {
            $ipaddress = false;
        }

        return $ipaddress;
    }

    public static function getCustomFieldsOfPostType($postType)
    {
        if (empty($postType)) {
            return false;
        }

        $aUsedSections = GetSettings::getOptions(self::getUsedSectionKey($postType));
        if (empty($aUsedSections)) {
            return false;
        }

        $aCustomSections = array_filter($aUsedSections, function ($aSection) {
            if (isset($aSection['isCustomSection']) && $aSection['isCustomSection'] == 'yes') {
                return true;
            }

            return false;
        });

        return empty($aCustomSections) ? false : $aCustomSections;
    }

    public static function getCustomGroupsOfPostType($postType)
    {
        if (empty($postType)) {
            return false;
        }

        $aUsedSections = GetSettings::getOptions(self::getUsedSectionKey($postType));

        if (empty($aUsedSections)) {
            return false;
        }

        $aGroups = array_filter($aUsedSections, function ($aSection) {
            if (isset($aSection['type']) && $aSection['type'] == 'group') {
                return true;
            }

            return false;
        });

        return empty($aGroups) ? false : $aGroups;
    }

    public static function convertToNiceNumber($number, $evenZero = false)
    {
        if (empty($number) && !$evenZero) {
            return 0;
        }

        if ($number < 10) {
            return 0 . $number;
        } else if ($number >= 1000) {
            $prefix = floor($number / 1000);
            $subFix = $number - $prefix * 10000;

            return $prefix . $subFix;
        }

        return $number;
    }

    public static function ksesHtml($content, $isReturn = false)
    {
        $allowed_html = [
            'a'      => [
                'href'     => [],
                'style'    => [
                    'color' => []
                ],
                'title'    => [],
                'target'   => [],
                'class'    => [],
                'data-msg' => []
            ],
            'div'    => ['class' => []],
            'h1'     => ['class' => []],
            'h2'     => ['class' => []],
            'h3'     => ['class' => []],
            'h4'     => ['class' => []],
            'h5'     => ['class' => []],
            'h6'     => ['class' => []],
            'br'     => ['class' => []],
            'p'      => ['class' => [], 'style' => []],
            'em'     => ['class' => []],
            'strong' => ['class' => []],
            'span'   => ['data-typer-targets' => [], 'class' => []],
            'i'      => ['class' => []],
            'ul'     => ['class' => []],
            'ol'     => ['class' => []],
            'li'     => ['class' => []],
            'code'   => ['class' => []],
            'pre'    => ['class' => []],
            'iframe' => ['src' => [], 'width' => [], 'height' => [], 'class' => ['embed-responsive-item']],
            'img'    => ['src' => [], 'width' => [], 'height' => [], 'class' => [], 'alt' => []],
            'embed'  => ['src' => [], 'width' => [], 'height' => [], 'class' => []],
        ];

        if (!$isReturn) {
            echo wp_kses(wp_unslash($content), $allowed_html);
        } else {
            return wp_kses(wp_unslash($content), $allowed_html);
        }
    }

    public static function detectPostTypeSubmission()
    {
        $postType = self::detectCurrentPostType();
        if ($postType === 'product' && defined('WILCITY_ADVANCED_CONFIG_DIR')) {
            return $postType;
        }

        return !empty($postType) && General::isPostTypeSubmission($postType) ? $postType : '';
    }

    public static function detectCurrentPostType()
    {
        $postType = '';
        if (isset($_REQUEST['post'])) {
            $postType = get_post_field('post_type', $_REQUEST['post']);
        } else if (isset($_REQUEST['post_type'])) {
            $postType = $_REQUEST['post_type'];
        } else if (isset($_REQUEST['page'])) {
            if ($_REQUEST['page'] == 'wiloke-event-settings') {
                $postType = 'event';
            } else {
                $postType = str_replace('_settings', '', $_REQUEST['page']);
            }
        }

        return $postType;
    }

    public static function getEventGeneralKey($postType = 'event', $isCheckWPML = false)
    {
        $key = wilokeListingToolsRepository()->get('event-settings:keys', true)->sub('general');
        //		if ( $isCheckWPML && defined('ICL_LANGUAGE_CODE') ){
        //			$key .= '_' . ICL_LANGUAGE_CODE;
        //		}
        return str_replace('event_', $postType . '_', $key);
    }

    public static function getEventFieldKey($postType = 'event')
    {
        $key = wilokeListingToolsRepository()
            ->get('event-settings:designFields', true)
            ->sub('usedSectionKey');
        return str_replace('event', $postType, $key);
    }

    public static function getEventContentFieldKey($postType = 'event')
    {
        if (empty($postType)) {
            $postType = 'event';
        }

        return $postType . '_content_fields';
    }


    public static function getUsedSectionKey($postType)
    {
        $key = wilokeListingToolsRepository()->get('addlisting:usedSectionKey');

        return str_replace('add_listing', 'add_' . $postType, $key);
    }

    public static function getUsedSectionSavedAt($postType)
    {
        $key = wilokeListingToolsRepository()->get('addlisting:usedSectionSavedAtKey');

        return str_replace('add_listing', 'add_' . $postType, $key);
    }

    public static function getClaimKey($postType)
    {
        return $postType . '_claim_settings';
    }

    public static function getSchemaMarkupKey($postType)
    {
        return $postType . '_schema_markup';
    }

    public static function getSchemaMarkupSavedAtKey($postType)
    {
        return $postType . '_schema_markup_saved_at';
    }

    /**
     * @param $postType
     * @return string
     */
    public static function getSearchFieldsKey($postType)
    {
        return $postType . '_search_fields';
    }

    public static function getSearchFieldToggleKey($postType)
    {
        return $postType . '_toggle';
    }

    /**
     * @param $postType
     * @return string
     */
    public static function mainSearchFormSavedAtKey($postType)
    {
        return $postType . 'main_search_form_' . $postType . '_saved_at';
    }

    public static function getHeroSearchFieldsKey($postType)
    {
        return $postType . '_hero_search_fields';
    }

    public static function heroSearchFormSavedAt($postType)
    {
        return $postType . 'hero_search_form_' . $postType . '_saved_at';
    }

    public static function getReviewKey($type, $postType)
    {
        $aReviews = wilokeListingToolsRepository()->get('reviews');

        return $postType . '_' . $aReviews[$type];
    }

    public static function numberFormat($number, $decimals)
    {
        return number_format($number, $decimals);
    }

    /*
     * @settingType: navigation or sidebar
     * @postType: Post Type
     */
    public static function getSingleListingSettingKey($settingType, $postType)
    {
        $key = wilokeListingToolsRepository()->get('listing-settings:keys', true)->sub($settingType);

        return $postType . '_' . $key;
    }

    public static function isPostType($postType)
    {
        if (!is_admin()) {
            return false;
        }

        return self::detectPostTypeSubmission() == $postType;
    }

    /**
     * @param bool $isIncludedDefaults
     * @param bool $exceptEvents
     * @param bool $isReturnArrayAssoc
     * @param bool $isFocus
     * @return array
     */
    public static function getPostTypes(
        $isIncludedDefaults = true,
        $exceptEvents = false,
        $isReturnArrayAssoc = true,
        $isFocus = false
    ): array
    {
        $key = 'post_type';

        if ($isIncludedDefaults) {
            $key = $key . '_with_default';
        }

        if ($exceptEvents) {
            $key = $key . '_with_event';
        }

        if (!$isFocus && isset(self::$aCachePostType[$key]) && is_array(self::$aCachePostType[$key])) {
            return $isReturnArrayAssoc ? self::$aCachePostType[$key] : array_values(self::$aCachePostType[$key]);
        }

        $aDefaults = [
            'post' => [
                'key'           => 'post',
                'slug'          => 'post',
                'singular_name' => esc_html__('Post', 'wiloke-listing-tools'),
                'name'          => esc_html__('Posts', 'wiloke-listing-tools'),
                'icon'          => ''
            ],
            'page' => [
                'key'           => 'page',
                'slug'          => 'page',
                'singular_name' => esc_html__('Page', 'wiloke-listing-tools'),
                'name'          => esc_html__('Pages', 'wiloke-listing-tools'),
                'icon'          => ''
            ]
        ];

        $aCustomPostTypes = GetSettings::getOptions(
            wilokeListingToolsRepository()->get('addlisting:customPostTypesKey'), $isFocus, true
        );

        if ($isIncludedDefaults) {
            $aPostTypes = !empty($aCustomPostTypes) && is_array($aCustomPostTypes) ? $aCustomPostTypes +
                $aDefaults : $aDefaults;
        } else {
            $aPostTypes = $aCustomPostTypes;
        }

        if (empty($aPostTypes)) {
            $aPostTypes = [
                [
                    'key'           => 'listing',
                    'slug'          => 'listing',
                    'singular_name' => 'Listing',
                    'name'          => 'Listings',
                    'icon'          => ''
                ],
                [
                    'key'           => 'event',
                    'slug'          => 'event',
                    'singular_name' => 'Event',
                    'name'          => 'Events',
                    'icon'          => ''
                ]
            ];
        }
        $aPostTypesWithKey = [];
        foreach ($aPostTypes as $aInfo) {
            if ($exceptEvents) {
                if ((!isset($aInfo['group']) && $aInfo['key'] === 'event') ||
                    (isset($aInfo['group']) && $aInfo['group'] === 'event')) {
                    continue;
                }
            }

            if (isset($aInfo['isDisabled']) && $aInfo['isDisabled'] === 'yes') {
                continue;
            }
            $aPostTypesWithKey[$aInfo['key']] = [];
            $aPostType = [
                'name'          => isset($aInfo['name']) ? $aInfo['name'] : 'My Custom Post Type',
                'singular_name' => isset($aInfo['singular_name']) ? $aInfo['singular_name'] : 'My Custom Post Type',
                'icon'          => isset($aInfo['icon']) ? $aInfo['icon'] : 'la la-rocket',
                'bgColor'       => isset($aInfo['addListingLabelBg']) ? $aInfo['addListingLabelBg'] : '',
                'bgImg'         => isset($aInfo['bgImg']) ? $aInfo['bgImg'] : [],
                'desc'          => isset($aInfo['desc']) ? $aInfo['desc'] : '',
                'endpoint'      => $aInfo['key'] . 's',
                'postType'      => $aInfo['key'],
                'menu_name'     => $aInfo['singular_name'] . ' Settings',
                'menu_slug'     => $aInfo['key'] . '_settings'
            ];

            if (!isset($aInfo['group'])) {
                if ($aInfo['key'] === 'event') {
                    $aPostType['group'] = 'event';
                } else {
                    $aPostType['group'] = 'listing';
                }
            } else {
                $aPostType['group'] = $aInfo['group'];
            }

            $aPostTypesWithKey[$aInfo['key']] = $aPostType;
        }

        $aPostTypesWithKey = apply_filters('wilcity/filter/directory-types', $aPostTypesWithKey);
        self::$aCachePostType[$key] = $aPostTypesWithKey;

        return $isReturnArrayAssoc ? $aPostTypesWithKey : array_values($aPostTypesWithKey);
    }

    public static function getPostTypesGroup($group = 'all')
    {
        $aPostTypes = self::getPostTypes(false, false);

        if ($group === 'all') {
            return $aPostTypes;
        }

        $aPostTypeGroup = [];
        foreach ($aPostTypes as $postType => $aSettings) {
            if ($aSettings['group'] == $group) {
                $aPostTypeGroup[] = $aSettings;
            }
        }

        return $aPostTypeGroup;
    }

    public static function getPostTypeKeysGroup($group = 'all')
    {
        if ($group === 'all') {
            return self::getPostTypeKeys(false, false);
        }

        $aPostTypes = self::getPostTypesGroup($group);

        if (!empty($aPostTypes)) {
            $aPostTypes = array_reduce(
                $aPostTypes, function ($result, $item) {
                $result[] = $item['postType'];
                return $result;
            }
            );
        }

        return $aPostTypes;
    }

    public static function getPostTypeGroup($postType, $defaultGroup = 'listing')
    {
        if (empty($postType)) {
            return false;
        }

        $postType = is_array($postType) ? $postType[0] : $postType;
        $aPostTypes = self::getPostTypes(false);

        if (!is_array($aPostTypes) || !isset($aPostTypes[$postType])) {
            return false;
        }
        return !isset($aPostTypes[$postType]['group']) ? $defaultGroup : $aPostTypes[$postType]['group'];
    }

    public static function isPostTypeInGroup($postTypes, $group, $defaultGroup = 'listing'): bool
    {
        if (is_array($postTypes)) {
            foreach ($postTypes as $postType) {
                $postTypeGroup = self::getPostTypeGroup($postType, $defaultGroup);
                if ($postTypeGroup === $group) {
                    return true;
                }
            }

            return false;
        }

        $postTypeGroup = self::getPostTypeGroup($postTypes, $defaultGroup);
        return $postTypeGroup === $group;
    }

    public static function getPostTypeSettings($postType): array
    {
        $aPostTypes = self::getPostTypes(false, false);

        return $aPostTypes[$postType] ?? [];
    }

    public static function getPostTypeKeys($isIncludedDefaults, $exceptEvents = false, $isFocus = false)
    {
        $aPostTypes = self::getPostTypes($isIncludedDefaults, $exceptEvents, true, $isFocus);

        return $aPostTypes ? array_keys($aPostTypes) : false;
    }

    public static function isPostTypeSubmission($currentPostType, $isIncludedDefaults = false,
                                                $exceptEvent = false): bool
    {
        $aPostTypeKeys = self::getPostTypeKeys($isIncludedDefaults, $exceptEvent);

        return in_array($currentPostType, $aPostTypeKeys);
    }

    public static function getDefaultPostTypeKey($exceptEvent = false, $isAddListing = false)
    {
        $aDirectoryType = $isAddListing ? GetSettings::getFrontendPostTypes(true) : self::getPostTypeKeys(
            false,
            $exceptEvent
        );

        return array_shift($aDirectoryType);
    }

    public static function getDefaultPostType($exceptEvent = false)
    {
        $aDirectoryType = self::getPostTypes(false, $exceptEvent);

        return array_shift($aDirectoryType);
    }

    public static function getFirstPostTypeKey($isIncludedDefaults, $exceptEvents = false)
    {
        $aPostTypes = self::getPostTypeKeys($isIncludedDefaults, $exceptEvents);
        if ($aPostTypes) {
            return array_shift($aPostTypes);
        }

        return false;
    }

    public static function getPostTypeOptions($isIncludedDefaults, $exceptEvents = false)
    {
        $aPostTypes = self::getPostTypes($isIncludedDefaults, $exceptEvents);
        $aOptions = [];
        foreach ($aPostTypes as $postType => $aInfo) {
            $aOptions[$postType] = $aInfo['singular_name'];
        }

        return $aOptions;
    }

    public static function generateBusinessHours()
    {
        if (self::$aBusinessHours !== null) {
            return self::$aBusinessHours;
        }

        $aCreatingAM = [];
        $aForm = apply_filters(
            'wilcity/filter/business-hours-skeleton',
            wilokeListingToolsRepository()->get('addlisting:aFormBusinessHour')
        );

        for ($i = 0; $i <= 11; $i = $i + 1) {
            $aGenerated = [];
            foreach ($aForm as $key => $aItem) {
                if ($i > 9) {
                    $newHour = $i;
                } else {
                    $newHour = '0' . $i;
                }
                $aGenerated['value'] = str_replace('00::', $newHour . ':', $aItem['value']);
                $twentyFormat = $newHour == '00' ? 12 : $newHour;
                $aGenerated['name'] = str_replace('00:', $twentyFormat . ':', $aItem['name']);
                // $aGenerated['name24'] = date('H:i', strtotime($aGenerated['value']));

                $aCreatingAM[] = $aGenerated;
            }
        }

        $aCreatingAM = array_merge($aCreatingAM, [['name' => '24hours', 'value' => '24:00:00']]);
        self::$aBusinessHours = $aCreatingAM;

        $aCreatingPM = [];

        for ($i = 12; $i < 24; $i++) {
            $aGenerated = [];
            foreach ($aForm as $key => $aItem) {
                if ($i == 12) {
                    $newHour = $i;
                } else {
                    $newHour = $i - 12;
                }

                $aGenerated['value'] = str_replace('00::', $i . ':', $aItem['value']);
                $aGenerated['name'] = str_replace(['00:', 'AM'], [$newHour . ':', 'PM'], $aItem['name']);
                $aCreatingPM[] = $aGenerated;
            }
        }
        $aCreatingPM = array_merge($aCreatingPM, [['name' => '24hours', 'value' => '24:00:00']]);

        self::$aBusinessHours = array_merge(self::$aBusinessHours, $aCreatingPM);

        return self::$aBusinessHours;
    }

    public static function getDayOfWeek($day)
    {
        $aDaysOfWeek = wilokeListingToolsRepository()->get('general:aDayOfWeek');

        return $aDaysOfWeek[$day];
    }

    public static function getPostsStatus($isGetAny = false)
    {
        $aCustom = wilokeListingToolsRepository()->get('posttypes:post_statuses');

        $aPostStatuses = [];
        if ($isGetAny) {
            $aPostStatuses['any'] = [
                'label' => esc_html__('Any', 'wiloke-listing-tools'),
                'icon'  => 'la la-globe',
                'id'    => 'any'
            ];
        }

        $aPostStatuses['publish'] = [
            'label' => esc_html__('Published', 'wiloke-listing-tools'),
            'icon'  => 'la la-share-alt',
            'id'    => 'publish'
        ];

        $aPostStatuses['pending'] = [
            'label' => esc_html__('In Review', 'wiloke-listing-tools'),
            'icon'  => 'la la-refresh',
            'id'    => 'pending'
        ];

        foreach ($aCustom as $postType => $aInfo) {
            $aPostStatuses[$postType] = [
                'label' => $aInfo['label'],
                'icon'  => $aInfo['icon'],
                'id'    => $postType
            ];
        }

        return $aPostStatuses;
    }

    public static function generateMetaKey($name): string
    {
        return wilokeListingToolsRepository()->get('general:metaboxPrefix') . $name;
    }

    public static function addPrefixToPromotionPosition($position)
    {
        return 'wilcity_promote_' . $position;
    }

    public static function findField($postType, $fieldKey)
    {
        if (isset(self::$aFieldSettings[$postType]) && isset(self::$aFieldSettings[$postType][$fieldKey])) {
            return self::$aFieldSettings[$postType][$fieldKey];
        }

        if (!isset(self::$aFieldSettings[$postType])) {
            self::$aFieldSettings[$postType] = [];
        }

        $aSettings = GetSettings::getOptions(General::getUsedSectionKey($postType), false, true);
        if (is_array($aSettings)) {
            foreach ($aSettings as $aField) {
                if ($aField['key'] == $fieldKey) {
                    self::$aFieldSettings[$postType][$fieldKey] = $aField;

                    return self::$aFieldSettings[$postType][$fieldKey];
                }
            }
        }
    }

    public static function buildSelect2OptionForm($post)
    {
        $aTemporary['id'] = $post->ID;
        $aTemporary['text'] = $post->post_title;
        $aTemporary['label'] = $post->post_title;

        return $aTemporary;
    }

    public static function printVal($aFieldSettings)
    {
        echo '<pre>';
        var_export($aFieldSettings);
        echo '</pre>';
        die();
    }

    public static function taxonomyPostTypeCacheKey()
    {
        return 'get_taxonomy_saved_at';
    }

    public static function renderWhatsApp($link)
    {
        if (strpos($link, 'http') === false) {
            if (preg_match('/[0-9]/', $link)) {
                $link = 'https://api.whatsapp.com/send?phone=' . $link;
            } else {
                $link = 'https://api.whatsapp.com/send?text=' . $link;
            }
        }

        return $link;
    }

    public static function getFBID($url)
    {
        if (empty($url)) {
            return false;
        }

        $aThemeOptions = Wiloke::getThemeOptions(true);
        if (
            empty($aThemeOptions['fb_api_id']) || empty($aThemeOptions['fb_app_secret']) ||
            empty($aThemeOptions['fb_access_token'])
        ) {
            return false;
        }

        /* PHP SDK v5.0.0 */
        /* make the API call */
        try {
            $fb = new Facebook([
                'app_id'                => $aThemeOptions['fb_api_id'],
                'app_secret'            => $aThemeOptions['fb_app_secret'],
                'default_graph_version' => 'v3.1',
            ]);
        }
        catch (FacebookSDKException $e) {
        }

        $aUrl = explode('/', $url);

        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = $fb->get(
                '/',
                end($aUrl),
                $aThemeOptions['fb_access_token']
            );
        }
        catch (FacebookResponseException $e) {
            return false;
        }
        catch (FacebookSDKException $e) {
            return false;
        }
        try {
            $aStatus = $response->getGraphNode();
        }
        catch (FacebookSDKException $e) {
        }
        if (is_array($aStatus) && isset($aStatus['id'])) {
            return $aStatus['id'];
        }

        return $aStatus;
        /* handle the result */
    }

    public static function isRemoveWooCommerceSection()
    {
        return General::$isBookingFormOnSidebar || (isset($_REQUEST['iswebview']) && $_REQUEST['iswebview'] == 'yes');
    }

    public static function isEnableDebug()
    {
        $status = GetWilokeSubmission::getField('toggle_debug');

        return $status == 'enable';
    }

    public static function getDebugAddListingStep($text = '')
    {
        $step = 'Step ' . self::$step;
        self::$step = self::$step++;

        return $step . ':' . $text;
    }
}
