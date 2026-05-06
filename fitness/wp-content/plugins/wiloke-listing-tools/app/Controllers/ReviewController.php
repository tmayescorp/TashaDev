<?php

namespace WilokeListingTools\Controllers;

use WILCITY_APP\Controllers\BuildQuery;
use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Framework\Helpers\DebugStatus;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\PostSkeleton;
use WilokeListingTools\Framework\Helpers\QueryHelper;
use WilokeListingTools\Framework\Helpers\ReviewSkeleton;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Submission;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Helpers\UserSkeleton;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Framework\Upload\Upload;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\PostModel;
use WilokeListingTools\Models\ReviewMetaModel;
use WilokeListingTools\Models\ReviewModel;
use WilokeListingTools\Models\SharesStatistic;
use WilokeListingTools\Framework\Helpers\Validation;
use WP_Post;
use WP_Query;

class ReviewController extends Controller
{
    public        $aReviewSettings;
    public static $foundSticky              = false;
    public static $stickyID                 = 0;
    public static $postType                 = 'review';
    private       $aReviewPluck
                                            = [
            'ID',
            'title',
            'content',
            'date',
            'isLiked',
            'isParentAuthor',
            'isAuthor',
            'isPintToTop',
            'countShared',
            'countDiscussion',
            'countLiked',
            'author',
            'parentID',
            'link',
            'average',
            'mode',
            'quality',
            'details',
            'gallery',
            'menuOrder'
        ];
    private       $aEventDiscussionSettings = [];

    public function __construct()
    {
        add_action('wp_ajax_wilcity_submit_review', [$this, 'handleWebSubmitReview']);
        add_filter('wilcity/wilcity-mobile-app/submit-review', [$this, 'handleAppSubmitReview'], 10, 4);
        add_action('wp_ajax_wilcity_fetch_user_reviewed_data', [$this, 'fetchUserReviewedData']);
        add_action('wp_ajax_wilcity_review_is_update_like', [$this, 'updateLike']);
        add_action('wp_ajax_nopriv_wilcity_review_is_update_like', [$this, 'updateLike']);
        add_action('wp_ajax_wilcity_submit_review_discussion', [$this, 'ajaxBeforeSetReviewDiscussion']);
        add_action('wp_ajax_wilcity_review_discussion', [$this, 'ajaxBeforeSetReviewDiscussion']);

        add_action('wp_ajax_wilcity_delete_discussion', [$this, 'deleteDiscussion']);
        add_filter('wilcity/single-listing/tabs', [$this, 'checkReviewStatus'], 10, 2);
        add_action('wp_ajax_wilcity_delete_comment', [$this, 'deleteReview']);
        add_action('wp_ajax_wilcity_like_review', [$this, 'likeReview']);
        add_action('wp_enqueue_scripts', [$this, 'printReviewSettings']);
        add_action('wp_enqueue_scripts', [$this, 'printMyReview']);
        add_action('wp_ajax_wilcity_fetch_review_general', [$this, 'fetchReviewGeneral']);
        add_action('wp_ajax_nopriv_wilcity_fetch_review_general', [$this, 'fetchReviewGeneral']);
        add_action('wp_ajax_wilcity_pin_review_to_top', [$this, 'pinReviewToTop']);
        add_action('wp_ajax_wilcity_post_comment', [$this, 'postComment']);
        add_action('wp_ajax_nopriv_wilcity_post_comment', [$this, 'postComment']);

        add_filter('wilcity/addMiddlewareToReview/of/listing', [$this, 'addMiddleWareToReviewHandler']);

        add_action('wp_ajax_wilcity_fetch_ratings_general', [$this, 'fetchGeneralRatings']);
        add_action('wp_ajax_wilcity_ratings_latest_week', [$this, 'fetchRatingLastWeek']);

        add_action('wp_ajax_wilcity_fetch_discussions', [$this, 'fetchDiscussions']);
        add_action('wp_ajax_nopriv_wilcity_fetch_discussions', [$this, 'fetchDiscussions']);

        add_action('wp_ajax_wilcity_delete_review', [$this, 'deleteReviews']);

        add_filter('wilcity/wilcity-mobile-app/like-a-review', [$this, 'updateLikeReviewViaApp'], 10, 2);
        add_filter('wilcity/wilcity-mobile-app/post-review-discussion', [$this, 'appSetReviewDiscussion'], 10, 2);
        add_filter('wilcity/wilcity-mobile-app/put-review-discussion', [$this, 'appUpdateReviewDiscussion'], 10, 2);

        add_action('wp_ajax_wil_reviews_statistic', [$this, 'fetchReviewsStatistic']);
        add_action('wp_ajax_wil_publish_review', [$this, 'handlePublishReview']);
        add_action('wp_ajax_wil_hide_review', [$this, 'handleHideReview']);

        add_action('post_updated', [$this, 'updateAverageReviews'], 10, 3);
        add_action('init', [$this, 'setAverageRatingViaBackend']);
        add_action('before_delete_post', [$this, 'flushAllCache']);
        add_action(
            'wilcity/wiloke-listing-tools/app/Register/RegisterMenu/RegisterListingScripts/after/saved-review',
            [$this, 'flushReviewCacheInPostType'],
            10,
            2
        );

        add_action('wp_ajax_wil_fetch_reviews', [$this, 'fetchReviews']);
        add_action('wp_ajax_nopriv_wil_fetch_reviews', [$this, 'fetchReviews']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('template_redirect', [$this, 'redirectReviewToSingleListing']);
    }

    public function redirectReviewToSingleListing()
    {
        if (is_singular('review')) {
            global $post;
            $parentId = wp_get_post_parent_id($post->ID);
            if (!empty($parentId) && get_post_status($parentId) === 'publish') {
                wp_redirect(add_query_arg(
                    [
                        'tab'      => 'reviews',
                        'reviewId' => $post->ID
                    ],
                    get_permalink($parentId)
                ));
                die;
            }
        }
    }

    private function getEventDiscussionSettings($key): bool
    {
        if (empty($this->aEventDiscussionSettings)) {
            $this->aEventDiscussionSettings = GetSettings::getOptions(
                wilokeListingToolsRepository()
                    ->get('event-settings:keys', true)
                    ->sub('general'),
                false,
                true
            );
        }
        if (!empty($key)) {
            return isset($this->aEventDiscussionSettings[$key]) && $this->aEventDiscussionSettings[$key] === 'enable';
        }

        return false;
    }

    private function getSharingOn()
    {
        global $wiloke;
        $socials = \WilokeThemeOptions::getOptionDetail('sharing_on');
        return empty($socials) ? ['facebook', 'twitter', 'linkedin', 'whatsapp'] : $socials;
    }

    private function isDiscussionAllowed($post)
    {
        if ($post->post_type == 'event') {
            return $this->getEventDiscussionSettings('toggle_comment_discussion') ? 'yes' : 'no';
        } else {
            $key = General::getReviewKey('toggle_review_discussion', $post->post_type);

            return GetSettings::getOptions($key, false, true) === 'enable' ? 'yes' : 'no';
        }
    }

    private function getMyInfo()
    {
        if (!is_user_logged_in()) {
            return [];
        }

        $oUser = new UserSkeleton(get_current_user_id());

        return $oUser->pluck([
            'displayName',
            'avatar',
            'authorLink'
        ]);
    }

    public function enqueueScripts()
    {
        if (!is_singular(General::getPostTypeKeys(false, false)) && !is_author()) {
            return false;
        }

        global $post;

        $aMyReviews = [];
        if (is_user_logged_in()) {
            if (is_author()) {
                $aMyReviews = ReviewModel::getMyReviews([
                    'author' => get_current_user_id()
                ]);
            } else {
                $aMyReviews = ReviewModel::getMyReviews([
                    'post_parent' => $post->ID,
                    'author'      => get_current_user_id()
                ]);
            }
        }

        wp_localize_script('wilcity-empty', 'WIL_REVIEW_CONFIGURATION', [
            'sharingOn'           => $this->getSharingOn(),
            'reviews'             => [
                'statistic'  => ReviewMetaModel::getGeneralReviewData($post, true),
                'total'      => ReviewModel::countTotalReviews($post->ID),
                'isReviewed' => !ReviewModel::isEnabledReview($post->post_type) || ReviewModel::isUserReviewed
                ($post->ID) ? 'yes' : 'no',
                'mode'       => ReviewModel::getReviewMode($post->post_type),
                'myReview'   => empty($aMyReviews) ? [] : $aMyReviews
            ],
            'isAllowReported'     => GetSettings::getOptions('toggle_report', false, true),
            'isDiscussionAllowed' => $this->isDiscussionAllowed($post),
            'isAdministrator'     => current_user_can('administrator') ? 'yes' : 'no',
            'myInfo'              => $this->getMyInfo(),
            'isUserLoggedIn'      => is_user_logged_in() ? 'yes' : 'no'
        ]);
    }

    public function fetchReviews()
    {
        $aArgs = $_GET;
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $aArgs['parentID'] = $aArgs['post_parent'] ?? $aArgs['parentID'];

        if (!isset($aArgs['parentID']) && !isset($aArgs['author'])) {
            $oRetrieve->success([
                'reviews'  => [],
                'total'    => 0,
                'maxPages' => 0,
                'msg'      => empty($aReviews) ? esc_html__('Be first to leave a review', 'wiloke-listing-tools') :
                    ''
            ]);
        }

        $isEventGroup = false;
        if (isset($aArgs['parentID'])) {
            $parentPostType = get_post_type($aArgs['parentID']);
            $isEventGroup = General::getPostTypeGroup($parentPostType) === 'event';
            $aArgs['post_type'] = $isEventGroup ? 'event_comment' : 'review';
        } else {
            $aArgs['post_type'] = 'review';
        }

        $aArgs['post_status'] = 'publish';
        $aArgs['orderby'] = 'menu_order post_date';
        $aArgs = QueryHelper::buildQueryArgs($aArgs);

        $aGeneralStatistic = [];
        if (!$isEventGroup) {
            $oPostSkeleton = new PostSkeleton();
            $aGeneralStatistic = $oPostSkeleton->getSkeleton(
                $_GET['parentID'],
                ['averageRating', 'reviewCategoriesStatistic', 'qualityRating', 'modeRating']
            );
        }

        $query = new WP_Query($aArgs);

        $oReviewSkeleton = new ReviewSkeleton();
        $aReviews = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $aReviews[] = $oReviewSkeleton->getSkeleton($query->post, $this->aReviewPluck);
            }
        }
        wp_reset_postdata();

        if (is_user_logged_in()) {
            $aArgs['post_author'] = get_current_user_id();
            $aArgs['post_status'] = 'draft';
            $oMyReview = new WP_Query($aArgs);
            if ($oMyReview->have_posts()) {
                while ($oMyReview->have_posts()) {
                    $oMyReview->the_post();
                    $aReview = $oReviewSkeleton->getSkeleton($oMyReview->post, $this->aReviewPluck);
                    $aReview['title'] = sprintf(esc_html__('%s (pending)', 'wiloke-listing-tools'), $aReview['title']);
                    array_unshift($aReviews, $aReview);
                }
            }
            wp_reset_postdata();
        }

        if ((!isset($_GET['alwaysReturnReview']) || $_GET['alwaysReturnReview'] === 'no') && empty($aReviews)) {
            $oRetrieve->error(
                array_merge(
                    [
                        'msg' => esc_html__('Be first to leave a review', 'wiloke-listing-tools')
                    ],
                    $aGeneralStatistic
                )
            );
        }

        $oRetrieve->success(array_merge([
            'reviews'  => $aReviews,
            'total'    => absint($query->found_posts),
            'maxPages' => absint($query->max_num_pages),
            'msg'      => empty($aReviews) ? esc_html__('Be first to leave a review', 'wiloke-listing-tools') :
                ''
        ], $aGeneralStatistic));
    }

    private function updateListingAverageReview($parentID)
    {
        $averageReview = ReviewMetaModel::getAverageReviews($parentID);
        SetSettings::setPostMeta($parentID, 'average_reviews', $averageReview);
    }

    // It's average rating of each review not listing
    private function setAverageRating($reviewID, $aDetailReviews)
    {
        $averageRating = round(array_sum($aDetailReviews) / count($aDetailReviews), 2);
        SetSettings::setPostMeta($reviewID, 'average_reviews', $averageRating);
    }

    private function flushCache($parentID)
    {
        SetSettings::deletePostMeta($parentID, 'average_review_categories');
        SetSettings::deletePostMeta($parentID, 'general_review_data');
    }

    public function setAverageRatingViaBackend()
    {
        if (!$this->checkAdminReferrer() || !$this->isAdminEditing() || !$this->isPostType('review')) {
            return false;
        }

        if (isset($_POST['wiloke_custom_field']['review_category'])) {
            $this->setAverageRating($_GET['post'], $_POST['wiloke_custom_field']['review_category']);
        }
    }

    public function flushReviewCacheInPostType($aData, $postType)
    {
        global $wpdb;
        $status = $wpdb->query(
            $wpdb->prepare(
                "DELETE postmeta FROM  $wpdb->postmeta postmeta INNER JOIN $wpdb->posts ON $wpdb->posts.ID = postmeta.post_id WHERE (postmeta.meta_key=%s OR postmeta.meta_key=%s) AND $wpdb->posts.post_type=%s",
                SetSettings::setPrefix('average_review_categories'),
                SetSettings::setPrefix('general_review_data'),
                $postType
            )
        );
    }

    public function flushAllCache($postID)
    {
        if (get_post_type($postID) !== 'review') {
            return false;
        }

        $this->flushCache(wp_get_post_parent_id($postID));
    }

    private function setReviewDiscussion($parentID, $content)
    {
        $discussionPostType = get_post_type($parentID) == 'event_comment' ? 'event_comment' : 'review';
        $commentID = ReviewModel::setDiscussion($parentID, $discussionPostType, $content);

        return $commentID;
    }

    public function afterDeleteReview($postID)
    {
        if (get_post_type($postID) != 'review') {
            return false;
        }
    }

    public function deleteReviews()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        if (!isset($_POST['reviewID']) || empty($_POST['reviewID'])) {
            $oRetrieve->error([
                'msg' => esc_html__('The review id is required.', 'wiloke-listingt-tools')
            ]);
        }

        $this->middleware(['isPostAuthor'], ['postID' => $_POST['reviewID'], 'passedIfAdmin' => true]);
        $parentID = wp_get_post_parent_id($_POST['reviewID']);
        wp_delete_post($_POST['reviewID'], true);
        $this->updateListingAverageReview($parentID);

        $oPostSkeleton = new PostSkeleton();
        $aGeneralStatistic = $oPostSkeleton->getSkeleton(
            $parentID,
            ['averageRating', 'reviewCategoriesStatistic', 'qualityRating', 'modeRating']
        );

        $oRetrieve->success(array_merge([
            'msg'      => esc_html__('Congratulations! The review has been deleted.', 'wiloke-listing-tools'),
            'reviewID' => absint($_POST['reviewID'])
        ], $aGeneralStatistic));
    }

    public function updateAverageReviews($postID, $oPostAfter, $oPostBefore)
    {
        if ($oPostAfter->post_type !== 'review') {
            return false;
        }

        $parentID = isset($_POST['post_parent']) && !empty($_POST['post_parent']) ? absint($_POST['post_parent']) :
            wp_get_post_parent_id($postID);

        $this->updateListingAverageReview($parentID);
        $this->flushCache($parentID);
    }

    public function fetchDiscussions()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        if (empty($_GET['parentID'])) {
            $oRetrieve->error(['msg' => esc_html__('The review ID is required', 'wiloke-listing-tools')]);
        }

        $postsPerPage = isset($_GET['postsPerPage']) ? absint($_GET['postsPerPage']) : WILCITY_NUMBER_OF_DISCUSSIONS;

        $query = ReviewModel::getReviews($_GET['parentID'], [
            'postsPerPage' => $postsPerPage > 20 ? 20 : $postsPerPage,
            'paged'        => absint($_GET['page']),
            'orderby'      => 'post_date',
            'order'        => 'ASC'
        ]);

        if (!$query) {
            $oRetrieve->error(['msg' => esc_html__('We found no discussions', 'wiloke-listing-tools')]);
        }

        $aDiscussions = [];

        $oReviewSkeleton = new ReviewSkeleton();

        while ($query->have_posts()) {
            $query->the_post();
            $aDiscussions[] = $oReviewSkeleton->getSkeleton($query->post, [
                'ID',
                'author',
                'content',
                'title',
                'isAuthor'
            ]);
        }

        $oRetrieve->success([
            'discussions' => $aDiscussions,
            'maxPages'    => absint($query->max_num_pages)
        ]);
    }

    private function handleChangeReviewStatus($newStatus)
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        $parentID = get_post_field('post_parent', $_POST['postID']);
        $aStatus = $this->middleware(['isUserLoggedIn', 'isPostAuthor'], [
            'postID'     => $parentID,
            'postAuthor' => get_current_user_id()
        ]);

        if ($aStatus['status'] === 'error') {
            $oRetrieve->error($aStatus);
        }

        wp_update_post([
            'ID'          => $_POST['postID'],
            'post_status' => $newStatus
        ]);

        $oRetrieve->success(['postStatus' => $newStatus]);
    }

    public function handlePublishReview()
    {
        $this->handleChangeReviewStatus('publish');
    }

    public function handleHideReview()
    {
        $this->handleChangeReviewStatus('pending');

    }

    private function getReview(?WP_Post $post): ?array
    {
        if (empty($post)) {
            return [];
        }

        $parentPostType = get_post_type($post->post_parent);
        $aReviewDetails = GetSettings::getOptions(General::getReviewKey('details', $parentPostType), false,
            true);
        $aDetails = ReviewMetaModel::getReviewDetailsScore($post->ID, $aReviewDetails, true);
        $averageReview = ReviewMetaModel::getAverageReviews($post->post_parent);

        return array_merge(
            [
                'postID'        => absint($post->ID),
                'title'         => $post->post_title,
                'featuredImage' => User::getAvatar($post->post_author),
                'parentTitle'   => get_the_title($post->post_parent),
                'permalink'     => get_permalink($post->post_parent),
                'quality'       => ReviewMetaModel::getReviewQualityString(
                    $averageReview,
                    $post->post_parent
                ),
                'content'       => $post->post_content,
                'postStatus'    => $post->post_status,
                'author'        => User::getField('display_name', $post->post_author)
            ],
            $aDetails
        );
    }

    /**
     * @throws \Exception
     */
    public function fetchReviewsStatistic()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        $aMiddleware = ['isUserLoggedIn'];
        $aOptions = [];
        if (isset($_GET['parentID'])) {
            $aMiddleware[] = 'isPostAuthor';
            $postParent = trim($_GET['parentID']);
            $aOptions['postID'] = $postParent;
            $aPostParentsIn = [$postParent];
        } else {
            $aMyPostIds = User::getAllMyPostIds();
            if (empty($aMyPostIds)) {
                $oRetrieve->error(['msg' => esc_html__('There is no review yet', 'wiloke-listing-tools')]);
            }
            $aPostParentsIn = $aMyPostIds;
        }

        $this->middleware($aMiddleware, $aOptions);

        $aArgs = QueryHelper::buildQueryArgs($_GET);
        $aArgs['post_type'] = 'review';
        $aArgs['not_post_mime_type'] = 'discussion';
        $aArgs['post_status'] = isset($_GET['postStatus']) ? sanitize_text_field($_GET['postStatus']) : 'any';
        $aArgs['post_parent__in'] = $aPostParentsIn;
        $query = new WP_Query($aArgs);
        $aData = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $aData[] = $this->getReview($query->post);
            }
        } else {
            $oRetrieve->error(['msg' => esc_html__('There is no review yet', 'wiloke-listing-tools')]);
        }
        wp_reset_postdata();

        $oRetrieve->success([
            'reviews'  => $aData,
            'maxPages' => absint($query->max_num_pages)
        ]);
    }

    public function fetchRatingLastWeek()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        $this->middleware(['isUserLoggedIn'], []);
        $userID = get_current_user_id();

        $aDateInThisWeek = Time::getAllDaysInThis();
        $aCountAverageRatingOfWeek = [];

        foreach ($aDateInThisWeek as $date) {
            $aCountAverageRatingOfWeek[] = ReviewModel::getAuthorAverageRatingsInDay($userID, $date);
        }

        //        $start = date(get_option('date_format'), strtotime($aDateInThisWeek['monday']));
        //        $end   = date(get_option('date_format'), strtotime(end($aDateInThisWeek)));

        $aCompareShares = ReviewModel::compare($userID);

        $oRetrieve->success([
            'data'    => $aCountAverageRatingOfWeek,
            'total'   => $aCompareShares['total'],
            'compare' => [
                'diff'           => $aCompareShares['diff'],
                'tooltip'        => esc_html__('Compare with the last week', 'wiloke-listing-tools'),
                'label'          => esc_html__('Average Rating Statistic', 'wiloke-listing-tools'),
                'status'         => $aCompareShares['status'],
                'representColor' => $aCompareShares['representColor']
            ]
        ]);
    }

    public function fetchGeneralRatings()
    {
        $this->middleware(['isUserLoggedIn'], []);
        $userID = get_current_user_id();
        $averageRating = ReviewModel::getAuthorAverageRatings($userID);
        $mondayThisWeek = Time::mysqlDate(strtotime('monday this week'));
        $sundayThisWeek = Time::mysqlDate(strtotime('sunday this week'));

        $mondayLastWeek = Time::mysqlDate(strtotime('monday last week'));
        $sundayLastWeek = Time::mysqlDate(strtotime('sunday last week'));

        $averageRatingThisWeek = ReviewModel::getAuthorAverageRatingsInRange($userID, $mondayThisWeek, $sundayThisWeek);
        $averageRatingLastWeek = ReviewModel::getAuthorAverageRatingsInRange($userID, $mondayLastWeek, $sundayLastWeek);

        $is = 'up';
        if ($averageRatingThisWeek == $averageRatingLastWeek) {
            $status = '';
            $percentage = 0;
        } else {
            $percentage = empty($averageRatingLastWeek) ? round($averageRatingThisWeek * 100, 2) :
                round(($averageRatingThisWeek / $averageRatingLastWeek) * 100, 2);

            if ($averageRatingThisWeek < $averageRatingLastWeek) {
                $percentage = -$percentage;
                $status = 'red';
                $is = 'down';
            } else {
                $status = 'green';
            }
        }

        wp_send_json_success(
            [
                'averageRating' => round($averageRating, 2),
                'mode'          => GetSettings::getOptions(General::getReviewKey('mode', 'listing'), false, true),
                'oChanging'     => [
                    'percentage' => $percentage . '%',
                    'status'     => $status,
                    'is'         => $is
                ]
            ]
        );
    }

    public function addMiddleWareToReviewHandler($aMiddleware)
    {
        return array_merge($aMiddleware, ['isPublishedPost']);
    }

    public static function isEnableGallery($postType)
    {
        return GetSettings::getOptions(General::getReviewKey('toggle_gallery', $postType), false, true);
    }

    public static function isEnableRating($post = null)
    {
        if (empty($post)) {
            global $post;
        }

        if (!isset($post->post_type)) {
            return false;
        }

        $toggle = GetSettings::getOptions(General::getReviewKey('toggle', $post->post_type), false, true);

        return apply_filters('wilcity/is_enable_rating', $toggle);
    }

    public function postComment()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        $aResponse = $this->middleware(['isPublishedPost'], [
            'postID' => $_POST['parentID']
        ]);

        if ($aResponse['status'] === 'error') {
            $oRetrieve->error($aResponse);
        }

        if (!isset($_POST['content']) || empty($_POST['content'])) {
            $oRetrieve->error([
                'msg' => esc_html__('We need your comment.', 'wiloke-listing-tools')
            ]);
        }

        if (!is_user_logged_in()) {
            if (get_option('comment_registration')) {
                $oRetrieve->error([
                    'msg' => esc_html__('You do not have permission to access this page', 'wiloke-listing-tools')
                ]);
            } else {
                if (!is_email($_POST['email'])) {
                    $oRetrieve->error([
                        'type' => 'email',
                        'msg'  => esc_html__('You entered an invalid email.', 'wiloke-listing-tools')
                    ]);
                }

                if (email_exists($_POST['email'])) {
                    $oRetrieve->error([
                        'type' => 'email',
                        'msg'  => esc_html__('This email is existed', 'wiloke-listing-tools')
                    ]);
                }

                if (username_exists($_POST['email'])) {
                    $oRetrieve->error([
                        'type' => 'email',
                        'msg'  => esc_html__('This email is existed', 'wiloke-listing-tools')
                    ]);
                }

                $userName = sanitize_text_field($_POST['email']);
                $password = uniqid();
                $userID = wp_insert_user([
                    'user_login'   => $userName,
                    'user_url'     => '',
                    'display_name' => sanitize_text_field($_POST['name']),
                    'user_pass'    => md5($password)
                ]);

                if ($userID && !is_wp_error($userID)) {
                    wp_set_current_user($userID, $userName);
                    wp_set_auth_cookie($userID);
                } else {
                    $oRetrieve->error([
                        'type' => 'email',
                        'msg'  => esc_html__('We could not insert a new account.', 'wiloke-listing-tools')
                    ]);
                }
            }
        }

        $postType = get_post_type($_POST['parentID']);
        $groupType = General::isPostTypeInGroup([$postType], 'event') ? 'event' : $postType;
        $aResponse = apply_filters('wilcity/ajax/post-comment/' . $groupType, [], $_POST);

        if ($aResponse['status'] === 'success') {
            $aResponse['msg'] = esc_html__(
                'Congrats, your comment has been posted successfully',
                'wiloke-listing-tools'
            );

            $aDiscussion = $this->getResponseAfterSubmitting($aResponse['commentID'], $_POST['parentID'], false);
            $oRetrieve->success(
                array_merge($aDiscussion, $aResponse)
            );
        }

        $oRetrieve->error($aResponse);
    }

    public function pinReviewToTop()
    {
        $this->middleware(['isPublishedPost', 'isPostAuthor', 'isReviewExists'], [
            'postType'      => 'listing',
            'reviewID'      => $_POST['reviewID'],
            'postID'        => $_POST['postID'],
            'passedIfAdmin' => true
        ]);

        $stickyID = GetSettings::getPostMeta($_POST['postID'], 'sticky_review');
        if (!empty($stickyID)) {
            PostModel::setMenuOrder($stickyID, 0);
        }

        if ($stickyID != $_POST['reviewID']) {
            PostModel::setMenuOrder($_POST['reviewID'], 100);
            SetSettings::setPostMeta($_POST['postID'], 'sticky_review', $_POST['reviewID']);
            $is = 'added';
        } else {
            SetSettings::deletePostMeta($_POST['postID'], 'sticky_review');
            PostModel::setMenuOrder($_POST['reviewID'], 0);
            $is = 'removed';
        }
        wp_send_json_success([
            'is' => $is
        ]);
    }

    public function fetchReviewGeneral()
    {
        if (!isset($_GET['postID']) || empty($_GET['postID'])) {
            wp_send_json_error();
        }

        $aData = ReviewMetaModel::getGeneralReviewData(get_post($_GET['postID']));
        wp_send_json_success($aData);
    }

    public static function getReviewInfo(
        $oReview, $parentID, $isFetchingDiscussion = false)
    {
        $aReview['ID'] = $oReview->ID;
        $aReview['avatar'] = User::getAvatar($oReview->post_author);
        $aReview['position'] = User::getPosition($oReview->post_author);
        $aReview['displayName'] = User::getField('display_name', $oReview->post_author);
        $parentPostType = get_post_type($parentID);

        $average = ReviewMetaModel::getAverageReviewsItem($oReview->ID);
        if (!empty($average)) {
            $aReview['oRating']['average'] = $average;
            $aReview['oRating']['mode'] = ReviewModel::getReviewMode($parentPostType);
            $aReview['oRating']['quality'] = ReviewMetaModel::getReviewQualityString($average, $parentPostType);
        } else {
            $aReview['oRating'] = false;
        }

        $aReview['postDate'] = Time::getPostDate($oReview->post_date);
        $aReview['postContent'] = nl2br(get_post_field('post_content', $oReview->ID));
        $aReview['gallery'] = self::parseGallery($oReview->ID);
        $aReview['postTitle'] = get_the_title($oReview->ID);
        $aReview['postLink'] = add_query_arg(
            [
                '#tab'     => 'reviews',
                'reviewID' => $oReview->ID
            ],
            get_permalink($parentID)
        );
        $aReview['authorLink'] = get_author_posts_url($oReview->post_author);
        $aReview['countLiked'] = ReviewMetaModel::countLiked($oReview->ID);
        $aReview['countDiscussion'] = ReviewMetaModel::countDiscussion($oReview->ID);
        $aReview['countShared'] = absint(GetSettings::getPostMeta($oReview->ID, 'count_shared'));
        $aReview['isLiked'] = ReviewModel::isLikedReview($oReview->ID, true);
        $aReview['isAuthor'] = 'no';
        $aReview['isAdmin'] = 'no';
        $aReview['parentID'] = $parentID;
        $aReview['isPintToTop'] = !empty($oReview->menu_order) ? 'yes' : 'no';

        if (User::isUserLoggedIn()) {
            $userID = get_current_user_id();
            if ($userID == $oReview->post_author) {
                $aReview['isAuthor'] = 'yes';
            }

            if (current_user_can('edit_theme_options')) {
                $aReview['isAdmin'] = 'yes';
            }

            if (get_current_user_id() == $parentID) {
                $aReview['isParentAuthor'] = 'yes';
            }
        }

        if (!$isFetchingDiscussion) {
            $aReview['isEnabledDiscussion'] = ReviewModel::isEnabledDiscussion(get_post_type($parentID)) ? 'yes' : 'no';
            $oRawDiscussions
                = ReviewModel::getReviews($oReview->ID, ['postsPerPage' => WILCITY_NUMBER_OF_DISCUSSIONS]);
            if (!$oRawDiscussions) {
                $aReview['aDiscussions'] = false;
            } else {
                $aReview['aDiscussions'] = [];
                $aReview['maxDiscussions'] = $oRawDiscussions->found_posts;
                while ($oRawDiscussions->have_posts()) {
                    $oRawDiscussions->the_post();
                    $aReview['aDiscussions'][] = self::getReviewInfo($oRawDiscussions->post, $oReview->ID, true);
                }
            }
        }

        return $aReview;
    }

    public function getUserInfo($userID)
    {
        $aUser['avatar'] = User::getAvatar($userID);
        $aUser['position'] = User::getPosition($userID);
        $aUser['displayName'] = User::getField('display_name', $userID);

        return $aUser;
    }

    public static function getMode(
        $postType)
    {
        $mode = GetSettings::getOptions(General::getReviewKey('mode', $postType), false, true);
        $mode = empty($mode) ? 5 : absint($mode);

        return $mode;
    }

    public static function getNewReviewStatus(
        $postType)
    {
        if (User::currentUserCan('administrator')) {
            return 'publish';
        }
        $isImmediatelyApproved = GetSettings::getOptions(General::getReviewKey('is_immediately_approved', $postType),
            false, true);
        $isImmediatelyApproved = !empty($isImmediatelyApproved) ? $isImmediatelyApproved : 'no';

        if ($isImmediatelyApproved == 'yes') {
            return 'publish';
        }

        return 'pending';
    }

    public static function getDetailsSettings(
        $postType)
    {
        return GetSettings::getOptions(General::getReviewKey('details', $postType), false, true);
    }

    public function printMyReview()
    {
        $aPostTypeKeys = General::getPostTypeKeys(false);

        if (!is_user_logged_in() || !is_singular($aPostTypeKeys)) {
            return false;
        }

        global $post;
        if (!isset($post->post_type)) {
            return false;
        }

        wp_localize_script('wilcity-empty', 'WILCITY_MY_REVIEW', $this->getReview(ReviewModel::getMyReview($post->ID)));
    }

    public function printReviewSettings()
    {
        $aPostTypeKeys = General::getPostTypeKeys(false);

        if (!is_user_logged_in() || !is_singular($aPostTypeKeys)) {
            return false;
        }

        global $post;
        if (!isset($post->post_type)) {
            return false;
        }

        $toggle = GetSettings::getOptions(General::getReviewKey('toggle', $post->post_type), false, true);
        if (empty($toggle) || $toggle == 'disable') {
            return false;
        }

        $aDetails = GetSettings::getOptions(General::getReviewKey('details', $post->post_type), false, true);
        $mode = GetSettings::getOptions(General::getReviewKey('mode', $post->post_type), false, true);

        wp_localize_script('wilcity-empty', strtoupper(WILCITY_WHITE_LABEL) . '_REVIEW_SETTINGS', [
            'mode'             => absint($mode),
            'details'          => $aDetails,
            'isGalleryAllowed' => GetSettings::getOptions(General::getReviewKey('toggle_gallery', $post->post_type),
                false, true)
        ]);
    }

    public function deleteReview()
    {
        $this->middleware(['isPostAuthor'], [
            'postID'        => $_POST['postID'],
            'passedIfAdmin' => true
        ]);

        wp_delete_post($_POST['postID']);
    }

    public static function isLikedReview($reviewID, $returnYesNoOnly = false)
    {
        return ReviewModel::isLikedReview($reviewID, $returnYesNoOnly);
    }

    public static function isEnabledDiscussion($postType)
    {
        return ReviewModel::isEnabledDiscussion($postType);
    }

    public static function isEnabledReview($postType)
    {
        return ReviewModel::isEnabledReview($postType);
    }

    public function likeReview()
    {
        $this->middleware(['isPublishedPost', 'isPostType'], [
            'postID'   => $_POST['reviewID'],
            'postType' => 'review'
        ]);

        $likedID = is_user_logged_in() ? get_current_user_id() : General::clientIP();
        $aLikedReview
            = GetSettings::getPostMeta($_POST['reviewID'], wilokeListingToolsRepository()->get('reviews:liked'));

        if (empty($aLikedReview)) {
            $aNewLikedReview = [$likedID];
        } else if (!in_array($likedID, $aLikedReview)) {
            $aNewLikedReview = array_push($aLikedReview, $likedID);
        } else {
            $aLikedReview = array_flip($aLikedReview);
            unset($aLikedReview[$likedID]);
            $aNewLikedReview = array_flip($aLikedReview);
        }

        SetSettings::setPostMeta($_POST['reviewID'], 'liked', $aNewLikedReview);

        wp_send_json_success(
            [
                'countLiked' => count($aNewLikedReview) . ' ' . esc_html__('Liked', 'wiloke-listing-tools')
            ]
        );
    }

    public function checkReviewStatus($aTabs, $post)
    {
        if (empty($aTabs)) {
            return $aTabs;
        }

        $status = GetSettings::getOptions(General::getReviewKey('toggle', $post->post_type), false, true);

        if ($status == 'disable' || !$status) {
            unset($aTabs['reviews']);
        }

        return $aTabs;
    }

    public function deleteDiscussion()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $aStatus = $this->middleware(['isPublishedPost', 'isUserLoggedIn', 'isPostAuthor'], [
            'postID'        => $_POST['postID'],
            'passedIfAdmin' => 'yes'
        ]);

        if ($aStatus['status'] === 'error') {
            return $oRetrieve->error($aStatus);
        }

        wp_delete_post($_POST['postID'], true);

        $oRetrieve->success([
            'msg' => 'Congrats, the discussion has been deleted successfully',
            'wiloke-listing-tools'
        ]);
    }

    private function updateReviewDiscussion(
        $discussionID, $content)
    {
        $status = wp_update_post([
            'ID'           => $discussionID,
            'post_content' => $content
        ]);

        return !empty($status);
    }

    public function appSetReviewDiscussion(
        $reviewID, $content)
    {
        return $this->setReviewDiscussion($reviewID, $content);
    }

    public function appUpdateReviewDiscussion(
        $discussionID, $content)
    {
        return $this->updateReviewDiscussion($discussionID, $content);
    }

    public function updateDiscussion()
    {
        $this->middleware(['isPostAuthor'], [
            'postID'        => $_POST['discussionID'],
            'passedIfAdmin' => true
        ]);

        $this->updateReviewDiscussion($_POST['discussionID'], $_POST['content']);
        wp_send_json_success();
    }

    private function getDiscussionInfo($reviewID)
    {
        $parentID = wp_get_post_parent_id($reviewID);
        $oDiscussion = get_post($reviewID);
        $aDiscussionInfo = self::getReviewInfo($oDiscussion, $parentID, true);
        do_action('wilcity/review/discussion', $parentID, $parentID);

        return $aDiscussionInfo;
    }

    public function ajaxBeforeSetReviewDiscussion()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $discussionID = isset($_POST['postID']) && !empty($_POST['postID']) ? absint($_POST['postID']) : 0;

        $aMiddleware = ['isReviewExists', 'isUserLoggedIn'];
        $postType = get_post_type($_POST['parentID']);

        if (!$postType) {
            $oRetrieve->error(['msg' => esc_html__('The review ID is required', 'wiloke-listing-tools')]);
        }

        $aMiddleware = apply_filters('wilcity/addMiddlewareToReview/of/' . $postType, $aMiddleware);
        $aMiddlewareOptions = apply_filters('wilcity/addMiddlewareOptionsToReview/of/' . $postType, [
            'reviewID' => $_POST['parentID']
        ]);

        if (!empty($discussionID)) {
            $aMiddleware[] = 'isPostAuthor';
            $aMiddleware['postID'] = $discussionID;
        }

        $aStatus = $this->middleware($aMiddleware, $aMiddlewareOptions);
        if ($aStatus['status'] === 'error') {
            $oRetrieve->error($aStatus);
        }

        if (empty($discussionID)) {
            $discussionID = $this->setReviewDiscussion($_POST['parentID'], $_POST['content']);
        } else {
            $this->updateReviewDiscussion($discussionID, $_POST['content']);
        }

        do_action('wilcity/review/discussion', $discussionID, $_POST['parentID']);

        $oRetrieve->success(['ID' => absint($discussionID)]);
    }

    private function handleUpdateLike($reviewID, $userID, $isApp = false)
    {
        $aLiked = GetSettings::getPostMeta($reviewID, wilokeListingToolsRepository()->get('reviews:liked'));
        if (empty($aLiked) || !in_array($userID, $aLiked)) {
            if (empty($aLiked)) {
                $aLiked = [$userID];
            } else {
                array_push($aLiked, $userID);
            }
            $isLiked = true;
        } else {
            $key = array_search($userID, $aLiked);
            unset($aLiked[$key]);
            $isLiked = false;
        }
        $countLiked = count($aLiked);

        SetSettings::setPostMeta($reviewID, wilokeListingToolsRepository()->get('reviews:liked'), $aLiked);
        SetSettings::setPostMeta($reviewID, 'total_liked', count($aLiked));

        if (!$isApp) {
            wp_send_json_success(
                [
                    'numberOfLiked' => $countLiked
                ]
            );
        }

        if ($isApp) {
            $isLiked = $isLiked ? 'yes' : 'no';
        }

        return [
            'status'     => 'success',
            'isLiked'    => $isLiked,
            'countLiked' => $countLiked
        ];
    }

    public function updateLikeReviewViaApp($reviewID, $userID)
    {
        return $this->handleUpdateLike($reviewID, $userID, true);
    }

    public function updateLike()
    {
        $this->middleware(['isReviewExists'], [
            'reviewID' => $_POST['reviewID']
        ]);

        $userID = is_user_logged_in() ? User::getCurrentUserID() : General::clientIP();
        $this->handleUpdateLike($_POST['reviewID'], $userID);
    }

    public static function getReviewDetails(
        $reviewID, $isEditing = false)
    {
        $parentID = wp_get_post_parent_id($reviewID);
        $aCategoriesSettings = GetSettings::getOptions(General::getReviewKey('details', get_post_type($parentID)),
            false, true);

        if (empty($aCategoriesSettings)) {
            return false;
        }

        $aDetails = [];
        if ($isEditing) {
            foreach ($aCategoriesSettings as $aCategorySetting) {
                $aDetails[$aCategorySetting['key']]['name'] = $aCategorySetting['name'];
                $aDetails[$aCategorySetting['key']]['value']
                    = ReviewMetaModel::getReviewMeta($reviewID, $aCategorySetting['key']);
            }
        } else {
            foreach ($aCategoriesSettings as $aCategorySetting) {
                $aDetails[$aCategorySetting['key']]
                    = ReviewMetaModel::getReviewMeta($reviewID, $aCategorySetting['key']);
            }
        }

        return $aDetails;
    }

    public static function parseGallery(
        $postID)
    {
        $aGallery = GetSettings::getPostMeta($postID, 'gallery');
        $aParsedGallery = [];

        if (!empty($aGallery)) {
            foreach ($aGallery as $galleryID => $source) {
                $aSetupGallery['medium'] = wp_get_attachment_image_url($galleryID, 'medium');
                $aSetupGallery['src'] = $source;
                $aSetupGallery['link'] = $source;
                $aSetupGallery['full'] = $source;
                $aParsedGallery[] = $aSetupGallery;
            }
        }

        return $aParsedGallery;
    }

    public static function fetchSomeReviews(
        $aArgs)
    {
        global $post;

        return ReviewModel::getReviews($post->ID, $aArgs);
    }

    public function fetchUserReviewedData()
    {
        $reviewID = $_POST['reviewID'];

        $this->middleware(['isPostAuthor'], [
            'postID'        => $reviewID,
            'passedIfAdmin' => true
        ]);

        $oReview = get_post($reviewID);
        if (empty($oReview) || is_wp_error($oReview)) {
            wp_send_json_error([
                'msg' => esc_html__('You do not permission to access this review', 'wiloke-listing-tools')
            ]);
        }

        $aReview['title'] = $oReview->post_title;
        $aReview['content'] = $oReview->post_content;
        $aReview['details'] = self::getReviewDetails($reviewID, true);

        $aGallery = GetSettings::getPostMeta($reviewID, 'gallery');
        if (empty($aGallery)) {
            $aReview['gallery'] = '';
        } else {
            foreach ($aGallery as $imgID => $src) {
                $aX['imgID'] = $imgID;
                $aX['src'] = $src;
                $aReview['gallery'][] = $aX;
            }
        }

        wp_send_json_success($aReview);
    }

    public function handleAppSubmitReview($listingID, $aData, $reviewID = '', $isApp = false)
    {
        $aStatus = $this->middleware(
            [
                'isUserLoggedIn',
                'isPublishedPost',
                'verifyReview',
                'isReviewEnabled',
                'isAccountConfirmed'
            ],
            [
                'postID' => $listingID,
                'aData'  => $aData,
                'isApp'  => $isApp
            ]
        );

        if ($isApp && $aStatus['status'] === 'error') {
            return $aStatus;
        }

        $parentID = absint($listingID);
        $postType = get_post_type($parentID);
        $isNewSubmitted = false;

        if (isset($reviewID) && !empty($reviewID)) {
            $aStatus = $this->middleware(['isPostAuthor'], [
                'postID' => $reviewID,
                'isApp'  => $isApp
            ]);

            if ($isApp && $aStatus['status'] === 'error') {
                return $aStatus;
            }

            wp_update_post(
                [
                    'ID'           => $reviewID,
                    'post_type'    => 'review',
                    'post_title'   => $aData['title'],
                    'post_content' => $aData['content']
                ]
            );
        } else {
            $isNewSubmitted = true;
            //            if (ReviewModel::isUserReviewed($parentID)) {
            //                return [
            //                    'status' => 'error',
            //                    'msg'    => 'youLeftAReviewBefore'
            //                ];
            //            }

            $reviewID = wp_insert_post(
                [
                    'post_type'    => 'review',
                    'post_title'   => $aData['title'],
                    'post_content' => $aData['content'],
                    'post_author'  => User::getCurrentUserID(),
                    'post_parent'  => $parentID,
                    'post_status'  => self::getNewReviewStatus($postType)
                ]
            );
        }

        if (!$reviewID) {
            return [
                'status' => 'error',
                'msg'    => 'couldNotInsertReview'
            ];
        }

        $this->aReviewSettings['toggle_gallery'] = GetSettings::getOptions(
            General::getReviewKey('toggle_gallery', $postType), false, true
        );

        $isNothingChange = true;
        $aOldGalleryIDs = [];

        if ($this->aReviewSettings['toggle_gallery'] == 'enable') {
            $userID = User::getCurrentUserID();
            $aRawGallery = isset($aData['gallery']) ? $aData['gallery'] : '';
            $isFakeFile = isset($aData['isFakeGallery']) ? $aData['isFakeGallery'] : false;
            $aOldGallery = GetSettings::getPostMeta($reviewID, 'gallery');
            if (!empty($aOldGallery)) {
                $aOldGalleryIDs = array_keys($aOldGallery);
            } else {
                $isNothingChange = false;
            }

            if (empty($aRawGallery)) {
                SetSettings::deletePostMeta($reviewID, 'gallery');
            } else {
                $aNewGalleryIDs = [];
                $aGallery = [];
                $aRawGallery
                    = !is_array($aRawGallery) && isJson($aRawGallery) ? json_decode($aRawGallery, true) : $aRawGallery;

                foreach ($aRawGallery as $order => $aItem) {
                    if (isset($aItem['imgID']) && !empty($aItem['imgID'])) {
                        $aGallery[$aItem['imgID']] = $aItem['src'];
                        $aNewGalleryIDs[] = absint($aItem['imgID']);
                    } else if (isset($aItem['id']) && !empty($aItem['id'])) {
                        $aGallery[$aItem['id']] = $aItem['src'];
                        $aNewGalleryIDs[] = absint($aItem['id']);
                    } else {
                        $instUploadImg = new Upload();
                        $instUploadImg->userID = $userID;

                        if (!$isFakeFile) {
                            $instUploadImg->aData['imageData'] = $aItem['src'];
                            $instUploadImg->aData['fileName'] = $aItem['fileName'];
                            $instUploadImg->aData['fileType'] = $aItem['fileType'];
                            $instUploadImg->aData['uploadTo'] = $instUploadImg::getUserUploadFolder();
                            $imgID = $instUploadImg->image($reviewID);
                        } else {
                            $instUploadImg->aData['aFile'] = $aItem;
                            $imgID = $instUploadImg->uploadFakeFile();
                        }

                        if (!empty($imgID) && is_numeric($imgID)) {
                            $aGallery[$imgID] = wp_get_attachment_image_url($imgID, 'large');
                            $aNewGalleryIDs[] = $imgID;
                            $isNothingChange = false;
                        }
                    }
                }

                if ($isNothingChange) {
                    $aDifferent = array_diff($aNewGalleryIDs, $aOldGalleryIDs);

                    if (empty($aDifferent) && (count($aNewGalleryIDs) == count($aOldGalleryIDs))) {
                        $isNothingChange = true;
                    } else {
                        $isNothingChange = false;
                    }
                }

                if (!$isNothingChange) {
                    if (!empty($aOldGalleryIDs)) {
                        foreach ($aOldGalleryIDs as $oldID) {
                            if (!in_array($oldID, $aNewGalleryIDs)) {
                                Upload::deleteImg($oldID);
                            }
                        }
                    }
                    if (!empty($aGallery)) {
                        SetSettings::setPostMeta($reviewID, 'gallery', $aGallery);
                    }
                }
            }
        }

        $this->aReviewSettings['details'] = self::getDetailsSettings($postType);
        $this->aReviewSettings['mode'] = self::getMode($postType);

        $aScores = [];
        if (!empty($this->aReviewSettings['details'])) {
            foreach ($this->aReviewSettings['details'] as $aDetail) {
                $score = isset($aData['details'][$aDetail['key']]['value']) ?
                    absint($aData['details'][$aDetail['key']]['value']) : 5;
                if (empty($score)) {
                    continue;
                }
                if ($score > $this->aReviewSettings['mode']) {
                    $score = $this->aReviewSettings['mode'];
                }
                $aScores[] = $score;
                ReviewMetaModel::setReviewMeta($reviewID, $aDetail['key'], $score);
            }
        }

        $aResponse['reviewID'] = $reviewID;
        $aResponse['isNewSubmitted'] = $isNewSubmitted;
        if (!$isNewSubmitted) {
            $aResponse['averageReviewScore'] = ReviewMetaModel::getAverageReviews($parentID);
            $aResponse['reviewQuality'] = ReviewMetaModel::getReviewQualityString(
                $aResponse['averageReviewScore'], $postType
            );

            if (!$isNothingChange) {
                $aRawGallery = GetSettings::getPostMeta($reviewID, 'gallery');
                $aNewGallery = [];
                if (!empty($aRawGallery)) {
                    foreach ($aRawGallery as $galleryID => $src) {
                        $aX['imgID'] = $galleryID;
                        $aX['src'] = $src;
                        $aNewGallery[] = $aX;
                    }
                }
                $aResponse['gallery'] = $aNewGallery;
            }
        }

        $averageReview = ReviewMetaModel::getAverageReviews($parentID);
        SetSettings::setPostMeta($parentID, 'average_reviews', $averageReview);

        do_action('wilcity/submitted-new-review', $reviewID, $parentID, User::getCurrentUserID());
        $this->setAverageRating($reviewID, $aScores);
        $this->flushCache($parentID);

        return [
            'status'       => 'success',
            'reviewID'     => $reviewID,
            'reviewStatus' => get_post_status($reviewID)
        ];
    }

    private function getResponseAfterSubmitting($reviewID, $parentID, $isUpdated)
    {
        $oPostSkeleton = new PostSkeleton();
        $aGeneralStatistic = [];

        if (get_post_type($reviewID) !== 'event_comment') {
            $aGeneralStatistic = $oPostSkeleton->getSkeleton(
                $parentID,
                ['averageRating', 'reviewCategoriesStatistic', 'qualityRating', 'modeRating']
            );
        }
        $oReviewSkeleton = new ReviewSkeleton();
        $aReview = $isUpdated ? $oReviewSkeleton->getSkeleton($reviewID, [
            'details',
            'average',
            'quality',
            'ID'
        ]) : $oReviewSkeleton->getSkeleton($reviewID, $this->aReviewPluck);

        return array_merge(['review' => $aReview], $aGeneralStatistic);
    }

    /**
     * Only administrator can review more than 1 review
     *
     * @return float|int|string|null
     */
    private function findUserReviewID($parentID)
    {
        return !isset($_POST['reviewID']) || empty($_POST['reviewID']) ? ReviewModel::getReviewID($parentID) :
            absint($_POST['reviewID']);
    }

    public function handleWebSubmitReview()
    {
        $parentID = $_POST['postID'];
        $reviewID = $this->findUserReviewID($parentID);

        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        if (empty($_POST['data']) || !Validation::isValidJson($_POST['data'])) {
            $oRetrieve->error([
                'msg' => esc_html__('You have to filled up all required information', 'wiloke-listing-tools')
            ]);
        }

        $aReviewData = apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Controllers/ReviewController/handleWebSubmitReview/review-data',
            Validation::getJsonDecoded()
        );


        $this->middleware(
            apply_filters(
                'wilcity/filter/wiloke-listing-tools/app/Controllers/ReviewController/handleWebSubmitReview/middleware',
                [
                    'isUserLoggedIn',
                    'isPublishedPost',
                    'verifyReview',
                    'isReviewEnabled',
                    'isAccountConfirmed'
                ]
            ),
            apply_filters(
                'wilcity/filter/wiloke-listing-tools/app/Controllers/ReviewController/handleWebSubmitReview/middleware-options',
                [
                    'postID' => $parentID,
                    'userID' => get_current_user_id(),
                    'aData'  => $aReviewData,
                    'isAjax' => true
                ]
            )
        );

        $postType = get_post_type($parentID);
        $content = wp_kses_post($aReviewData['content']);
        $aReviewData = Validation::deepValidation($aReviewData, 'wp_kses_post');
        $aReviewData['content'] = $content;

        $isUpdated = false;
        if (!empty($reviewID)) {
            $isUpdated = true;
            $this->middleware(['isPostAuthor'], [
                'postID' => $reviewID
            ]);
            wp_update_post(
                [
                    'ID'           => $reviewID,
                    'post_type'    => 'review',
                    'post_title'   => $aReviewData['title'],
                    'post_content' => $aReviewData['content']
                ]
            );
        } else {
            if (ReviewModel::isUserReviewed($parentID)) {
                $oRetrieve->error(
                    [
                        'msg' => esc_html__('You already left a review before.', 'wiloke-listing-tools')
                    ]
                );
            }

            $reviewID = wp_insert_post(
                [
                    'post_type'    => 'review',
                    'post_title'   => $aReviewData['title'],
                    'post_content' => $aReviewData['content'],
                    'post_author'  => User::getCurrentUserID(),
                    'post_parent'  => $parentID,
                    'post_status'  => self::getNewReviewStatus($postType)
                ]
            );
        }

        if (!$reviewID) {
            $oRetrieve->error(
                [
                    'msg' => esc_html__('Oops! We could not insert the review', 'wiloke-listing-tools')
                ]
            );
        }

        $isGalleryAllowed = GetSettings::getOptions(General::getReviewKey('toggle_gallery', $postType), false, true) ===
            'enable';
        if ($isGalleryAllowed) {
            if (isset($aReviewData['gallery']) && !empty($aReviewData['gallery'])) {
                $aGallery = Submission::convertGalleryToBackendFormat($aReviewData['gallery']);
                SetSettings::setPostMeta($reviewID, 'gallery', $aGallery);
            } else {
                SetSettings::deletePostMeta($reviewID, 'gallery');
            }
        }

        $this->aReviewSettings['details'] = self::getDetailsSettings($postType);
        $this->aReviewSettings['mode'] = self::getMode($postType);

        $aScores = [];
        if (!empty($this->aReviewSettings['details'])) {
            foreach ($this->aReviewSettings['details'] as $aDetail) {
                if (!isset($aReviewData['details'][$aDetail['key']])) {
                    continue;
                }

                $score = $aReviewData['details'][$aDetail['key']];
                if ($score > $this->aReviewSettings['mode']) {
                    $score = $this->aReviewSettings['mode'];
                }
                $aScores[] = $score;
                ReviewMetaModel::setReviewMeta($reviewID, $aDetail['key'], $score);
            }
        }

        $averageReview = ReviewMetaModel::getAverageReviews($parentID);
        SetSettings::setPostMeta($parentID, 'average_reviews', $averageReview);
        $this->setAverageRating($reviewID, $aScores);

        if (get_post_status($reviewID) !== 'publish') {
            $msg = esc_html__(
                'Your comment has been received and is being reviewed by our team staff. It will be published after approval.',
                'wiloke-listing-tools'
            );
        } else {
            if ($isUpdated) {
                $msg = esc_html__('Congrats, Your review has been updated', 'wiloke-listing-tools');
            } else {
                $msg = esc_html__('Congrats, Your review has been published', 'wiloke-listing-tools');
            }
        }

        do_action('wilcity/submitted-new-review', $reviewID, $parentID, User::getCurrentUserID());

        /**
         * There is a different from details review popup format and review display format, so We will have to
         * re-update it
         */
        $this->flushCache($parentID);

        $oRetrieve->success(array_merge([
            'msg' => $msg
        ], $this->getResponseAfterSubmitting($reviewID, $parentID, $isUpdated)));
    }

    public static function isSticky(
        $oReview)
    {
        if (self::$foundSticky && $oReview->ID !== self::$stickyID) {
            return false;
        }

        if (!empty($oReview->menu_order)) {
            self::$foundSticky = true;
            self::$stickyID = $oReview->ID;

            return true;
        }
    }

    public static function toTenMode(
        $score, $mode)
    {
        if ($mode == 10) {
            return $score;
        }

        return floor($score) * 2;
    }
}
