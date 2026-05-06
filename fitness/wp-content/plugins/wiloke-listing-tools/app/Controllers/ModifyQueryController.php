<?php

namespace WilokeListingTools\Controllers;

use Exception;
use WilokeListingTools\AlterTable\AlterTableBusinessHourMeta;
use WilokeListingTools\AlterTable\AlterTableBusinessHours;
use WilokeListingTools\AlterTable\AlterTableEventsData;
use WilokeListingTools\AlterTable\AlterTableLatLng;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Helpers\Validation;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Register\WilokeSubmission;
use WilokeThemeOptions;
use WP_Query;

class ModifyQueryController extends Controller
{
    private $isImprovedSearchByTitle = false;
    private $aDefaultOrderBy         = ['post_title', 'post_date', 'post_name', 'slug', 'menu_order post_date'];
    private $aSpecialOrderBy         = ['meta_value_num'];
    private $aNeedJoinPostMeta       = ['search_terms', 'price_range'];
    private $isUsingDefaultOrderBy   = false;
    private $aEventOrders;

    public function __construct()
    {
        add_filter('query_vars', [$this, 'customQuery']);
        //        add_filter('posts_where', [$this, 'addFulltextSearch'], 10, 2);

        add_filter('posts_search', [$this, 'improveSearchTitle'], 10, 2);
        add_filter('posts_distinct', [$this, 'addUniQueryToSelectID'], 10, 2);
        add_filter('posts_join', [$this, 'joinPriceRangeMeta'], 10, 2);
//        add_filter('posts_join', [$this, 'joinLatLngTbl'], 10, 2);

        add_filter('posts_where', [$this, 'addEventWhen'], 10, 2);
        add_filter('posts_join', [$this, 'joinEvents'], 10, 2);
        add_filter('posts_where', [$this, 'addEventBetweenDateRange'], 10, 2);
        add_filter('posts_fields', [$this, 'addEventSelection'], 10, 2);
        add_filter('posts_orderby', [$this, 'orderByEventDate'], 99, 2);

        /* Latlng and map bound query  */
        add_filter('posts_join', [$this, 'joinLatLng'], 10, 2);
        add_filter('posts_join', [$this, 'preventListingsThatDoesNotHaveLatLng'], 10, 2);
        add_filter('posts_where', [$this, 'addPreventListingsThatDoesNotHaveLatLng'], 10, 2);
        add_filter('posts_pre_query', [$this, 'addHavingDistance'], 10, 2);
        add_filter('posts_orderby', [$this, 'orderByDistance'], 10, 2);

        add_filter('posts_fields', [$this, 'addLatLngSelectionToQuery'], 10, 2);
        add_filter('posts_where', [$this, 'addMapBoundsToQuery'], 10, 2);
        /* End */

        /* Price Range */
        add_filter('posts_where', [$this, 'addPriceRangeQuery'], 10, 2);
        add_filter('posts_groupby_request', [$this, 'addGroupByID'], 10, 2);
        //        add_filter('posts_orderby', [$this, 'orderByPriceRange'], 10, 2);

        /**
         * Review Item
         */
        add_filter('posts_where', [$this, 'notPostMimeType'], 10, 2);

        add_filter('posts_join', [$this, 'joinOpenNow'], 50, 2);
        add_filter('posts_where', [$this, 'addOpenNowToPostsWhere'], 50, 2);

        add_filter('terms_clauses', [$this, 'modifyTermsClauses'], 99999, 3);
        add_filter('wilcity/filter/wiloke-shortcodes/new-grid/query-args', [$this, 'addWPMLCurrentLanguageToQuery']);
        add_filter('wilcity/filter/wiloke-shortcodes/listings-tabs/query-args',
            [$this, 'addWPMLCurrentLanguageToQuery']);

//        add_filter('terms_pre_query', [$this, 'printTermQuery'], 10, 2);

//        add_filter('posts_pre_query', [$this, 'checkEventQuery'], 10, 2);
    }

    public function printTermQuery($term, $that)
    {
        if (isset($_GET['test'])) {
            echo $that->request;
            die;
        }
    }

    public function addWPMLCurrentLanguageToQuery($aArgs)
    {
        if (defined('ICL_LANGUAGE_CODE')) {
            $aArgs['lang'] = ICL_LANGUAGE_CODE;
        }

        return $aArgs;
    }

    public function checkEventQuery($x, $that)
    {
        //        if ($that->query_vars['price_range']) {
        //            var_export($that->request);
        //            die();
        //        }
        //
        //        if (strpos($that->request, 'term_meta1') !== false) {
        //            var_export($that->request);
        //            die;
        //        }
        if ($that->query_vars['post_type'] === 'event' && isset($_GET['test'])) {
            echo($that->request);
            die;
        }
//        $aPostTypes = $that->query_vars['post_type'];
//        $isEventGroup = (is_string($aPostTypes) && in_array($aPostTypes, General::getPostTypeKeysGroup('event'))) ||
//            (is_array($aPostTypes) && array_intersect($aPostTypes, General::getPostTypeKeysGroup('event')));
//        if (current_user_can('administrator') && $isEventGroup) {
//            global $wpdb;
//            var_export($that->request);
//            die;
//        }
//        var_export($x);
//        die;
//
        if ($that->query_vars['post_type'] === 'post1') {
            var_export($that->request);
            die;
        }

        return $x;
    }

    private function isListingQuery($postTypes): bool
    {
        if (is_array($postTypes)) {
            foreach ($postTypes as $postType) {
                if (!General::isPostTypeSubmission($postType)) {
                    return false;
                }
            }
        } else {
            if (!General::isPostTypeSubmission($postTypes)) {
                return false;
            }
        }

        return true;
    }

    private function getEventOrders()
    {
        if (empty($this->aEventOrders)) {
            $aAllEventFilters = wilokeListingToolsRepository()
                ->get('listing-settings:searchFields', true)
                ->sub('event_filter', true)
                ->sub('options');

            unset($aAllEventFilters['any_event']);
            unset($aAllEventFilters['pick_a_date_event']);
            unset($aAllEventFilters['recommended']);

            $this->aEventOrders = array_keys($aAllEventFilters);
            array_push($this->aEventOrders, 'wilcity_event_starts_on');
            array_push($this->aEventOrders, 'starts_from_ongoing_event');
        }

        return $this->aEventOrders;
    }

    private function isEventFilter($order)
    {
        $this->getEventOrders();

        return in_array($order, $this->aEventOrders);
    }

    public function addSubCategoriesToQuery(WP_Query $that)
    {
        if ($this->isAdminQuery()) {
            return false;
        }

        $aListingTypes = General::getPostTypeKeys(false, false);
        $postTypes = $that->get('post_type');
        $postTypes = is_string($postTypes) ? [$postTypes] : $postTypes;


        if (!array_intersect($postTypes, $aListingTypes)) {
            return false;
        }

        $aTaxonomies = $that->get('tax_query');
        if (empty($aTaxonomies)) {
            return false;
        }

    }

    private function hasQueryVar($that, $key): bool
    {
        if (!isset($that->query_vars[$key]) || empty($that->query_vars[$key]) || $that->query_vars[$key] === 'no') {
            return false;
        }

        return true;
    }

    private function orderByEventConditional($that): bool
    {
        if (!$this->hasQueryVar($that, 'orderby') ||
            (strpos($that->query_vars['orderby'], '_event') === false)
        ) {
            return false;
        }

        return true;
    }

    public function checkTermQuery($x, $that)
    {

        if (strpos($that->request, 'term_meta1') !== false) {
            var_export($that->request);
            die;
        }

        var_export($that->request);
        die;

        return $x;
    }

    public function addHavingDistance($nothing, $that)
    {
        if ($this->isAdminQuery() ||
            !$this->hasQueryVar($that, 'geocode') ||
            !isset($that->query_vars['post_type']) ||
            !$this->isListingQuery($that->query_vars['post_type'])
        ) {
            return $nothing;
        }
        global $wpdb;
        $radius = $wpdb->_real_escape($that->query_vars['geocode']['radius']);
        $that->request = str_replace('ORDER BY', 'HAVING wiloke_distance < ' . $radius . ' ORDER BY', $that->request);


        return $nothing;
    }

    public function customQuery($aVars)
    {
        $aVars[] = 'geocode';
        $aVars[] = 'map_bounds';
        $aVars[] = 'latLng';
        $aVars[] = 'unit';
        $aVars[] = 'open_now';
        $aVars[] = 'date_range';
        $aVars[] = 'is_map';
        $aVars[] = 'match';
        $aVars[] = 'not_post_mine_type';
        $aVars[] = 'price_range';
        $aVars[] = 'event_filter';

        return $aVars;
    }

    public function addFulltextSearch($where, $that)
    {
        global $wpdb;
        if (isset($that->query_vars['match'])) {
            $relationship = $that->query_vars['match']['relation'] ?? 'AND';
            $aParams = $that->query_vars['match']['params'];


            $query = '';
            foreach ($aParams as $aParam) {
                $conditional = 'MATCH(' . $wpdb->_real_escape($aParam['match']) . ') AGAINST ("' .
                    $wpdb->_real_escape($aParam['against']) . '")';
                if (!empty($query)) {
                    $query .= $relationship . ' ' . $conditional;
                } else {
                    $query = $conditional;
                }
            }


            $where .= ' AND ' . $query;
        }

        return $where;
    }

    public function improveSearchTitle($search, $wpQuery)
    {
        if ($this->isAdminQuery()) {
            return $search;
        }

        if (isset($wpQuery->query_vars['post_type'])) {
            if (is_array($wpQuery->query_vars['post_type'])) {
                foreach ($wpQuery->query_vars['post_type'] as $postType) {
                    if (!General::isPostTypeSubmission($postType, false, false)) {
                        return $search;
                    }
                }
            } else {
                if (!General::isPostTypeSubmission($wpQuery->query_vars['post_type'], false, false)) {
                    return $search;
                }
            }

            if (!empty($search) && !empty($wpQuery->query_vars['s'])) {
                global $wpdb;
                $n = !empty($wpQuery->query_vars['exact']) ? '' : '%';
                $aSearch = [];

                if (has_filter('wilcity/filter/wiloke-listing-tools/wp_search')) {
                    $search = apply_filters(
                        'wilcity/filter/wiloke-listing-tools/wp_search',
                        $wpdb->_real_escape($wpQuery->query_vars['s']),
                        $wpQuery
                    );
                }

                if ($search === $wpQuery->query_vars['s']) {
                    $keyword = $n . $wpdb->_real_escape($wpQuery->query_vars['s']) . $n;
                    $aSpecialSearch[] = $wpdb->prepare(
                        "($wpdb->posts.post_title LIKE %s)",
                        $keyword
                    );

                    $aSpecialSearch[] = $wpdb->prepare(
                        "($wpdb->posts.post_content LIKE %s)",
                        $keyword
                    );

                    $fQuery = implode(' OR ', $aSpecialSearch);

                    $aSearch[] = $wpdb->prepare(
                        "($wpdb->postmeta.meta_key IN ('wilcity_phone', 'wilcity_website', 'wilcity_tagline') AND $wpdb->postmeta.meta_value LIKE %s)",
                        $keyword
                    );
                    $search = ' AND (' . $fQuery . ' OR ' . ' (' . implode(' OR ', $aSearch) . ') )';
                }

                $this->isImprovedSearchByTitle = true;
            }
        }

        return $search;
    }

    public function addUniQueryToSelectID($district, $that)
    {
        if ($this->isAdminQuery()) {
            return $district;
        }

        if (!isset($that->query_vars['search_terms']) || empty($that->query_vars['search_terms'])) {
            return $district;
        }

        if (!$this->isImprovedSearchByTitle) {
            return $district;
        }

        $this->isImprovedSearchByTitle = true;

        return 'DISTINCT';
    }

    public function maybeJoinEventData($join, $that)
    {
        if (!isset($that->query_vars['post_type']) ||
            !General::isPostTypeInGroup($that->query_vars['post_type'], 'event')) {
            return $join;
        }

        if ($this->isAdminQuery()) {
            return $join;
        }

        global $wpdb;
        $eventDataTbl = $wpdb->prefix . AlterTableEventsData::$tblName;
        if (strpos($join, $eventDataTbl) !== false) {
            return $join;
        }

        $join .= " LEFT JOIN $eventDataTbl ON ($eventDataTbl.objectID = $wpdb->posts.ID)";

        return $join;
    }

    public function joinLatLngTbl($join, $that)
    {
        global $wpdb;
        if ($this->isAdminQuery() || !isset($that->query_vars['post_type']) || !$this->isListingQuery
            ($that->query_vars['post_type'])) {
            return $join;
        }

        if (!$this->hasQueryVar($that, 'search_terms')) {
            return $join;
        }

        $postMetaTbl = $wpdb->postmeta;
        if (strpos($join, 'postmeta') === false) {
            $join .= " INNER JOIN $postMetaTbl ON ($postMetaTbl.post_id = $wpdb->posts.ID)";
        }

        $latLngTbl = $wpdb->prefix . AlterTableLatLng::$tblName;

        if (strpos($join, $latLngTbl) !== false) {
            return $join;
        }

        $join .= " LEFT JOIN $latLngTbl ON ($latLngTbl.objectID = $wpdb->posts.ID)";

        return $join;
    }

    public function preventListingsThatDoesNotHaveLatLng($join, $that)
    {
        if ($this->isAdminQuery() || !isset($that->query_vars['post_type']) || !$this->isListingQuery
            ($that->query_vars['post_type'])) {
            return $join;
        }
        global $wpdb;
        $latLngTbl = $wpdb->prefix . AlterTableLatLng::$tblName;
        if (isset($that->query_vars['is_map']) && $that->query_vars['is_map'] == 'yes') {
            $joinLatLng = " LEFT JOIN $latLngTbl ON ($latLngTbl.objectID = $wpdb->posts.ID)";
            if (strpos($join, $joinLatLng) === false) {
                $join .= " " . $joinLatLng;
            }
        }

        return $join;
    }

    public function joinLatLng($join, $that)
    {
        if ($this->isAdminQuery() ||
            !isset($that->query_vars['post_type']) ||
            !$this->isListingQuery($that->query_vars['post_type']) ||
            (!$this->hasQueryVar($that, 'geocode') && !$this->hasQueryVar($that, 'map_bounds'))) {
            return $join;
        }

        global $wpdb;
        $latLngTbl = $wpdb->prefix . AlterTableLatLng::$tblName;
        $joinLatLng = " LEFT JOIN $latLngTbl ON ($latLngTbl.objectID = $wpdb->posts.ID)";
        if (strpos($join, $joinLatLng) === false) {
            $join .= $joinLatLng;
        }

        return $join;
    }

    public function joinOpenNow($join, $that): string
    {
        global $wpdb;

        if ($this->isAdminQuery() || !$this->hasQueryVar($that, 'open_now')) {
            return $join;
        }
        $businessHourTbl = $wpdb->prefix . AlterTableBusinessHours::$tblName;
        $bhMeta = $wpdb->prefix . AlterTableBusinessHourMeta::$tblName;


        $join .= " LEFT JOIN $businessHourTbl ON ($businessHourTbl.objectID=$wpdb->posts.ID) LEFT JOIN $bhMeta ON ($bhMeta.objectID=$wpdb->posts.ID) ";

        return $join;
    }

    protected function convertTimeToNumber($number)
    {
        return absint(str_replace(':', '', $number));
    }

    public function addOpenNowToPostsWhere($where, $that): string
    {
        if ($this->isAdminQuery() || !$this->hasQueryVar($that, 'open_now')) {
            return $where;
        }

        global $wpdb;

        $businessHourTbl = $wpdb->prefix . AlterTableBusinessHours::$tblName;
        $businessHourMeta = $wpdb->prefix . AlterTableBusinessHourMeta::$tblName;
        $siteTimeString = get_option('timezone_string');
        $utcTimestampNow = current_time('timestamp', true);
        $todayIndex = Time::convertToNewDateFormat($utcTimestampNow, 'w', $siteTimeString);
        $dayKey = Time::getDayKey($todayIndex);
        $prevDayIndex = $todayIndex == 0 ? 6 : $todayIndex - 1;
        $prevDayKey = Time::getDayKey($prevDayIndex);

        $utcHourNow = Time::convertToNewDateFormat($utcTimestampNow, 'H:i:s', $siteTimeString);

        $utcHourNumberNow = $this->convertTimeToNumber($utcHourNow);

        $metaValueQuery = apply_filters(
            'wilcity/filter/wiloke-listing-tools/add-open-now-to-post-where',
            "('always_open', 'no_hours_available')"
        );

        $where .= " AND (($businessHourMeta.meta_key = 'wilcity_hourMode') AND
	   (
	       ($businessHourMeta.meta_value != 'business_closures') AND (
	           ($businessHourMeta.meta_value IN $metaValueQuery) OR
                (
                    ($businessHourMeta.meta_value = 'open_for_selected_hours') AND
                    (
                        (
                            $businessHourTbl.dayOfWeek='" . $wpdb->_real_escape($dayKey) . "' AND
                            $businessHourTbl.isOpen='yes' AND
                            (
                                ($businessHourTbl.firstOpenHour =  '24:00:00' AND $businessHourTbl.firstCloseHour = '24:00:00')
                                OR
                                ($businessHourTbl.firstOpenHour <= '" . $utcHourNow .
            "' AND $businessHourTbl.firstCloseHour >= '" . $utcHourNow . "')
                                OR
                                ($businessHourTbl.secondOpenHour <= '" . $utcHourNow .
            "' AND $businessHourTbl.secondCloseHour >= '" . $utcHourNow . "')
                                OR
                                (
                                    $utcHourNumberNow < 235959 AND
                                    (
                                        ($businessHourTbl.firstOpenHour <= '" . $utcHourNow . "' AND '" . $utcHourNow . "' >= $businessHourTbl.firstCloseHour AND $businessHourTbl.firstCloseHour < $businessHourTbl.firstOpenHour)
                                        OR
                                        (
                                            ($businessHourTbl.secondOpenHour IS NOT NULL AND $businessHourTbl.secondCloseHour IS NOT NULL)
                                            AND
                                            ($businessHourTbl.secondOpenHour <= '" . $utcHourNow . "' AND '" .
            $utcHourNow . "' >= $businessHourTbl.secondCloseHour AND $businessHourTbl.secondCloseHour < $businessHourTbl.secondOpenHour)
                                        )
                                    )
                                )
                            )
                        ) OR (
                            $businessHourTbl.dayOfWeek='" . $wpdb->_real_escape($prevDayKey) . "' AND
                            $businessHourTbl.isOpen='yes' AND
                            $utcHourNumberNow <= '50000' AND
                            (
                                (
                                    ($businessHourTbl.secondCloseHour IS NOT NULL) AND
                                    ($businessHourTbl.secondCloseHour <= '05:00:00') AND
                                    ($businessHourTbl.secondCloseHour > '" . $utcHourNow . "')
                                ) OR (
                                    ($businessHourTbl.secondCloseHour IS  NULL) AND
                                    ($businessHourTbl.firstCloseHour IS NOT NULL) AND
                                    ($businessHourTbl.firstCloseHour <= '05:00:00') AND
                                    ($businessHourTbl.firstCloseHour > '" . $utcHourNow . "')
                                )
                            )
                        )
                    )
                )
            )
        )
    ) ";

        return $where;
    }

    public function addEventWhen($where, $that)
    {
        if ($this->isSingleEventPage($that) ||
            (!$this->isQueryEvent($that, false) && !$this->isFocusExcludeEventExpired($that))) {
            return $where;
        }

        global $wpdb;
        $eventTbl = $wpdb->prefix . AlterTableEventsData::$tblName;
        $now = Time::mysqlDateTime(current_time('timestamp'), 'UTC+0');
        $filterBy = $that->query_vars['event_filter'] ?? '';
        if (empty($filterBy)) {
            if (isset($that->query_vars['orderby']) &&
                in_array($that->query_vars['orderby'], ['upcoming_event', 'ongoing_event'])) {
                $filterBy = $that->query_vars['orderby'];
            }
        }
        $isShowUpExpiredEvent = apply_filters('wilcity/filter/wiloke-listing-tools/show-expired-event', false);

        if (!$this->isEventFilter($filterBy) && $filterBy !== 'expired_event') {
            if (!empty($that->query_vars['date_range'])) {
                return $where;
            }

            if (!$isShowUpExpiredEvent) {
                $where .= " AND ($eventTbl.endsOnUTC >= '" . $now . "' OR $eventTbl.endsOnUTC IS NULL)";
            }

            return $where;
        }

        if (!$isShowUpExpiredEvent && $filterBy !== 'expired_event') {
            $where .= " AND ($eventTbl.endsOnUTC >= '" . $now . "' OR $eventTbl.endsOnUTC IS NULL)";
        }

        switch ($filterBy) {
            case 'today_event':
            case 'this_month_event':
            case 'next_week_event':
            case 'this_week_event':
            case 'tomorrow_event':
                try {
                    switch ($filterBy) {
                        case 'this_month_event':
                            $earliest = Time::getFirstDayOfThisMonth('earliest', 'mysql');
                            $latest = Time::getLastDayOfThisMonth('latest', 'mysql');
                            break;
                        case 'this_week_event':
                            $earliest = Time::getFirstDayOfThisWeek('earliest', 'mysql');
                            $latest = Time::getLastDayOfThisWeek('latest', 'mysql');
                            break;
                        case 'next_week_event':
                            $earliest = Time::getFirstDayOfNextWeek('earliest', 'mysql');
                            $latest = Time::getLastDayOfNextWeek('latest', 'mysql');
                            break;
                        case 'tomorrow_event':
                            $earliest = Time::getTomorrow('latest', 'mysql');
                            $latest = Time::getTomorrow('earliest', 'mysql');
                            break;
                        default:
                            $earliest = Time::getToday('latest', 'mysql');
                            $latest = Time::getToday('earliest', 'mysql');
                            break;
                    }

                    $where .= " AND (
                        ($eventTbl.startsOnUTC <= '{$earliest}' AND $eventTbl.endsOnUTC >= '{$latest}')
                        OR
                        ($eventTbl.startsOnUTC BETWEEN '{$earliest}' AND '{$latest}')
                        OR
                        ($eventTbl.endsOnUTC BETWEEN '{$earliest}' AND '{$latest}')
                    )";
                }
                catch (Exception $e) {
                    $where .= " AND 1=2";
                }

                break;
            case 'upcoming_event':
                $where .= " AND $eventTbl.startsOnUTC > '" . $now . "'";
                break;
            case 'ongoing_event':
            case 'happening_event':
                $where .= " AND $eventTbl.startsOnUTC <= '" . $now . "' AND $eventTbl.endsOnUTC >= '" . $now . "'";
                break;
            case 'expired_event':
                $where .= " AND (($eventTbl.endsOnUTC <= '" . $now .
                    "' OR $eventTbl.endsOnUTC <= $eventTbl.startsOn) || ($eventTbl.endsOnUTC IS NULL))";
                break;
            case 'starts_from_ongoing_event':
                $where .= " AND ( ( $eventTbl.startsOnUTC <= '" . $now . "' AND $eventTbl.endsOnUTC >= '" . $now .
                    "') OR $eventTbl.startsOnUTC > '" . $now . "' )";
                break;
            default:
                $originalWhere = $where;
                if (!isset($that->query_vars['isDashboard']) && !$isShowUpExpiredEvent) {
                    $where .= " AND $eventTbl.endsOnUTC >= '" . $now . "'";
                }

                $where = apply_filters(
                    'wilcity/wiloke-listing-tools/filter/event-orderby',
                    $where,
                    $originalWhere,
                    $filterBy,
                    $now
                );
                break;
        }

        return $where;
    }

    public function addPreventListingsThatDoesNotHaveLatLng($where, $that)
    {
        if ($this->isAdminQuery()) {
            return $where;
        }
        global $wpdb;
        $latLngTbl = $wpdb->prefix . AlterTableLatLng::$tblName;


        if (isset($that->query_vars['is_map']) && $that->query_vars['is_map'] == 'yes') {
            $where .= " AND ($latLngTbl.lat != '' AND $latLngTbl.lng != '' AND $latLngTbl.lat != $latLngTbl.lng) ";
        }

        return $where;
    }

    public function orderByEventDate($orderBy, $that)
    {
        if ($this->isSingleEventPage($that) || !$this->isQueryEvent($that, false)) {
            return $orderBy;
        }

        global $wpdb;
        $order
            = isset($that->query_vars['order']) ? $wpdb->_escape($that->query_vars['order']) : 'ASC';

        if (!isset($that->query_vars['orderby'])) {
            $that->query_vars['orderby'] = 'wilcity_event_starts_on';
            $orderBy = $that->query_vars['orderby'] . ' ' . $that->query_vars['order'];
        } else {
            if (strpos($that->query_vars['orderby'], 'menu_order') !== false) {
                $aParseOrder = explode(' ', $that->query_vars['orderby']);
                if (isset($aParseOrder[1]) && !empty(trim($aParseOrder[1]))) {
                    $orderBy = $aParseOrder[0] . ' DESC ,' . $aParseOrder[1] . ' ' . $order;
                }
            } else {
                if ($that->query_vars['orderby'] == 'rand') {
                    $orderBy = $orderBy . ' ' . ($that->query_vars['order'] ?? 'ASC');
                } else {
                    $orderBy = $that->query_vars['orderby'] . ' ' . ($that->query_vars['order'] ?? 'ASC');
                }
            }
        }

        return $orderBy;
    }

    public function joinEvents($join, $that)
    {
        if ($this->isSingleEventPage($that)) {
            return $join;
        }

        global $wpdb;
        if (!$this->isQueryEvent($that, false) && !$this->isFocusExcludeEventExpired($that)) {
            return $join;
        }

        $eventsDataTbl = $wpdb->prefix . AlterTableEventsData::$tblName;


        if (strpos($join, $eventsDataTbl) !== false) {
            return $join;
        }

        $join .= " LEFT JOIN $eventsDataTbl ON ($eventsDataTbl.objectID = $wpdb->posts.ID)";

        return $join;
    }

    private function isJoinPostMeta($that)
    {
        foreach ($this->aNeedJoinPostMeta as $queryVar) {
            if (isset($that->query_vars[$queryVar]) && !empty($that->query_vars[$queryVar])) {
                return true;
            }
        }

        return false;
    }

    public function joinPriceRangeMeta($join, $that)
    {
        if ($this->isAdminQuery() || !isset($that->query_vars['post_type']) || !$this->isListingQuery
            ($that->query_vars['post_type'])) {
            return $join;
        }

        if (!$this->isJoinPostMeta($that)) {
            return $join;
        }

        global $wpdb;
        $postMetaTbl = $wpdb->postmeta;

        if (strpos($join, 'postmeta') === false) {
            $join .= " INNER JOIN $postMetaTbl ON ($postMetaTbl.post_id = $wpdb->posts.ID)";
        }
        //

        if ($this->hasQueryVar($that, 'price_range')) {
            $join .= " INNER JOIN $postMetaTbl AS wilcity_postmeta_price_range ON (wilcity_postmeta_price_range.post_id = $wpdb->posts.ID)";
        }

        return $join;
    }

    public function orderByDistance($orderBy, $that)
    {
        if (
            $this->isAdminQuery() ||
            !$this->hasQueryVar($that, 'geocode') ||
            $this->isUsingDefaultOrderBy($that) ||
            !isset($that->query_vars['post_type']) ||
            !$this->isListingQuery($that->query_vars['post_type'])
        ) {
            return $orderBy;
        }

        return 'wiloke_distance';
    }

    public function orderByPriceRange($orderBy, $that)
    {
        if ($this->isAdminQuery() || $this->hasQueryVar($that, 'price_range')) {
            return $orderBy;
        }

    }

    public function addEventSelection($field, $that)
    {
        if ($this->isSingleEventPage($that)) {
            return $field;
        }

        if (!$this->isQueryEvent($that, false)) {
            return $field;
        }
        global $wpdb;
        $eventDataTbl = $wpdb->prefix . AlterTableEventsData::$tblName;

        $field .= ", $eventDataTbl.startsOn as wilcity_event_starts_on";
        if ($this->orderByEventConditional($that)) {
            $field .= ",$eventDataTbl.endsOn, $eventDataTbl.frequency";
        }

        return $field;
    }

    public function addGroupByID($groupby, $that)
    {
        if ($this->isAdminQuery()) {
            return $groupby;
        }

        if (!$this->hasQueryVar($that, 'price_range') && !$this->hasQueryVar($that, 'open_now')) {
            return $groupby;
        }

        global $wpdb;

        if (strpos($groupby, "GROUP BY $wpdb->posts.ID") !== false) {
            return $groupby;
        }

        return $wpdb->posts . ".ID";

    }

    private function parseQueryVars($key, $that)
    {
        if (is_string($that->query_vars[$key])) {
            if (Validation::isValidJson($that->query_vars[$key])) {
                $that->query_vars[$key] = Validation::getJsonDecoded();
            }
        }

        return true;
    }

    public function addPriceRangeQuery($where, $that)
    {
        global $wpdb;
        if ($this->isAdminQuery() || !$this->hasQueryVar($that, 'price_range') ||
            ($this->hasQueryVar($that, 'post_type') && $that->query_vars['post_type'] == 'product')) {
            return $where;
        }

        $postMetaTbl = $wpdb->postmeta;
        $this->parseQueryVars('price_range', $that);
        $min = floatval($that->query_vars['price_range']['min']);

        if (!isset($that->query_vars['price_range']['max']) || empty($that->query_vars['price_range']['max'])) {
            $where .= " AND ((wilcity_postmeta_price_range.meta_key='wilcity_single_price' OR wilcity_postmeta_price_range.meta_key='wilcity_minimum_price') AND wilcity_postmeta_price_range.meta_value > $min)";

            return $where;
        }

        $max = $that->query_vars['price_range']['max'];
        $max = $wpdb->_real_escape($max);
        $max = str_replace(['&lt;'], ['<'], $max);

        if (strpos($max, '>') !== false) {
            $compare = strpos($max, '>=') !== false ? '>=' : '>';
            $max = floatval(str_replace($compare, '', $max));
            $where .= " AND (wilcity_postmeta_price_range.meta_value $compare $max AND wilcity_postmeta_price_range.meta_key IN ('wilcity_single_price', 'wilcity_minimum_price'))";

            return $where;
        }

        if (strpos($max, '<') !== false) {
            $compare = strpos($max, '<=') !== false ? '<=' : '<';

            $max = floatval(str_replace($compare, '', $max));
            $where .= " AND (wilcity_postmeta_price_range.meta_value $compare $max AND wilcity_postmeta_price_range.meta_key IN ('wilcity_single_price', 'wilcity_maximum_price'))";

            return $where;
        }

        $max = floatval($max);
        $where .= " AND (
        (wilcity_postmeta_price_range.meta_value BETWEEN $min AND $max AND wilcity_postmeta_price_range.meta_key IN ('wilcity_single_price', 'wilcity_minimum_price', 'wilcity_maximum_price'))
         OR ( $postMetaTbl.meta_key ='wilcity_minimum_price' AND $postMetaTbl.meta_value < $min AND wilcity_postmeta_price_range.meta_key ='wilcity_maximum_price' AND wilcity_postmeta_price_range.meta_value > $max))";

        return $where;
    }

    public function addMapBoundsToQuery($where, $that)
    {
        if ($this->isAdminQuery() ||
            !isset($that->query_vars['post_type']) ||
            !$this->isListingQuery($that->query_vars['post_type']) ||
            !$this->hasQueryVar($that, 'map_bounds')) {
            return $where;
        }

        $this->parseQueryVars('map_bounds', $that);

        global $wpdb;
        $latLngTbl = $wpdb->prefix . AlterTableLatLng::$tblName;
        $additional
            = " AND ( ($latLngTbl.lat >= " . $wpdb->_real_escape($that->query_vars['map_bounds']['aFLatLng']['lat']) .
            " AND $latLngTbl.lat <= " . $wpdb->_real_escape($that->query_vars['map_bounds']['aSLatLng']['lat']) .
            ") AND ( $latLngTbl.lng >= " . $wpdb->_real_escape($that->query_vars['map_bounds']['aFLatLng']['lng']) .
            " AND  $latLngTbl.lng <= " . $wpdb->_real_escape($that->query_vars['map_bounds']['aSLatLng']['lng']) .
            " ) )";
        $where .= $additional;


        return $where;
    }

    public function notPostMimeType($where, $that)
    {
        if (isset($that->query_vars['not_post_mime_type']) && !empty($that->query_vars['not_post_mime_type'])) {
            global $wpdb;
            $additional = " AND $wpdb->posts.post_mime_type != '" . $wpdb->_real_escape
                ($that->query_vars['not_post_mime_type']) . "'";
            $where .= $additional;
        }

        return $where;
    }

    public function addLatLngSelectionToQuery($field, $that): string
    {
        if ($this->isAdminQuery() ||
            !$this->hasQueryVar($that, 'geocode') ||
            !isset($that->query_vars['post_type']) ||
            !$this->isListingQuery($that->query_vars['post_type'])
        ) {
            return $field;
        }

        global $wpdb;
        $latLngTbl = $wpdb->prefix . AlterTableLatLng::$tblName;

        $unit = $wpdb->_real_escape($that->query_vars['geocode']['unit']);
        $aParseLatLng = explode(',', $that->query_vars['geocode']['latLng']);
        $unit = strtolower($unit) == 'km' ? 6371 : 3959;
        $lat = $wpdb->_real_escape(trim($aParseLatLng[0]));
        $lng = $wpdb->_real_escape(trim($aParseLatLng[1]));

        $field .= ",( $unit * acos( cos( radians('" . $lat .
            "') ) * cos( radians( $latLngTbl.lat ) ) * cos( radians( $latLngTbl.lng ) - radians('" . $lng .
            "') ) + sin( radians('" . $lat . "') ) * sin( radians( $latLngTbl.lat ) ) ) ) as wiloke_distance";


        return $field;
    }

    public function addEventBetweenDateRange($where, $that): string
    {
        if ($this->isSingleEventPage($that)) {
            return $where;
        }

        if (!$this->isQueryEvent($that) || !isset($that->query_vars['date_range'])) {
            return $where;
        }

        $this->parseQueryVars('date_range', $that);

        global $wpdb;

        $eventTbl = $wpdb->prefix . AlterTableEventsData::$tblName;

        $aDataRange = $that->query_vars['date_range'];

        $dateFormat = apply_filters('wilcity_date_picker_format', 'mm/dd/yy');
        $dateFormat = Time::convertJSDateFormatToPHPDateFormat($dateFormat);

        // Ban than timestamp da la UTC+0
        $aDataRange['from'] = Time::resolveJSAndPHPTimestamp($aDataRange['from']);
        $aDataRange['to'] = Time::resolveJSAndPHPTimestamp($aDataRange['to']);

        $from = Time::mysqlDateTime(
            Time::toTimestamp($dateFormat, $wpdb->_real_escape($aDataRange['from']))
        );

        $to = Time::mysqlDateTime(
            Time::toTimestamp($dateFormat, $wpdb->_real_escape($aDataRange['to']))
        );

        $where .= " AND ($eventTbl.startsOnUTC <= $eventTbl.endsOnUTC) AND (
            ($eventTbl.startsOnUTC >= '" . $from . "' AND $eventTbl.startsOnUTC <= '" . $to . "')
            OR (
                $eventTbl.startsOnUTC < '" . $from . "'
                AND
                ($eventTbl.endsOnUTC >= '" . $from . "' OR $eventTbl.endsOnUTC >= '" . $to . "')
            )
        )";

        return $where;
    }

    /**
     * @param $that
     * @return bool
     */
    private function isSingleEventPage($that): bool
    {
        if (is_singular() && !is_page_template() && (!isset($that->query_vars['isEventOnListingPage']))) {
            return General::isPostTypeInGroup($that->query_vars['post_type'], 'event');
        }

        return false;
    }

    private function isQueryEvent($that, $isFocusDate = true): bool
    {
        if (WilokeSubmission::isAddListingMode()) {
            return false;
        }

        if ((is_admin() && !wp_doing_ajax())) {
            return false;
        }

        if (General::isPostTypeInGroup($that->query_vars['post_type'], 'event') ||
            isset($that->query_vars['isAppEventQuery'])) {
            return true;
        }

        if (is_singular() && !is_page_template()) {
            if (General::isPostTypeInGroup($that->query_vars['post_type'], 'event')) {
                return false;
            }
        }

        if ($this->isIgnoreModify($that, 'event')) {
            return false;
        }

        if ($isFocusDate) {
            return General::isPostTypeInGroup($that->query_vars['post_type'], 'event') &&
                isset($that->query_vars['date_range']) &&
                !empty($that->query_vars['date_range']);
        }

        if (is_string($that->meta_query) && strpos($that->meta_query, '_trackbackme') !== false) {
            return false;
        }

        return General::isPostTypeInGroup($that->query_vars['post_type'], 'event');

    }

    private function isUsingDefaultOrderBy($that)
    {
        if ($this->isUsingDefaultOrderBy) {
            return true;
        }

        foreach ($this->aSpecialOrderBy as $special) {
            if (strpos($that->query_vars['orderby'], $special) === 0) {
                $this->isUsingDefaultOrderBy = true;
            }
        }

        return $this->isUsingDefaultOrderBy;
    }

    public function modifyTermsClauses($clauses, $taxonomy, $aArgs)
    {
        if ($this->isAdminQuery()) {
            return $clauses;
        }

        global $wpdb;
        if (isset($aArgs['postTypes'])) {
            $postTypes = $aArgs['postTypes'];
        } elseif (isset($aArgs['post_types'])) {
            $postTypes = $aArgs['post_types'];
        }

        if (empty($postTypes)) {
            return $clauses;
        }

        // allow for arrays
        if (is_array($postTypes)) {
            $postTypes = $postTypes[0];
        } else {
            $postTypes = $wpdb->_real_escape($postTypes);
        }

        $clauses['join'] .= " LEFT JOIN $wpdb->termmeta AS wilcity_term_meta1 ON (
            tt.term_id = wilcity_term_meta1.term_id AND wilcity_term_meta1.meta_key='wilcity_belongs_to'
        )";

        $clauses['join'] .= " LEFT JOIN $wpdb->termmeta AS wilcity_term_meta2 ON (
            tt.term_id = wilcity_term_meta2.term_id
        )";

        $clauses['where'] .= " AND (wilcity_term_meta1.term_id IS NULL OR (wilcity_term_meta2.meta_key = 'wilcity_belongs_to' AND (wilcity_term_meta2.meta_value LIKE '%" .
            $wpdb->esc_like($postTypes) . "%')))";


        $clauses['distinct'] = 'distinct';

        return $clauses;
    }

    public function debugQuery($null, $that)
    {
        if (strpos($that->request, 'wiloke_distance') !== false) {
            var_export($that->query_vars['orderby']);
            die;
        }
    }

    protected function isIgnoreModify($that, $postType)
    {
        if (isset($that->query_vars['isIgnoreAllQueries']) && $that->query_vars['isIgnoreAllQueries']) {
            return true;
        }

        if (
            isset($that->query_vars['aIgnoreModifyPostTypes']) &&
            is_array($that->query_vars['aIgnoreModifyPostTypes']) && in_array(
                $postType,
                $that->query_vars['aIgnoreModifyPostTypes']
            )
        ) {
            return true;
        }

        return false;
    }

    protected function isFocusExcludeEventExpired($that): bool
    {
        if (!isset($that->query_vars['post_type']) ||
            !General::isPostTypeInGroup([$that->query_vars['post_type']], 'event')) {
            return false;
        }

        if (isset($that->query_vars['isFocusExcludeEventExpired']) && $that->query_vars['isFocusExcludeEventExpired']) {
            return true;
        }

        return false;
    }
}
