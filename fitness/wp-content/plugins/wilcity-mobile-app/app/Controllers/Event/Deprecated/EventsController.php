<?php

namespace WILCITY_APP\Controllers\Deprecated;

use WILCITY_APP\Controllers\JsonSkeleton;
use WilokeListingTools\Controllers\EventController;
use WilokeListingTools\Controllers\SearchFormController;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\WPML;
use WilokeThemeOptions;

class EventsController
{
	private $postType = 'event';
	use JsonSkeleton;

	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/v2', 'events', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getEvents']
			]);
		});
	}

	public function getEvents($aData)
	{
		WPML::switchLanguageApp();
		$page = isset($aData['page']) ? abs($aData['page']) : 1;
		$aQuery = $aData;
		$aQuery['postType'] = $this->postType;
		$aQuery['page'] = $page;
		if (!isset($aData['postsPerPage']) || (abs($aData['postsPerPage']) > 100)) {
			$aData['postsPerPage'] = 18;
		}

		if (isset($aData['lat']) && !empty($aData['lat']) && isset($aData['lng']) && !empty($aData['lng'])) {

			$radius = WilokeThemeOptions::getOptionDetail('default_radius');
			if (empty($radius)) {
				$radius = 10;
			}

			$aQuery['oAddress'] = [
				'lat'         => $aData['lat'],
				'lng'         => $aData['lng'],
				'radius'      => absint($radius),
				'unit'        => $aData['unit'] ?? 'km',
				'isMobileApp' => true
			];
		} else {
			$aQuery['orderby'] = 'wilcity_event_starts_on';
			$aQuery['order'] = 'ASC';
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
		$query = new \WP_Query(WPML::addFilterLanguagePostArgs($aArgs));

		if ($query->have_posts()) {
			$aPosts = [];
			while ($query->have_posts()) {
				$query->the_post();
				$aPost = $this->listingSkeleton($query->post);
				$aPosts[] = $aPost;
			}

			$aReturn['status'] = 'success';
			if ($page < $query->max_num_pages) {
				$aReturn['next'] = $nextPage = $page + 1;
			} else {
				$aReturn['next'] = false;
			}
			$aReturn['oResults'] = $aPosts;
			$aReturn = apply_filters('wilcity/wilcity-mobile-app/filter/get-listings', $aReturn, $aData);

			return $aReturn;
		} else {
			return [
				'status' => 'error',
				'msg'    => esc_html__('No posts found', 'wilcity-mobile-app')
			];
		}
	}
}
