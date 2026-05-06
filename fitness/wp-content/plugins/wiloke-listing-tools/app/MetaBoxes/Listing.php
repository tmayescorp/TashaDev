<?php

namespace WilokeListingTools\MetaBoxes;

use Wiloke;
use WilokeListingTools\AlterTable\AlterTableBusinessHours;
use WilokeListingTools\AlterTable\AlterTableLatLng;
use WilokeListingTools\Framework\Helpers\AddListingFieldSkeleton;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\SearchFormSkeleton;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Submission;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Frontend\BusinessHours;
use WilokeSocialNetworks;

class Listing extends Controller
{
    protected     $aSection;
    public static $aDefault
                                       = [
            'lat'     => '',
            'lng'     => '',
            'address' => ''
        ];
    public        $aPostTypes          = [];
    private       $aRelationshipValues = [];
    private       $countRelationships  = 0;

    public function __construct()
    {
        add_filter('cmb2_sanitize_wiloke_map', [$this, 'savePWMAP'], 10, 4);
        add_action('cmb2_admin_init', [$this, 'timezoneBox'], 10);
        add_filter('cmb2_render_wilcity_date_time', [$this, 'renderDateTimeField'], 10, 5);
        add_filter('cmb2_sanitize_wilcity_date_time', [$this, 'sanitizeDateTimeCallBack'], 10, 2);
        add_filter('cmb2_render_wilcity_social_networks', [$this, 'renderSocialNetworks'], 10, 5);
        add_action('cmb2_admin_init', [$this, 'registerMyProductsMetaBox'], 10);
        add_action('cmb2_admin_init', [$this, 'registerMyPosts'], 10);
        add_action('cmb2_admin_init', [$this, 'registerRestaurantMenu'], 10);
        add_action('add_meta_boxes', [$this, 'registerMetaBoxes'], 15);
        add_action('save_post', [$this, 'saveSettingsInWP52'], 10, 3);
        add_action('init', [$this, 'saveSettingsWP53'], 1);

        add_filter('wiloke-listing-tools/map-field-values', [__CLASS__, 'getListingAddress']);
        add_action('wp_ajax_wilcity_get_timezone_by_latlng', [$this, 'getTimezoneByLatLng']);
    }

    public function modifySaveCustomRelationshipViaBackend($metaID, $listingID, $metaKey, $metaVal)
    {
        if (!General::isAdmin()) {
            return false;
        }

        if (strpos($metaKey, 'wilcity_custom_') === false || strpos($metaKey, '_relationship') === false) {
            return false;
        }

        global $wpdb;
        $this->countRelationships = $this->countRelationships + 1;
        if ($this->countRelationships != count($_POST[$metaKey])) {
            SetSettings::deletePostMeta($listingID, $metaKey);
        }

        if (!empty($_POST[$metaKey]) && $this->countRelationships == count($_POST[$metaKey])) {
            $aRelationshipIDs = array_map(function ($postID) {
                global $wpdb;

                return $wpdb->_real_escape($postID);
            }, $_POST[$metaKey]);
            $wpdb->update(
                $wpdb->postmeta,
                [
                    'meta_value' => implode(',', $aRelationshipIDs)
                ],
                [
                    'meta_key' => $metaKey,
                    'post_id'  => $listingID
                ],
                [
                    '%s'
                ],
                [
                    '%s',
                    '%d'
                ]
            );
            $this->countRelationships = 1;
        }
    }

    public static function getListingRelations($aArgs)
    {
        if (current_user_can('administrator')) {
            return '';
        }

        if (!isset($_GET['post']) || empty($_GET['post'])) {
            return '';
        }

        $ids = GetSettings::getPostMeta($_GET['post']);
    }

    public static function setListingTypesOptions()
    {
        $aListingTypes = General::getPostTypes(false, false);
        $aOptions = [];
        foreach ($aListingTypes as $postType => $aPostType) {
            $aOptions[$postType] = $aPostType['singular_name'];
        }

        return $aOptions;
    }

    public function getTimezoneByLatLng()
    {
        if (!isset($_POST['latLng']) || empty($_POST['latLng'])) {
            wp_send_json_error();
        }

        $aThemeOptions = Wiloke::getThemeOptions();
        $url
            = 'https://maps.googleapis.com/maps/api/timezone/json?location=' . $_POST['latLng'] . '&timestamp=' .
            time() .
            '&key=' . $aThemeOptions['general_google_web_service_api'];
        $response = wp_remote_get(esc_url_raw($url));
        if (is_wp_error($response)) {
            wp_send_json_error();
        } else {
            $body = wp_remote_retrieve_body($response);
            $aBody = json_decode($body, true);

            if ($aBody['status'] === 'REQUEST_DENIED') {
                wp_send_json_error(['msg' => $aBody['errorMessage']]);
            }

            wp_send_json_success($aBody['timeZoneId']);
        }
    }

    public function getPostTypes()
    {
        if (!empty($this->aPostTypes)) {
            return $this->aPostTypes;
        }

        $this->aPostTypes = General::getPostTypeKeysGroup('listing');

        return $this->aPostTypes;
    }

    public function sanitizeDateTimeCallBack($override_value, $value)
    {
        return sanitize_text_field($value);
    }

    public function renderDateTimeField(
        $field, $field_escaped_value, $field_object_id, $field_object_type, $field_type_object)
    {
        $name = str_replace('wilcity_', '', $field->args('_name'));

        $val = GetSettings::getPostMeta($field_object_id, $name);

        $field_type_object->_desc(true, true); ?>
        <input type="datetime-local" name="<?php echo esc_attr($field->args('_name')); ?>" class="regular-text"
               id="<?php echo esc_attr($field->args('_name')); ?>" value="<?php echo esc_attr($val); ?>">
        <?php
    }

    protected static function isDataExisting($listingID, $dayOfWeek)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . AlterTableBusinessHours::$tblName;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM $tbl WHERE objectID=%d AND dayOfWeek=%s",
                $listingID,
                $dayOfWeek
            )
        );
    }

    public static function updateBusinessHourTbl($listingID, $dayOfWeek, $aBusinessHour, $timezone = '')
    {
        global $wpdb;
        $tbl = $wpdb->prefix . AlterTableBusinessHours::$tblName;
        $fromTimezone = !empty($timezone) ? $timezone : GetSettings::getPostMeta($listingID, 'timezone');

        if (empty($fromTimezone)) {
            $fromTimezone = Time::getDefaultTimezoneString();

            if (!$fromTimezone && wp_doing_ajax()) {
                wp_send_json_error([
                    'msg' => esc_html__('Please use Timezone String instead of UTC timezone offset: Settings &gt;  General',
                        'wiloke-listing-tools')
                ]);
            }
        }

        if (isset($aBusinessHour['firstOpenHour']) && $aBusinessHour['firstOpenHour'] == '24:00:00') {
            $aBusinessHour['firstCloseHour'] = '24:00:00';
            $aBusinessHour['secondOpenHour'] = null;
            $aBusinessHour['secondCloseHour'] = null;
        }

        if ($id = self::isDataExisting($listingID, $dayOfWeek)) {
            unset($aBusinessHour['objectID']);

            if (empty($aBusinessHour['firstCloseHour']) || empty($aBusinessHour['firstOpenHour'])) {
                $aBusinessHour['firstOpenHour'] = null;
                $aBusinessHour['firstCloseHour'] = null;
                $aBusinessHour['firstOpenHourUTC'] = null;
                $aBusinessHour['firstCloseHourUTC'] = null;
                $aBusinessHour['isOpen'] = 'no';
            } else {
                $aBusinessHour['firstOpenHourUTC']
                    = Time::convertToTimezoneUTC($aBusinessHour['firstOpenHour'], $fromTimezone, 'H:i:s');
                $aBusinessHour['firstCloseHourUTC']
                    = Time::convertToTimezoneUTC($aBusinessHour['firstCloseHour'], $fromTimezone, 'H:i:s');
            }

            if (!isset($aBusinessHour['secondOpenHour']) || !isset($aBusinessHour['secondCloseHour']) ||
                empty($aBusinessHour['secondOpenHour']) || empty($aBusinessHour['secondCloseHour']) ||
                ($aBusinessHour['secondOpenHour'] == $aBusinessHour['secondCloseHour'])) {
                $aBusinessHour['secondOpenHour'] = null;
                $aBusinessHour['secondCloseHour'] = null;
                $aBusinessHour['secondOpenHourUTC'] = null;
                $aBusinessHour['secondCloseHourUTC'] = null;
            }

            $status = $wpdb->update(
                $tbl,
                $aBusinessHour,
                [
                    'ID' => $id
                ],
                [
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s'
                ],
                [
                    '%d'
                ]
            );
            $isUpdate = true;
        } else {
            $aBusinessHour = array_merge([
                'objectID'  => $listingID,
                'dayOfWeek' => $dayOfWeek
            ], $aBusinessHour);

            if (!isset($aBusinessHour['secondOpenHour']) || !isset($aBusinessHour['secondCloseHour']) ||
                empty($aBusinessHour['secondOpenHour']) || empty($aBusinessHour['secondCloseHour'])) {
                unset($aBusinessHour['secondOpenHour']);
                unset($aBusinessHour['secondCloseHour']);

                if (!isset($aBusinessHour['firstOpenHour']) || empty($aBusinessHour['firstOpenHour']) || !isset
                    ($aBusinessHour['firstCloseHour']) || empty($aBusinessHour['firstCloseHour'])) {

                    $aBusinessHour['firstOpenHour'] = null;
                    $aBusinessHour['secondOpenHour'] = null;

                   $status = $wpdb->insert(
                        $tbl,
                        $aBusinessHour,
                        [
                            '%d',
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%s'
                        ]
                    );
                } else {
                    $aBusinessHour['firstOpenHourUTC']
                        = Time::convertToTimezoneUTC($aBusinessHour['firstOpenHour'], $fromTimezone, 'H:i:s');
                    $aBusinessHour['firstCloseHourUTC']
                        = Time::convertToTimezoneUTC($aBusinessHour['firstCloseHour'], $fromTimezone, 'H:i:s');

                    $status = $wpdb->insert(
                        $tbl,
                        $aBusinessHour,
                        [
                            '%d',
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%s'
                        ]
                    );
                }
            } else {
                $aBusinessHour['firstOpenHourUTC']
                    = Time::convertToTimezoneUTC($aBusinessHour['firstOpenHour'], $fromTimezone, 'H:i:s');

                $aBusinessHour['firstCloseHourUTC']
                    = Time::convertToTimezoneUTC($aBusinessHour['firstCloseHour'], $fromTimezone, 'H:i:s');

                $aBusinessHour['secondOpenHourUTC']
                    = Time::convertToTimezoneUTC($aBusinessHour['secondOpenHour'], $fromTimezone, 'H:i:s');
                $aBusinessHour['secondCloseHourUTC']
                    = Time::convertToTimezoneUTC($aBusinessHour['secondCloseHour'], $fromTimezone, 'H:i:s');

                $status = $wpdb->insert(
                    $tbl,
                    $aBusinessHour,
                    [
                        '%d',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s'
                    ]
                );
            }

            $isUpdate = false;
        }

        do_action('wilcity/wiloke-listing-tools/updated-business-hours', $listingID, $id, $aBusinessHour, $isUpdate);

        return $status;
    }

    private function setDefaults($listingID)
    {
        $averageRating = GetSettings::getPostMeta($listingID, 'average_reviews');
        if (!$averageRating) {
            SetSettings::setPostMeta($listingID, 'average_reviews', 0);
        }

        $countViewed = GetSettings::getPostMeta($listingID, 'count_viewed');
        if (!$countViewed) {
            SetSettings::setPostMeta($listingID, 'count_viewed', 0);
        }

        $countShared = GetSettings::getPostMeta($listingID, 'count_shared');
        if (!$countShared) {
            SetSettings::setPostMeta($listingID, 'count_shared', 0);
        }

        $countFavorites = GetSettings::getPostMeta($listingID, 'count_favorites');
        if (!$countFavorites) {
            SetSettings::setPostMeta($listingID, 'count_favorites', 0);
        }
    }

    public function saveSettingsWP53()
    {
        if (!$this->isWP53() || !$this->isSavedPostMeta()) {
            return false;
        }

        $this->saveSettings($_POST['post_ID'], get_post($_POST['post_ID']), true);

        if (isset($_POST['wilcity_location']) && !empty($_POST['wilcity_location'])) {
            $this->savePWMAP(null, $_POST['wilcity_location'], $_POST['post_ID'], null);
        }
    }

    public function saveSettingsInWP52($listingID, $post, $updated)
    {
        if (!current_user_can('administrator') || $this->isWP53()) {
            return false;
        }

        $this->saveSettings($listingID, $post, $updated);
        $this->saveRestaurantMenu($listingID, $post);
    }

    public function saveSettings($listingID, $post, $updated)
    {
        $aPostTypeKeys = General::getPostTypeKeys(true, false);
        if (!in_array($post->post_type, $aPostTypeKeys)) {
            return false;
        }

        $this->setDefaults($listingID);

        if (isset($_POST['wilcity_business_hours']) && !empty($_POST['wilcity_business_hours'])) {
            $aData = $_POST['wilcity_business_hours'];
            $timezone = isset($_POST['wilcity_timezone']) ? $_POST['wilcity_timezone'] : '';
            self::saveBusinessHours($listingID, $aData, $timezone);
        }

        if (isset($_POST['wilcity_belongs_to']) && !empty($_POST['wilcity_belongs_to'])) {
            $newBelongsToID = absint($_POST['wilcity_belongs_to']);
            $oldBelongsToID = absint(GetSettings::getPostMeta($listingID, 'belongs_to'));
            if ($newBelongsToID != $oldBelongsToID) {
                $plan = GetSettings::getPlanSettings($newBelongsToID);
                $menuOrder = isset($plan['menu_order']) ? absint($plan['menu_order']) : 0;
                self::saveMenuOrder($listingID, $menuOrder);
            }
        }

        if (isset($_POST['wilcity_my_posts']) && !empty($_POST['wilcity_my_posts'])) {
            $aData = $_POST['wilcity_my_posts'];

            foreach ($aData as $postOrder => $postID) {
                $postType = get_post_type($postID);
                if (empty($postType) || $postType != 'post') {
                    unset($aData[$postOrder]);
                }

                global $wpdb;
                $wpdb->update(
                    $wpdb->posts,
                    [
                        'post_parent' => $listingID
                    ],
                    [
                        'ID' => $postID
                    ],
                    [
                        '%d'
                    ],
                    [
                        '%d'
                    ]
                );
            }

            SetSettings::setPostMeta($listingID, 'my_posts', $aData);
        } else {
            $aOldPosts = GetSettings::getPostMeta($listingID, 'my_posts');
            if (!empty($aOldPosts) && is_array($aOldPosts)) {
                foreach ($aOldPosts as $postID) {
                    global $wpdb;
                    $wpdb->update(
                        $wpdb->posts,
                        [
                            'post_parent' => 0
                        ],
                        [
                            'ID' => $postID
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
            SetSettings::deletePostMeta($listingID, 'my_posts');
        }

        if (isset($_POST['wilcity_my_product_mode'])) {
            SetSettings::deletePostMeta($listingID, 'my_product_mode');
            SetSettings::setPostMeta($listingID, 'my_product_mode', $_POST['wilcity_my_product_mode']);
        }

        // array [1,2,3]
        if (isset($_POST['wilcity_my_products'])) {
            $aMyProducts = array_map('absint', $_POST['wilcity_my_products']);
            SetSettings::setPostMeta($listingID, 'my_products', $aMyProducts);
        } else {
            SetSettings::deletePostMeta($listingID, 'my_products');
        }
        if (isset($_POST['wilcity_my_product_cats'])) {
            $aMyProducts = array_map('absint', $_POST['wilcity_my_product_cats']);
            SetSettings::setPostMeta($listingID, 'my_product_cats', $aMyProducts);
        } else {
            SetSettings::deletePostMeta($listingID, 'my_product_cats');
        }

        if (isset($_POST['wilcity_my_room'])) {
            $aMyProducts = absint($_POST['wilcity_my_room']);
            SetSettings::setPostMeta($listingID, 'my_room', $aMyProducts);
        } else {
            SetSettings::deletePostMeta($listingID, 'my_room');
        }
    }

    public static function saveMenuOrder($post_id, $menu_order)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'posts';
        $status = $wpdb->update(
            $table,
            [
                'menu_order' => $menu_order
            ],
            ['ID' => $post_id],
            [
                '%d'
            ],
            ['%d']
        );

        return $status;
    }

    public static function saveBusinessHours($listingID, $aData, $timezone = '')
    {
        SetSettings::setPostMeta($listingID, 'hourMode', $aData['hourMode']);
        SetSettings::setPostMeta($listingID, 'timeFormat', $aData['timeFormat']);

        if ($aData['hourMode'] == 'open_for_selected_hours') {
            foreach (wilokeListingToolsRepository()->get('general:aDayOfWeek') as $dayOfWeek => $name) {
                $aBusinessHour = [];

                if (isset($aData['businessHours'][$dayOfWeek]['operating_times'])) {
                    foreach ($aData['businessHours'][$dayOfWeek]['operating_times'] as $key => $val) {
                        $aBusinessHour[sanitize_text_field($key)] = sanitize_text_field($val);
                    }

                    $aBusinessHour['isOpen'] = isset($aData['businessHours'][$dayOfWeek]['isOpen']) ?
                        sanitize_text_field($aData['businessHours'][$dayOfWeek]['isOpen']) : 'no';
                } else {
                    $aBusinessHour['isOpen'] = 'no';
                }

                self::updateBusinessHourTbl($listingID, $dayOfWeek, $aBusinessHour, $timezone);
            }
        }

        return true;
    }

    public static function getBusinessHoursOfDay($listingID, $dayOfWeek)
    {
        if (empty($listingID)) {
            return false;
        }

        global $wpdb;
        $tbl = $wpdb->prefix . AlterTableBusinessHours::$tblName;

        $aBusinessHours = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $tbl WHERE objectID=%d AND dayOfWeek=%s",
                $listingID,
                $dayOfWeek
            ),
            ARRAY_A
        );

        if (empty($aBusinessHours) || !is_array($aBusinessHours)) {
            return [
                'ID'                => null,
                'objectID'          => $listingID,
                'dayOfWeek'         => $dayOfWeek,
                'isOpen'            => 'no',
                'firstOpenHour'     => null,
                'firstCloseHour'    => null,
                'firstOpenHourUTC'  => null,
                'firstCloseHourUTC' => null
            ];
        }

        return $aBusinessHours;
    }

    public static function getBusinessHoursOfListing($listingID)
    {
        if (empty($listingID)) {
            return false;
        }

        global $wpdb;
        $tbl = $wpdb->prefix . AlterTableBusinessHours::$tblName;

        $aBusinessHours = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $tbl WHERE objectID=%d ORDER BY FIELD(dayOfWeek, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')",
                $listingID
            ),
            ARRAY_A
        );

        if (empty($aBusinessHours)) {
            return false;
        }

        return $aBusinessHours;
    }

    public function timezoneBox()
    {
        $postID = isset($_GET['post']) && !empty($_GET['post']) ? $_GET['post'] : '';

        if (is_array($postID)) {
            return false;
        }

        $aListingTimezone = wilokeListingToolsRepository()->get('listing-settings:timezone');
        $aPostTypes = General::getPostTypeKeys(false, true);
        $aListingTimezone['object_types'] = $aPostTypes;
        new_cmb2_box($aListingTimezone);
    }

    public function registerMyProductsMetaBox()
    {
        if (!class_exists('WooCommerce') || !$this->isCurrentAdminListingType()) {
            return false;
        }

        if (!$this->isDisableMetaBlock(['fieldKey' => 'my_products'])) {
            $aMyProducts = wilokeListingToolsRepository()->get('listing-settings:myProducts');
            new_cmb2_box($aMyProducts);
        }

        if (!$this->isDisableMetaBlock(['fieldKey' => 'my_room'])) {
            $aMyRoom = wilokeListingToolsRepository()->get('listing-settings:myRoom');
            new_cmb2_box($aMyRoom);
        }
    }

    public function registerMyPosts()
    {
        if (!$this->isCurrentAdminListingType() || $this->isDisableMetaBlock(['fieldKey' => 'my_posts'])) {
            return false;
        }

        $aMyPosts = wilokeListingToolsRepository()->get('listing-settings:myPosts');
        new_cmb2_box($aMyPosts);
    }

    public function saveRestaurantMenu($postID, $post)
    {
        if (!isset($_POST['wilcity_number_restaurant_menus']) || empty($_POST['wilcity_number_restaurant_menus'])) {
            return false;
        }

        $aRestaurantMenuPrefixKeys = [
            'wilcity_group_title_',
            'wilcity_group_description_',
            'wilcity_group_icon_',
            'wilcity_restaurant_menu_group_'
        ];
        //        $aNewGroupOrders           = explode(',', $_POST['wilcity_number_restaurant_menus']);
        for ($order = 0; $order < $_POST['wilcity_number_restaurant_menus']; $order++) {
            foreach ($aRestaurantMenuPrefixKeys as $prefixKey) {
                if (isset($_POST[$prefixKey . $order])) {
                    SetSettings::setPostMeta($postID, $prefixKey . $order, $_POST[$prefixKey . $order]);
                }
            }
        }
    }

    public function registerRestaurantMenu($addNew = false)
    {
        if (!$this->isCurrentAdminListingType() || $this->isDisableMetaBlock(['fieldKey' => 'restaurant_menu'])) {
            return false;
        }

        if (!$addNew) {
            new_cmb2_box(wilokeListingToolsRepository()->get('listing-settings:myNumberRestaurantMenus'));
        }

        $aRestaurantMenu = wilokeListingToolsRepository()->get('listing-settings:myRestaurantMenu');

        $aGeneralSettings = $aRestaurantMenu['general_settings'];
        unset($aRestaurantMenu['general_settings']);

        $aGroupFields = $aRestaurantMenu['group_fields'];
        unset($aRestaurantMenu['group_fields']);

        $aGeneralGroupInfoSettings = $aGeneralSettings['general_settings'];
        $aGeneralGroupSettings = $aGeneralSettings['group_settings'];

        if (isset($_GET['post']) && !empty($_GET['post'])) {
            $numberOfMenus = GetSettings::getPostMeta($_GET['post'], 'number_restaurant_menus');
        }

        if (empty($numberOfMenus)) {
            $numberOfMenus = 1;
        }

        $originalFieldID = $aGeneralGroupSettings['id'];

        if ($addNew) {
            $start = $numberOfMenus;
            $numberOfMenus = $numberOfMenus + 1;
        } else {
            $start = 0;
        }

        for ($i = $start; $i < $numberOfMenus; $i++) {
            $aMenuGroup = $aRestaurantMenu;

            $aMenuGroup['id'] = $aRestaurantMenu['id'] . '_' . $i;
            $aMenuGroup['title'] = $aRestaurantMenu['title'] . ' ' . $i;
            $oCmbRepeat = new_cmb2_box($aMenuGroup);

            foreach ($aGeneralGroupInfoSettings as $aGeneralField) {
                $aGeneralField['id'] = $aGeneralField['id'] . '_' . $i;
                $oCmbRepeat->add_field($aGeneralField);
            }

            $aGroupFieldsSetting = $aGeneralGroupSettings;
            $aGroupFieldsSetting['id'] = $originalFieldID . '_' . $i;

            $oGroupFieldInit = $oCmbRepeat->add_field($aGroupFieldsSetting);
            foreach ($aGroupFields as $groupField) {
                $oCmbRepeat->add_group_field($oGroupFieldInit, $groupField);
            }
        }
    }

    public static function getMyProductMode()
    {
        if (!isset($_GET['post']) || empty($_GET['post'])) {
            return '';
        }

        return GetSettings::getPostMeta($_GET['post'], 'my_product_mode');
    }

    public static function getMyProducts()
    {
        if (!isset($_GET['post']) || empty($_GET['post'])) {
            return false;
        }

        $productIds = GetSettings::getPostMeta($_GET['post'], 'my_products');
        if (empty($productIds)) {
            return false;
        }

        $aParseIds = array_filter($productIds, function ($id) {
            return get_post_field('ID', $id);
        });

        return implode(',', $aParseIds);
    }

    public static function getMyProductCats()
    {
        if (!isset($_GET['post']) || empty($_GET['post'])) {
            return false;
        }

        $catIds = GetSettings::getPostMeta($_GET['post'], 'my_product_cats');

        if (empty($catIds)) {
            return false;
        }

        $catIds = array_filter($catIds, function ($id) {
            $termField = get_term_field('term_id', $id, 'product_cat');

            return $id == $termField;
        });

        return implode(',', $catIds);
    }

    public static function getMyPosts()
    {
        if (!isset($_GET['post']) || empty($_GET['post'])) {
            return false;
        }

        return GetSettings::getPostMeta($_GET['post'], 'my_posts');
    }

    public static function getMyRoom()
    {
        if (!isset($_GET['post']) || empty($_GET['post'])) {
            return false;
        }

        return GetSettings::getPostMeta($_GET['post'], 'my_room');
    }

    public function registerMetaBoxesUseCMBTwo()
    {
        $this->getPostTypes();
        $aSettings = GetSettings::getPromotionPlans();
        if (empty($aSettings)) {
            return false;
        }

        $aPositions = [];
        foreach ($aSettings as $planKey => $aSetting) {
            $aPositions[] = [
                'type' => 'text_datetime_timestamp',
                'id'   => 'wilcity_promote_' . $planKey,
                'name' => $aSetting['name'] . ' (Expiration)'
            ];
        }

        new_cmb2_box([
            'id'           => 'listing_ads_box',
            'title'        => 'Ads Positions',
            'context'      => 'normal',
            'object_types' => $this->getPostTypes(),
            'priority'     => 'low',
            'show_names'   => true, // Show field names on the left
            'fields'       => $aPositions
        ]);
    }

    public function registerMetaBoxes()
    {
        if (!$this->isCurrentAdminListingType() || $this->isDisableMetaBlock(['fieldKey' => 'business_hours'])) {
            return false;
        }

        $this->getPostTypes();
        add_meta_box(
            'wilcity-business-hours',
            'Business Hours',
            [$this, 'renderBusinessHourSettings'],
            $this->aPostTypes,
            'normal'
        );
    }

    public function listingScripts()
    {
        if (General::isPostType('listing')) {
            wp_enqueue_script('vuejs', WILOKE_LISTING_TOOL_URL . 'admin/assets/vue/vue.js', [], '2.5.13', true);
        }
    }

    public function renderBusinessHourSettings($post)
    {
        $aHours = General::generateBusinessHours();
        $timeFormat = GetSettings::getPostMeta($post->ID, 'timeFormat');
        $hourMode = GetSettings::getPostMeta($post->ID, 'hourMode');
        ?>
        <div class="cmb2-wrap form-table">
            <div class="cmb2-metabox cmb-field-list">
                <div class="cmb-row cmb-type-select">
                    <div class="cmb-th">
                        <label for="wilcity_business_hourMode"><?php esc_html_e('Hour Mode',
                                'wiloke-listing-tools'); ?></label>
                    </div>
                    <div class="cmb-td">
                        <select name="wilcity_business_hours[hourMode]" id="wilcity_business_hourMode"
                                class="cmb2_select">
                            <option value="no_hours_available" <?php selected($hourMode, 'no_hours_available'); ?>>
                                <?php esc_html_e('No Hours Available', 'wiloke-listing-tools'); ?>
                            </option>
                            <option value="open_for_selected_hours" <?php selected($hourMode,
                                'open_for_selected_hours'); ?>>
                                <?php esc_html_e('Open For Selected Hours', 'wiloke-listing-tools'); ?>
                            </option>
                            <option value="always_open" <?php selected($hourMode, 'always_open'); ?>>
                                <?php esc_html_e('Always Open', 'wiloke-listing-tools'); ?>
                            </option>
                            <option value="business_closures" <?php selected($hourMode, 'business_closures'); ?>>
                                <?php esc_html_e('Business closures', 'wiloke-listing-tools'); ?>
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="cmb2-metabox cmb-field-list">
                <div class="cmb-row cmb-type-select">
                    <div class="cmb-th">
                        <label for="wilcity_business_timeFormat"><?php esc_html_e('Time Format',
                                'wiloke-listing-tools'); ?></label>
                    </div>
                    <div class="cmb-td">
                        <select name="wilcity_business_hours[timeFormat]" id="wilcity_business_timeFormat"
                                class="cmb2_select">
                            <option value="inherit" <?php selected($timeFormat, 'inherit'); ?>>
                                <?php esc_html_e('Inherit Theme Options', 'wiloke-listing-tools'); ?>
                            </option>
                            <option value="12" <?php selected($timeFormat, 12); ?>>
                                <?php esc_html_e('12-Hour Format', 'wiloke-listing-tools'); ?>
                            </option>
                            <option value="24" <?php selected($timeFormat, 24); ?>>
                                <?php esc_html_e('24-Hour Format', 'wiloke-listing-tools'); ?>
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <?php
            $wrapperBHClass
                = $hourMode != 'open_for_selected_hours' ? 'hidden cmb2-metabox cmb-field-list wilcity-bh-settings' :
                'wilcity-bh-settings cmb2-metabox cmb-field-list'; ?>
            <div class="<?php echo esc_attr($wrapperBHClass); ?>">
                <div class="cmb-row cmb-type-table">
                    <div class="cmb-th">
                        <label for="wilcity_business_hours_mode"><?php esc_html_e('Business Hours',
                                'wiloke-listing-tools'); ?></label>
                        <p>Warning: The timezone value is required. In case, you want to inherit the Timezone setting
                            from
                            General ->
                            Settings, make sure that it's GMT format, please do not use UTC format.</p>
                        <p><i style="font-weight: normal">You can set the default Business Hours at Appearance -> Theme
                                Options
                                ->
                                Listing</i></p>
                    </div>
                    <div class="cmb-td">
                        <div class="table-responsive">
                            <table class="table table-bordered profile-hour">
                                <thead>
                                <tr>
                                    <th>Day</th>
                                    <th>Business Hours</th>
                                    <th>Is Settings Available?</th>
                                </tr>
                                </thead>

                                <?php
                                $aThemeOptions = Wiloke::getThemeOptions(true);
                                $aDefaultBusinessHours = [
                                    'firstOpenHour'   => isset($aThemeOptions['listing_default_opening_hour']) ?
                                        $aThemeOptions['listing_default_opening_hour'] : '',
                                    'firstCloseHour'  => isset($aThemeOptions['listing_default_closed_hour']) ?
                                        $aThemeOptions['listing_default_closed_hour'] : '',
                                    'secondOpenHour'  => isset($aThemeOptions['listing_default_second_opening_hour']) ?
                                        $aThemeOptions['listing_default_second_opening_hour'] : '',
                                    'secondCloseHour' => isset($aThemeOptions['listing_default_second_closed_hour']) ?
                                        $aThemeOptions['listing_default_second_closed_hour'] : '',
                                    'isOpen'          => 'yes'
                                ];

                                $hasBusiness = false;
                                $aBusinessHours = [];
                                foreach (wilokeListingToolsRepository()->get('general:aDayOfWeek') as $key => $day) :
                                    if (isset($_GET['post']) && !empty($_GET['post'])) {
                                        $aBusinessHours = self::getBusinessHoursOfDay($_GET['post'], $key);
                                    }

                                    if (!empty($aBusinessHours)) {
                                        $hasBusiness = true;
                                    }

                                    if (!$hasBusiness) {
                                        $aBusinessHours = $aDefaultBusinessHours;
                                    } else {
                                        $aBusinessHours = wp_parse_args(
                                            $aBusinessHours,
                                            [
                                                'firstOpenHour'   => null,
                                                'firstCloseHour'  => null,
                                                'secondOpenHour'  => null,
                                                'secondCloseHour' => null,
                                                'isOpen'          => 'yes'
                                            ]
                                        );
                                    } ?>
                                    <tr>
                                        <td><?php echo esc_html($day); ?>
                                        </td>
                                        <td>
                                            <div>
                                                <select
                                                    class="wil-select-business-hour wil-first-open-business-hour"
                                                    name="wilcity_business_hours[businessHours][<?php echo esc_attr($key) ?>][operating_times][firstOpenHour]">
                                                    <option value="">---</option>
                                                    <?php foreach ($aHours as $aHour) : ?>
                                                        <option
                                                            value="<?php echo esc_attr($aHour['value']); ?>" <?php selected($aBusinessHours['firstOpenHour'],
                                                            $aHour['value']); ?>>
                                                            <?php echo esc_attr($aHour['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <select
                                                    class="wil-select-business-hour wil-first-close-business-hour"
                                                    name="wilcity_business_hours[businessHours][<?php echo esc_attr($key) ?>][operating_times][firstCloseHour]">
                                                    <option value="">---</option>
                                                    <?php foreach ($aHours as $aHour) : ?>
                                                        <option
                                                            value="<?php echo esc_attr($aHour['value']); ?>" <?php selected($aBusinessHours['firstCloseHour'],
                                                            $aHour['value']); ?>>
                                                            <?php echo esc_attr($aHour['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div>
                                                <select
                                                    class="wil-select-business-hour wil-second-open-business-hour"
                                                    name="wilcity_business_hours[businessHours][<?php echo esc_attr($key) ?>][operating_times][secondOpenHour]">
                                                    <option value="">---</option>
                                                    <?php foreach ($aHours as $aHour) : ?>
                                                        <option
                                                            value="<?php echo esc_attr($aHour['value']); ?>" <?php selected($aBusinessHours['secondOpenHour'],
                                                            $aHour['value']); ?>>
                                                            <?php echo esc_attr($aHour['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <select
                                                    class="wil-select-business-hour wil-second-close-business-hour"
                                                    name="wilcity_business_hours[businessHours][<?php echo esc_attr($key) ?>][operating_times][secondCloseHour]">
                                                    <option value="">---</option>
                                                    <?php foreach ($aHours as $aHour) : ?>
                                                        <option
                                                            value="<?php echo esc_attr($aHour['value']); ?>" <?php
                                                        selected($aBusinessHours['secondCloseHour'],
                                                            $aHour['value']); ?>>
                                                            <?php echo esc_attr($aHour['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </td>
                                        <td>
                                            <label for="bh-available-<?php echo esc_attr($key); ?>"
                                                   class="input-checkbox">
                                                <input id="bh-available-<?php echo esc_attr($key); ?>" type="checkbox"
                                                       name="wilcity_business_hours[businessHours][<?php echo esc_attr($key) ?>][isOpen]"
                                                       value="yes" <?php echo isset($aBusinessHours['isOpen']) &&
                                                $aBusinessHours['isOpen'] == 'yes' ?
                                                    'checked' : ''; ?> value="yes">
                                                <span></span>
                                            </label>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public static function getListingAddress($postID)
    {
        if (empty($postID)) {
            return self::$aDefault;
        }

        global $wpdb;
        $tbl = $wpdb->prefix . AlterTableLatLng::$tblName;

        $aResult = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . $tbl . ' WHERE objectID=%d',
                $postID
            ),
            ARRAY_A
        );

        if (empty($aResult)) {
            return self::$aDefault;
        }

        $aResult['googleMapUrl'] = esc_url('https://www.google.com/maps/search/' . urlencode($aResult['address']));

        return $aResult;
    }

    public static function isUpdate($objectID)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . AlterTableLatLng::$tblName;

        $id = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT ID FROM ' . $tbl . ' WHERE objectID=%d',
                $objectID
            )
        );

        return !empty($id);
    }

    public static function removeGoogleAddress($objectID)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . AlterTableLatLng::$tblName;
        $status = $wpdb->delete(
            $tbl,
            [
                'objectID' => $objectID
            ],
            [
                '%d'
            ]
        );

        return $status;
    }

    /**
     * @param $objectID
     * @param $aGoogleAddress ['address' => '', 'lat' => '', 'lng' => ''],
     */
    public static function saveData($objectID, $aGoogleAddress)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . AlterTableLatLng::$tblName;
        if (self::isUpdate($objectID)) {
            $wpdb->update(
                $tbl,
                $aGoogleAddress,
                [
                    'objectID' => $objectID
                ],
                [
                    '%s',
                    '%s',
                    '%s'
                ],
                [
                    '%d'
                ]
            );
        } else {
            $aGoogleAddress['objectID'] = $objectID;
            $wpdb->insert(
                $tbl,
                $aGoogleAddress,
                [
                    '%s',
                    '%s',
                    '%s',
                    '%d'
                ]
            );
        }
    }

    private function isUsingMapOnAddListing($postType)
    {
        $aField = (new AddListingFieldSkeleton($postType))->getFieldParam('listing_address', 'fieldGroups');
        if (empty($aField) || !isset($aField['address']['isEnable']) || $aField['address']['isEnable'] === 'no') {
            return false;
        }

        return true;
    }

    public function savePWMAP($override_value, $value, $object_id, $field_args)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . AlterTableLatLng::$tblName;

        if (empty($value['lat']) || empty($value['lng']) || empty($value['address'])) {
            if ($this->isUsingMapOnAddListing(get_post_type($object_id))) {
                $wpdb->delete(
                    $tbl,
                    [
                        'objectID' => $object_id
                    ],
                    [
                        '%d'
                    ]
                );
            }
            return false;
        }

        $aGoogleAddress['lat'] = floatval($value['lat']);
        $aGoogleAddress['lng'] = floatval($value['lng']);
        $aGoogleAddress['address'] = $value['address'];

        self::saveData($object_id, $aGoogleAddress);
    }

    public function renderSocialNetworks($field, $fieldEscapedValue, $fieldObjectID, $fieldObjectType, $oFieldType)
    {
        switch ($field->args('is')) {
            case 'usermeta':
                $aSocialNetworks = GetSettings::getUserMeta($fieldObjectID, 'social_networks');
                break;
            default:
                $aSocialNetworks = GetSettings::getPostMeta($fieldObjectID, 'social_networks');
                break;
        }

        foreach (WilokeSocialNetworks::$aSocialNetworks as $socialKey) {
            ?>
            <div>
                <label><strong><?php echo ucfirst($socialKey); ?></strong></label>
                <?php
                echo $oFieldType->input([
                    'title' => ucfirst($socialKey),
                    'type'  => 'text',
                    'id'    => $field->args('_name') . $socialKey,
                    'name'  => $field->args('_name') . '[' . $socialKey . ']',
                    'value' => isset($aSocialNetworks[$socialKey]) ? $aSocialNetworks[$socialKey] : '',
                    'class' => 'large-text',
                    'desc'  => '',
                ]); ?>
            </div>
            <?php
        }
    }
}
