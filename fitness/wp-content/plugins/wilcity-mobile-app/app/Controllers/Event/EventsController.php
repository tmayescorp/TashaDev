<?php

namespace WILCITY_APP\Controllers\Event;

use WILCITY_APP\Controllers\JsonSkeleton;
use WILCITY_APP\Controllers\VerifyToken;
use WilokeListingTools\Framework\Helpers\WPML;
use WilokeListingTools\Controllers\DashboardController;
use WilokeListingTools\Controllers\EventController;
use WilokeListingTools\Controllers\SearchFormController;
use WilokeThemeOptions;
use WP_Query;
use WP_REST_Request;

class EventsController
{
	private $postType = 'event';
	use JsonSkeleton;
	use VerifyToken;

	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'events', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getEvents']
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'get-event-status', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getEventStatus']
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'get-my-events', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getMyEvents']
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'me/events',
				[
					'methods'             => 'GET',
					'permission_callback' => '__return_true',
					'callback'            => [$this, 'getMyEvents']
				]
			);
		});
	}

	public function getMyEvents(WP_REST_Request $request)
	{
		WPML::switchLanguageApp();
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}

		$oToken->getUserID();

		$aArgs = wp_parse_args(
			[
				'post_status'    => $request->get_param('postStatus'),
				'paged'          => $request->get_param('page'),
				'posts_per_page' => $request->get_param('postsPerPage'),
				'author'         => $oToken->userID,
				'post_type'      => 'event'
			],
			[
				'post_type'      => 'event',
				'post_status'    => 'publish',
				'posts_per_page' => 10,
				'paged'          => 1
			]
		);
		$query = new WP_Query(WPML::addFilterLanguagePostArgs($aArgs));
		if (!$query->have_posts()) {
			return [
				'status' => 'error',
				'msg'    => 'noDataFound'
			];
		}

		$aEvents = [];
		while ($query->have_posts()) {
			$query->the_post();
			$aEvents[] = $this->listingSkeleton($query->post);
		}

		$aReturn['status'] = 'success';
		if ($aArgs['page'] < $query->max_num_pages) {
			$aReturn['next'] = $nextPage = $aArgs['page'] + 1;
		} else {
			$aReturn['next'] = false;
		}
		$aReturn['oResults'] = $aEvents;
		$aReturn = apply_filters(
			'wilcity/wilcity-mobile-app/filter/get-listings',
			$aReturn,
			$request->get_params()
		);

		return $aReturn;
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

	public function getEvents(WP_REST_Request $oRequest)
	{
		WPML::switchLanguageApp();
		$aData = $oRequest->get_params();
		$aData = is_array($aData) ? $aData : json_decode($aData, true);
		$page = isset($aData['page']) ? absint($aData['page']) : 1;
		$aQuery = $aData;
		$aQuery['postType'] = $this->postType;
		$aQuery['page'] = $page;
		if (!isset($aData['postsPerPage']) || (absint($aData['postsPerPage']) > 100)) {
			$aData['postsPerPage'] = 18;
		}

		if (isset($aData['lat']) && !empty($aData['lat']) && isset($aData['lng']) && !empty($aData['lng'])) {
			$radius = WilokeThemeOptions::getOptionDetail('default_radius');
			if (empty($radius)) {
				$radius = 10;
			}

			$aQuery['oAddress'] = [
				'isMobileApp' => true,
				'lat'         => $aData['lat'],
				'lng'         => $aData['lng'],
				'radius'      => absint($radius),
				'unit'        => $aData['unit'] ?? 'km',
			];
		}

		$aArgs = SearchFormController::buildQueryArgs($aQuery);

		if (isset($aData['date_range']) && !empty($aData['date_range'])) {
			$aParseDateRange = json_decode($aData['date_range'], true);
			if (!empty($aParseDateRange['to']) && !empty($aParseDateRange['from'])) {
				$aArgs['date_range'] = [
					'from' => $aParseDateRange['from'],
					'to'   => $aParseDateRange['to']
				];
			}
		}
		$query = new WP_Query(WPML::addFilterLanguagePostArgs($aArgs));

		$aData = [];
		if (!empty($content)) {
			$aData = json_decode($content, true);
		}
		if ($query->have_posts()) {
			$aPosts = [];
			while ($query->have_posts()) {
				$query->the_post();
				$aPost = $this->listingSkeleton($query->post);
				if (isset($aPost['oCalendar']) && empty($aPost['oCalendar'])) {
					unset($aPost['oCalendar']);
				}
				$aPosts[] = $aPost;
			}

			$aReturn['status'] = 'success';
			if ($page < $query->max_num_pages) {
				$aReturn['next'] = absint($page) + 1;
			} else {
				$aReturn['next'] = false;
			}
			$aReturn['totalPage'] = $query->max_num_pages;
			$aReturn['oResults'] = $aPosts;
			$aReturn = apply_filters('wilcity/wilcity-mobile-app/filter/get-listings', $aReturn, $aData);
			return $aReturn;
		} else {
			return [
				'status'   => 'success',
				'oResults' => []
			];
		}
	}
}
