<?php

namespace WilokeListingTools\Models;

use WilokeListingTools\AlterTable\AlterTableReviewMeta;
use WilokeListingTools\AlterTable\AlterTableReviews;
use WilokeListingTools\Controllers\ReviewController;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Frontend\User;

class ReviewMetaModel
{
    public static  $postType         = 'review';
    public static  $aCache           = [];
    private static $aCacheReviewName = [];

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

    public static function setPrefix($metaKey)
    {
        $prefix = wilokeListingToolsRepository()->get('general:metaboxPrefix');
        $prefix
            = strpos($metaKey, $prefix) === false ? wilokeListingToolsRepository()->get('general:metaboxPrefix') : '';

        return $prefix . $metaKey;
    }

    public static function countLiked($reviewID)
    {
        $aLikedReview = GetSettings::getPostMeta($reviewID, wilokeListingToolsRepository()->get('reviews:liked'));

        if (empty($aLikedReview)) {
            return 0;
        }

        return count($aLikedReview);
    }

    public static function countDiscussion($reviewID)
    {
        return absint(ReviewModel::countDiscussion($reviewID));
    }

    public static function isLiked($reviewID)
    {
        $aLikedReview = GetSettings::getPostMeta($reviewID, wilokeListingToolsRepository()->get('reviews:liked'));
        if (empty($aLikedReview)) {
            $status = false;
        } else {
            $userID = User::getCurrentUserID();
            $userID = !empty($userID) ? $userID : General::clientIP();

            $status = in_array($userID, $aLikedReview);
        }

        return $status;
    }

    public static function deleteReviewMeta($reviewID, $metaKey)
    {
        global $wpdb;
        $tblName = $wpdb->prefix . AlterTableReviewMeta::$tblName;
        $metaKey = self::setPrefix($metaKey);

        return $wpdb->delete(
            $tblName,
            [
                'reviewID' => $reviewID,
                'meta_key' => $metaKey
            ],
            [
                '%d',
                '%s'
            ]
        );
    }

    public static function isMetaExist($reviewID, $metaKey)
    {
        global $wpdb;
        $tblName = $wpdb->prefix . AlterTableReviewMeta::$tblName;
        $metaKey = self::setPrefix($metaKey);

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM $tblName WHERE reviewID=%d AND meta_key=%s",
                $reviewID, $metaKey
            )
        );
    }

    public static function updateReviewData($reviewMetaID, $metaKey, $metaValue)
    {
        global $wpdb;
        $tblName = $wpdb->prefix . AlterTableReviewMeta::$tblName;

        $metaKey = self::setPrefix($metaKey);

        return $wpdb->update(
            $tblName,
            [
                'meta_value' => maybe_serialize($metaValue),
                'date'       => Time::mysqlDate()
            ],
            [
                'ID'       => $reviewMetaID,
                'meta_key' => $metaKey
            ],
            [
                '%s',
                '%s'
            ],
            [
                '%d',
                '%s'
            ]
        );
    }

    public static function setReviewMeta($reviewID, $metaKey, $metaValue)
    {
        global $wpdb;
        $tblName = $wpdb->prefix . AlterTableReviewMeta::$tblName;
        $metaKey = self::setPrefix($metaKey);
        $reviewMetaID = self::isMetaExist($reviewID, $metaKey);

        if ($reviewMetaID) {
            self::updateReviewData($reviewMetaID, $metaKey, $metaValue);

            return $reviewMetaID;
        } else {
            $status = $wpdb->insert(
                $tblName,
                [
                    'reviewID'   => $reviewID,
                    'meta_key'   => $metaKey,
                    'meta_value' => maybe_serialize($metaValue),
                    'date'       => Time::mysqlDate()
                ],
                [
                    '%d',
                    '%s',
                    '%s',
                    '%s'
                ]
            );

            if (!$status) {
                return false;
            }

            return $wpdb->insert_id;
        }
    }

    public static function getReviewMeta($reviewID, $metaKey, $isLastItem = true)
    {
        global $wpdb;
        $tblName = $wpdb->prefix . AlterTableReviewMeta::$tblName;
        $metaKey = self::setPrefix($metaKey);

        if ($isLastItem) {
            $data = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT meta_value FROM $tblName WHERE reviewID=%d AND meta_key=%s",
                    $reviewID, $metaKey
                )
            );

            return maybe_unserialize($data);
        } else {
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT meta_value FROM $tblName WHERE reviewID=%d AND meta_key=%s",
                    $reviewID, $metaKey
                ),
                ARRAY_A
            );
        }
    }

    public static function getReviewDetailsScore($reviewID, $aDetails, $isCalculateAverage = false)
    {
        $aData = [];
        $total = 0;
        if (empty($aDetails)) {
            return $aData;
        }

        foreach ($aDetails as $aDetail) {
            unset($aDetail['isEditable']);
            $aDetail['score'] = self::getReviewMeta($reviewID, $aDetail['key']);
            $aDetail['score'] = empty($aDetail['score']) ? 0 : round($aDetail['score'], 1);
            $total += $aDetail['score'];
            $aData['oDetails'][] = $aDetail;
        }

        if ($isCalculateAverage) {
            $count = count($aDetails);
            $aData['average'] = round($total / $count, 1);
        }

        return $aData;
    }

    public static function getAverageReviewMeta($postID, $metaKey)
    {
        global $wpdb;
        $reviewMetaTbl = $wpdb->prefix . AlterTableReviewMeta::$tblName;
        $reviewTbl = $wpdb->posts;

        $metaKey = self::setPrefix($metaKey);

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT AVG(meta_value) FROM $reviewMetaTbl LEFT JOIN $reviewTbl ON ($reviewMetaTbl.reviewID = $reviewTbl.ID) AND $reviewTbl.ID=%d AND meta_key=%s",
                $postID, $metaKey
            )
        );
    }

    public static function getAverageListingReview($parentID, $metaKey)
    {
        global $wpdb;
        $reviewMetaTbl = $wpdb->prefix . AlterTableReviewMeta::$tblName;
        $reviewTbl = $wpdb->posts;

        $aRawAllReviewIDs = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT (ID) FROM $reviewTbl WHERE post_type=%s AND post_parent=%d AND post_status=%s",
            self::$postType, $parentID, 'publish'
        ), ARRAY_A);

        if (empty($aRawAllReviewIDs)) {
            return 0;
        }

        $aAllReviewIDs = array_map(function ($aData) {
            return absint($aData['ID']);
        }, $aRawAllReviewIDs);

        $metaKey = self::setPrefix($metaKey);

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT AVG(meta_value) FROM $reviewMetaTbl WHERE reviewID IN (" . implode(',', $aAllReviewIDs) .
                ") AND meta_key=%s",
                $metaKey
            )
        );
    }

    public static function getReviewQualityString($score, $postType)
    {
        if (empty($score)) {
            return '';
        }

        if (is_numeric($postType)) {
            $postType = get_post_type($postType);
        }

        $aReviewSettings = wilokeListingToolsRepository()->get('reviews');
        $mode = GetSettings::getOptions(General::getReviewKey('mode', $postType), false, true);

        $aQualities = $aReviewSettings['review_qualities'];

        if (empty($mode)) {
            return false;
        }

        $mode = absint($mode);
        $score = floatval($score);
        $aReviewQualities = $aQualities[$mode];

        if (!is_array($aReviewQualities)) {
            wp_send_json_error(
                [
                    'msg' => esc_html__('Please go to Wiloke Tools -> [Your Listing Type] Settings -> Review Settings -> Add one category at least',
                        'wiloke-listing-tools')
                ]
            );
        }

        $i = 1;
        $total = count($aReviewQualities);
        foreach ($aReviewQualities as $approachScore => $name) {
            $approachScore = absint($approachScore);
            if ($score >= $approachScore || ($i == $total)) {
                return $name;
            }
            $i += 1;
        }
    }

    /**
     * @param        $parentID
     *
     * @return array ['overall' => 5, 'food' => 4]
     */
    public static function getAverageReviewCategories($parentID): array
    {
        $aAverageReviewCategories
            = GetSettings::getPostMeta(General::getReviewKey('details', get_post_type($parentID)));

        if (!empty($aAverageReviewCategories)) {
            return $aAverageReviewCategories;
        }

        global $wpdb;
        $reviewMetaTbl = $wpdb->prefix . AlterTableReviewMeta::$tblName;

        $aRawReviewIDs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID FROM $wpdb->posts WHERE $wpdb->posts.post_type=%s AND $wpdb->posts.post_status=%s AND $wpdb->posts.post_parent=%d",
                'review', 'publish', $parentID
            ),
            ARRAY_A
        );

        $aRawCategories = GetSettings::getOptions(General::getReviewKey('details', get_post_type($parentID)), false,
            true);
        $aCategoryKeys = [];
        $aCategories = [];
        foreach ($aRawCategories as $aItem) {
            $aCategories[$aItem['key']] = $aItem;
            $aCategoryKeys[] = 'wilcity_' . $aItem['key'];
        }

        $aStatistic = [];

        if (empty($aRawReviewIDs)) {
            foreach ($aCategories as $category => $aCategory) {
                $aStatistic[] = [
                    'average' => 0,
                    'name'    => $aCategory['name'],
                    'key'     => $aCategory['key']
                ];
            }

            SetSettings::setPostMeta($parentID, 'average_review_categories', $aStatistic);

            return $aStatistic;
        }

        $aReviewIDs = array_reduce($aRawReviewIDs, function ($aCarry, $aItem) {
            $aCarry[] = $aItem['ID'];

            return $aCarry;
        }, []);

        $reviewIDs = implode(',', $aReviewIDs);
        $categories = '"' . implode('","', $aCategoryKeys) . '"';
        $aAverages = $wpdb->get_results(
            "SELECT meta_key, ROUND(AVG($reviewMetaTbl.meta_value),2) as average, COUNT($reviewMetaTbl.meta_value) as total FROM $reviewMetaTbl WHERE $reviewMetaTbl.reviewID IN ($reviewIDs) AND $reviewMetaTbl.meta_key IN ($categories) GROUP BY meta_key",
            ARRAY_A
        );

        if (empty($aAverages) || is_wp_error($aAverages)) {
            foreach ($aCategories as $category => $aCategory) {
                $aStatistic[] = [
                    'average' => 0,
                    'name'    => $aCategory['name'],
                    'key'     => $aCategory['key']
                ];
            }
            SetSettings::setPostMeta($parentID, 'average_review_categories', $aStatistic);

            return $aStatistic;
        }

        foreach ($aAverages as $aAverage) {
            $key = str_replace('wilcity_', '', $aAverage['meta_key']);

            $aStatistic[] = [
                'average' => floatval($aAverage['average']),
                'name'    => $aCategories[$key]['name'],
                'key'     => $aCategories[$key]['key']
            ];
        }
        SetSettings::setPostMeta($parentID, 'average_review_categories', $aStatistic);

        return $aStatistic;
    }

    public static function getAverageReviewsItem($reviewID, $post_status = 'publish'): float
    {
        $score = GetSettings::getPostMeta($reviewID, 'average_reviews');
        if (!empty($score)) {
            return floatval($score);
        }

        global $wpdb;
        $postTbl = $wpdb->posts;
        $reviewMetaTbl = $wpdb->prefix . AlterTableReviewMeta::$tblName;

        $score = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT AVG($reviewMetaTbl.meta_value) FROM $reviewMetaTbl LEFT JOIN $postTbl ON ($postTbl.ID = $reviewMetaTbl.reviewID) WHERE $postTbl.ID=%d AND $postTbl.post_type=%s AND $postTbl.post_status=%s",
                $reviewID, 'review', $post_status
            )
        );

        $score = !$score ? 0 : round($score, 1);
        SetSettings::setPostMeta($reviewID, 'average_reviews', $score);

        return floatval($score);
    }

    public static function getAverageReviews($parentID, $post_status = 'publish')
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

    public static function getReviewName($reviewKey, $listingID)
    {
        $postType = get_post_type($listingID);
        if (isset(self::$aCacheReviewName[$postType]) && isset(self::$aCacheReviewName[$postType][$reviewKey])) {
            return self::$aCacheReviewName[$postType][$reviewKey];
        }

        $aReviewCats = GetSettings::getOptions(General::getReviewKey('details', get_post_type($listingID)), false,
            true);
        if (empty($aReviewCats)) {
            return false;
        }
        self::$aCacheReviewName[$postType] = [];
        foreach ($aReviewCats as $aReviewCat) {
            if ('wilcity_' . $aReviewCat['key'] == $reviewKey) {
                self::$aCacheReviewName[$postType]['wilcity_' . $aReviewCat['key']] = $aReviewCat['name'];

                return $aReviewCat['name'];
            }
        }

        return false;
    }

    public static function getAverageCategoriesReview($parentID)
    {
        global $wpdb;
        $postTbl = $wpdb->posts;
        $reviewMetaTbl = $wpdb->prefix . AlterTableReviewMeta::$tblName;

        $aRawScores = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT AVG($reviewMetaTbl.meta_value) as average_scores, $reviewMetaTbl.meta_key as review_category FROM $reviewMetaTbl LEFT JOIN $postTbl ON ($postTbl.ID = $reviewMetaTbl.reviewID) WHERE $postTbl.post_parent=%d AND $postTbl.post_type=%s AND $postTbl.post_status='publish' GROUP BY review_category",
                $parentID, 'review'
            )
        );

        if (empty($aRawScores) || is_wp_error($aRawScores)) {
            return [];
        }

        $aScores = [];
        foreach ($aRawScores as $oScore) {

            $reviewName = self::getReviewName($oScore->review_category, $parentID);
            if ($reviewName) {
                $aScores[] = [
                    'average' => round(floatval($oScore->average_scores), 1),
                    'text'    => self::getReviewName($oScore->review_category, $parentID)
                ];
            }
        }

        return $aScores;
    }

    //    public static function getDataReviewItemRated($postID, $score = null)
    //    {
    //        if (empty($score)) {
    //            $score = self::getAverageReviewsItem($postID);
    //        }
    //
    //        $mode = ReviewModel::getReviewMode(get_post_type($postID));
    //        if ($mode == 5) {
    //            $score = floatval($score) * 2;
    //        }
    //
    //        return $score;
    //    }

    //    public static function getDataRated($postID, $score = null)
    //    {
    //        if (empty($score)) {
    //            $score = self::getAverageReviews($postID);
    //        }
    //
    //        $mode = ReviewModel::getReviewMode(get_post_type($postID));
    //        if ($mode == 5) {
    //            $score = floatval($score) * 2;
    //        }
    //
    //        return $score;
    //    }

    public static function getGeneralReviewData($post, $isFocus = false)
    {
        if ($cache = self::getCache($post->ID . 'general_review_data', $isFocus)) {
            return $cache;
        }

        $cache = GetSettings::getPostMeta($post->ID, 'general_review_data');

        if (!empty($cache)) {
            return $cache;
        }

        if ($post instanceof \WP_Post) {
            $postID = $post->ID;
            $postType = $post->post_type;
        } else {
            $postID = $post;
            $postType = get_post_type($post);
        }

        $totalReviews = ReviewModel::countTotalReviews($postID);
        $aReviewDetails = GetSettings::getOptions(General::getReviewKey('details', $postType), false, true);
        $mode = GetSettings::getOptions(General::getReviewKey('mode', $postType), false, true);
        $mode = empty($mode) ? 5 : $mode;
        $totalScore = 0;
        $average = 0;

        if ($aReviewDetails) {
            $totalReviewItems = count($aReviewDetails);
            foreach ($aReviewDetails as $key => $aReview) {
                $aReviewDetails[$key]['average']
                    = floatval(General::numberFormat(self::getAverageListingReview($postID, $aReview['key']), 1));
                $totalScore += $aReviewDetails[$key]['average'];
            }

            foreach ($aReviewDetails as $key => $aReview) {
                if (empty($totalScore)) {
                    $aReviewDetails[$key]['percentage'] = 0;
                } else {
                    $aReviewDetails[$key]['percentage'] = round(($aReviewDetails[$key]['average'] / $mode) * 100, 1);
                }
            }
            $average = round($totalScore / $totalReviewItems, 1);
        }

        $reviewQuality = ReviewMetaModel::getReviewQualityString($average, get_post_type($postID));

        if ($mode == 5) {
            $dataRated = floatval($average) * 2;
        } else {
            $dataRated = $average;
        }

        $aResponse = [
            'total'          => absint($totalReviews),
            'mode'           => absint($mode),
            'quality'        => $reviewQuality,
            'totalScore'     => floatval($totalScore),
            'average'        => floatval($average),
            'dataRated'      => floatval($dataRated),
            'aDetails'       => $aReviewDetails,
            'isUserReviewed' => ReviewModel::isUserReviewed($post->ID) ? 'yes' : 'no'
        ];

        self::setCache($post->ID . 'general_review_data', $aResponse);
        SetSettings::setPostMeta($post->ID, 'general_review_data', $aResponse);

        return $aResponse;
    }
}
