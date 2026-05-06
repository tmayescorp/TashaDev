<?php

namespace WilokeListingTools\Framework\Helpers;

use WILCITY_SC\SCHelpers;
use WilokeListingTools\Controllers\ReviewController;
use WilokeListingTools\Framework\Helpers\Collection\ArrayCollectionFactory;
use WilokeListingTools\Frontend\BusinessHours;
use WilokeListingTools\Frontend\SingleListing;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\FollowerModel;
use WilokeListingTools\Models\ReviewMetaModel;
use WilokeListingTools\Models\ReviewModel;
use WilokeListingTools\Models\UserModel;

final class ReviewSkeleton extends AbstractSkeleton
{
    public function getAuthor()
    {
        $oAuthorSkeleton = new AuthorSkeleton();

        return $oAuthorSkeleton->getSkeleton($this->getPostAuthor(), ['ID', 'avatar', 'displayName', 'authorLink']);
    }

    public function getMode(): int
    {
        return ReviewModel::getReviewMode(get_post_type($this->getParentID()));
    }

    public function getID(): int
    {
        return absint($this->post->ID);
    }

    public function getDetails(): array
    {
        if ($aReviewDetails = $this->getCache('review_details')) {
            return $aReviewDetails;
        }

        $postType    = get_post_type($this->getParentID());
        $aCategories = GetSettings::getOptions(General::getReviewKey('details', $postType), false, true);
        if ($aCategories) {
            $aReviewDetails = ReviewMetaModel::getReviewDetailsScore($this->postID, $aCategories, false);
            $this->setCache('review_details', $aReviewDetails['oDetails']);

            return $aReviewDetails['oDetails'];
        }

        return [];
    }

    public function getAverage(): float
    {
        if ($this->getCache('average')) {
            return $this->getCache('average');
        }

        $aReviewDetails = $this->getDetails();
        if (empty($aReviewDetails)) {
            return 0;
        }

        $totalReviewItems = count($aReviewDetails);
        $totalScore       = array_reduce($aReviewDetails, function ($score, $aItem) {
            return $score + $aItem['score'];
        }, 0);
        $average          = round($totalScore / $totalReviewItems, 1);
        $this->setCache('average', $average);

        return $average;
    }

    public function getQuality(): ?string
    {
        if ($this->getCache('quality')) {
            return $this->getCache('quality');
        }

        $average = $this->getAverage();
        if (empty($average)) {
            return '';
        }

        $reviewQuality = ReviewMetaModel::getReviewQualityString($average, get_post_type($this->getParentID()));
        $this->setCache('quality', $reviewQuality);

        return $reviewQuality;
    }

    public function getIsLiked(): string
    {
        return ReviewModel::isLikedReview($this->getID(), true);
    }

    public function getHasDiscussion(): string
    {
        return ReviewModel::hasDiscussion($this->getID());
    }

    public function getIsEnableDiscussion()
    {
        $key = General::getReviewKey('toggle_review_discussion', get_post_type($this->getParentID()));

        return GetSettings::getOptions($key, false, true) === 'enable' ? 'yes' : 'no';
    }

    public function getIsPinToTop(): string
    {
        return !empty($this->post->menu_order) ? 'yes' : 'no';
    }

    public function getIsParentAuthor(): string
    {
        return get_current_user_id() == get_post_field('post_author', $this->getParentID()) ? 'yes' : 'no';
    }

    public function getPostParentPermalink()
    {
        return get_permalink($this->getParentID());
    }

    public function getShareURL()
    {
        return GetSettings::getShareReviewURL($this->getPostParentPermalink(), $this->getID());
    }

    public function getIsPintToTop(): string
    {
        return !empty($this->post->menu_order) ? 'yes' : 'no';
    }

    public function getCountLiked(): int
    {
        $aLikedReview = GetSettings::getPostMeta(
            $this->postID,
            wilokeListingToolsRepository()->get('reviews:liked'),
            '',
            '',
            $this->isFocus
        );
        if (empty($aLikedReview)) {
            return 0;
        }

        return count($aLikedReview);
    }

    public function getCountDiscussion(): int
    {
        return absint(ReviewModel::countDiscussion($this->postID));
    }

    public function getCountDiscussions(): int
    {
        return $this->getCountDiscussion();
    }

    public function getSkeleton($post, $aPluck, $aAtts = [], $isFocus = false)
    {
        if (empty($aPluck)) {
            $aPluck = [
                'ID',
                'title',
                'permalink',
            ];
        } else {
            $aPluck = is_array($aPluck) ? $aPluck : explode(',', $aPluck);
            $aPluck = array_map(function ($key) {
                return $key;
            }, $aPluck);
        }

        if (is_numeric($post)) {
            $post = get_post($post);
        }

        $this->setPost($post);
        $this->aAtts = $aAtts;

        /**
         * @hooked WilcityRedis\Controllers@removeCachingPluckItems
         */
        $aPluck = apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Framework/Helpers/ReviewSkeleton/pluck',
            $aPluck,
            $post,
            $this->aAtts
        );

        $aReview = $this->pluck($aPluck, $isFocus);

        /**
         * @hooked WilcityRedis\Controllers@getPostSkeleton 5
         * @hooked WilcityRedis\Controllers@setPostSkeleton 10
         */
        $aReview = apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Framework/Helpers/ReviewSkeleton/review',
            $aReview,
            $post,
            $this->aAtts
        );

        return $aReview;
    }
}
