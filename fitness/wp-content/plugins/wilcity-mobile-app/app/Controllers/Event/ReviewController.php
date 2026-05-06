<?php

namespace WILCITY_APP\Controllers;

use WILCITY_APP\Controllers\Listing\ListingReview;
use WILCITY_APP\Helpers\App;
use \WilokeListingTools\Controllers\ReviewController as WebReviewController;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Framework\Upload\Upload;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\ReviewMetaModel;
use WilokeListingTools\Models\ReviewModel;
use WP_Query;
use WP_REST_Request;
use WilokeListingTools\Framework\Helpers\WPML;

class ReviewController extends Controller
{
	use VerifyToken;
	use ParsePost;
	use JsonSkeleton;
	use Message;

	private $reviewID     = '';
	private $postID       = '';
	private $discussionID = '';
	private $commentID    = '';
	private $oToken;
	private $aReviewData;
	/**
	 * @var WP_REST_Request $oRestData
	 */
	private $oRestData;
	private $isDeleteReview = false;

	public function __construct()
	{
		add_filter('wilcity/wilcity-mobile-app/filter/wilcity-reviews', [$this, 'generateReviewsForShortcode'], 10, 2);

		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, '/reviews', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getReviewsViaRestAPI'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, '/review-fields/(?P<postID>\w+)', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getReviewFields'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, '/posts/(?P<postID>\w+)/reviews', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getReviewsOfPost'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION,
				'/posts/(?P<postID>\w+)/reviews/(?P<reviewID>\w+)', [
					'methods'             => 'GET',
					'callback'            => [$this, 'getReview'],
					'permission_callback' => '__return_true'
				]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION,
				'/posts/(?P<postID>\w+)/reviews/(?P<reviewID>\w+)', [
					'methods'             => 'POST',
					'callback'            => [$this, 'updateReview'],
					'permission_callback' => '__return_true'
				]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, '/posts/(?P<postID>\w+)/reviews', [
				'methods'             => 'POST',
				'callback'            => [$this, 'postReview'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION,
				'/posts/(?P<postID>\w+)/reviews/(?P<reviewID>\w+)', [
					'methods'             => 'DELETE',
					'callback'            => [$this, 'deleteReview'],
					'permission_callback' => '__return_true'
				]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, '/reviews/(?P<reviewID>\w+)/like', [
				'methods'             => 'POST',
				'callback'            => [$this, 'updateLikeReview'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' .
				WILOKE_MOBILE_REST_VERSION, '/reviews/(?P<reviewID>\w+)/discussions',
				[
					'methods'             => 'POST',
					'callback'            => [$this, 'postReviewDiscussion'],
					'permission_callback' => '__return_true'
				]);

			register_rest_route(WILOKE_PREFIX . '/' .
				WILOKE_MOBILE_REST_VERSION, '/reviews/(?P<reviewID>\d+)/discussions',
				[
					'methods'             => 'GET',
					'callback'            => [$this, 'getDiscussions'],
					'permission_callback' => '__return_true'
				]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION,
				'/reviews/(?P<reviewID>\w+)/discussions/(?P<discussionID>\w+)', [
					'methods'             => 'PUT',
					'callback'            => [$this, 'updateReviewDiscussion'],
					'permission_callback' => '__return_true'
				]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION,
				'/reviews/(?P<reviewID>\w+)/discussions/(?P<discussionID>\w+)',
				[
					'methods'             => 'DELETE',
					'callback'            => [$this, 'deleteReviewDiscussion'],
					'permission_callback' => '__return_true'
				]
			);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, '/reviews/(?P<reviewID>\w+)/share', [
				'methods'             => 'POST',
				'callback'            => [$this, 'updateReviewCountShares'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' .
				WILOKE_MOBILE_REST_VERSION, '/events/(?P<eventID>\w+)/discussions', [
				'methods'             => 'POST',
				'callback'            => [$this, 'postEventDiscussion'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION,
				'/events/(?P<eventID>\w+)/discussions/(?P<discussionID>\w+)', [
					'methods'             => 'PUT',
					'callback'            => [$this, 'updateEventDiscussion'],
					'permission_callback' => '__return_true'
				]);

			register_rest_route(WILOKE_PREFIX . '/' .
				WILOKE_MOBILE_REST_VERSION, '/events/(?P<eventID>\w+)/discussions', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getEventDiscussions'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION,
				'/events/(?P<eventID>\w+)/discussions/(?P<discussionID>\w+)', [
					'methods'             => 'DELETE',
					'callback'            => [$this, 'deleteEventDiscussion'],
					'permission_callback' => '__return_true'
				]);

			register_rest_route(WILOKE_PREFIX . '/' .
				WILOKE_MOBILE_REST_VERSION, '/discussions/(?P<discussionID>\w+)/like',
				[
					'methods'             => 'POST',
					'callback'            => [$this, 'updateEventDiscussionLiked'],
					'permission_callback' => '__return_true'
				]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION,
				'/discussions/(?P<discussionID>\w+)/comments', [
					'methods'             => 'POST',
					'callback'            => [$this, 'postCommentOnEventDiscussion'],
					'permission_callback' => '__return_true'
				]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION,
				'/discussions/(?P<discussionID>\w+)/comments/(?P<commentID>\w+)', [
					'methods'             => 'PUT',
					'callback'            => [$this, 'updateCommentOnEventDiscussion'],
					'permission_callback' => '__return_true'
				]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION,
				'/discussions/(?P<discussionID>\w+)/comments/(?P<commentID>\w+)', [
					'methods'             => 'DELETE',
					'callback'            => [$this, 'deleteCommentOnEventDiscussion'],
					'permission_callback' => '__return_true'
				]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION,
				'/discussions/(?P<discussionID>\w+)/share', [
					'methods'             => 'POST',
					'callback'            => [$this, 'updateDiscussionCountShares'],
					'permission_callback' => '__return_true'
				]);

			//Dang lam
			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION,
				'/me/reviews', [
					'methods'             => 'GET',
					'callback'            => [$this, 'getMyReviews'],
					'permission_callback' => '__return_true'
				]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/reviews', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getReviewsViaRestAPI'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/review-fields/(?P<postID>\w+)', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getReviewFields'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/posts/(?P<postID>\w+)/reviews', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getReviewsOfPost'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/posts/(?P<postID>\w+)/reviews/(?P<reviewID>\w+)', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getReview'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/posts/(?P<postID>\w+)/reviews/(?P<reviewID>\w+)', [
				'methods'             => 'POST',
				'callback'            => [$this, 'updateReview'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/posts/(?P<postID>\w+)/reviews', [
				'methods'             => 'POST',
				'callback'            => [$this, 'postReview'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/posts/(?P<postID>\w+)/reviews/(?P<reviewID>\w+)', [
				'methods'             => 'DELETE',
				'callback'            => [$this, 'deleteReview'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/reviews/(?P<reviewID>\w+)/like', [
				'methods'             => 'POST',
				'callback'            => [$this, 'updateLikeReview'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/reviews/(?P<reviewID>\w+)/discussions', [
				'methods'             => 'POST',
				'callback'            => [$this, 'postReviewDiscussion'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/reviews/(?P<reviewID>\d+)/discussions', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getDiscussions'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/reviews/(?P<reviewID>\w+)/discussions/(?P<discussionID>\w+)', [
				'methods'             => 'PUT',
				'callback'            => [$this, 'updateReviewDiscussion'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/reviews/(?P<reviewID>\w+)/discussions/(?P<discussionID>\w+)', [
				'methods'             => 'DELETE',
				'callback'            => [$this, 'deleteReviewDiscussion'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/reviews/(?P<reviewID>\w+)/share', [
				'methods'             => 'POST',
				'callback'            => [$this, 'updateReviewCountShares'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/events/(?P<eventID>\w+)/discussions', [
				'methods'             => 'POST',
				'callback'            => [$this, 'postEventDiscussion'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/events/(?P<eventID>\w+)/discussions/(?P<discussionID>\w+)', [
				'methods'             => 'PUT',
				'callback'            => [$this, 'updateEventDiscussion'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/events/(?P<eventID>\w+)/discussions', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getEventDiscussions'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/events/(?P<eventID>\w+)/discussions/(?P<discussionID>\w+)', [
				'methods'             => 'DELETE',
				'callback'            => [$this, 'deleteEventDiscussion'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/discussions/(?P<discussionID>\w+)/like', [
				'methods'             => 'POST',
				'callback'            => [$this, 'updateEventDiscussionLiked'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/discussions/(?P<discussionID>\w+)/comments', [
				'methods'             => 'POST',
				'callback'            => [$this, 'postCommentOnEventDiscussion'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX .
				'/v2', '/discussions/(?P<discussionID>\w+)/comments/(?P<commentID>\w+)', [
				'methods'             => 'PUT',
				'callback'            => [$this, 'updateCommentOnEventDiscussion'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX .
				'/v2', '/discussions/(?P<discussionID>\w+)/comments/(?P<commentID>\w+)', [
				'methods'             => 'DELETE',
				'callback'            => [$this, 'deleteCommentOnEventDiscussion'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/discussions/(?P<discussionID>\w+)/share', [
				'methods'             => 'POST',
				'callback'            => [$this, 'updateDiscussionCountShares'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/test-reviews/(?P<reviewID>\d+)', [
				'methods'             => 'GET',
				'callback'            => [$this, 'testReview'],
				'permission_callback' => '__return_true'
			]);

			//Dang lam
			register_rest_route(WILOKE_PREFIX . '/v2', '/me/reviews', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getMyReviews'],
				'permission_callback' => '__return_true'
			]);
		});
	}

	/*
	 * @since 1.4.4
	 *
	 * @var $orderBy. You can filter Reviews by Order By
	 */
	public function getReviews($aData)
	{
		if (!isset($aData['number_of_reviews'])) {
			$numberOfReviews = 3;
		} else if ($aData['number_of_reviews'] > 100) {
			$numberOfReviews = 100;
		} else {
			$numberOfReviews = abs($aData['number_of_reviews']);
		}

		$orderBy = isset($aData['orderby']) ? $aData['orderby'] : 'top_liked';
		$offset = isset($aData['offset']) ? $aData['offset'] : 0;

		if ($orderBy == 'specify_review_ids') {
			if (!isset($aData['review_ids'])) {
				$orderBy = 'latest_reviews';
			}
		}

		if (!isset($aData['author'])) {
			switch ($orderBy) {
				case 'top_liked':
					$aReviewIDs = ReviewModel::getTopReviewsByLiked($numberOfReviews, $offset);
					break;
				case 'top_discussions':
					$aReviewIDs = ReviewModel::getTopReviewsByDiscussion($numberOfReviews, $offset);
					break;
				case 'specify_review_ids':
					$aReviewIDs = explode(',', $aData['review_ids']);
					break;
				default:
					$aReviewIDs = ReviewModel::getLatestReviews($numberOfReviews, $offset);
					break;
			}

			if (empty($aReviewIDs)) {
				return [
					'status'   => 'success',
					'oResults' => [],
					'msg'      => wilcityAppGetLanguageFiles('youHaveNoReview')
				];
			}

			$aArgs = [
				'post_type' => 'review',
				'post__in'  => $aReviewIDs,
				'orderby'   => 'post__in'
			];
		} else {
			$aArgs = wp_parse_args($aData, [
				'post_type' => 'review'
			]);
		}

		if (isset($aData['not_post_mime_type'])) {
			$aArgs['not_post_mime_type'] = $aData['not_post_mime_type'];
		} else if ($aData['post_mime_type']) {
			$aArgs['post_mime_type'] = $aData['post_mime_type'];
		}

		$query = new WP_Query(WPML::addFilterLanguagePostArgs($aArgs));

		if (!$query->have_posts()) {
			return [
				'status'   => 'success',
				'oResults' => [],
				'msg'      => wilcityAppGetLanguageFiles('youHaveNoReview')
			];
		}

		$aReviewDetails = [];
		while ($query->have_posts()) {
			$query->the_post();
			$postParentType = get_post_type($query->post->post_parent);
			if (!isset($aReviewDetails[$postParentType])) {
				$aReviewDetails[$postParentType] = GetSettings::getOptions(
					General::getReviewKey('details', $postParentType), false, true
				);
			}

			$aReview['oReview'] = $this->getReviewItem(
				$query->post,
				$query->post->post_parent,
				$aReviewDetails[$postParentType]
			);

			$aReview['oParent'] = [
				'id'      => abs($query->post->post_parent),
				'title'   => get_the_title($query->post->post_parent),
				'tagline' => GetSettings::getTagLine($query->post->post_parent),
				'link'    => get_permalink($query->post->post_parent),
				'author'  => $this->getUserInfo(get_post_field('post_author', $query->post->post_parent)),
				'image'   => $this->getFeaturedImg($query->post->post_parent),
				'logo'    => GetSettings::getLogo($query->post->post_parent)
			];

			$aResponse[] = $aReview;
		}
		wp_reset_postdata();

		$page = $aData['paged'];
		if ($page < $query->max_num_pages) {
			$nextPage = $page + 1;
		} else {
			$nextPage = false;
		}
		return [
			'status'   => 'success',
			'total'    => abs($query->found_posts),
			'maxPages' => abs($query->max_num_pages),
			'next'     => $nextPage,
			'aResults' => $aResponse
		];
	}

	public function getReviewsViaRestAPI(WP_REST_Request $oRequest)
	{
		WPML::switchLanguageApp();
		$aData = $oRequest->get_params();

		return $this->getReviews($aData);
	}

	public function generateReviewsForShortcode($aResponse, $aData)
	{
		return $this->getReviews($aData);
	}

	public function getReviewsOfPost(WP_REST_Request $oRequest)
	{
		WPML::switchLanguageApp();
		$aData = $oRequest->get_params();
		if (!isset($aData['page'])) {
			$aData['page'] = 1;
		}
		$aData['metaKey'] = 'reviews';
		$aData['target'] = $aData['postID'];

		return App::get('ListingReview')->getData(get_post($aData['postID']), $aData);
	}

	private function responseEventDiscussion($oDiscussion)
	{
		$aResponse = [
			'status'   => 'success',
			'ID'       => abs($oDiscussion->ID),
			'postDate' => Time::getPostDate($oDiscussion->post_date),
			'msg'      => wilcityAppGetLanguageFiles('discussionUpdatedSuccessfully')
		];

		return $aResponse;
	}

	public function deleteEventDiscussion($oData)
	{
		WPML::switchLanguageApp();
		$this->discussionID = $oData->get_param('discussionID');
		$this->postID = $oData->get_param('eventID');
		$aStatus = $this->middleware(['isPostAuthor'], [
			'postID' => $this->discussionID,
			'isApp'  => 'yes'
		]);

		if ($aStatus !== true) {
			return $aStatus;
		}

		wp_delete_post($this->discussionID, true);
		$aResponse['status'] = 'success';

		return $aResponse;
	}

	public function updateEventDiscussion($oData)
	{
		WPML::switchLanguageApp();
		$this->discussionID = $oData->get_param('discussionID');
		$this->postID = $oData->get_param('eventID');
		$aStatus = $this->middleware(['isPostAuthor'], [
			'postID' => $this->discussionID,
			'isApp'  => 'yes'
		]);

		if ($aStatus !== true) {
			return $aStatus;
		}

		$content = $oData->get_param('content');
		if (empty($content)) {
			return $this->error(wilcityAppGetLanguageFiles('discussionEmpty'));
		}

		apply_filters('wilcity/wilcity-mobile-app/put-event-discussion', $this->postID, $content, $this->discussionID);

		return $this->responseEventDiscussion(get_post($this->discussionID));
	}

	public function deleteCommentOnEventDiscussion($oData)
	{
		WPML::switchLanguageApp();
		$this->oToken = $this->verifyPermanentToken();
		if (!$this->oToken) {
			return $this->tokenExpiration();
		}
		$this->oToken->getUserID();
		$this->commentID = $oData->get_param('commentID');

		$aStatus = $this->middleware(['isPostAuthor'], [
			'postID' => $this->commentID,
			'isApp'  => 'yes'
		]);

		if ($aStatus !== true) {
			return $aStatus;
		}

		wp_delete_post($this->commentID, true);

		return ['status' => 'success'];
	}

	public function updateCommentOnEventDiscussion($oData)
	{
		WPML::switchLanguageApp();
		$this->oToken = $this->verifyPermanentToken();
		if (!$this->oToken) {
			return $this->tokenExpiration();
		}
		$this->oToken->getUserID();
		$this->discussionID = $oData->get_param('discussionID');
		$this->commentID = $oData->get_param('commentID');

		$aStatus = $this->middleware(['isPostAuthor'], [
			'postID' => $this->commentID,
			'isApp'  => 'yes'
		]);

		if ($aStatus !== true) {
			return $aStatus;
		}

		$content = $oData->get_param('content');
		if (empty($content)) {
			return $this->error(wilcityAppGetLanguageFiles('discussionEmpty'));
		}

		$oComment = apply_filters('wilcity/wilcity-mobile-app/put-event-discussion', $this->discussionID, $content,
			$this->commentID);

		return $this->responseEventDiscussion($oComment);
	}

	public function postCommentOnEventDiscussion($oData)
	{
		WPML::switchLanguageApp();
		$this->oToken = $this->verifyPermanentToken();
		if (!$this->oToken) {
			return $this->tokenExpiration();
		}

		$this->oToken->getUserID();

		$this->discussionID = $oData->get_param('discussionID');

		if (get_post_type($this->discussionID) != 'event_comment') {
			return $this->error(403);
		}

		$content = $oData->get_param('content');

		if (empty($content)) {
			return $this->error(wilcityAppGetLanguageFiles('discussionEmpty'));
		}

		$oDiscussion = apply_filters('wilcity/wilcity-mobile-app/post-event-discussion', $this->discussionID, $content);
		$aResponse = $this->responseEventDiscussion($oDiscussion);
		if ($oDiscussion->post_status == 'publish') {
			$aResponse['msg'] = wilcityAppGetLanguageFiles('discussionUpdatedSuccessfully');
		} else {
			$aResponse['msg'] = wilcityAppGetLanguageFiles('discussionBeingReviewed');
		}

		return $aResponse;
	}

	public function getEventDiscussions($aData)
	{
		WPML::switchLanguageApp();
		if (!isset($aData['eventID']) || empty($aData['eventID'])) {
			return [
				'status' => 'error',
				'msg'    => esc_html__('There are no discussions', 'wilcity-mobile-app')
			];
		}
		$eventID = abs($aData['eventID']);
		$paged = isset($aData['page']) ? abs($aData['page']) : 1;
		if (!isset($aData['postsPerPage']) || $aData['postsPerPage'] == -1) {
			$postsPerPage = -1;
		} else {
			$postsPerPage = isset($aData['postsPerPage']) ? abs($aData['postsPerPage']) : 6;
		}

		$query = new WP_Query(
			$aArgs = WPML::addFilterLanguagePostArgs([
				'post_type'      => 'event_comment',
				'post_status'    => 'publish',
				'post_parent'    => $eventID,
				'paged'          => $paged,
				'posts_per_page' => $postsPerPage
			])
		);

		if (!$query->have_posts()) {
			return [
				'status' => 'error',
				'msg'    => $paged > 1 ? esc_html__('All discussions have been loaded', 'wilcity-listing-tools') :
					esc_html__('We found no discussions', 'wilcity-listing-tools')
			];
		}
		$aComments = [];

		while ($query->have_posts()) {
			$query->the_post();
			$aComments[] = $this->eventCommentItem($query->post);
		}
		wp_reset_postdata();

		$basedOnPostType = get_post_field('post_type', $eventID);
		if ($basedOnPostType == 'event_comment') {
			$authorID = get_post_field('post_author', $eventID);
			$displayName = User::getField('display_name', $authorID);
			$repliedOn = sprintf(esc_html__('Replied on %s discussion', 'wiloke-mobile-app'), $displayName);
		} else {
			$title = get_the_title($eventID);
			$repliedOn = sprintf(esc_html__('All discussions on %s', 'wiloke-mobile-app'), $title);
		}

		return [
			'status'           => 'success',
			'discussionsOn'    => $repliedOn,
			'countDiscussions' => GetSettings::countNumberOfChildrenReviews($eventID),
			'next'             => $query->max_num_pages > $paged ? $paged + 1 : false,
			'oResults'         => $aComments
		];
	}

	public function postEventDiscussion($oData)
	{
		WPML::switchLanguageApp();
		$this->oToken = $this->verifyPermanentToken();
		if (!$this->oToken) {
			return $this->tokenExpiration();
		}

		$this->oToken->getUserID();

		$this->postID = $oData->get_param('eventID');
		$aStatus = $this->middleware(['isPublishedPost'], [
			'postID' => $this->postID,
			'isApp'  => 'yes'
		]);

		if ($aStatus !== true) {
			return $aStatus;
		}

		$content = $oData->get_param('content');
		if (empty($content)) {
			return $this->error(wilcityAppGetLanguageFiles('discussionEmpty'));
		}

		$oDiscussion = apply_filters('wilcity/wilcity-mobile-app/post-event-discussion', $this->postID, $content);
		$aResponse = $this->responseEventDiscussion($oDiscussion);
		if ($oDiscussion->post_status == 'publish') {
			$aResponse['msg'] = wilcityAppGetLanguageFiles('discussionUpdatedSuccessfully');
		} else {
			$aResponse['msg'] = wilcityAppGetLanguageFiles('discussionBeingReviewed');
		}

		return $aResponse;
	}

	public function updateEventDiscussionLiked($oData)
	{
		WPML::switchLanguageApp();
		$this->oToken = $this->verifyPermanentToken();
		if (!$this->oToken) {
			return $this->tokenExpiration();
		}

		$this->oToken->getUserID();
		$this->discussionID = $oData->get_param('discussionID');

		$parent = wp_get_post_parent_id($this->discussionID);
		if (get_post_type($parent) != 'event') {
			return $this->error(403);
		}

		$aStatus
			= apply_filters('wilcity/wilcity-mobile-app/like-a-review', $this->discussionID, $this->oToken->userID);

		return $aStatus;
	}

	private function updateCountShares($postID)
	{
		$aStatus = $this->middleware(['isPublishedPost'], [
			'isApp'  => true,
			'postID' => $postID
		]);

		if ($aStatus !== true) {
			return $aStatus;
		}

		$total = GetSettings::getPostMeta($postID, 'count_shared');
		$total = empty($total) ? 0 : abs($total);
		$total = $total + 1;
		SetSettings::setPostMeta($postID, 'count_shared', $total);

		return [
			'status'      => 'success',
			'countShares' => abs($total)
		];
	}

	public function updateDiscussionCountShares($oData)
	{
		WPML::switchLanguageApp();
		$this->discussionID = $oData->get_param('discussionID');
		$eventID = wp_get_post_parent_id($this->discussionID);

		$aStatus = $this->middleware(['isPostType'], [
			'isApp'    => 'yes',
			'postID'   => $eventID,
			'postType' => 'event'
		]);

		if ($aStatus !== true) {
			return $aStatus;
		}

		return $this->updateCountShares($this->discussionID);
	}

	public function updateReviewCountShares($oData)
	{
		WPML::switchLanguageApp();
		$this->reviewID = $oData->get_param('reviewID');

		$aStatus = $this->middleware(['isPostType'], [
			'isApp'    => 'yes',
			'postID'   => $this->reviewID,
			'postType' => 'review'
		]);

		if ($aStatus !== true) {
			return $aStatus;
		}

		return $this->updateCountShares($this->reviewID);
	}

	public function getDiscussions($aData)
	{
		WPML::switchLanguageApp();
		$page = isset($aData['page']) ? abs($aData['page']) : 1;
		if (isset($aData['postsPerPage'])) {
			$postsPerPage = $aData['postsPerPage'] == -1 ? -1 : abs($aData['postsPerPage']);
		} else {
			$postsPerPage = 10;
		}

		$query = new WP_Query(WPML::addFilterLanguagePostArgs([
			'post_type'      => 'review',
			'post_status'    => 'publish',
			'post_parent'    => $aData['reviewID'],
			'page'           => $page,
			'posts_per_page' => $postsPerPage
		]));

		if ($query->have_posts()) {
			global $post;

			$aResponse['total'] = $query->found_posts;
			$aResponse['maxPages'] = $query->max_num_pages;

			if ($page < $query->max_num_pages) {
				$aResponse['next'] = $page + 1;
			} else {
				$aResponse['next'] = false;
			}

			while ($query->have_posts()) {
				$query->the_post();
				$aDiscussion['ID'] = abs($post->ID);
				$aDiscussion['postTitle'] = get_the_title($post->ID);
				$aDiscussion['postContent'] = strip_tags(get_post_field('post_content', $post->ID));
				$aDiscussion['postStatus'] = $post->post_status;
				$aDiscussion['postDate'] = get_the_date(get_option('date_format'), $post->ID);
				$aDiscussion['oUserInfo'] = $this->getUserInfo($post->post_author);
				$aResponse['aDiscussion'][] = $aDiscussion;
			}

			wp_reset_postdata();
		} else {
			$aResponse = false;
		}

		if (empty($aResponse)) {
			return $this->error(wilcityAppStripTags('noDiscussion'));
		}

		return [
			'status'   => 'success',
			'oResults' => $aResponse
		];
	}

	private function responseDiscussionInfo($discussionID)
	{
		$reviewID = wp_get_post_parent_id($discussionID);

		return [
			'oUserInfo'   => [
				'userID'      => abs($this->oToken->userID),
				'avatar'      => User::getAvatar($this->oToken->userID),
				'displayName' => User::getField('display_name', $this->oToken->userID),
				'position'    => User::getField('position', $this->oToken->userID)
			],
			'ID'          => abs($discussionID),
			'postTitle'   => get_the_title($discussionID),
			'postContent' => get_post_field('post_content', $discussionID),
			'postDate'    => Time::getPostDate(get_post_field('post_date', $discussionID)),
			'oReview'     => [
				'countComments' => ReviewMetaModel::countDiscussion($reviewID)
			]
		];
	}

	public function postReviewDiscussion($oData)
	{
		WPML::switchLanguageApp();
		$this->oToken = $this->verifyPermanentToken();
		if (!$this->oToken) {
			return $this->tokenExpiration();
		}

		$this->oToken->getUserID();
		$this->reviewID = $oData->get_param('reviewID');

		$aData = $this->parsePost();

		$aStatus = $this->middleware(['isReviewExists'], [
			'reviewID' => $this->reviewID,
			'isApp'    => 'yes'
		]);

		if ($aStatus !== true) {
			return $aStatus;
		}

		$discussionID
			= apply_filters('wilcity/wilcity-mobile-app/post-review-discussion', $this->reviewID, $aData['content']);
		$aResponse = $this->responseDiscussionInfo($discussionID);
		$aResponse['status'] = 'success';

		return $aResponse;
	}

	public function updateReviewDiscussion($oData)
	{
		WPML::switchLanguageApp();
		$this->oToken = $this->verifyPermanentToken();
		if (!$this->oToken) {
			return $this->tokenExpiration();
		}

		$this->oToken->getUserID();
		$this->reviewID = $oData->get_param('reviewID');
		$this->discussionID = $oData->get_param('discussionID');
		$content = $oData->get_param('content');

		$aStatus = $this->middleware(['isPostAuthor'], [
			'isApp'         => 'yes',
			'postID'        => $this->discussionID,
			'passedIfAdmin' => true
		]);

		if (empty($content)) {
			$this->error('discussionContentRequired');
		}

		if ($aStatus !== true) {
			return $aStatus;
		}

		$aStatus
			= apply_filters('wilcity/wilcity-mobile-app/put-review-discussion', $this->discussionID, $content);
		$aStatus['status'] = 'success';

		return $aStatus;
	}

	public function deleteReviewDiscussion($oData)
	{
		WPML::switchLanguageApp();
		$this->oToken = $this->verifyPermanentToken();
		if (!$this->oToken) {
			return $this->tokenExpiration();
		}

		$this->oToken->getUserID();
		$this->reviewID = $oData->get_param('reviewID');
		$this->discussionID = $oData->get_param('discussionID');

		$aStatus = $this->middleware(['isPostAuthor'], [
			'postID' => $this->discussionID,
			'isApp'  => 'yes'
		]);

		if ($aStatus !== true) {
			return $aStatus;
		}

		wp_delete_post($this->discussionID, true);

		return [
			'status'           => 'success',
			'countDiscussions' => abs(ReviewModel::countDiscussion($this->reviewID))
		];
	}

	public function updateLikeReview($oData)
	{
		WPML::switchLanguageApp();
		$this->oToken = $this->verifyPermanentToken();
		if (!$this->oToken) {
			return $this->tokenExpiration();
		}

		$this->oToken->getUserID();
		$this->reviewID = $oData->get_param('reviewID');

		$this->middleware(['isReviewExists'], [
			'reviewID' => $this->reviewID,
			'isApp'    => 'yes'
		]);

		$aStatus = apply_filters('wilcity/wilcity-mobile-app/like-a-review', $this->reviewID, $this->oToken->userID);

		return $aStatus;
	}

	private function validateReview()
	{
		$this->oToken = $this->verifyPermanentToken();
		if (!$this->oToken) {
			return $this->tokenExpiration();
		}

		$this->oToken->getUserID();
		$this->aReviewData = $this->parsePost();

		$aStatus = $this->middleware([
			'isUserLoggedIn',
			'isPublishedPost',
			'verifyReview',
			'isReviewEnabled',
			'isAccountConfirmed'
		], [
			'postID' => $this->postID,
			'userID' => $this->oToken->userID,
			'aData'  => $this->aReviewData,
			'isApp'  => 'yes'
		]);

		if ($aStatus !== true) {
			return $aStatus;
		}

		if (!empty($this->reviewID)) {
			$aStatus = $this->middleware(['isOwnerOfReview'], [
				'reviewID'       => $this->reviewID,
				'reviewAuthorID' => $this->oToken->userID,
				'isApp'          => 'yes'
			]);
		}

		if ($aStatus !== true) {
			return $aStatus;
		}

		return true;
	}

	private function afterSubmittingReview($aStatus, $isDelReview = false)
	{
		$postType = get_post_type($this->postID);
		$aDetails = GetSettings::getOptions(General::getReviewKey('details', $postType), false, true);

		if ($aStatus['status'] == 'success') {
			$aReturn['msg']
				= $aStatus['reviewStatus'] == 'publish' ? wilcityAppGetLanguageFiles('reviewSubmittedSuccessfully') :
				wilcityAppGetLanguageFiles('reviewBeingReviewed');

			if (!$isDelReview) {
				$aStatus['oItem'] = $this->getReviewItem(get_post($aStatus['reviewID']), $this->postID, $aDetails);
			}

			$averageReviews = GetSettings::getPostMeta($this->postID, 'average_reviews');
			$aGeneralReviewsInfo = $this->getGeneralReviewInfo($this->postID, $postType);

			$aStatus['oGeneral'] = [
				'mode'    => abs(GetSettings::getOptions(General::getReviewKey('mode', $postType), false, true)),
				'average' => floatval($averageReviews),
				'quality' => ReviewMetaModel::getReviewQualityString($averageReviews, $postType)
			];

			$aStatus['oGeneral'] = array_merge($aStatus['oGeneral'], $aGeneralReviewsInfo);
		}

		if (!empty($this->reviewID)) {
			$aStatus['reviewID'] = $this->reviewID;
		}

		return $aStatus;
	}

	private function returnReviewStatus()
	{
		$aResponse = ['status' => 'success'];
		$postType = get_post_type($this->postID);
		$aResponse['oGeneral'] = $this->getGeneralReviewInfo($this->postID, $postType);
		if (!$this->isDeleteReview) {
			$aDetails = GetSettings::getOptions(General::getReviewKey('details', $postType), false, true);
			$aResponse['oItem'] = $this->getReviewItem(get_post($this->reviewID), $this->postID, $aDetails);
		}

		if (!empty($this->reviewID)) {
			$aResponse['reviewID'] = abs($this->reviewID);
		}

		return $aResponse;
	}

	private function handleSubmitReview()
	{
		foreach ($this->aReviewData as $key => $value) {
			$this->aReviewData[$key] = stripslashes($value);
		}

		$aGallery = [];
		if (isset($this->aReviewData['gallery']) && !empty($this->aReviewData['gallery'])) {
			$this->aReviewData['gallery'] = json_decode($this->aReviewData['gallery'], true);

			if (is_array($this->aReviewData['gallery'])) {
				$aUserRoles = User::getField('roles', $this->oToken->userID);
				foreach ($this->aReviewData['gallery'] as $galleryKey => $galleryID) {
					if (!in_array('administrator', (array)$aUserRoles) &&
						get_post_field('post_author', $galleryID) != $this->oToken->userID) {
						unset($this->aReviewData['gallery'][$galleryKey]);
					} else {
						$aGallery[] = [
							'id'  => $galleryID,
							'src' => wp_get_attachment_image_url($galleryID, 'full')
						];
					}
				}
			} else {
				$this->aReviewData['gallery'] = [];
			}
		}

		$aReviewDetailKeys = WebReviewController::getDetailsSettings(get_post_type($this->postID));
		$this->aReviewData['details'] = [];
		if (!empty($aReviewDetailKeys)) {
			foreach ($aReviewDetailKeys as $aDetail) {
				$score = isset($this->aReviewData[$aDetail['key']]) ? absint($this->aReviewData[$aDetail['key']]) : 5;
				if (empty($score)) {
					continue;
				}

				$this->aReviewData['details'][$aDetail['key']]['value'] = $score;
				unset($this->aReviewData[$aDetail['key']]);
			}
		}

		$aGallery = array_merge($aGallery, $this->oRestData->get_file_params());
		if (!empty($aGallery)) {
			$this->aReviewData['gallery'] = $aGallery;
		}

		$this->aReviewData['isFakeGallery'] = true;
		$aAddReviewStatus = apply_filters(
			'wilcity/wilcity-mobile-app/submit-review',
			$this->postID,
			$this->aReviewData,
			$this->reviewID,
			true
		);

		if ($aAddReviewStatus['status'] == 'error') {
			return $aAddReviewStatus;
		}

		$this->reviewID = abs($aAddReviewStatus['reviewID']);

		$post = get_post($this->reviewID);
		$aReview = App::get('ListingReview')->getReview($post, [], true);
		$aResponse['oGeneral'] = App::get('ListingReview')->getListingReviewGeneral($post);
		$aResponse['oItem'] = $aReview;

		$aResponse['status'] = 'success';
		$reviewStatus = get_post_status($this->reviewID);

		if ($reviewStatus === 'publish') {
			$msg = wilcityAppStripTags('reviewSubmittedSuccessfully');
		} else {
			$msg = wilcityAppStripTags('reviewBeingReviewed');
		}
		$aResponse = array_merge($aResponse, ['msg' => $msg]);

		return $aResponse;
	}

	public function deleteReview(WP_REST_Request $request)
	{
		WPML::switchLanguageApp();
		$this->oToken = $this->verifyPermanentToken();
		if (!$this->oToken) {
			return $this->tokenExpiration();
		}

		$this->oToken->getUserID();
		$this->reviewID = abs($request->get_param('reviewID'));
		$this->postID = $request->get_param('postID');
		$this->aReviewData = $this->parsePost();

		$aStatus = $this->middleware(['isOwnerOfReview'], [
			'reviewID'       => $this->reviewID,
			'reviewAuthorID' => $this->oToken->userID,
			'isApp'          => 'yes'
		]);

		if ($aStatus !== true) {
			return $aStatus;
		}

		wp_delete_post($request->get_param('reviewID'), true);
		$this->isDeleteReview = true;

		$aResponse['oGeneral'] = App::get('ListingReview')->getListingReviewGeneral(get_post($this->postID));
		$aResponse['reviewID'] = $this->reviewID;
		$aResponse['status'] = 'success';

		return $aResponse;
	}

	public function updateReview(WP_REST_Request $request)
	{
		WPML::switchLanguageApp();
		$this->reviewID = abs($request->get_param('reviewID'));
		$this->postID = abs($request->get_param('postID'));
		$this->oRestData = $request;
		//        $aValidated     = $this->validateReview();
		//
		//        if ($aValidated !== true) {
		//            return $aValidated;
		//        }

		//        if (ReviewModel::isUserReviewed($this->postID)) {
		//            return $this->error('youLeftAReviewBefore');
		//        }

		$this->aReviewData = $request->get_params();

		return $this->handleSubmitReview();
	}

	public function postReview(WP_REST_Request $request)
	{
		WPML::switchLanguageApp();
		$this->oToken = $this->verifyPermanentToken();
		if (!$this->oToken) {
			return $this->tokenExpiration();
		}
		$this->oToken->getUserID();

		$this->oRestData = $request;
		$this->postID = abs($request->get_param('postID'));

		$aResponse = $this->middleware(
			[
				'isAccountConfirmed',
				'verifyReview'
			],
			[
				'userID' => $this->oToken->userID,
				'aData'  => $this->oRestData->get_params()
			]
		);

		if ($aResponse['status'] === 'error') {
			return $aResponse;
		}

		if (ReviewModel::getReviewID($this->postID, $this->userID)) {
			return [
				'status' => 'error',
				'msg'    => wilcityAppGetLanguageFiles('youLeftAReviewBefore')
			];
		}

		$this->aReviewData = $request->get_params();
		$aResponse = $this->handleSubmitReview();

		if (strpos($request->get_route(), '/v2') !== false) {
			$postType = get_post_type($this->postID);
			$aDetails = GetSettings::getOptions(General::getReviewKey('details', $postType), false, true);
			$aResponse['oItem'] = $this->getReviewItem(
				get_post($this->reviewID),
				$this->postID,
				$aDetails
			);
		}

		return $aResponse;
	}

	public function testReview(WP_REST_Request $request)
	{

		$this->oRestData = $request->get_params();

		$this->reviewID = abs($request->get_param('reviewID'));
		$this->postID = abs($request->get_param('postID'));
		$this->aReviewData = $this->parsePost();

		$aReviewDetailKeys = WebReviewController::getDetailsSettings(get_post_type($this->postID));
		$this->aReviewData['details'] = [];
		if (!empty($aReviewDetailKeys)) {
			foreach ($aReviewDetailKeys as $aDetail) {
				$score = isset($this->aReviewData[$aDetail['key']]) ? absint($this->aReviewData[$aDetail['key']]) : 5;
				if (empty($score)) {
					continue;
				}

				$this->aReviewData['details'][$aDetail['key']]['value'] = $score;
				unset($this->aReviewData[$aDetail['key']]);
			}
		}

		return $this->aReviewData;
	}

	public function getReview($oData)
	{

	}

	public function getReviewFields($oData)
	{
		WPML::switchLanguageApp();
		$aData = $oData->get_params();
		if (!isset($aData['postID']) || empty($aData['postID'])) {
			return [
				'status' => 'error'
			];
		}

		$postType = get_post_type($aData['postID']);

		if (!WebReviewController::isEnabledReview($postType)) {
			return [
				'status' => 'error'
			];
		}

		$aFields = WebReviewController::getDetailsSettings($postType);
		if (empty($aFields)) {
			return [
				'status' => 'error'
			];
		}
		$aReturn = [];
		foreach ($aFields as $aField) {
			if (!isset($aField['key']) || empty($aField['key'])) {
				continue;
			}

			$aReturn[trim($aField['key'])] = [
				'type' => 'inputRange',
				'name' => $aField['name']
			];
		}

		$aReturn['title'] = [
			'placeholder' => wilcityAppGetLanguageFiles('reviewTitle'),
			'type'        => 'inputText',
			'required'    => true
		];

		$aReturn['content'] = [
			'placeholder' => wilcityAppGetLanguageFiles('yourReview'),
			'type'        => 'inputText',
			'required'    => true,
			'multiline'   => true
		];

		if (WebReviewController::isEnableGallery($postType) !== 'disable') {
			$aReturn['gallery'] = [
				'type' => 'gallery'
			];
		}

		return [
			'status'  => 'success',
			'oFields' => $aReturn
		];
	}

	/**
	 * @param WP_REST_Request $oRequest
	 * @return array
	 */
	public function getMyReviews(WP_REST_Request $oRequest)
	{
		WPML::switchLanguageApp();
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}

		$oToken->getUserID();

		$aParams = $oRequest->get_params();
		if (isset($aParams['postsPerPage']) && $aParams['postsPerPage'] <= 100) {
			$aData['number_of_reviews'] = $aParams['postsPerPage'];
		}
		$aData['author'] = $oToken->userID;
		if (isset($aParams['page'])) {
			unset($aData['page']);
			$aData['paged'] = $aParams['page'];
		}

		$aData['not_post_mime_type'] = 'discussion';

		return $this->getReviews($aData);
	}
}
