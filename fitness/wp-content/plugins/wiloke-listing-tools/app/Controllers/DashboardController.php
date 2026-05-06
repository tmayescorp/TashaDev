<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Framework\Helpers\Firebase;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\EventModel;
use WilokeListingTools\Models\MessageModel;
use WilokeListingTools\Models\FavoriteStatistic;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Models\NotificationsModel;
use WilokeListingTools\Models\ReviewModel;
use WilokeThemeOptions;

class DashboardController extends Controller
{

    private static $aEndpoint
        = [
            'favorites'     => 'favorites',
            'profile'       => 'get-profile',
            'messages'      => 'messages',
            'listings'      => 'listings',
            'events'        => 'events',
            'reviews'       => 'reviews',
            'notifications' => 'notifications',
            'dokan'         => 'dokan/sub-menus'
        ];

    public function __construct()
    {
        add_action('wp_ajax_dashboard_menu', [$this, 'fetchDashboardMenu']);
        add_action('wp_ajax_wilcity_fetch_general_status_statistics', [$this, 'fetchGeneralStatusStatistics']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('wilcity/theme-options/configurations', [$this, 'addDashboardThemeOptions']);
    }

    public function addDashboardThemeOptions($aThemeOptions)
    {
        $aThemeOptions['dashboard_settings'] = wilokeListingToolsRepository()->get('dashboard:themeoptions');

        return $aThemeOptions;
    }

    /*
     * 1. All
     * 2. Event Only
     * 3. Excepet Event
     */
    public static function countPostStatus($postStatus, $posttypeType = 3)
    {
        switch ($posttypeType) {
            case 2:
                $aPostTypes = ['event'];
                break;
            case 3:
                $aPostTypes = GetSettings::getFrontendPostTypes(true, false);
                break;
            default:
                $aPostTypes = GetSettings::getFrontendPostTypes(true, true);
                break;
        }

        switch ($postStatus) {
            case 'up_coming_events':
                $count = EventModel::countUpcomingEventsOfAuthor(User::getCurrentUserID());

                return empty($count) ? 0 : absint($count);
                break;
            case 'on_going_events':
                $count = EventModel::countOnGoingEventsOfAuthor(User::getCurrentUserID());

                return empty($count) ? 0 : absint($count);
                break;
            case 'expired_events':
                $count = EventModel::countExpiredEventsOfAuthor(User::getCurrentUserID());

                return empty($count) ? 0 : absint($count);
                break;
        }

        $postTypeKeys = '("' . implode('","', $aPostTypes) . '")';


        global $wpdb;
        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT($wpdb->posts.ID) FROM $wpdb->posts WHERE $wpdb->posts.post_type IN $postTypeKeys AND $wpdb->posts.post_status=%s AND $wpdb->posts.post_author=%d",
                $postStatus, User::getCurrentUserID()
            )
        );

        return empty($total) ? 0 : absint($total);
    }

    public static function getPostStatuses($hasAny = false)
    {
        $aData = [
            [
                'label'   => esc_html__('Published', 'wiloke-listing-tools'),
                'icon'    => 'la la-share-alt',
                'bgColor' => 'bg-gradient-1',
                'id'      => 'publish',
                'total'   => 0
            ],
            [
                'label'   => esc_html__('In Review', 'wiloke-listing-tools'),
                'icon'    => 'la la-refresh',
                'bgColor' => 'bg-gradient-2',
                'id'      => 'pending',
                'total'   => 0
            ],
            [
                'label'   => esc_html__('Unpaid', 'wiloke-listing-tools'),
                'icon'    => 'la la-money',
                'bgColor' => 'bg-gradient-3',
                'id'      => 'unpaid',
                'total'   => 0
            ],
            [
                'label'   => esc_html__('Expired', 'wiloke-listing-tools'),
                'icon'    => 'la la-exclamation-triangle',
                'bgColor' => 'bg-gradient-4',
                'id'      => 'expired',
                'total'   => 0
            ],
            [
                'label'   => esc_html__('Editing', 'wiloke-listing-tools'),
                'icon'    => 'la la-refresh',
                'bgColor' => 'bg-gradient-4',
                'id'      => 'editing',
                'total'   => 0
            ],
            [
                'label'                       => esc_html__('Temporary Close', 'wiloke-listing-tools'),
                'icon'                        => 'la la-toggle-off',
                'bgColor'                     => 'bg-gradient-4',
                'id'                          => 'temporary_close',
                'total'                       => 0,
                'excludeFromGeneralDashboard' => true
            ]
        ];

        if ($hasAny) {
            $aData = array_merge([
                [
                    'label'   => esc_html__('Any', 'wiloke-listing-tools'),
                    'icon'    => 'la la-globe',
                    'bgColor' => 'bg-gradient-1',
                    'id'      => 'any',
                    'total'   => 0
                ],
            ], $aData);
        }

        return apply_filters('wilcity/dashboard/general-listing-status-statistic', $aData);
    }

    public function fetchGeneralStatusStatistics()
    {
        $this->middleware(['canSubmissionListing'], []);
        $aData = self::getPostStatuses();

        $totalPostStatus = count($aData);

        foreach ($aData as $order => $aInfo) {
            if (isset($aInfo['excludeFromGeneralDashboard']) && $aInfo['excludeFromGeneralDashboard']) {
                unset($aData[$order]);
                continue;
            }

            if ($aInfo['id'] === 'editing') {
                unset($aData[$order]);
                continue;
            }

            $aData[$order]['total'] = self::countPostStatus($aInfo['id'], 3);

            if ($totalPostStatus == 3) {
                $aData[$order]['wrapperClasses'] = 'col-sm-6 col-md-4 col-lg-4 ' . $aInfo['id'];
            } else {
                $aData[$order]['wrapperClasses'] = 'col-sm-6 col-md-3 col-lg-3 ' . $aInfo['id'];
            }
        }
        wp_send_json_success(array_values($aData));
    }

    public function enqueueScripts()
    {
        wp_localize_script('wilcity-empty', 'WIL_DASHBOARD', apply_filters(
            'wilcity/filter/app/Controllers/DashboardController/dashboardInfo',
            [
                'postStatuses' => self::getPostStatuses(true),
                'dashboardUrl' => trailingslashit(GetWilokeSubmission::getField('dashboard_page', true))
            ]
        ));
    }

    public static function getNavigation($userID = null)
    {
        $aNavigation = wilokeListingToolsRepository()->get('dashboard:aNavigation');

        if (!WilokeThemeOptions::isEnable('listing_toggle_favorite')) {
            unset($aNavigation['favorites']);
        }

        $aDokanDashboardPage = GetSettings::getDokanPages(true);

        if ($aDokanDashboardPage) {
            $aNavigation['dokan'] = [
                'name'               => $aDokanDashboardPage['title'],
                'icon'               => 'la la-shopping-cart',
                'endpoint'           => 'dokan/sub-menus',
                'redirectTo'         => $aDokanDashboardPage['permalink'],
                'externalLinkTarget' => '_self',
                'externalLink'       => $aDokanDashboardPage['permalink']
            ];
        }

        if (function_exists("is_woocommerce")) {
            $dashboardId = wc_get_page_id('myaccount');
            if (!empty($dashboardId)) {
                $wooDashboardLink = get_permalink($dashboardId);

                $aNavigation['woodashboard'] = [
                    'name'               => get_the_title($dashboardId),
                    'icon'               => 'la la-cart-plus',
                    'endpoint'           => 'woo/dashboard',
                    'redirectTo'         => $wooDashboardLink,
                    'externalLinkTarget' => '_self',
                    'externalLink'       => $wooDashboardLink
                ];
            }
        }

        if (empty($aNavigation)) {
            return false;
        }
        $userID = empty($userID) ? User::getCurrentUserID() : $userID;

        if (!User::canSubmitListing($userID)) {
            $aNavigation = array_filter($aNavigation, function ($aItem, $key) {
                if (in_array($key, ['favorites', 'messages', 'profile'])) {
                    return true;
                }
            }, ARRAY_FILTER_USE_BOTH);
        }

        foreach ($aNavigation as $key => $aItem) {
            if (!isset($aItem['endpoint'])) {
                if (isset(self::$aEndpoint[$key])) {
                    $aNavigation[$key]['endpoint'] = self::$aEndpoint[$key];
                } else {
                    $aNavigation[$key]['endpoint'] = '';
                }
            }

            switch ($key) {
                case 'favorites':
                    $aNavigation[$key]['count'] = absint(FavoriteStatistic::countMyFavorites($userID));
                    break;
                case 'messages':
                    $aNavigation[$key]['count'] = absint(MessageModel::countUnReadMessages($userID));
                    break;
                case 'listings':
                    unset($aNavigation[$key]);
                    $aPostTypes = General::getPostTypes(false, false);
                    $aPostTypes = array_map(function ($aPostType) {
                        $aPostType['group'] = General::getPostTypeGroup($aPostType['postType']);
                        $aPostType['params'] = [
                            'postType' => $aPostType['postType'],
                            'endpoint' => $aPostType['postType']
                        ];

                        if (General::isPostTypeInGroup($aPostType['postType'], 'event')) {
                            $aPostType['webEndpoint'] = 'events/' . $aPostType['postType'];
                            $aPostType['routeName'] = 'events';
                            return $aPostType;
                        }

                        $aPostType['routeName'] = 'listings';
                        $aPostType['webEndpoint'] = 'listings/' . $aPostType['postType'];


                        return $aPostType;
                    }, $aPostTypes);
                    $aNavigation = array_merge($aNavigation, $aPostTypes);
                    break;
                case 'notifications':
                    $aNavigation[$key]['count']
                        = absint(GetSettings::getUserMeta($userID, NotificationsModel::$countNewKey));
                    break;
            }
        }

        $aNavigation = apply_filters('wilcity/dashboard/navigation', $aNavigation, $userID);

        if (has_filter('wilcity/dashboard/navigation')) {
            $aNavigation = array_map(function ($aItem) {
                if (isset($aItem['redirect']) && strpos($aItem['redirect'], 'http') === 0) {
                    if (!isset($aItem['externalLinkTarget'])) {
                        $aItem['externalLinkTarget'] = '_blank';
                    }
                }

                return $aItem;
            }, $aNavigation);
        }

        $aNavigation['logout'] = [
            'icon'               => 'la la-sign-out',
            'externalLink'       => wp_logout_url(),
            'endpoint'           => 'dashboard',
            'externalLinkTarget' => '_self',
            'isExcludeFromApp'   => false,
            'name'               => esc_html__('Logout', 'wiloke-listing-tools')
        ];

        return $aNavigation;
    }

    public function fetchDashboardMenu()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        if (!is_user_logged_in()) {
            $oRetrieve->error([]);
        }

        if (isset($_GET['isCollapseMenu'])) {
            $aRawNavigation = self::getNavigation();
            $aPostTypes = General::getPostTypeKeys(false, false);


            if (count($aPostTypes) > 3) {
                foreach ($aRawNavigation as $key => $aItem) {
                    if (isset($aItem['postType'])) {
                        $aNavigation['posts']['items'][] = $aItem;
                    } else {
                        $aNavigation[$key] = $aItem;
                    }
                }

                if (isset($aNavigation['posts'])) {
                    $aNavigation['posts'] = [
                        'endpoint' => 'posts',
                        'isParent' => true,
                        'icon'     => 'la la-pencil',
                        'label'    => sprintf(esc_html__('Listings (%s)', 'wiloke-listing-tools'), count($aPostTypes)),
                        'items'    => $aNavigation['posts']['items']
                    ];
                }
            } else {
                $aNavigation = $aRawNavigation;
            }
        } else {
            $aNavigation = self::getNavigation();
        }

        $oRetrieve->success([
            'items'   => $aNavigation,
            'rootUrl' => GetWilokeSubmission::getField('dashboard_page', true)
        ]);
    }
}
