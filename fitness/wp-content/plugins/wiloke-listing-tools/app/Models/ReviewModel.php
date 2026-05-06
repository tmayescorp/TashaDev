<?php

namespace WilokeListingTools\Models;

use WilokeListingTools\AlterTable\AlterTableReviewMeta;
use WilokeListingTools\AlterTable\AlterTableReviews;
use WilokeListingTools\Framework\Helpers\DebugStatus;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\ReviewSkeleton;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Frontend\User;
use WP_Post;

class ReviewModel
{
    public static    $postType        = 'review';
    public static    $aPostsOfUsers   = [];
    protected static $countAllReviews = null;
    private static   $aCache;

    public static function getListingAverageCategories($parentID)
    {
        return ReviewMetaModel::getAverageCategoriesReview($parentID);
    }

    public static function getListingQuality($average, $postType)
    {
        return ReviewMetaModel::getReviewQualityString($average, $postType);
    }

    public static function getListingAverageReviews($parentID, $post_status = 'publish')
    {
        global $wpdb;
        $postTbl = $wpdb->posts;
        $reviewMetaTbl = $wpdb->prefix . AlterTableReviewMeta::$tblName;

        $score = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT AVG ($reviewMetaTbl.meta_value) FROM $reviewMetaTbl LEFT JOIN $postTbl ON ($postTbl.ID = $reviewMetaTbl.reviewID) WHERE $postTbl.post_parent=%d AND $postTbl.post_type=%s AND $postTbl.post_status=%s AND $reviewMetaTbl.meta_value IS NOT NULL",
                $parentID, 'review', $post_status
            )
        );

        return !$score ? 0 : round($score, 1);
    }

    public static function compareRatingByWeek($authorID, $postID)
    {
        $mondayThisWeek = Time::mysqlDate(strtotime('monday this week'));
        $sundayThisWeek = Time::mysqlDate(strtotime('sunday this week'));

        $mondayLastWeek = Time::mysqlDate(strtotime('monday last week'));
        $sundayLastWeek = Time::mysqlDate(strtotime('sunday last week'));

        $averageRatingThisWeek = self::getAuthorAverageRatingsInRange($authorID, $mondayThisWeek,
            $sundayThisWeek, $postID);
        $averageRatingLastWeek = self::getAuthorAverageRatingsInRange($authorID, $mondayLastWeek,
            $sundayLastWeek, $postID);

        return [
            'current' => $averageRatingThisWeek,
            'past'    => $averageRatingLastWeek
        ];
    }

    private static function getCache($key, $isFocus)
    {
        if ($isFocus) {
            return null;
        }

        return isset(self::$aCache[$key]) ? self::$aCache[$key] : null;
    }

    private static function setCache($key, $val)
    {
        self::$aCache[$key] = $val;
    }

    public static function compare($authorID, $postID = null, $compareBy = 'week')
    {
        $averageRating = self::getAuthorAverageRatings($authorID);

        switch ($compareBy) {
            case 'week':
                $aStatistic = self::compareRatingByWeek($authorID, $postID);
                break;
        }
        $changing = $aStatistic['current'] - $aStatistic['past'];

        $status = 'up';
        $representColor = '';
        if ($changing === 0) {
            $status = '';
            $percentage = 0;
        } else {
            $percentage = empty($aStatistic['past']) ? round($aStatistic['current'] * 100, 2) :
                round(($aStatistic['current'] / $aStatistic['past']) * 100, 2);

            if ($aStatistic['current'] < $aStatistic['past']) {
                $percentage = -$percentage;
                $representColor = 'red';
                $status = 'down';
            } else {
                $representColor = 'green';
            }
        }

        return [
            'total'          => $averageRating,
            'totalCurrent'   => $aStatistic['current'], // EG: Total views on this week
            'diff'           => $percentage,
            'representColor' => $representColor,
            'status'         => $status
        ];
    }

    public static function getPostsOfUser($userID)
    {
        if (isset(self::$aPostsOfUsers[$userID])) {
            return self::$aPostsOfUsers[$userID];
        }

        global $wpdb;
        $postsTbl = $wpdb->posts;
        $aPostTypes = General::getPostTypeKeys(false, false);

        $aPostTypes = array_map(
            function ($type) {
                global $wpdb;

                return $wpdb->_real_escape($type);
            },
            $aPostTypes
        );

        $aRawPostParentIDs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID FROM $postsTbl WHERE $postsTbl.post_status=%s AND $postsTbl.post_type IN ('" .
                implode("','", $aPostTypes) . "') AND $postsTbl.post_author=%d",
                'publish', $userID
            ),
            ARRAY_A
        );

        if (empty($aRawPostParentIDs)) {
            self::$aPostsOfUsers[$userID] = false;

            return false;
        }

        $aPostParentIDs = array_map(function ($aPost) {
            return $aPost['ID'];
        }, $aRawPostParentIDs);

        self::$aPostsOfUsers[$userID] = $aPostParentIDs;

        return $aPostParentIDs;
    }

    public static function countAllReviewed()
    {
        if (self::$countAllReviews !== null) {
            return self::$countAllReviews;
        }

        global $wpdb;
        $postsTbl = $wpdb->posts;
        self::$countAllReviews = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT($postsTbl.ID) FROM $postsTbl WHERE $postsTbl.post_type=%s AND $postsTbl.post_status='publish'",
                'review'
            )
        );

        return absint(self::$countAllReviews);
    }

    public static function countAllReviewedOfListing($postID)
    {
        global $wpdb;
        $postsTbl = $wpdb->posts;
        $value = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT($postsTbl.ID) FROM $postsTbl WHERE $postsTbl.post_type=%s AND $postsTbl.post_parent=%d AND $postsTbl.post_status='publish'",
                'review', $postID
            )
        );

        return absint($value);
    }

    public static function getAuthorAverageRatings($userID)
    {
        global $wpdb;
        $postsTbl = $wpdb->posts;
        $postMetaTbl = $wpdb->postmeta;

        $value = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT AVG($postMetaTbl.meta_value) FROM $postMetaTbl LEFT JOIN $postsTbl ON ($postMetaTbl.post_id = $postsTbl.ID) WHERE $postsTbl.post_author=%d AND $postMetaTbl.meta_key=%s",
                $userID, 'wilcity_average_reviews'
            )
        );

        return absint($value);
    }

    public static function getAuthorAverageRatingsInRange($userID, $start, $end, $postID = null)
    {
        global $wpdb;
        $postsTbl = $wpdb->posts;
        $reviewMeta = $wpdb->prefix . AlterTableReviewMeta::$tblName;

        $aPostParentIDs = self::getPostsOfUser($userID);
        if (empty($aPostParentIDs)) {
            return 0;
        }

        $value = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT AVG($reviewMeta.meta_value) FROM $reviewMeta LEFT JOIN $postsTbl ON ($reviewMeta.reviewID = $postsTbl.ID) WHERE $postsTbl.post_parent IN (" .
                implode(',', $aPostParentIDs) .
                ") AND $postsTbl.post_status=%s AND $postsTbl.post_type=%s AND $reviewMeta.date BETWEEN %s AND %s",
                'publish', 'review', $start, $end
            )
        );

        return absint($value);
    }

    public static function getAuthorAverageRatingsInDay($userID, $day, $postID = null)
    {
        global $wpdb;
        $postsTbl = $wpdb->posts;
        $reviewMeta = $wpdb->prefix . AlterTableReviewMeta::$tblName;

        $aPostParentIDs = self::getPostsOfUser($userID);
        if (empty($aPostParentIDs)) {
            return 0;
        }

        $value = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT AVG($reviewMeta.meta_value) FROM $reviewMeta LEFT JOIN $postsTbl ON ($reviewMeta.reviewID = $postsTbl.ID) WHERE $postsTbl.post_parent IN (" .
                implode(',', $aPostParentIDs) .
                ") AND $postsTbl.post_status=%s AND $postsTbl.post_type=%s AND $reviewMeta.date=%s",
                'publish', 'review', $day
            )
        );

        return absint($value);
    }

    public static function userWroteReviewBefore($postID, $userID)
    {
        global $wpdb;
        $reviewTbl = $wpdb->prefix . AlterTableReviews::$tblName;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM $reviewTbl WHERE objectID=%d AND userID=%d",
                $postID, $userID
            )
        );
    }

    public static function isReviewExist($reviewID)
    {
        global $wpdb;
        $reviewTbl = $wpdb->prefix . AlterTableReviews::$tblName;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM $reviewTbl WHERE ID=%d",
                $reviewID
            )
        );
    }

    public static function patchReview($id, $postID, $userID, $title, $content, $parentID = null)
    {
        global $wpdb;
        $reviewTbl = $wpdb->prefix . AlterTableReviews::$tblName;

        return $wpdb->update(
            $reviewTbl,
            [
                'objectID' => $postID,
                'userID'   => $userID,
                'title'    => $title,
                'content'  => $content,
                'parentID' => $parentID
            ],
            [
                'ID' => $id
            ],
            [
                '%d',
                '%d',
                '%s',
                '%s',
                '%d'
            ],
            [
                '%d'
            ]
        );
    }

    /*
     * @param int $postID
     * @param int $userID
     * @param longtext $content
     * @param int $parentID
     */
    public static function setReview($postID, $userID, $title, $content, $parentID = null)
    {
        global $wpdb;
        $reviewTbl = $wpdb->prefix . AlterTableReviews::$tblName;
        $reviewID = self::userWroteReviewBefore($postID, $userID);
        if ($reviewID) {
            self::patchReview($reviewID, $postID, $userID, $title, $content, $parentID = null);

            return $reviewID;
        } else {
            $status = $wpdb->insert(
                $reviewTbl,
                [
                    'objectID' => $postID,
                    'userID'   => $userID,
                    'title'    => $title,
                    'content'  => $content,
                    'parentID' => $parentID
                ],
                [
                    '%d',
                    '%d',
                    '%s',
                    '%s',
                    '%d'
                ]
            );

            return $status ? $wpdb->insert_id : false;
        }
    }

    public static function getReviewID($postID, $userID = null)
    {
        global $wpdb;

        $userID = empty($userID) ? get_current_user_id() : $userID;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM $wpdb->posts WHERE  post_parent=%d and post_author=%d AND post_type='review'",
                $postID, $userID
            )
        );
    }

    public static function getReviewMode($postType)
    {
        $mode = GetSettings::getOptions(General::getReviewKey('mode', $postType), false, true);

        return empty($mode) ? 5 : absint($mode);
    }

    public static function getReview($postID, $userID = null)
    {
        global $wpdb;
        $reviewTbl = $wpdb->prefix . AlterTableReviews::$tblName;
        $userID = empty($userID) ? get_current_user_id() : $userID;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $reviewTbl WHERE objectID=%d AND userID=%d AND  parentID IS NULL ORDER BY ID DESC",
                $postID, $userID
            ),
            ARRAY_A
        );
    }

    public static function getMyReview($postParentID, $userId = null): ?WP_Post
    {
        $userId = empty($userId) ? get_current_user_id() : $userId;
        $query = new \WP_Query(
            [
                'post_type'      => 'review',
                'post_parent'    => $postParentID,
                'posts_per_page' => 1,
                'post_status'    => ['publish', 'pending'],
                'author'         => $userId
            ]
        );


        if (!$query->have_posts()) {
            return null;
        }

        $review = null;
        while ($query->have_posts()) {
            $query->the_post();
            $review = $query->post;
        }
        wp_reset_postdata();

        return $review;
    }

    public static function getReviewItem($parentID, $userId)
    {
        $query = new \WP_Query(
            [
                'post_type'      => 'review',
                'post_parent'    => $parentID,
                'posts_per_page' => 1,
                'post_status'    => ['publish', 'pending'],
                'author'         => $userId
            ]
        );

        if (!$query->have_posts()) {
            return false;
        }

        $aReview = [];
        $oReviewSkeleton = new ReviewSkeleton();

        while ($query->have_posts()) {
            $query->the_post();
            $aReview = $oReviewSkeleton->getSkeleton($query->post, [
                'ID',
                'title',
                'content',
                'details',
                'gallery'
            ]);
        }
        wp_reset_postdata();

        return $aReview;
    }

    public static function getMyReviews($aArgs)
    {
        $aArgs = wp_parse_args(
            $aArgs,
            [
                'post_type'      => 'review',
                'posts_per_page' => 1,
                'post_status'    => ['publish', 'pending']
            ]
        );

        $query = new \WP_Query($aArgs);

        if (!$query->have_posts()) {
            return false;
        }

        $aReview = [];
        $oReviewSkeleton = new ReviewSkeleton();

        while ($query->have_posts()) {
            $query->the_post();
            $aReview = $oReviewSkeleton->getSkeleton($query->post, [
                'ID',
                'title',
                'content',
                'details',
                'gallery'
            ]);
        }
        wp_reset_postdata();

        return $aReview;
    }

    /**
     * @param null $postID
     * @param string $postType
     *
     * @return bool
     */
    public static function isEnabledReview($postType = null)
    {
        if (empty($postType)) {
            global $post;
            if (empty($post)) {
                return false;
            }

            $postType = $post->post_type;
        } else if ($postType instanceof WP_Post) {
            $postType = $postType->post_type;
        }

        $toggleReview = GetSettings::getOptions(General::getReviewKey('toggle', $postType), false, true);

        return $toggleReview == 'enable';
    }

    public static function isUserReviewed($postID = null, $userID = null)
    {
        if (DebugStatus::status('WILOKE_TESTING_REVIEW')) {
            return false;
        }

        if (empty($userID)) {
            $userID = get_current_user_id();
        }

        if (empty($postID)) {
            global $post;
            if (empty($post)) {
                return false;
            }

            $postID = $post->ID;
        }

        global $wpdb;
        $reviewTbl = $wpdb->posts;

        $status = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM $reviewTbl WHERE post_type=%s AND post_parent=%d AND post_author=%d",
                self::$postType, $postID, $userID
            )
        );

        return empty($status) ? false : true;
    }

    public static function getReviews($parentID, $aArgs)
    {
        if (!isset($aArgs['postsPerPage'])) {
            $aArgs['postsPerPage'] = 10;
        }

        $postType = get_post_type($parentID) == 'event_comment' ? 'event_comment' : 'review';
        $aArgs = wp_parse_args(
            $aArgs,
            [
                'post_parent' => $parentID,
                'post_type'   => $postType,
                'post_status' => 'publish',
                'orderby'     => 'menu_order post_date',
                'order'       => 'DESC'
            ]
        );
        $query = new \WP_Query($aArgs);

        if (!$query->have_posts()) {
            wp_reset_postdata();

            return false;
        }

        return $query;
    }

    /*
     * @postID: int this is listing ID
     */
    public static function countTotalReviews($postID, $isFocus = false)
    {
        if ($cache = self::getCache($postID . '_count_total_reviews', $isFocus)) {
            return $cache;
        }

        global $wpdb;
        $reviewTbl = $wpdb->posts;

        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT($reviewTbl.ID) FROM $reviewTbl WHERE post_parent=%d AND post_status='publish' AND post_type=%s",
                $postID, 'review'
            )
        );

        $total = empty($total) ? 0 : absint($total);
        self::setCache($postID . '_count_total_reviews', $total);

        return $total;
    }

    public static function isLikedReview($reviewID, $returnYesNoOnly = false)
    {
        $status = ReviewMetaModel::isLiked($reviewID);
        if ($returnYesNoOnly) {
            return $status ? 'yes' : 'no';
        }

        if ($status) {
            return [
                'is'    => esc_html__('Liked', 'wiloke-listing-tools'),
                'class' => 'liked color-primary'
            ];
        }

        return [
            'is'    => esc_html__('Like', 'wiloke-listing-tools'),
            'class' => ''
        ];
    }

    public static function isEnabledDiscussion($postType): bool
    {
        $toggleDiscussion = GetSettings::getOptions(General::getReviewKey('toggle_review_discussion', $postType), false,
            true);

        return $toggleDiscussion == 'enable';
    }

    public static function countDiscussion($reviewID)
    {
        global $wpdb;
        $postTbl = $wpdb->posts;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT($postTbl.ID) FROM $postTbl WHERE post_parent=%d AND post_status='publish' AND post_type=%s",
                $reviewID, get_post_type($reviewID)
            )
        );
    }

    /*
     * @param int $reviewID
     */
    public static function getDiscussions($reviewID, $numberOfChild = null)
    {
        return self::getReviews($reviewID, ['postsPerPage' => $numberOfChild]);
    }

    public static function deleteDiscussion($reviewID, $userID = null)
    {
        global $wpdb;
        $reviewTbl = $wpdb->prefix . AlterTableReviews::$tblName;
        $userID = empty($userID) ? get_current_user_id() : $userID;

        return $wpdb->delete(
            $reviewTbl,
            [
                'ID'     => $reviewID,
                'userID' => $userID
            ],
            [
                '%d',
                '%d'
            ]
        );
    }

    /*
     * @param int $parentID
     * @param int $objectID => Or Listing ID
     * @param string $content
     * @param string $reviewPostType
     */
    public static function setDiscussion($parentID, $reviewPostType, $content)
    {
        $postID = wp_insert_post(
            [
                'post_type'      => $reviewPostType,
                'post_content'   => $content,
                'post_title'     => esc_html__('Discussion of ') . get_the_title($parentID),
                'post_parent'    => $parentID,
                'post_status'    => 'publish',
                'post_author'    => User::getCurrentUserID(),
                'post_mime_type' => 'discussion'
            ]
        );

        return $postID;
    }

    /*
     * @param int $discussionID
     * @param string $content
     */
    public static function patchDiscussion($discussionID, $content)
    {
        global $wpdb;
        $reviewTbl = $wpdb->prefix . AlterTableReviews::$tblName;

        return $wpdb->update(
            $reviewTbl,
            [
                'content' => $content
            ],
            [
                'ID'     => $discussionID,
                'userID' => get_current_user_id()
            ],
            [
                '%s'
            ],
            [
                '%d',
                '%d'
            ]
        );
    }

    public static function hasDiscussion($reviewID)
    {
        global $wpdb;
        $status = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM $wpdb->posts WHERE post_parent=%d AND post_status='publish' AND post_type='review'",
            $reviewID
        ));

        if ($status) {
            return 'yes';
        }

        return 'no';
    }

    public static function getReviewInfo($post, $key)
    {
        $averageReview = ReviewMetaModel::getAverageReviewsItem($post->ID);

        $aReviewInfo = [
            'parentID'      => absint(get_post_field('post_parent', $post->ID)),
            'reviewID'      => absint($post->ID),
            'reviewTitle'   => $post->post_title,
            'reviewContent' => $post->post_content,
            'gallery'       => GetSettings::getPostMeta($post->ID, 'gallery'),
            'avarageRating' => $averageReview,
            'averageRating' => $averageReview,
            'oUser'         => [
                'ID'          => absint($post->post_author),
                'displayName' => User::getField('display_name', $post->post_author),
                'avatar'      => User::getAvatar($post->post_author)
            ]
        ];

        return apply_filters('wilcity_filter_get_top_reviews_by_liked_' . $key, $aReviewInfo, $post);
    }

    /*
     * @since 1.2.1.2
     *
     * Get reviews have the most liked
     */
    public static function getTopReviewsByLiked($numberOfReviews = 10, $offset = 0, $key = '')
    {
        global $wpdb;
        $metaKey = WILOKE_LISTING_PREFIX . 'total_liked';

        $aResults = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT($wpdb->posts.ID) as postID, $wpdb->postmeta.meta_value as countLiked FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) WHERE $wpdb->posts.post_status='publish' AND $wpdb->posts.post_type='review' AND $wpdb->posts.post_mime_type!='discussion' AND $wpdb->postmeta.meta_key=%s ORDER BY countLiked DESC, postID DESC LIMIT %d, %d",
                $metaKey, $offset, $numberOfReviews
            )
        );

        return array_map(function ($oResult) {
            return $oResult->postID;
        }, $aResults);
    }

    /*
     * @since 1.2.1.2
     *
     * Get reviews have the most liked
     */
    public static function getLatestReviews($numberOfReviews = 10, $offset = 0, $key = '')
    {
        global $wpdb;

        $aResults = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT($wpdb->posts.ID) as postID FROM $wpdb->posts WHERE $wpdb->posts.post_status='publish' AND $wpdb->posts.post_type='review' AND $wpdb->posts.post_mime_type!='discussion' ORDER BY postID DESC LIMIT %d, %d",
                $offset, $numberOfReviews
            )
        );

        return array_map(function ($oResult) {
            return $oResult->postID;
        }, $aResults);
    }

    /*
     * @since 1.2.1.2
     *
     * Get reviews have the most liked
     */
    public static function getSpecifyReviewIDs($aReviewIDs, $key = '')
    {
        $query = new \WP_Query(
            [
                'post_type' => 'review',
                'post__in'  => $aReviewIDs,
                'orderby'   => 'post__in'
            ]
        );

        if (!$query->have_posts()) {
            return false;
        }

        $aResponse = [];
        while ($query->have_posts()) {
            $query->the_post();
            $aResponse[] = self::getReviewInfo($query->post, $key);
        }
        wp_reset_postdata();

        return $aResponse;
    }

    /*
     * @since 1.2.1.2
     *
     * Get reviews have the most commented
     */
    public static function getTopReviewsByDiscussion($numberOfReviews = 10, $offset = 0, $key = '')
    {
        global $wpdb;

        $aResults = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT(post_parent), COUNT(post_parent) as numberOfPostsParent FROM $wpdb->posts WHERE post_status='publish' AND post_type='review' AND post_mime_type='discussion' Group BY post_parent ORDER BY numberOfPostsParent DESC LIMIT %d, %d",
                $offset, $numberOfReviews
            )
        );
        if (empty($aResults)) {
            return false;
        }

        return array_map(function ($oResult) {
            return $oResult->post_parent;
        }, $aResults);
    }
}
