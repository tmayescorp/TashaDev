<?php

namespace WILCITY_APP\Controllers\Deprecated;

use WILCITY_APP\Controllers\JsonSkeleton;
use WilokeListingTools\Controllers\SearchFormController;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\WPML;

class NearByMe
{
	use JsonSkeleton;

	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/v2', '/nearbyme', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getNearByMe'],
				'permission_callback' => '__return_true',
			]);
		});
	}

	public function getNearByMe($aData)
	{
		WPML::switchLanguageApp();
		$aQuery = $aData;
		$aQuery['postType'] = isset($aData['postType']) ? $aData['postType'] : General::getPostTypeKeys(false, false);
		$aQuery['page'] = isset($aData['page']) ? abs($aData['page']) : 1;

		if (!isset($aData['postsPerPage']) || (abs($aData['postsPerPage']) > 100)) {
			$aData['postsPerPage'] = 18;
		}

		if (isset($aData['lat']) && isset($aData['lng'])) {

			$radius = \WilokeThemeOptions::getOptionDetail('default_radius');
			if (empty($radius)) {
				$radius = 10;
			}

			$aQuery['oAddress'] = [
				'lat'    => trim($aData['lat']),
				'lng'    => trim($aData['lng']),
				'radius' => absint($radius),
				'unit'   => isset($aData['unit']) ? $aData['unit'] : 'km'
			];

		} else {
			return [
				'status' => 'error',
				'msg'    => esc_html__('No Posts Found', 'wilcity-mobile-app')
			];
		}

		$aArgs = SearchFormController::buildQueryArgs($aQuery);
		$query = new \WP_Query(WPML::addFilterLanguagePostArgs($aArgs));
		if ($query->have_posts()) {
			$aPosts = [];
			while ($query->have_posts()) {
				$query->the_post();
				$aPosts[] = $this->listingSkeleton($query->post);
			}
			wp_reset_postdata();
			$aReturn['status'] = 'success';
			if ($aQuery['page'] < $query->max_num_pages) {
				$aReturn['next'] = $nextPage = $aQuery['page'] + 1;
			} else {
				$aReturn['next'] = false;
			}
			$aReturn['oResults'] = $aPosts;
			$aReturn['args'] =  $aArgs;

			return $aReturn;
		} else {
			return [
				'status' => 'error',
				'msg'    => esc_html__('No Posts Found', 'wilcity-mobile-app')
			];
		}
	}
}
