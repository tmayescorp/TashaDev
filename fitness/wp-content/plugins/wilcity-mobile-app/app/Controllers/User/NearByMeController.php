<?php

namespace WILCITY_APP\Controllers\User;

use WILCITY_APP\Controllers\JsonSkeleton;
use WILCITY_APP\Controllers\VerifyToken;
use WILCITY_APP\Helpers\App;
use WilokeListingTools\Controllers\SearchFormController;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\WPML;
use WilokeThemeOptions;
use WP_Query;
use WP_REST_Request;
use WP_User;

class NearByMeController
{
	private $oToken;

	use JsonSkeleton;
	use VerifyToken;

	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'nearbyme', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getNearByMe'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'me/nearbyme', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getNearByMe'],
				'permission_callback' => '__return_true'
			]);
		});
	}


	public function getNearByMe(WP_REST_Request $request): array
	{
		WPML::switchLanguageApp();
		$aQuery = wp_parse_args(
			$request->get_params(),
			[
				'postType'       => General::getPostTypeKeys(false),
				'page'           => 1,
				'posts_per_page' => 10,
			]
		);


		if (isset($aQuery['lat']) && isset($aQuery['lng'])) {
			$radius = WilokeThemeOptions::getOptionDetail('default_radius');
			if (empty($radius)) {
				$radius = 10;
			}

			$aQuery['oAddress'] = [
				'lat'         => trim($aQuery['lat']),
				'lng'         => trim($aQuery['lng']),
				'radius'      => absint($radius),
				'unit'        => isset($aData['unit']) ? $aQuery['unit'] : 'km',
				'isMobileApp' => true
			];

			unset($aQuery['lat']);
			unset($aQuery['lng']);
		} else {
			return [
				'status' => 'error',
				'msg'    => 'noDataFound'
			];
		}
		$aArgs = SearchFormController::buildQueryArgs($aQuery);

		$query = new WP_Query(WPML::addFilterLanguagePostArgs($aArgs));
		if ($query->have_posts()) {
			$aPosts = [];
			while ($query->have_posts()) {
				$query->the_post();
				$aPosts[] = $this->listingSkeleton($query->post);
			}

			$aReturn['status'] = 'success';
			if ($aQuery['page'] < $query->max_num_pages) {
				$aReturn['next'] = $aQuery['page'] + 1;
			} else {
				$aReturn['next'] = false;
			}
			$aReturn['oResults'] = $aPosts;

			return $aReturn;
		} else {
			return [
				'status' => 'error',
				'msg'    => 'noDataFound'
			];
		}
	}
}
