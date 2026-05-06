<?php

namespace WilokeListingTools\Register;

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\Inc;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\General;

class RegisterMobileMenu
{
    protected $slug = 'wilcity-mobile-menu';
    use ListingToolsGeneralConfig;

    private static   $mainMenuKey                = 'mobile_main_menu';
    private static   $secondaryMenuKey           = 'mobile_secondary_menu';
    private          $sKeyWord                   = '';
    private          $aStacks
                                                 = [
            'home'    => 'homeStack',
            'event'   => 'eventStack',
            'account' => 'accountStack',
            'menu'    => 'menuStack',
            'rest'    => 'listingStack',
            'page'    => 'pageStack',
            'posts'   => 'blogStack'
        ];
    private static   $aMainMenuSettings          = [];
    private static   $aAvailableMainMenuSettings = [];
    protected static $aSecondaryMenu
                                                 = [
            [
                'key'      => 'home',
                'name'     => 'Home',
                'iconName' => 'home',
                'screen'   => 'homeStack'
            ]
        ];
    protected static $aDefaultMainMenu
                                                 = [
            [
                'key'      => 'home',
                'name'     => 'Home',
                'iconName' => 'home',
                'screen'   => 'homeStack',
                'status'   => 'enable'
            ],
            [
                'key'      => 'listing',
                'name'     => 'Listing',
                'iconName' => 'map-pin',
                'screen'   => 'listingStack',
                'status'   => 'enable'
            ],
            [
                'key'      => 'event',
                'name'     => 'Event',
                'iconName' => 'calendar',
                'screen'   => 'eventStack',
                'status'   => 'enable'
            ],
            [
                'key'      => 'account',
                'name'     => 'Profile',
                'iconName' => 'user',
                'screen'   => 'accountStack',
                'status'   => 'enable'
            ],
            [
                'key'      => 'menu',
                'name'     => 'Secondary Menu',
                'iconName' => 'three-line',
                'screen'   => 'menuStack',
                'status'   => 'enable'
            ]
        ];

    public function __construct()
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('wp_ajax_wilcity_get_page_id', [$this, 'getPageID']);
//        add_action('wp_ajax_wilcity_get_directory_key', [$this, 'getListingDirectoryKey']);
        add_action('wp_ajax_wilcity_get_listing_directory_key', [$this, 'getListingDirectoryKey']);
        add_action('wp_ajax_wilcity_get_event_directory_key', [$this, 'getEventDirectoryKey']);
        add_action('wp_ajax_wilcity_save_main_mobile_menu_settings', [$this, 'saveMobileMenuSettings']);
        add_action('wp_ajax_wilcity_save_secondary_menu_settings', [$this, 'saveSecondaryMenuSettings']);
    }

    private function saveSecondary($aData)
    {
        if (!current_user_can('edit_theme_options')) {
            return false;
        }

        if (empty($aData)) {
            SetSettings::deleteOption(self::$secondaryMenuKey, true);
        } else {
	        SetSettings::setOptions(self::$secondaryMenuKey, $aData, true);
        }

        return true;
    }

    public function saveSecondaryMenuSettings()
    {
        $status = $this->saveSecondary($_POST['data']);
        if ($status) {
            wp_send_json_success([
                'msg' => 'Congratulations! The Main Apps Menu has been setup successfully!'
            ]);
        } else {
            wp_send_json_error([
                'msg' => 'Oos! You do not have permission to access this page or the data is emptied now.'
            ]);
        }
    }

    private function saveMainMenu($aData)
    {
        if (!current_user_can('edit_theme_options')) {
            return false;
        }

        if (empty($aData)) {
            SetSettings::deleteOption(self::$mainMenuKey, true);
        } else {
            foreach ($aData as $order => $aSegment) {
                if (isset($this->aStacks[$aSegment['key']])) {
                    $aData[$order]['screen'] = $this->aStacks[$aSegment['key']];
                } else {
                    $aData[$order]['screen'] = 'listingStack';
                }
            }
            SetSettings::setOptions(self::$mainMenuKey, self::unSlashDeep($aData), true);
        }

        return true;
    }

    public function saveMobileMenuSettings()
    {
        $status = $this->saveMainMenu($_POST['data']);
        if ($status) {
            wp_send_json_success([
                'msg' => 'Congratulations! The Main Apps Menu has been setup successfully!'
            ]);
        } else {
            wp_send_json_error([
                'msg' => 'Oos! You do not have permission to access this page or the data is emptied now.'
            ]);
        }
    }

    public function getPageID()
    {
        if (!current_user_can('administrator')) {
            wp_send_json_error();
        }
        global $wpdb;

        $this->sKeyWord = isset($_GET['s']) ? $wpdb->_real_escape(strtolower($_GET['s'])) : '';

        $query = new \WP_Query([
            'post_type'      => 'page',
            'posts_per_page' => 20,
            'post_status'    => 'publish',
            's'              => $this->sKeyWord
        ]);
        if (!$query->have_posts()) {
            wp_send_json_error();
        }

        $aResponse = [];
        while ($query->have_posts()) {
            $query->the_post();
            $aResponse[] = [
                'name'  => $query->post->post_title,
                'value' => $query->post->ID
            ];
        }
        echo json_encode([
            'results' => $aResponse
        ]);
        die();
    }

    public function getEventDirectoryKey()
    {
        $this->getPostTypesGroup('event');
    }

    public function getListingDirectoryKey()
    {
        $this->getPostTypesGroup('listing');
    }

    protected function getPostTypesGroup($group)
    {
        if (!current_user_can('administrator')) {
            wp_send_json_error();
        }
        global $wpdb;

        $this->sKeyWord = isset($_GET['s']) ? $wpdb->_real_escape(strtolower($_GET['s'])) : '';
        $aAllDirectoryPostTypes = General::getPostTypeKeysGroup($group);

        if (!empty($this->sKeyWord)) {
            $aMatchedDirectoryTypes = array_filter($aAllDirectoryPostTypes, function ($type) {
                return strpos($type, $this->sKeyWord) !== false;
            });
        } else {
            $aMatchedDirectoryTypes = $aAllDirectoryPostTypes;
        }

        $aResponse = [];
        foreach ($aMatchedDirectoryTypes as $postType) {
            $aResponse[] = [
                'name'  => $postType,
                'value' => $postType
            ];
        }

        echo json_encode([
            'results' => $aResponse
        ]);
        die();
    }

    public function registerMenu()
    {
        if (!defined('WILCITY_MOBILE_APP')) {
            return false;
        }

        add_submenu_page('wiloke-listing-tools', 'Mobile Menu', 'Mobile Menu', 'edit_theme_options', $this->slug,
            [$this, 'settings']);
    }

    protected static function getSecondaryMenuItems()
    {
        return wilokeListingToolsRepository()->get('mobile-menus:aSecondaryMenu');
    }

    protected static function parseSecondaryMenuSettings()
    {
        $aOptions = self::getSecondaryUsedFields();
        $aConfigurations = wilokeListingToolsRepository()->get('mobile-menus:aSecondaryMenu');
        $aSettings = [];

        foreach ($aOptions as $aOption) {
            if (!isset($aConfigurations[$aOption['screen']])) {
                continue;
            }
            $aSegment = $aConfigurations[$aOption['screen']];
            foreach ($aSegment['aFields'] as $order => $aField) {
                if (isset($aOption[$aField['key']])) {
                    $aSegment['aFields'][$order]['value'] = $aOption[$aField['key']];
                }
            }

            if ($aOption['screen'] == 'pageStack') {
                $title = isset($aOption['name']) && !empty($aOption['name']) ? $aOption['name'] : $aOption['key'];
            } else {
                $title = $aOption['key'];
            }

            $aSegment['oGeneral']['heading'] = $title;
            $aSettings[] = $aSegment;

        }

        return $aSettings;
    }

    protected static function getSecondaryUsedFields()
    {
        $aOptions = GetSettings::getOptions(self::$secondaryMenuKey, false, true);
        if (empty($aOptions) || !is_array($aOptions)) {
            SetSettings::setOptions(self::$secondaryMenuKey, self::$aSecondaryMenu, true);

            return self::$aSecondaryMenu;
        }

        return $aOptions;
    }

    public static function getMainMenuSettings()
    {
        $aOptions = GetSettings::getOptions(self::$mainMenuKey, false, true);
        if (empty($aOptions) || !is_array($aOptions)) {
            SetSettings::setOptions(self::$mainMenuKey, self::$aDefaultMainMenu);
            $aOptions = self::$aDefaultMainMenu;
        }

        return $aOptions;
    }

    private static function parseMainMenuSettings()
    {
        $aOptions = self::getMainMenuSettings();
        $aConfigurations = wilokeListingToolsRepository()->get('mobile-menus:aMainMenu');
        $aSettings = [];
        foreach ($aOptions as $aOption) {
            if (!isset($aConfigurations[$aOption['screen']])) {
                continue;
            }
            $aSegment = $aConfigurations[$aOption['screen']];
            foreach ($aSegment['aFields'] as $order => $aField) {
                if (isset($aOption[$aField['key']])) {
                    $aSegment['aFields'][$order]['value'] = $aOption[$aField['key']];
                }
            }
            $aSettings[] = $aSegment;
        }

        return $aSettings;
    }

    private static function getAvailableMainMenuSettings()
    {
        $aConfigurations = wilokeListingToolsRepository()->get('mobile-menus:aMainMenu');
        if (empty(self::$aMainMenuSettings)) {
            self::$aAvailableMainMenuSettings = $aConfigurations;

            return self::$aAvailableMainMenuSettings;
        }

        $aUsedKeys = array_map(function ($aField) {
            return $aField['oGeneral']['key'];
        }, self::$aMainMenuSettings);

        foreach ($aConfigurations as $key => $aSetting) {
            if (!in_array($key, $aUsedKeys) ||
                (isset($aSetting['oGeneral']['isClone']) && $aSetting['oGeneral']['isClone'] == 'yes')) {
                self::$aAvailableMainMenuSettings[] = $aSetting;
            }
        }

        return self::$aAvailableMainMenuSettings;
    }

    public function settings()
    {
        Inc::file('mobile-menu:index');
    }

    public function enqueueScripts($hook)
    {
        if (strpos($hook, $this->slug) === false) {
            return false;
        }
        self::$aMainMenuSettings = self::parseMainMenuSettings();
        $aAvailableMainMenuItems = self::getAvailableMainMenuSettings();
        $aSecondaryMenuItems = self::getSecondaryMenuItems();
        //        $aAvailableSecondaryMenuMenuItems     = array_values($aSecondaryMenuItems);
        //        $aUsedMenuItems          = self::parseSecondaryMenuSettings();
        $this->requiredScripts();
        $this->draggable();
        $this->generalScripts();

        wp_enqueue_script('general-design-tool', WILOKE_LISTING_TOOL_URL . 'admin/source/js/general.js', ['jquery'],
            WILOKE_LISTING_TOOL_VERSION, true);
        wp_register_script('wilcity-mobile-menu', WILOKE_LISTING_TOOL_URL .
            'admin/source/js/mobile-menu.js', ['jquery'],
            WILOKE_LISTING_TOOL_VERSION, true);
        wp_enqueue_script('wilcity-mobile-menu');
        wp_localize_script('wilcity-mobile-menu', 'WILOKE_MAIN_MOBILE_MENU', [
            'allFields'       => wilokeListingToolsRepository()->get('mobile-menus:aMainMenu'),
            'usingFields'     => self::$aMainMenuSettings,
            'availableFields' => $aAvailableMainMenuItems,
            'value'           => self::getMainMenuSettings(),
        ]);

        wp_localize_script('wilcity-mobile-menu', 'WILOKE_SECONDARY_MENU', [
            'allFields'       => wilokeListingToolsRepository()->get('mobile-menus:aSecondaryMenu'),
            'usingFields'     => self::parseSecondaryMenuSettings(),
            'availableFields' => array_values($aSecondaryMenuItems),
            'value'           => self::getSecondaryUsedFields()
        ]);
    }
}
