<?php

namespace WilokeListingTools\Controllers;

use Wilcity\Map\FactoryMap;
use WilokeListingTools\AlterTable\AlterTableEventsData;
use WilokeListingTools\Framework\Helpers\AjaxMsg;
use WilokeListingTools\Framework\Helpers\GalleryHelper;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\HTML;
use WilokeListingTools\Framework\Helpers\Message;
use WilokeListingTools\Framework\Helpers\QueryHelper;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Submission;
use WilokeListingTools\Framework\Helpers\TermSetting;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Helpers\WPML;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Framework\Upload\Upload;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\EventModel;
use WilokeListingTools\Models\UserModel;
use WilokeListingTools\Frontend\SingleListing;
use WilokeThemeOptions;
use WP_Post;
use WP_Query;

class EventController extends Controller
{
    use SingleJsonSkeleton;
    use SetListingBelongsToPlanID;
    use Validation;
    use GetSingleImage;
    use SetPlanRelationship;
    use PrintAddListingSettings;
    use SetPostDuration;
    use GetWilokeToolSettings;
    use MergingSettingValues;
    use BelongsToCategories;
    use BelongsToLocation;
    use BelongsToTags;
    use SetCustomSections;
    use SetGeneral;
    use InsertImg;
    use SetContactInfo;
    use InsertAddress;
    use InsertGallery;
    use SetVideo;
    use SetProductsToListing;
    use SetPriceRange;
    use SetSinglePrice;
    use HandleSubmit;
    use SetSocialNetworks;
    use SetCustomGroup;
    use InsertFeaturedImg;

    protected $aPlanSettings     = [];
    public    $aData             = [];
    public    $postType          = 'event';
    public    $postStatus;
    public    $eventPlanPostType = 'event_plan';
    //    public $parentListingID;
    protected     $postID       = null;
    protected     $listingID;
    protected     $planID;
    protected     $isNewListing = true;
    public static $aEventsData  = [];
    public static $aEventSkeleton
                                = [
            'name'        => '',
            'content'     => '',
            'img'         => '',
            'video'       => '',
            'weekly'      => '',
            'daily'       => '',
            'occurs_once' => '',
            'frequency'   => '',
            'address'     => [
                'address' => '',
                'lat'     => '',
                'lng'     => ''
            ]
        ];
    public        $aEventsDataAndPrepares
                                = [
            'parentID'      => '%d',
            'frequency'     => '%s',
            'starts'        => '%s',
            'endsOn'        => '%s',
            'openingAt'     => '%s',
            'closedAt'      => '%s',
            'timezone'      => '%s',
            'specifyDays'   => '%s',
            'weekly'        => [
                'specifyDays' => '%s'
            ],
            'googleAddress' => [
                'address' => '%s',
                'lat'     => '%s',
                'lng'     => '%s'
            ],
            'address'       => [
                'address' => '%s',
                'lat'     => '%s',
                'lng'     => '%s'
            ]
        ];

    public function __construct()
    {
        add_action('wp_ajax_wilcity_edit_event', [$this, 'editEvent']);
        add_action('wp_ajax_wilcity_get_event_data', [$this, 'getEventItemData']);
        add_action('wp_ajax_wilcity_fetch_events', [$this, 'fetchEvents']);
        add_action('wp_ajax_nopriv_wilcity_fetch_events', [$this, 'fetchEvents']);

        add_filter('wilcity/ajax/post-comment/event', [$this, 'ajaxBeforePostComment'], 10, 2);
        add_filter('wilcity/determining/reviewPostType/of/event', [$this, 'setReviewPostType']);
        add_filter('wilcity/addMiddlewareToReview/of/event', [$this, 'addMiddleWareToReviewHandler']);
        add_filter('wilcity/addMiddlewareOptionsToReview/of/event', [$this, 'addMiddleWareOptionsToReviewHandler']);
        add_action('wiloke/wilcity/addlisting/print-fields/event', [$this, 'printAddEventFields']);
        add_action('wiloke/wilcity/addlisting/print-sidebar-items/event', [$this, 'printAddEventSidebars']);
        add_action('wilcity/addlisting/validation/event_calendar', [$this, 'validateEventCalendar'], 10, 2);
        add_action(
            'wilcity/addlisting/validation/event_belongs_to_listing',
            [$this, 'validateEventBelongsToListing'],
            10,
            2
        );
        add_action('wp_ajax_wilcity_fetch_events_json', [$this, 'fetchEventsJson']);
        add_action('wp_ajax_wilcity_load_more_events', [$this, 'loadMoreListings']);
        add_action('wp_ajax_nopriv_wilcity_load_more_events', [$this, 'loadMoreListings']);
        add_action('wp_ajax_wilcity_fetch_count_author_event_types', [$this, 'countAuthorEventTypes']);

        add_action('wilcity/single-event/calendar', [__CLASS__, 'renderEventCalendar'], 10, 2);
        add_action('wilcity/single-event/meta-data', [$this, 'renderEventMetaData'], 10, 1);
        add_filter('wilcity/dashboard/navigation', [$this, 'profileNavigation']);
        add_filter('wilcity/filter-listing-slider/meta-data', [$this, 'addDataToEventGrid'], 10, 3);
        add_filter('wilcity/wilcity-mobile-app/post-event-discussion', [$this, 'appBeforePostComment'], 10, 2);
        add_filter('wilcity/wilcity-mobile-app/put-event-discussion', [$this, 'appBeforeUpdateComment'], 10, 3);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_filter('wilcity/filter/wiloke-listing-tools/show-expired-event', [$this, 'handleShowExpiredEvent']);
        add_action('wilcity/update-db', [$this, 'resolveEventDate']);
        add_action('wilcity/single-event/before/event-header', [$this, 'renderEventBreadCrumbAboveHeader']);
        add_action('wilcity/single-event/after/event-header', [$this, 'renderEventBreadCrumbBelowHeader']);
        add_shortcode('wilcity_event_breadcrumb', [$this, 'breadcrumbShortcode']);
    }

    public function renderEventBreadCrumbAboveHeader(WP_Post $post)
    {
        if (!WilokeThemeOptions::isEnable('event_toggle_event_breadcrumb')) {
            return;
        }

        if (WilokeThemeOptions::getOptionDetail('event_breadcrumb_position') == 'below_featured_image') {
            return;
        }

        echo $this->breadcrumbShortcode();
    }

    public function renderEventBreadCrumbBelowHeader(WP_Post $post)
    {
        if (!WilokeThemeOptions::isEnable('event_toggle_event_breadcrumb')) {
            return;
        }

        if (WilokeThemeOptions::getOptionDetail('event_breadcrumb_position') == 'above_featured_image') {
            return;
        }
        echo $this->breadcrumbShortcode();
    }

    public function breadcrumbShortcode($aAtts = [])
    {
        global $post;

        $searchId = WilokeThemeOptions::getOptionDetail('search_page');
        $aEventSettings = General::getPostTypeSettings($post->post_type);
        ob_start();
        ?>
        <ul class="wil-breadcrumb">
            <li>
                <a href="<?php echo home_url('/'); ?>">
                    <?php esc_html_e('Home', 'wiloke-listing-tools'); ?>
                </a>
            </li>
            <?php if (!empty($searchId)): ?>
                <li>
                    <a href="<?php echo add_query_arg(['postType' => $post->post_type], get_permalink($searchId)) ?>">
                        <?php echo esc_html($aEventSettings['name']); ?></a>
                </li>
            <?php endif; ?>
        </ul>
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public function resolveEventDate()
    {
        if (!current_user_can('administrator')) {
            return;
        }

        if (isset($_POST['wilcity_update_db_nonce_field']) &&
            wp_verify_nonce($_POST['wilcity_update_db_nonce_field'], 'wilcity_update_db_action')
        ) {
            $query = new WP_Query([
                'post_type'      => General::getPostTypeKeysGroup('event'),
                'posts_per_page' => 1000,
                'paged'          => 1,
                'post_status'    => 'publish'
            ]);

            global $wpdb;
            $tblName = $wpdb->prefix . AlterTableEventsData::$tblName;

            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $aEventDate = EventModel::getEventData($query->post->ID);
                    if (!empty($aEventDate)) {
                        $startsOn = $aEventDate['startsOn'];
                        $endsOn = $aEventDate['endsOn'];

                        $startsOnUTC = Time::convertFromCurrentSiteTimezoneToUTC($startsOn);
                        $endsOnUTC = Time::convertFromCurrentSiteTimezoneToUTC($endsOn);

                        $aUpdates['values']['startsOn'] = $startsOn;
                        $aUpdates['values']['endsOn'] = $endsOn;
                        $aUpdates['values']['startsOnUTC'] = $startsOnUTC;
                        $aUpdates['values']['endsOnUTC'] = $endsOnUTC;
                        $aUpdates['prepares'][] = '%s';
                        $aUpdates['prepares'][] = '%s';
                        $aUpdates['prepares'][] = '%s';
                        $aUpdates['prepares'][] = '%s';

                        $status = $wpdb->update(
                            $tblName,
                            [
                                'startsOnUTC' => $aUpdates['values']['startsOnUTC'],
                                'endsOnUTC'   => $aUpdates['values']['endsOnUTC']
                            ],
                            [
                                'objectID' => absint($query->post->ID)
                            ],
                            [
                                '%s',
                                '%s',
                            ],
                            [
                                '%d'
                            ]
                        );
                    }
                }
            }
            wp_reset_postdata();
        }
    }


    public function handleShowExpiredEvent($status): bool
    {
        return WilokeThemeOptions::isEnable('event_show_expired_event', false);
    }

    protected function getGallery($post)
    {
        $aRawGallery = GetSettings::getPostMeta($post->ID, 'gallery');
        if (empty($aRawGallery)) {
            return [
                'items' => []
            ];
        };

        $aGallery = GalleryHelper::gallerySkeleton($aRawGallery, 'medium');

        return [
            'items' => $aGallery
        ];
    }

    public function enqueueScripts()
    {
        global $post;
        if (!class_exists('WilokeListingTools\Framework\Helpers\Submission') || !is_singular()) {
            return false;
        }

        if (!\WilokeListingTools\Framework\Helpers\General::isPostTypeInGroup([$post->post_type], 'event')) {
            return false;
        }

        wp_localize_script('wilcity-empty', 'WIL_SINGLE_EVENT', [
            'gallery' => $this->getGallery($post),
            'coupon'  => $this->getCouponInfo($post)
        ]);
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
        if (empty($aCoupon['popup_image'])) {
            $aCoupon['popup_image'] = WilokeThemeOptions::getThumbnailUrl('listing_coupon_popup_img', 'large');
        }

        //        $aCoupon['expiry_date'] = date_i18n(get_option('date_format').' '.get_option('time_format'), $aCoupon['expiry_date']);
        return $aCoupon;
    }


    public function addDataToEventGrid($aListing, $post)
    {
        $aEventCalendarSettings = GetSettings::getEventSettings($post->ID);
        $aListing['interestedClass'] = UserModel::isMyFavorite($post->ID) ? 'la la-star color-primary' : 'la la-star-o';
        $aListing['hostedByName'] = GetSettings::getEventHostedByName($post);
        $aListing['hostedByURL'] = GetSettings::getEventHostedByUrl($post);
        $aListing['hostedByTarget'] = GetSettings::getEventHostedByTarget($aListing['hostedByURL']);

        $aListing['startAt'] = date_i18n('d', strtotime($aEventCalendarSettings['startsOn']));
        $aListing['startsOn'] = date_i18n('M', strtotime($aEventCalendarSettings['startsOn']));

        return $aListing;
    }

    public static function getEventStatuses($isKey = true)
    {
        $aTranslation = GetSettings::getTranslation();
        if (!$isKey) {
            return $aTranslation['aEventStatus'];
        }

        return array_map(function ($aEventStatus) {
            return [$aEventStatus['post_status']];
        }, $aTranslation['aEventStatus']);
    }

    public function profileNavigation($aNavigation)
    {
        $aCustomPostTypes = GetSettings::getOptions(
            wilokeListingToolsRepository()->get('addlisting:customPostTypesKey'),
            false,
            true
        );

        if (is_array($aCustomPostTypes)) {
            foreach ($aCustomPostTypes as $aInfo) {
                if ($aInfo['key'] === 'event' && isset($aInfo['isDisabled']) && $aInfo['isDisabled'] === 'yes') {
                    unset($aNavigation['events']);
                }
            }
        }

        return $aNavigation;
    }

    public static function getEventMetaData($post)
    {
        $aMapInformation = GetSettings::getListingMapInfo($post->ID);
        $aMetaData = [];
        if (!empty($aMapInformation) && !empty($aMapInformation['address']) && !empty($aMapInformation['lat']) &&
            !empty($aMapInformation['lng'])) {
            $aMetaData[] = [
                'icon'  => 'la la-map-marker',
                'type'  => 'map',
                'value' => $aMapInformation
            ];
        }

        $oTerm = wp_get_post_terms($post->ID, 'listing_location');
        if ($oTerm) {
            $aMetaData[] = [
                'icon'  => '',
                'type'  => 'term',
                'value' => $oTerm
            ];
        }

        $oListingCat = wp_get_post_terms($post->ID, 'listing_cat');
        if (!empty($oListingCat) && !is_wp_error($oListingCat)) {
            $aMetaData[] = [
                'icon'  => '',
                'type'  => 'term',
                'value' => $oListingCat
            ];
        }

        $oListingTag = wp_get_post_terms($post->ID, 'listing_tag');
        if (!empty($oListingTag) && !is_wp_error($oListingTag)) {
            $aMetaData[] = [
                'icon'  => '',
                'type'  => 'term',
                'value' => $oListingTag
            ];
        }

        $email = GetSettings::getPostMeta($post->ID, 'email');
        if ($email) {
            $aMetaData[] = [
                'icon' => 'la la-envelope',
                'type' => 'email',
                'link' => 'mailto:' . $email,
                'name' => $email
            ];
        }

        $phone = GetSettings::getPostMeta($post->ID, 'phone');
        if ($phone) {
            $aMetaData[] = [
                'icon' => 'la la-phone',
                'type' => 'phone',
                'link' => 'tel:' . $phone,
                'name' => $phone
            ];
        }

        $website = GetSettings::getPostMeta($post->ID, 'website');
        if ($website) {
            $aMetaData[] = [
                'icon' => 'la la-link',
                'type' => 'website',
                'name' => $website,
                'link' => $website
            ];
        }

        $aPriceRange = GetSettings::getPriceRange($post->ID, true);

        if ($aPriceRange) {
            $aMetaData[] = [
                'icon' => 'la la-money',
                'type' => 'price_range',
                'name' => $aPriceRange['minimumPrice'] . ' - ' . $aPriceRange['maximumPrice'],
                'link' => get_permalink($post->ID)
            ];
        }

        $singlePrice = GetSettings::getPostMeta($post->ID, 'single_price');
        if (!empty($singlePrice)) {
            $aMetaData[] = [
                'icon' => 'la la-money',
                'type' => 'single_price',
                'name' => $singlePrice,
                'link' => get_permalink($post->ID)
            ];
        }

        return apply_filters('wiloke-listing-tools/single-event/meta-data', $aMetaData, $post);
    }

    public function renderEventMetaData($post)
    {
        if (!function_exists('wilcityRenderBoxIcon1')) {
            return '';
        }

        global $wiloke;
        $aMetaData = self::getEventMetaData($post);
        if (empty($aMetaData)) {
            return '';
        }
        foreach ($aMetaData as $aItem) : ?>
            <div class="event-detail-content_firstItem__3vz2x">
                <?php
                switch ($aItem['type']) {
                    case 'map':
                        echo wilcityRenderBoxIcon1([
                            'wrapper_classes' => 'icon-box-1_module__uyg5F event-detail-content_location__1UYZY wilcity-event-map',
                            'inner_classes'   => 'icon-box-1_block1__bJ25J',
                            'icon_classes'    => 'icon-box-1_icon__3V5c0 rounded-circle',
                            'content_classes' => 'icon-box-1_text__3R39g',
                            'link'            => 'https://www.google.com/maps/search/' .
                                stripslashes($aItem['value']['address']),
                            'name'            => stripslashes($aItem['value']['address']),
                            'icon'            => $aItem['icon'],
                            'target'          => '_blank'
                        ]);
                        $oMap = new FactoryMap();
                        $aMapSettings = $oMap->set()->getAllConfig();
                        $aLatLng['lat'] = $aItem['value']['lat'];
                        $aLatLng['lng'] = $aItem['value']['lng'];
                        ?>
                        <wil-toggle-controller btn-name="<?php echo esc_html__('Toggle', 'wiloke-listing-tools');
                        ?>" icon="fa fa-toggle-on">
                            <template v-slot:default="{isOpen}">
                                <component
                                    v-if="isOpen"
                                    :max-zoom="<?php echo absint($wiloke->aThemeOptions['single_event_map_max_zoom']); ?>"
                                    :min-zoom="<?php echo absint($wiloke->aThemeOptions['single_event_map_minimum_zoom']); ?>"
                                    :default-zoom="<?php echo absint($wiloke->aThemeOptions['single_event_map_default_zoom']); ?>"
                                    marker-url="<?php echo esc_url(SingleListing::getMapIcon($post)); ?>"
                                    :lat-lng='<?php echo json_encode($aLatLng); ?>'
                                    access-token="<?php echo esc_attr($aMapSettings['accessToken']); ?>"
                                    style="height: 200px;"
                                    is="<?php echo $aMapSettings['vueComponent']; ?>"
                                    map-style="<?php echo esc_attr($aMapSettings['style']); ?>"
                                >
                                </component>
                            </template>
                        </wil-toggle-controller>
                        <?php
                        break;
                    case 'term':
                        if (empty($aItem['value'])) {
                            break;
                        }
                        ?>
                        <div class="icon-box-1_module__uyg5F event-detail-content__cat">
                            <div class="row">
                                <?php
                                do_action(
                                    'wilcity/wiloke-listing-tools/app/Controllers/EventController/meta-data/term',
                                    $aItem
                                );

                                $aArgs = [];
                                if (TermSetting::isTermRedirectToSearch()) {
                                    $aArgs['postType'] = 'event';
                                }
                                foreach ($aItem['value'] as $oTerm) :
                                    ?>
                                    <div class="col-sm-4 mb-20">
                                        <div class="icon-box-1_block1__bJ25J mr-20 wil-event-item<?php echo esc_attr
                                        ($oTerm->taxonomy); ?>">
                                            <?php
                                            echo \WilokeHelpers::getTermIcon(
                                                $oTerm,
                                                'icon-box-1_icon__3V5c0 rounded-circle',
                                                true,
                                                $aArgs
                                            );
                                            ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php
                        break;
                    default:
                        $target = isset($aItem['target']) ? $aItem['target'] : '_self';
                        if (isset($aItem['value'])) {
                            $aItem['name'] = $aItem['value'];
                            $aItem['link'] = $aItem['value'];
                        }
                        echo wilcityRenderBoxIcon1([
                            'wrapper_classes' => 'icon-box-1_module__uyg5F event-detail-content_location__1UYZY ' .
                                $aItem['type'],
                            'inner_classes'   => 'icon-box-1_block1__bJ25J',
                            'icon_classes'    => 'icon-box-1_icon__3V5c0 rounded-circle',
                            'content_classes' => 'icon-box-1_text__3R39g',
                            'link'            => $aItem['link'],
                            'name'            => $aItem['name'],
                            'icon'            => $aItem['icon'],
                            'target'          => $target
                        ]);
                        break;
                }
                ?>
            </div>
        <?php
        endforeach;

        $planID = GetSettings::getPostMeta($post->ID, 'belongs_to');
        if (empty($planID) || GetSettings::isPlanAvailableInListing($planID, 'toggle_social_networks')) {
            $socialNetworks = do_shortcode('[wilcity_listing_social_networks post_id="' . $post->ID . '"]');

            if (!empty($socialNetworks)) {
                echo '<div class="event-detail-content_firstItem__3vz2x">' . $socialNetworks . '</div>';
            }
        }
    }

    public function countAuthorEventTypes()
    {
        $userID = get_current_user_id();
        $this->middleware(['isUserLoggedIn'], ['userID' => $userID]);

        $aResponse = [];
        $aResponse['unpaid_events'] = EventModel::countUnpaidEvents($userID);
        $aResponse['up_coming_events'] = EventModel::countUpcomingEventsOfAuthor($userID);
        $aResponse['on_going_events'] = EventModel::countOnGoingEventsOfAuthor($userID);
        $aResponse['expired_events'] = EventModel::countExpiredEventsOfAuthor($userID);
        $aResponse['temporary_close'] = User::countPostsByPostStatus('temporary_close', 'event');
        $aResponse['pending'] = User::countPostsByPostStatus('pending', 'event');

        wp_send_json_success($aResponse);
    }

    public function loadMoreListings()
    {
        $aData = $_POST['data'];
        $page = isset($_POST['page']) ? absint($_POST['page']) : 2;

        foreach ($aData as $key => $val) {
            $aData[$key] = sanitize_text_field($val);
        }

        $query = new WP_Query(
            [
                'post_type'      => 'event',
                'posts_per_page' => $aData['postsPerPage'],
                'paged'          => $page,
                'post_status'    => 'publish'
            ]
        );

        if ($query->have_posts()) {
            ob_start();
            while ($query->have_posts()) {
                $query->the_post();
                wilcity_render_event_item($query->post, [
                    'img_size'                   => $aData['img_size'],
                    'maximum_posts_on_lg_screen' => $aData['maximum_posts_on_lg_screen'],
                    'maximum_posts_on_md_screen' => $aData['maximum_posts_on_md_screen'],
                    'maximum_posts_on_sm_screen' => $aData['maximum_posts_on_sm_screen'],
                ]);
            }
            $contents = ob_get_contents();
            ob_end_clean();
            wp_send_json_success(['msg' => $contents]);
        } else {
            wp_send_json_error(
                [
                    'msg' => sprintf(
                        esc_html__('Oops! Sorry, We found no %s', 'wiloke-listing-tools'),
                        $aData['postType']
                    )
                ]
            );
        }
    }

    public function fetchEventsJson()
    {
        WPML::cookieCurrentLanguage();
        $aData = isset($_GET['postStatus']) ? $_GET : $_POST;
        $aArgs = QueryHelper::buildQueryArgs($aData);

        if (isset($aData['parentID'])) {
            $aArgs['post_parent'] = $aData['parentID'];
        }
        $aArgs = wp_parse_args($aArgs, [
            'post_type'      => 'event',
            'posts_per_page' => 10,
            'post_status'    => 'publish',
            'order'          => 'ASC',
            'isDashboard'    => true,
            'author'         => User::getCurrentUserID()
        ]);

        $aEventsData = [];
        $query = new WP_Query(WPML::addFilterLanguagePostArgs($aArgs));
        $aFrequencies = wilokeListingToolsRepository()->get('event-settings:aFrequencies');
        $aPostTypeInfo = General::getPostTypeSettings($aData['postType']);
        $addListingURL = apply_filters(
            'wilcity/wiloke-submission/box-listing-type-url',
            GetWilokeSubmission::getField('package', true),
            [
                'key' => $aData['postType']
            ]
        );

        $dateFormat = get_option('date_format') . ' ' . get_option('time_format');
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $aEventData = $this->json($query->post);
                $aEventDate = GetSettings::getEventSettings($query->post->ID);
                $aEventData['frequency'] = $aFrequencies[$aEventDate['frequency']];
                $aEventData['starts'] = date_i18n($dateFormat, strtotime($aEventDate['startsOn']));
                $aEventData['ends'] = date_i18n($dateFormat, strtotime($aEventDate['endsOn']));
                $aEventsData[] = $aEventData;
            }
            wp_reset_postdata();
        } else {
            wp_send_json_error([
                'msg'               => esc_html__('There are no events', 'wiloke-listing-tools'),
                'maxPages'          => 0,
                'maxPosts'          => 0,
                'addListingBtnName' => sprintf(esc_html__('Add %s', 'wiloke-listing-tools'),
                    $aPostTypeInfo['singular_name']),
                'addListingUrl'     => $addListingURL
            ]);
        }

        wp_send_json_success([
            'addListingBtnName' => sprintf(esc_html__('Add %s', 'wiloke-listing-tools'),
                $aPostTypeInfo['singular_name']),
            'addListingUrl'     => $addListingURL,
            'info'              => $aEventsData,
            'maxPages'          => absint($query->max_num_pages),
            'maxPosts'          => absint($query->found_posts)
        ]);
    }

    public function printEventFields()
    {
        global $post;
        $aSupportedPostType = Submission::getSupportedPostTypes();
        if (!is_user_logged_in() || !is_singular($aSupportedPostType) ||
            (!current_user_can('edit_theme_options') && (get_current_user_id() != $post->post_author))) {
            return false;
        }

        $aEventFields = GetSettings::getOptions(wilokeListingToolsRepository()
            ->get('event-settings:designFields', true)
            ->sub('usedSectionKey'), false, true);
        $this->aSections = $this->getAvailableFields();

        wp_localize_script('wilcity-empty', 'WILCITY_EVENT_FIELDS', $aEventFields);
    }

    public function printAddEventSidebars()
    {
    }

    public function addMiddleWareOptionsToReviewHandler($aOptions)
    {
        return array_merge($aOptions, [
            'postType' => $this->postType
        ]);
    }

    public function addMiddleWareToReviewHandler($aMiddleware)
    {
        return array_merge($aMiddleware, ['isPublishedPost', 'isPostType']);
    }

    public function setReviewPostType()
    {
        return 'event_comment';
    }

    public static function isEnabledDiscussion()
    {
        $aGeneralSettings = GetSettings::getOptions(wilokeListingToolsRepository()
            ->get('event-settings:keys', true)
            ->sub('general'), true, true);

        return $aGeneralSettings['toggle_comment_discussion'] == 'enable';
    }

    public static function isEnableComment()
    {
        $aGeneralSettings = GetSettings::getOptions(
            wilokeListingToolsRepository()
                ->get('event-settings:keys', true)
                ->sub('general'),
            true,
            true
        );
        return isset($aGeneralSettings['toggle']) && $aGeneralSettings['toggle'] == 'enable';
    }

    protected function determineCommentStatus()
    {
        $aGeneralSettings = GetSettings::getOptions(
            wilokeListingToolsRepository()
                ->get('event-settings:keys', true)
                ->sub('general'),
            true,
            true
        );
        if ($aGeneralSettings['immediately_approved'] == 'enable') {
            return 'publish';
        }

        return 'draft';
    }

    private function postComment($parentID, $comment, $commentID = null)
    {
        $userID = User::getCurrentUserID();
        $displayName = User::getField('display_name', $userID);
        $post_title = $displayName . ' ' . esc_html__(
                'Left a Comment ',
                'wiloke-listing-tools'
            ) . ' ' . get_the_title($parentID);

        if (empty($commentID)) {
            $commentID = wp_insert_post(
                [
                    'post_type'    => 'event_comment',
                    'post_status'  => $this->determineCommentStatus(),
                    'post_title'   => apply_filters(
                        'wilcity/wiloke-listing-tools/post-comment/title',
                        $post_title,
                        $displayName,
                        $parentID
                    ),
                    'post_content' => $comment,
                    'post_parent'  => $parentID,
                    'post_author'  => $userID
                ]
            );
        } else {
            wp_update_post(
                [
                    'ID'           => $commentID,
                    'post_status'  => $this->determineCommentStatus(),
                    'post_content' => $comment
                ]
            );
        }

        global $wiloke;
        $wiloke->aThemeOptions = \Wiloke::getThemeOptions();
        $wiloke->aConfigs['translation'] = wilcityGetConfig('translation');
        $oComment = get_post($commentID);

        do_action('wilcity/event/after-inserted-comment', $commentID, $userID, $parentID);

        return $oComment;
    }

    public function appBeforePostComment($parentID, $comment)
    {
        return $this->postComment($parentID, $comment);
    }

    public function appBeforeUpdateComment($parentID, $comment, $commentID)
    {
        return $this->postComment($parentID, $comment, $commentID);
    }

    public function ajaxBeforePostComment($aResponse, $aData)
    {
        $aValidation = $this->middleware(['isGroupType'], [
            'postID'    => $aData['parentID'],
            'groupType' => 'event'
        ]);

        if ($aValidation['status'] === 'error') {
            return $aResponse;
        }

        $wilcityoReview = $this->postComment($aData['parentID'], $_POST['content']);

        return [
            'status'    => 'success',
            'commentID' => $wilcityoReview->ID
        ];
    }

    public static function fetchEvents($aData = [])
    {
        WPML::cookieCurrentLanguage();
        $aData = $_GET;

        $aArgs = [
            'post_type'                  => General::getPostTypeKeysGroup('event'),
            'post_status'                => 'publish',
            'orderby'                    => 'wilcity_event_starts_on',
            'isFocusExcludeEventExpired' => true,
            'order'                      => 'ASC',
            'posts_per_page'             => 10,
        ];

        $aArgs = QueryHelper::buildQueryArgs($aArgs);

        if (isset($aData['parentID'])) {
            $aArgs['post_parent'] = $aData['parentID'];
        }

        if (isset($aData['postNotIn']) && !empty($aData['postNotIn'])) {
            $aParseIDs = explode(',', $aData['postNotIn']);
            $aParseIDs = array_map(function ($id) {
                return absint($id);
            }, $aParseIDs);
            $aArgs['post__not_in'] = $aParseIDs;
        }
        $isAjax = false;
        if (wp_doing_ajax()) {
            $isAjax = true;
        }

        $query = new WP_Query(apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Controllers/EventControllers/fetchEvents/query',
            $aArgs
        ));

        if ($isAjax) {
            ob_start();
        }
        global $wilcityWrapperClass, $event;
        $wilcityWrapperClass = 'col-sm-6 col-md-4';
        $aPostIDs = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $event = $query->post;
                $aPostIDs[] = $query->post->ID;
                get_template_part('single-listing/partials/event');
            }
        } else {
            if (isset($aArgs['post__not_in'])) {
                wp_send_json_error([
                    'isLoaded' => 'yes'
                ]);
            } else {
                wp_send_json_error([
                    'msg' => esc_html__('There are no events', 'wiloke-listing-tools')
                ]);
            }
        }
        wp_reset_postdata();

        if ($isAjax) {
            $content = ob_get_contents();
            ob_end_clean();
            wp_send_json_success([
                'content' => $content,
                'postIDs' => $aPostIDs
            ]);
        }
    }

    /*
     * $aResponse: $paymentID, $planID, $gateway
     */
    public function setPlanRelationshipBeforePayment($aResponse)
    {
        General::deprecatedFunction(__METHOD__, 'PlanRelationshipController:addPlanRelationShipIfHasRemainingItems',
            '1.2.5');

        if (empty($aResponse['planID'])) {
            return false;
        }

        $userID = get_current_user_id();
        $aInfo = [
            'paymentID' => $aResponse['paymentID'],
            'planID'    => $aResponse['planID'],
            'userID'    => $userID,
            'objectID'  => Session::getSession(wilokeListingToolsRepository()->get('payment:sessionObjectStore'))
        ];

        $aUserPlan = UserModel::getSpecifyUserPlanID($aResponse['planID'], $userID, true);

        $this->setPlanRelationship($aUserPlan, $aInfo);
    }

    public function updateVideo($aData)
    {
        if (isset($aData['video']) && !empty($aData['video'])) {
            SetSettings::setPostMeta($this->postID, 'video', sanitize_text_field($aData['video']));
        } else {
            SetSettings::deletePostMeta($this->postID, 'video');
        }
    }

    public function updateTimeFormat($aData)
    {
        if (isset($aData['timeFormat']) && !empty($aData['timeFormat'])) {
            SetSettings::setPostMeta($this->postID, 'timeFormat', sanitize_text_field($aData['timeFormat']));
        } else {
            SetSettings::deletePostMeta($this->postID, 'timeFormat');
        }
    }

    public function validatingEventData($aInput, $aRawEventData)
    {
        $aEventData = [];
        $aPrepares = [];
        foreach ($aInput as $dataKey => $val) {
            if (!isset($aRawEventData[$dataKey])) {
                continue;
            }

            if (!is_array($val)) {
                $aEventData[$dataKey] = $aRawEventData[$dataKey];
                $aPrepares[] = sanitize_text_field($val);
            } else {
                foreach ($val as $subKey => $subVal) {
                    $aEventData[$subKey] = sanitize_text_field($aRawEventData[$dataKey][$subKey]);
                    $aPrepares[] = $subVal;
                }
            }
        }

        return [
            'data'     => $aEventData,
            'prepares' => $aPrepares
        ];
    }

    public function editEvent()
    {
        if (empty($_POST['eventID'])) {
            wp_send_json_error(
                [
                    'msg' => esc_html__('You do not have permission to access this page', 'wiloke-listing-tools')
                ]
            );
        }
        $eventID = $_POST['eventID'];
        $this->postID = $eventID;
        $aRawEventData = $_POST['data'];
        if (!current_user_can('edit_theme_options')) {
            $this->middleware(['isPostAuthor'], [
                'postID' => $eventID
            ]);
        }
        // Updating Events
        $this->uploadFeaturedImg($aRawEventData['img']);

        $aAfterValidation = $this->validatingEventData($this->aEventsDataAndPrepares, $aRawEventData);
        $aPrepares = $aAfterValidation['prepares'];
        $aEventData = $aAfterValidation['data'];
        $aEventData['objectID'] = $this->postID;
        $aPrepares[] = '%d';

        if ($_POST['isAddressChanged']) {
            $aEventData['timezone'] = GetSettings::getTimeZoneByGeocode($aRawEventData['address']['lat'] . ',' .
                $aRawEventData['address']['lng']);
            $aPrepares[] = '%s';
        }

        wp_update_post(
            [
                'ID'           => $this->postID,
                'post_title'   => $aRawEventData['listing_title'],
                'post_name'    => sanitize_title($aRawEventData['listing_title']),
                'post_content' => $aRawEventData['listing_content']
            ]
        );

        $this->updateVideo($_POST['data']);
        $this->updateTimeFormat($_POST['data']);

        EventModel::updateEventData($this->postID, [
            'values'   => $aEventData,
            'prepares' => $aPrepares
        ]);

        $aResponse['msg'] = esc_html__('Congrats! The event has been updated successfully', 'wiloke-listing-tools');
        if (!empty($aRawEventData['img'])) {
            $aResponse['img'] = $this->getFeaturedImageData($this->postID);
        }

        wp_send_json_success($aResponse);
    }

    public function getEventItemData()
    {
        if (!current_user_can('edit_theme_options')) {
            $this->middleware(['isPostAuthor'], [
                'postID' => $_POST['eventID']
            ]);
        }

        $eventID = $_POST['eventID'];
        $aData = [
            'listing_title'   => '',
            'listing_content' => ''
        ];

        if (!empty($eventID)) {
            $aData['listing_title'] = get_post_field('post_title', $eventID);
            $aData['listing_content'] = get_post_field('post_content', $eventID);
            if (has_post_thumbnail($eventID)) {
                $aData['img'][0]['src'] = get_the_post_thumbnail_url($eventID);
                $aData['img'][0]['fileName'] = get_the_title($eventID);
                $aData['img'][0]['imgID'] = get_post_thumbnail_id($eventID);
            }
        }

        $aEventData = EventModel::getEventData($eventID);
        self::$aEventsData[$eventID] = $aEventData;

        if (!empty($aEventData)) {
            $aData['video'] = $aEventData['video'];
            $aData['address']['address'] = $aEventData['address'];
            $aData['address']['lat'] = $aEventData['lat'];
            $aData['address']['lng'] = $aEventData['lng'];
            $aData['frequency'] = $aEventData['frequency'];
            $aData['endsOn'] = date('Y/m/d', strtotime($aEventData['endsOn']));
            $aData['starts'] = date('Y/m/d', strtotime($aEventData['starts']));
            $aData['openingAt'] = $aEventData['openingAt'];
            $aData['closedAt'] = $aEventData['closedAt'];

            if ($aData['frequency'] == 'weekly') {
                $aData['weekly']['specifyDays'] = isset($aEventData['specifyDays']) ? explode(
                    ',',
                    $aEventData['specifyDays']
                ) : [];
            }
        }

        wp_send_json_success($aData);
    }

    public function eventItems()
    {
        ?>

        <?php
    }

    public function uploadFeaturedImg($aImg)
    {
        if (empty($aImg)) {
            delete_post_thumbnail($this->listingID);
        }

        if (empty($aImg) || (isset($aImg[0]['imgID']) && !empty($aImg[0]['imgID']))) {
            return false;
        }

        $instUploadImg = new Upload();

        $instUploadImg->userID = get_current_user_id();
        $instUploadImg->aData['imageData'] = $aImg[0]['src'];
        $instUploadImg->aData['fileName'] = $aImg[0]['fileName'];
        $instUploadImg->aData['fileType'] = $aImg[0]['fileType'];
        $instUploadImg->aData['uploadTo'] = $instUploadImg::getUserUploadFolder();

        $id = $instUploadImg->image($this->listingID);
        set_post_thumbnail($this->listingID, $id);

        return true;
    }

    protected function isMustPayForEvent()
    {
        return [
            'redirectTo' => GetWilokeSubmission::getField('checkout', true)
        ];
    }

    public function detectPostStatus()
    {
        if (User::can('edit_theme_options')) {
            return 'publish';
        }

        return 'unpaid';
    }

    public function validateEventBelongsToListing($that, $aFieldData)
    {
        $that->parentListingID = $aFieldData['value'];
        if (!empty($that->parentListingID)) {
            $this->middleware(['isUserLoggedIn', 'isPostAuthor', 'isPublishedPost'], [
                'postID'        => $that->parentListingID,
                'postAuthor'    => get_current_user_id(),
                'passedIfAdmin' => true
            ]);
        }
    }

    public function validateEventCalendar($that, $aFieldData)
    {
        if (empty($aFieldData['value'])) {
            $that->aEventCalendar = $aFieldData['value'];
        } else {
            foreach ($aFieldData['value'] as $key => $val) {
                $that->aEventCalendar[sanitize_text_field($key)] = sanitize_text_field($val);
            }

            if (empty($that->aEventCalendar['starts']) || empty($that->aEventCalendar['endsOn'])) {
                wp_send_json_error(
                    [
                        'msg' => esc_html__('The event start date and end date are required', 'wiloke-listing-tools')
                    ]
                );
            }

            $start = strtotime($that->aEventCalendar['starts']);
            $end = strtotime($that->aEventCalendar['endsOn']);
            $wrongDateMsg = esc_html__(
                'The event start date must be smaller than event end date',
                'wiloke-listing-tools'
            );

            if ($start > $end) {
                wp_send_json_error(
                    [
                        'msg' => $wrongDateMsg
                    ]
                );
            } else if ($start == $end) {
                $openingAt = strtotime($that->aEventCalendar['openingAt']);
                $closedAt = strtotime($that->aEventCalendar['closedAt']);

                if ($openingAt > $closedAt) {
                    wp_send_json_error(
                        [
                            'msg' => $wrongDateMsg
                        ]
                    );
                }
            }
        }
    }

    protected function addressIsRequired()
    {
        if (empty($this->aGoogleAddress)) {
            wp_send_json_error(
                [
                    'msg' => esc_html__('Google Address is required', 'wiloke-listing-tools')
                ]
            );
        }

        if (empty($this->aGoogleAddress['latLng'])) {
            wp_send_json_error(
                [
                    'msg' => esc_html__(
                        'We could not get Geocode of the address, please try to enter it again',
                        'wiloke-listing-tools'
                    )
                ]
            );
        }
    }

    public static function eventStart($objectID, $isReturn = true)
    {
        if (isset(self::$aEventsData[$objectID])) {
            $aData = self::$aEventsData[$objectID];
        } else {
            $aData = EventModel::getEventData($objectID);
            self::$aEventsData[$objectID] = $aData;
        }

        if ($isReturn) {
            ob_start();
        }

        $date = date('M/d', strtotime($aData['startsOn']));
        $aDate = explode('/', $date);
        ?>
        <div class="event_calendar__2x4Hv"><span
                class="event_month__S8D_o color-primary"><?php echo esc_html($aDate[0]); ?></span><span
                class="event_date__2Z7TH"><?php echo esc_html($aDate[1]); ?></span></div>
        <?php
        if ($isReturn) {
            $content = ob_get_contents();
            ob_end_clean();

            return $content;
        }
    }

    public static function renderEventCalendar($post, $isReturn = false)
    {

        $aEventData = EventModel::getEventData($post->ID);
        if (empty($aEventData)) {
            return [];
        }

        $frequency = $aEventData['frequency'];
        $timezone = GetSettings::getPostMeta($post->ID, 'timezone');
        $timeFormat = GetSettings::getPostMeta($post->ID, 'event_time_format');

        $aTimeInformation = [];
        $aDetails = [];
        $aAjaxInfo = [];

        switch ($frequency) {
            case 'occurs_once':
                Time::findUTCOffsetByTimezoneID($timezone);
                $aTimeInformation['general'] = esc_html(Time::toDateFormat($aEventData['startsOn'])) . ' - ' .
                    esc_html(Time::toDateFormat($aEventData['endsOn']));

                $aDetails[0]['heading'] = esc_html__('Start Time', 'wiloke-listing-tools');
                $aDetails[0]['time'] = esc_html(Time::toTimeFormat($aEventData['startsOn'], $timeFormat));

                $aDetails[1]['heading'] = esc_html__('End Time', 'wiloke-listing-tools');
                $aDetails[1]['time'] = esc_html(Time::toTimeFormat($aEventData['endsOn'], $timeFormat));

                break;
            case 'daily':
                $aTimeInformation['general'] = esc_html__(
                        'Daily',
                        'wiloke-listing-tools'
                    ) . ', ' . esc_html(Time::toDateFormat($aEventData['startsOn'])) . ' - ' .
                    esc_html(Time::toDateFormat($aEventData['endsOn']));

                $aDetails[0]['heading'] = esc_html__('Start Time', 'wiloke-listing-tools');
                $aDetails[0]['time'] = Time::toTimeFormat($aEventData['startsOn'], $timeFormat);

                $aDetails[1]['heading'] = esc_html__('End Time', 'wiloke-listing-tools');
                $aDetails[1]['time'] = Time::toTimeFormat($aEventData['endsOn'], $timeFormat);

                break;

            case 'weekly':
                $specifyDay = $aEventData['specifyDays'];
                $dayName = wilokeListingToolsRepository()->get('general:aDayOfWeek', true)->sub($specifyDay);

                $aTimeInformation['general'] = sprintf(
                        esc_html__('Every %s', 'wiloke-listing-tools'),
                        $dayName
                    ) . ', ' . esc_html(Time::toDateFormat($aEventData['startsOn'])) . ' - ' .
                    esc_html(Time::toDateFormat($aEventData['endsOn']));

                $aDetails[0]['heading'] = esc_html__('Start Time', 'wiloke-listing-tools');
                $aDetails[0]['time'] = Time::toTimeFormat($aEventData['startsOn'], $timeFormat);

                $aDetails[1]['heading'] = esc_html__('End Time', 'wiloke-listing-tools');
                $aDetails[1]['time'] = Time::toTimeFormat($aEventData['endsOn'], $timeFormat);

                break;
        }

        if (!empty($aTimeInformation)) :
            if ($isReturn) :
                $aAjaxInfo['heading'] = esc_html__('Calendar', 'wiloke-listing-tools');
                $aAjaxInfo['general'] = $aTimeInformation['general'];

                $newTimeFormat = 'D ' . Time::getTimeFormat($timeFormat);
                $aAjaxInfo['oStarts'] = [
                    'date' => Time::toDateFormat($aEventData['startsOn']),
                    'hour' => date_i18n($newTimeFormat, strtotime($aEventData['startsOn']))
                ];

                $aAjaxInfo['oEnds'] = [
                    'date' => Time::toDateFormat($aEventData['endsOn']),
                    'hour' => date_i18n($newTimeFormat, strtotime($aEventData['endsOn']))
                ];
                $aAjaxInfo['oOccur'] = [
                    'frequency' => $frequency,
                    'text'      => $aTimeInformation['general']
                ];

                return $aAjaxInfo;
            else : ?>
                <div class="event-detail-content_firstItem__3vz2x">
                    <div class="icon-box-1_module__uyg5F">
                        <div class="icon-box-1_block1__bJ25J">
                            <div class="icon-box-1_icon__3V5c0 rounded-circle"><i class="la la-clock-o"></i></div>
                            <div class="icon-box-1_text__3R39g"><?php esc_html_e(
                                    'Calendar',
                                    'wiloke-listing-tools'
                                ); ?></div>
                        </div>
                        <div class="icon-box-1_block2__1y3h0">
                            <span class="color-secondary"><?php echo esc_html($aTimeInformation['general']); ?></span>
                        </div>
                    </div>
                    <?php
                    if (!empty($aDetails)) :
                        foreach ($aDetails as $aDetail) : ?>
                            <div class="date-item_module__2wyHG mt-10 mr-10">
                                <div class="date-item_date__3OIqD"><?php echo esc_html($aDetail['heading']); ?></div>
                                <div class="date-item_hours__3w6Rw"><?php echo esc_html($aDetail['time']); ?></div>
                            </div>
                        <?php
                        endforeach;
                    endif;
                    ?>
                </div>
            <?php
            endif;
        endif;
    }
}
