<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\HTML;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\FavoriteStatistic;
use WilokeListingTools\Models\ReviewModel;
use WilokeListingTools\Models\UserModel;

class FavoriteStatisticController extends Controller
{
    public function __construct()
    {
        add_action('wp_ajax_wilcity_favorite_statistics', [$this, 'listenAjaxUpdate']);
        add_action('wp_ajax_nopriv_wilcity_favorite_statistics', [$this, 'listenAjaxUpdate']);
        add_action('wp_ajax_wilcity_is_my_favorited', [$this, 'checkIsMyFavorite']);
        add_action('wp_ajax_me_favorite', [$this, 'checkIsMyFavorite']);

        add_action('wp_ajax_wilcity_favorites_latest_week', [$this, 'getFavoritesOfLatestWeek']);
        add_action('wp_ajax_wilcity_fetch_favorites_general', [$this, 'fetchFavoritesGeneral']);
        add_action('wp_ajax_wilcity_fetch_my_favorites', [$this, 'fetchMyFavorites']);
        add_action('wp_ajax_wilcity_remove_favorite_from_my_list', [$this, 'removeFavoritesFromMyList']);

        //		add_action('wp_ajax_wilcity_fetch_compare_favorites', array($this, 'fetchComparison'));
        //		add_action('wp_ajax_wilcity_fetch_user_liked', array($this, 'fetchUserLiked'));

        add_action('rest_api_init', function () {
            register_rest_route(WILOKE_PREFIX . '/v2', 'users/(?P<userID>\d+)/liked', [
                'methods'             => 'GET',
                'callback'            => [$this, 'getUserLiked'],
                'permission_callback' => '__return_true'
            ]);

            register_rest_route(WILOKE_PREFIX . '/v2', 'dashboard/(?P<postID>\d+)/compare-favorites', [
                'methods'             => 'GET',
                'callback'            => [$this, 'getCompareFavorites'],
                'permission_callback' => '__return_true'
            ]);
        });
    }

    public function checkIsMyFavorite()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        $aStatus = $this->middleware(['isUserLoggedIn'], [], 'normal');

        if ($aStatus['status'] === 'error') {
            $oRetrieve->error($aStatus);
        }

        $status = UserModel::isMyFavorite($_GET['postID'], false, get_current_user_id()) ? 'yes' : 'no';

        $oRetrieve->success(['status' => $status]);
    }

    public function getCompareFavorites(\WP_REST_Request $oRequest)
    {
        $postID = $oRequest->get_param('postID');

        $aComparison = FavoriteStatistic::compare(get_post_field('post_author', $postID), $postID);
        if ($aComparison['number'] > 1) {
            $aComparison['text'] = esc_html__('Favorites', 'wiloke-listing-tools');
        } else {
            $aComparison['text'] = esc_html__('Favorite', 'wiloke-listing-tools');
        }

        return ['data' => $aComparison];
    }

    public function removeFavoritesFromMyList()
    {
        $aRawFavorites = GetSettings::getUserMeta(get_current_user_id(), 'my_favorites');
        $order = array_search($_POST['postId'], $aRawFavorites);
        array_splice($aRawFavorites, $order, 1);
        SetSettings::setUserMeta(get_current_user_id(), 'my_favorites', $aRawFavorites);
    }

    public static function getFavoritesByPage($aRawFavorites, $page)
    {
        $limit = 20;
        $offset = $limit * $page;
        $total = count($aRawFavorites);

        $aRawFavorites = array_reverse($aRawFavorites);
        $aFavorites = array_splice($aRawFavorites, $offset, $limit);

        if (empty($aFavorites)) {
            return [
                'reachedMaximum' => 'yes'
            ];
        }

        $aListings = [];
        foreach ($aFavorites as $id => $postID) {
            if (get_post_status($postID) != 'publish') {
                unset($aFavorites[$id]);
                continue;
            }

            $aData = [
                'postID'        => absint($postID),
                'order'         => $id,
                'permalink'     => get_permalink($postID),
                'title'         => get_the_title($postID),
                'tagLine'       => GetSettings::getTagLine($postID, true),
                'featuredImage' => GetSettings::getFeaturedImg($postID, 'thumbnail'),
                'address'       => GetSettings::getAddress($postID, false),
                'mapPage'       => GetSettings::getAddress($postID, true)
            ];

            if (get_post_type($postID) == 'post') {
                $oRawCat = GetSettings::getLastPostTerm($postID, 'category');
                if ($oRawCat) {
                    $aData['oCategory'] = [
                        'link'  => get_term_link($oRawCat->term_id),
                        'name'  => $oRawCat->name,
                        'oIcon' => 'no'
                    ];
                }
            } else {
                $oRawCat = GetSettings::getLastPostTerm($postID, 'listing_cat');
                if ($oRawCat) {
                    $aData['oCategory'] = [
                        'link'  => get_term_link($oRawCat->term_id),
                        'name'  => $oRawCat->name,
                        'oIcon' => \WilokeHelpers::getTermOriginalIcon($oRawCat)
                    ];
                }
            }

            if (!isset($aData['oCategory'])) {
                $aData['oCategory'] = 'no';
            }

            $aListings[] = $aData;
        }

        return [
            'aInfo'    => $aListings,
            'total'    => $total,
            'maxPages' => ceil($total / $limit)
        ];
    }

    public function fetchMyFavorites()
    {
        $aRawFavorites = GetSettings::getUserMeta(get_current_user_id(), 'my_favorites');

        if (empty($aRawFavorites)) {
            wp_send_json_error([
                'msg' => esc_html__('There are no favorites', 'wiloke-listing-tools')
            ]);
        }

        $page = isset($_GET['page']) ? absint($_GET['page']) - 1 : 0;

        $aResult = self::getFavoritesByPage($aRawFavorites, $page);
        if (isset($aResult['reachedMaximum'])) {
            wp_send_json_error($aResult);
        }
        wp_send_json_success($aResult);
    }

    public function fetchFavoritesGeneral()
    {
        $this->middleware(['isUserLoggedIn'], []);
        $userID = get_current_user_id();

        $aCompareFavorites = FavoriteStatistic::compare($userID);

        wp_send_json_success(
            [
                'totalFavorites' => $aCompareFavorites['total'],
                'oChanging'      => [
                    'number'      => $aCompareFavorites['number'],
                    'description' => esc_html__('Compared to the last week', 'wiloke-listing-tools'),
                    'title'       => esc_html__('Favorites', 'wiloke-listing-tools'),
                    'status'      => $aCompareFavorites['status'],
                    'is'          => $aCompareFavorites['is']
                ]
            ]
        );

    }

    public function getFavoritesOfLatestWeek()
    {
        $this->middleware(['isUserLoggedIn'], []);

        $aDateInThisWeek = Time::getAllDaysInThis();
        $aCountViewsOfWeek = [];
        $userID = get_current_user_id();

        foreach ($aDateInThisWeek as $date) {
            $aCountViewsOfWeek[] = FavoriteStatistic::getTotalFavoritesOfAuthorInDay($userID, $date);
        }
        $aCompare = FavoriteStatistic::compare($userID);

        wp_send_json_success([
            'data'    => $aCountViewsOfWeek,
            'total'   => $aCompare['total'],
            'compare' => [
                'diff'           => $aCompare['diff'],
                'tooltip'        => esc_html__('Compare with the last week', 'wiloke-listing-tools'),
                'label'          => esc_html__('Favorites Statistic', 'wiloke-listing-tools'),
                'status'         => $aCompare['status'],
                'representColor' => $aCompare['representColor']
            ]
        ]);
    }

    public static function getFavorites($postID, $isRestyleText = false)
    {
        $today = Time::mysqlDate(\time());
        $countViewsToday = FavoriteStatistic::getTotalFavoritesOfAuthorInDay(get_current_user_id(), $today);

        $countViews = FavoriteStatistic::countFavorites($postID);

        if (empty($countViews)) {
            return !$isRestyleText ? $countViewsToday : HTML::reStyleText($countViews);
        }

        $totalViewed = $countViewsToday + absint($countViews);

        return !$isRestyleText ? $totalViewed : HTML::reStyleText($totalViewed);
    }

    public function fetchUserLiked()
    {
        $userID = get_current_user_id();
        $aLiked = GetSettings::getUserMeta($userID, 'my_favorites');
        if (empty($aLiked)) {
            wp_send_json_error();
        } else {
            wp_send_json_success($aLiked);
        }
    }

    public function getUserLiked($oInfo)
    {
        $aError = [
            'error' => [
                'internalMessage' => 'User does not exist',
                'status'          => 404
            ]
        ];

        $userID = $oInfo->get_param('userID');
        if (empty($userID)) {
            return $aError;
        }
        $aLiked = GetSettings::getUserMeta($userID, 'my_favorites');

        if (empty($aLiked)) {
            $aError['error']['internalMessage'] = 'No Like';

            return $aError;
        }

        return ['data' => $aLiked];
    }

    public static function update($postID, $userID = '')
    {
        $countFavorites = GetSettings::getPostMeta($postID, 'count_favorites');
        $countFavorites = empty($countFavorites) ? 0 : absint($countFavorites);
        $userID = empty($userID) ? get_current_user_id() : $userID;

        if (empty($userID)) {
            return false;
        }

        $aFavorites = GetSettings::getUserMeta($userID, 'my_favorites');
        if (empty($aFavorites)) {
            SetSettings::setUserMeta($userID, 'my_favorites', [$postID]);
            $countFavorites++;
            $is = 'added';
            $isPlus = true;
        } else {
            if (in_array($postID, $aFavorites)) {
                $key = array_search($postID, $aFavorites);
                unset($aFavorites[$key]);
                if (empty($aFavorites)) {
                    SetSettings::deleteUserMeta($userID, 'my_favorites');
                } else {
                    SetSettings::setUserMeta($userID, 'my_favorites', $aFavorites);
                }
                $countFavorites--;
                $is = 'removed';
                $isPlus = false;
            } else {
                $countFavorites++;
                $aFavorites[] = $postID;
                SetSettings::setUserMeta($userID, 'my_favorites', $aFavorites);
                $is = 'added';
                $isPlus = true;
            }
        }
        FavoriteStatistic::update($postID, $isPlus);
        SetSettings::setPostMeta($postID, 'count_favorites', $countFavorites);

        return $is;
    }

    public function listenAjaxUpdate()
    {
        $this->middleware(['isUserLoggedIn', 'isPublishedPost', 'isThemeOptionSupport'], [
            'postID'  => $_POST['postID'],
            'feature' => 'listing_toggle_favorite'
        ]);
        $postID = absint($_POST['postID']);
        $is = self::update($postID);

        do_action(
            'wilcity/wiloke-listing-tools/app/Controllers/FollowController/afterToggleFavorite',
            $is,
            $postID,
            User::getCurrentUserID(),  // follower
            get_post_field('post_author', $postID) // owner
        );

        wp_send_json_success($is);
    }
}
