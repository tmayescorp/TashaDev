<?php

namespace WILCITY_APP\Controllers\Listing;

use WILCITY_APP\Controllers\User\UserInfo;
use WilokeListingTools\Framework\Helpers\ReviewSkeleton;
use WilokeListingTools\Models\ReviewModel;
use WilokeListingTools\Framework\Helpers\WPML;

class ListingReview extends ListingSkeleton
{
	private $aCache          = [];
	private $oReviewSkeleton = null;
	private $oUserInfo       = null;

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	protected function hasCache($key)
	{
		return isset($this->aCache[$key]);
	}

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	protected function getCache($key)
	{
		return $this->aCache[$key];
	}

	protected function setCache($key, $val)
	{
		$this->aCache[$key] = $val;
	}

	protected function deleteCache($key, $val)
	{
		unset($this->aCache[$key]);
	}

	private function makeReviewSkeleton()
	{
		if ($this->oReviewSkeleton === null) {
			$this->oReviewSkeleton = new ReviewSkeleton();
		}

		return $this->oReviewSkeleton;
	}

	private function makeUserInfo()
	{
		if ($this->oUserInfo === null) {
			$this->oUserInfo = new UserInfo();
		}

		return $this->oUserInfo;
	}

	public function getMyReviews($aArgs)
	{
		$aArgs = wp_parse_args(
			$aArgs,
			[
				'post_type'      => 'review',
				'post_status'    => 'publish',
				'posts_per_page' => 10,
				'paged'          => 1
			]
		);
		if ($this->hasCache($aArgs['author'] . $aArgs['paged'])) {
			return $this->getCache($aArgs['author'] . $aArgs['paged']);
		}

		$query = new \WP_Query(WPML::addFilterLanguagePostArgs($aArgs));

		if (!$query->have_posts()) {
			return false;
		}

		if ($query->have_posts()) {
			$aResponse['total'] = abs($query->found_posts);
			$aResponse['maxPages'] = abs($query->max_num_pages);

			if ($aArgs['paged'] == $query->max_num_pages) {
				$aResponse['next'] = false;
			} else {
				$aResponse['next'] = abs($aArgs['paged']) + 1;
			}

			while ($query->have_posts()) {
				$query->the_post();
				$aItem = $this->getReview($query->post);

				$aResponse['reviewItems'][] = $aItem;
			}
			wp_reset_postdata();
		} else {
			$aResponse = false;
		}

		$this->setCache($aArgs['author'] . $aArgs['page'], $aResponse);

		return $aResponse;
	}


	public function getData(\WP_Post $oPostParent, $aAtts = [])
	{
		$aAtts = wp_parse_args(
			[
				'posts_per_page' => 10,
				'page'           => 1
			],
			$aAtts
		);

		if ($this->hasCache($oPostParent->ID . $aAtts['page'])) {
			return $this->getCache($oPostParent->ID . $aAtts['page']);
		}

		$query = new \WP_Query(
			WPML::addFilterLanguagePostArgs([
				'post_type'      => 'review',
				'post_status'    => 'publish',
				'posts_per_page' => $aAtts['posts_per_page'],
				'post_parent'    => abs($oPostParent->ID),
				'paged'          => $aAtts['page']
			])
		);

		if (!$query->have_posts()) {
			return false;
		}

		if ($query->have_posts()) {
			$aResponse = $this->getListingReviewGeneral($oPostParent);

			$aResponse['total'] = abs($query->found_posts);
			$aResponse['maxPages'] = abs($query->max_num_pages);

			if ($aAtts['page'] == $query->max_num_pages) {
				$aResponse['next'] = false;
			} else {
				$aResponse['next'] = abs($aAtts['page']) + 1;
			}

			while ($query->have_posts()) {
				$query->the_post();
				$aItem = $this->getReview($query->post);

				$aResponse['reviewItems'][] = $aItem;
			}
			wp_reset_postdata();
		} else {
			$aResponse = false;
		}

		$this->setCache($oPostParent->ID . $aAtts['page'], $aResponse);

		return $aResponse;
	}

	/**
	 * @param \WP_Post $oPostParent
	 *
	 * @return mixed
	 */
	public function getListingReviewGeneral(\WP_Post $oPostParent)
	{
		$aResponse['mode'] = ReviewModel::getReviewMode($oPostParent->ID);
		$aResponse['average'] = ReviewModel::getListingAverageReviews($oPostParent->ID);
		$aResponse['oAverageDetailsReview'] = ReviewModel::getListingAverageCategories($oPostParent->ID);
		$aResponse['quality'] = ReviewModel::getListingQuality(
			$aResponse['average'],
			$oPostParent->post_type
		);

		return $aResponse;
	}

	public function getReview(\WP_Post $post, $aAtts = [], $isFocus = false): array
	{
		$oReviewSkeleton = $this->makeReviewSkeleton();
		$oUserInfo = $this->makeUserInfo();

		$aItem = $oReviewSkeleton->getSkeleton(
			$post,
			[
				'ID',
				'postTitle',
				'postStatus',
				'permalink',
				'isEnableDiscussion',
				'shareURL',
				'postContent',
				'postDate',
				'countLiked',
				'countShared',
				'countDiscussions',
				'hasDiscussion',
				'oGallery',
				'isLiked',
				'isPintToTop',
				'average',
				'mode',
				'quality',
				'details'
			],
			$aAtts,
			$isFocus
		);

		if (!empty($aItem['oGallery'])) {
			$aItem['oGallery'] = $this->rebuildGallery($aItem['oGallery']);
		}

		$aItem['authorInfo'] = $oUserInfo->getData($post->post_author);

		return $aItem;
	}
}
