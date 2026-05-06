<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\HTML;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Models\SharesStatistic;
use WilokeListingTools\Models\ViewStatistic;

class SharesStatisticController extends Controller
{
    public function __construct()
    {
        add_action('wp_ajax_wilcity_count_shares', [$this, 'update']);
        add_action('wp_ajax_nopriv_wilcity_count_shares', [$this, 'update']);

        add_action('wp_ajax_wilcity_shares_latest_week', [$this, 'getSharesOfLatestWeek']);
        add_action('wp_ajax_wilcity_fetch_shares_general', [$this, 'fetchSharesGeneral']);

        add_action('rest_api_init', function () {
            register_rest_route(WILOKE_PREFIX . '/v2', '/dashboard/(?P<postID>\d+)/compare-shares', [
                'methods'             => 'GET',
                'callback'            => [$this, 'getCompareShare'],
                'permission_callback' => '__return_true'
            ]);
        });
    }

    public function getCompareShare(\WP_REST_Request $oRequest)
    {
        $postID = $oRequest->get_param('postID');
        $aComparison = SharesStatistic::compare(get_post_field('post_author', $postID), $postID);

        if ($aComparison['number'] > 1) {
            $aComparison['text'] = esc_html__('Shares', 'wiloke-listing-tools');
        } else {
            $aComparison['text'] = esc_html__('Share', 'wiloke-listing-tools');
        }

        return ['data' => $aComparison];
    }

    public function fetchSharesGeneral()
    {
        $this->middleware(['isUserLoggedIn'], []);
        $userID = get_current_user_id();
        $aCompare = SharesStatistic::compare($userID);

        wp_send_json_success(
            [
                'totalShares' => $aCompare['total'],
                'oChanging'   => [
                    'number'      => $aCompare['number'],
                    'description' => esc_html__('Compared to the last week', 'wiloke-listing-tools'),
                    'status'      => $aCompare['status'],
                    'is'          => $aCompare['is']
                ]
            ]
        );

    }

    public function getSharesOfLatestWeek()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        $this->middleware(['isUserLoggedIn'], []);

        $aDateInThisWeek = Time::getAllDaysInThis();
        $aCountViewsOfWeek = [];
        $today = Time::mysqlDate(\time());
        $userID = get_current_user_id();
        $aCompareShares = SharesStatistic::compare($userID);

        foreach ($aDateInThisWeek as $date) {
            if ($today == $date) {
                $shareToday = SharesStatistic::getShareToday($userID);
                $aCountViewsOfWeek[] = $shareToday;
            } else {
                $aCountViewsOfWeek[] = SharesStatistic::getTotalSharesOfAuthorInDay($userID, $date);
            }
        }

        $oRetrieve->success([
            'data'    => $aCountViewsOfWeek,
            'total'   => $aCompareShares['total'],
            'compare' => [
                'diff'           => $aCompareShares['diff'],
                'tooltip'        => esc_html__('Compare with the last week', 'wiloke-listing-tools'),
                'label'          => esc_html__('Share Statistic', 'wiloke-listing-tools'),
                'status'         => $aCompareShares['status'],
                'representColor' => $aCompareShares['representColor']
            ]
        ]);
    }

    public static function renderShared($postID, $hasDecoration = true)
    {
        $countShared = GetSettings::getPostMeta($postID, 'count_shared');
        $countShared = absint($countShared);

        if (!$hasDecoration) {
            return $countShared;
        }
        $countShared = empty($countShared) ? 0 : $countShared;

        echo '<span class="wilcity-count-shared-' . esc_attr($postID) . '">' . $countShared . ' ' .
            esc_html__('Shared', 'wiloke-listing-tools') . '</span>';
    }

    public function update()
    {
        $this->middleware(['isPublishedPost'], [
            'postID' => $_POST['postID']
        ]);
        $postID = absint($_POST['postID']);

        if ($countSharedToday = SharesStatistic::countSharesToday($postID, Time::mysqlDate())) {
            SharesStatistic::update($postID);
        } else {
            SharesStatistic::insert($postID, 1);
        }

        $postShared = GetSettings::getPostMeta($postID, 'count_shared');
        $postShared = absint($postShared) + 1;
        SetSettings::setPostMeta($postID, 'count_shared', $postShared);

        wp_send_json_success([
            'countShared' => $postShared,
            'text'        => esc_html__('Shared', 'wiloke-listing-tools')
        ]);
    }
}
