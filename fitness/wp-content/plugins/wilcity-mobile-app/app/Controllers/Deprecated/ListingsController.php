<?php

namespace WILCITY_APP\Controllers\Deprecated;

use WILCITY_APP\Controllers\JsonSkeleton;
use WilokeListingTools\Controllers\SearchFormController;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\WPML;

class ListingsController
{
	use JsonSkeleton;

	private $postType;

	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/v2/list', 'listings', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getListings'],
				'permission_callback' => '__return_true'
			]);
		});
	}

	public function getListings(\WP_REST_Request $request)
	{
		if (!class_exists('\WilokeThemeOptions')) {
			return  [
				'status' => 'error',
				'msg'    => esc_html__('You must active Wilcity theme', 'wilcity-mobile-app')
			];
		}

		WPML::switchLanguageApp();
		$aData = $request->get_params();

		$page = isset($aData['page']) ? abs($aData['page']) : 1;

		$aPostTypes = General::getPostTypeKeys(false, true);

		$this->postType = isset($aData['postType']) && !empty($aData['postType']) ? $aData['postType'] : $aPostTypes;

		$aQuery = $aData;

		$aQuery['postType']
			= isset($aData['postType']) && !empty($aData['postType']) ? $aData['postType'] : $aPostTypes;
		$aQuery['page'] = $page;

		if (!isset($aData['postsPerPage']) || (abs($aData['postsPerPage']) > 100)) {
			$aData['postsPerPage'] = 18;
		}

		if (isset($aData['lat']) && !empty($aData['lat']) && isset($aData['lng']) && !empty($aData['lng'])) {

			$radius = \WilokeThemeOptions::getOptionDetail('default_radius');

			if (empty($radius)) {
				$radius = 10;
			}

			$aQuery['oAddress'] = [
				'lat'    => $aData['lat'],
				'lng'    => $aData['lng'],
				'radius' => absint($radius),
				'unit'   => isset($aData['unit']) ? $aData['unit'] : 'km'
			];
		}

		$aThemeOptions = \Wiloke::getThemeOptions(true);
		$aQuery['orderby'] = isset($aThemeOptions['app_listings_orderby']) ? $aThemeOptions['app_listings_orderby'] :
			'menu_order post_date';
		$aQuery['order']
			= isset($aThemeOptions['app_listings_order']) ? $aThemeOptions['app_listings_order'] : 'DESC';
		$aArgs = SearchFormController::buildQueryArgs($aQuery);

		$query = new \WP_Query(WPML::addFilterLanguagePostArgs($aArgs));
		if ($query->have_posts()) {
			$aPosts = [];
			while ($query->have_posts()) {
				$query->the_post();
				$aPost = $this->listingSkeleton($query->post);
				$aNavAndHome = $this->getNavigationAndHome($query->post);
				$aPosts[] = $aPost + $aNavAndHome;
			}
			wp_reset_postdata();

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
				'msg'    => esc_html__('No Posts Found', 'wilcity-mobile-app')
			];
		}
	}
}
