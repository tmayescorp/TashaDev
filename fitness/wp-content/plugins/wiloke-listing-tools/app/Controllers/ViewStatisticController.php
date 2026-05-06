<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\HTML;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Models\ViewStatistic;

class ViewStatisticController extends Controller
{
    public function __construct()
    {
        add_action('wp_ajax_wilcity_count_views', [$this, 'update']);
        add_action('wp_ajax_nopriv_wilcity_count_views', [$this, 'update']);

        add_action('wp_ajax_wilcity_views_latest_week', [$this, 'getViewsOfLatestWeek']);
        //        add_action('wp_ajax_wilcity_fetch_views_general', [$this, 'fetchViewsGeneral']);

//        add_action('wp_ajax_wilcity_fetch_compare_views', [$this, 'fetchComparison']);

        add_action('rest_api_init', function () {
            register_rest_route(WILOKE_PREFIX . '/v2', '/dashboard/(?P<postID>\d+)/compare-views', [
                'methods'             => 'GET',
                'callback'            => [$this, 'getCompareViews'],
                'permission_callback' => '__return_true'
            ]);
        });
    }

    public function getCompareViews(\WP_REST_Request $oRequest): array
    {
        $postID = $oRequest->get_param('postID');
        $aComparison = ViewStatistic::compare(get_post_field('post_author', $postID), $postID);

        if ($aComparison['number'] > 1) {
            $aComparison['text'] = esc_html__('Views', 'wiloke-listing-tools');
        } else {
            $aComparison['text'] = esc_html__('View', 'wiloke-listing-tools');
        }

        return ['data' => $aComparison];
    }

    public static function getViewsToday($userID)
    {
        $today = Time::mysqlDate(\time());
        $viewToday = ViewStatistic::getTotalViewsOfAuthorInDay($userID, $today);

        return absint($viewToday);
    }

    public function fetchViewsGeneral()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        $this->middleware(['isUserLoggedIn'], []);
        $userID = get_current_user_id();
        $aCompareViews = ViewStatistic::compare($userID);

        $oRetrieve->success(
            [
                'totalViews' => $aCompareViews['total'],
                'oChanging'  => [
                    'number'      => $aCompareViews['number'],
                    'description' => esc_html__('Compared with the last week', 'wiloke-listing-tools'),
                    'title'       => esc_html__('Post Reach', 'wiloke-listing-tools'),
                    'status'      => $aCompareViews['status'],
                    'is'          => $aCompareViews['is']
                ]
            ]
        );

    }

    public function getViewsOfLatestWeek()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        $this->middleware(['isUserLoggedIn'], []);

        $aDateInThisWeek = Time::getAllDaysInThis();
        $aCountViewsOfWeek = [];
        $today = Time::mysqlDate(\time());
        $userID = get_current_user_id();

        $aCompareViews = ViewStatistic::compare($userID);

        foreach ($aDateInThisWeek as $date) {
            if ($today == $date) {
                $viewsToday = self::getViewsToday($userID);
                $aCountViewsOfWeek[] = $viewsToday;
            } else {
                $aCountViewsOfWeek[] = ViewStatistic::getTotalViewsOfAuthorInDay($userID, $date);
            }
        }

        $start = date(get_option('date_format'), strtotime($aDateInThisWeek['monday']));
        $end = date(get_option('date_format'), strtotime(end($aDateInThisWeek)));

        $oRetrieve->success([
            'data'    => $aCountViewsOfWeek,
            'total'   => $aCompareViews['total'],
            'range'   => $start . ' - ' . $end,
            'compare' => [
                'diff'           => $aCompareViews['diff'],
                'tooltip'        => esc_html__('Compare with the last week', 'wiloke-listing-tools'),
                'label'          => esc_html__('View Statistic', 'wiloke-listing-tools'),
                'status'         => $aCompareViews['status'],
                'representColor' => $aCompareViews['representColor']
            ]
        ]);
    }

    public static function getViews($postID, $isRestyleText = false)
    {
        //		$viewStatistic = FileSystem::fileGetContents(self::$cacheFile);
        //		if ( empty($viewStatistic) ){
        //			$today = Time::mysqlDate(\time());
        //			$aViewStatistic = maybe_serialize($viewStatistic);
        //			$countViewsToday = isset($aViewStatistic[$today]) ? absint($aViewStatistic[$today]) : 1;
        //		}else{
        //			$countViewsToday = 1;
        //		}

        $countViews = ViewStatistic::countViews($postID);

        if (empty($countViews)) {
            return !$isRestyleText ? 1 : HTML::reStyleText($countViews);
        }

        $totalViewed = absint($countViews);

        return !$isRestyleText ? $totalViewed : HTML::reStyleText($totalViewed);
    }

    public function update()
    {
        $this->middleware(['isPublishedPost'], [
            'postID' => $_POST['postID']
        ]);
        $postID = absint($_POST['postID']);
        if ($countViewToday = ViewStatistic::countViewsInDay($postID, Time::mysqlDate())) {
            ViewStatistic::update($postID);
        } else {
            ViewStatistic::insert($postID, 1);
        }

        $postViewed = GetSettings::getPostMeta($postID, 'count_viewed');
        $postViewed = absint($postViewed) + 1;
        SetSettings::setPostMeta($postID, 'count_viewed', $postViewed);

        wp_send_json_success($countViewToday + 1);
    }
}
