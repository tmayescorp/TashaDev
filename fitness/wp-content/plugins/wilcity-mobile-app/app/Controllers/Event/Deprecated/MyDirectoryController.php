<?php

namespace WILCITY_APP\Controllers\Deprecated;

use WILCITY_APP\Controllers\JsonSkeleton;
use WILCITY_APP\Controllers\VerifyToken;
use WilokeListingTools\Controllers\DashboardController;
use WilokeListingTools\Controllers\EventController;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Framework\Helpers\WPML;
use WP_Query;

class MyDirectoryController
{
	use JsonSkeleton;
	use VerifyToken;

	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/v2', '/get-my-listings', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getMyListings']
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/get-listing-status', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getPostStatus']
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/get-listing-types', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getListingTypes']
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/get-event-status', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getEventStatus']
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/get-my-events', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getMyEvents']
			]);
		});
	}

	public function getEventStatus()
	{
		WPML::switchLanguageApp();
		$aEventStatus = EventController::getEventStatuses(false);
		foreach ($aEventStatus as $order => $aInfo) {
			$aEventStatus[$order]['total'] = DashboardController::countPostStatus($aInfo['post_status'], 2);
		}

		return [
			'oResults' => array_values($aEventStatus),
			'status'   => 'success'
		];
	}

	public function getListingTypes()
	{
		WPML::switchLanguageApp();
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}
		$oToken->getUserID();

		$aCustomPostTypes = GetSettings::getFrontendPostTypes(false, false);

		foreach ($aCustomPostTypes as $order => $aPostType) {
			$aCount = User::countUserPosts($oToken->userID, $aPostType['key']);
			$aCustomPostTypes[$order]['total'] = abs($aCount['total']);
		}

		return [
			'oResults' => array_values($aCustomPostTypes),
			'status'   => 'success'
		];
	}

	public function getPostStatus()
	{
		WPML::switchLanguageApp();
		$aTranslation = wilcityAppGetLanguageFiles();

		$aPostStatus = $aTranslation['aPostStatus'];
		$aPostStatus = apply_filters('wilcity/dashboard/general-listing-status-statistic', $aPostStatus);

		foreach ($aPostStatus as $order => $aInfo) {
			$aPostStatus[$order]['total'] = DashboardController::countPostStatus($aInfo['post_status']);
		}

		return [
			'status'   => 'success',
			'oResults' => $aPostStatus
		];
	}

	protected function getListings($aArgs)
	{
		$query = new WP_Query(WPML::addFilterLanguagePostArgs($aArgs));

		if (!$query->have_posts()) {
			wp_reset_postdata();

			return [
				'status' => 'error',
				'msg'    => 'doNotHaveAnyArticleYet'
			];
		}

		$aListings = [];

		while ($query->have_posts()) {
			$query->the_post();
			$aListings[] = $this->listingSkeleton($query->post);
		}

		if ($aArgs['paged'] < $query->max_num_pages) {
			$next = $aArgs['paged'] + 1;
		} else {
			$next = false;
		}
		wp_reset_postdata();

		return [
			'status'   => 'success',
			'oResults' => $aListings,
			'maxPages' => $query->max_num_pages,
			'next'     => $next
		];
	}

	public function getMyListings()
	{
		WPML::switchLanguageApp();
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}

		$oToken->getUserID();

		$aListings = General::getPostTypeKeys(false, false);
		if (!isset($_GET['postType']) ||
			(!empty($_GET['postType']) && $_GET['postType'] != 'all' && !in_array($_GET['postType'], $aListings))) {
			return [
				'status' => 'error',
				'msg'    => 403
			];
		}

		if (empty($_GET['postType']) || $_GET['postType'] == 'all') {
			$postType = $aListings;
		} else {
			$postType = $_GET['postType'];
		}

		$postStatus = isset($_GET['postStatus']) ? $_GET['postStatus'] : 'publish';
		$page = isset($_GET['page']) ? $_GET['page'] : 1;
		$postsPerPage = isset($_GET['postsPerPage']) ? $_GET['postsPerPage'] : 10;
		$postsPerPage = $postsPerPage > 100 ? 10 : $postsPerPage;

		if ($postType == 'all' || empty($postType)) {
			$postType = GetSettings::getFrontendPostTypes(true);
			$postType = array_filter($postType, function ($type) {
				return $type != 'key';
			});
		}

		return $this->getListings([
			'post_type'      => $postType,
			'post_status'    => $postStatus,
			'posts_per_page' => $postsPerPage,
			'paged'          => $page,
			'author__in'     => [$oToken->userID]
		]);

	}

	public function getMyEvents()
	{
		WPML::switchLanguageApp();
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}

		$oToken->getUserID();

		$postStatus = isset($_GET['postStatus']) ? $_GET['postStatus'] : 'publish';
		$page = isset($_GET['page']) ? $_GET['page'] : 1;
		$postsPerPage = isset($_GET['postsPerPage']) ? $_GET['postsPerPage'] : 10;
		$postsPerPage = $postsPerPage > 100 ? 10 : $postsPerPage;

		return $this->getListings([
			'post_type'      => 'event',
			'post_status'    => $postStatus,
			'posts_per_page' => $postsPerPage,
			'paged'          => $page,
			'author'         => $oToken->userID
		]);
	}
}
