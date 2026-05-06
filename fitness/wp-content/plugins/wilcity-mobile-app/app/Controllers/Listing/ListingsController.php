<?php

namespace WILCITY_APP\Controllers\Listing;

use WILCITY_APP\Controllers\JsonSkeleton;
use WILCITY_APP\Controllers\VerifyToken;
use WILCITY_APP\Helpers\App;
use WilokeListingTools\Controllers\DashboardController;
use WilokeListingTools\Controllers\SearchFormController;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\TermSetting;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Framework\Helpers\WPML;
use WP_REST_Request;

class ListingsController
{
	use JsonSkeleton;
	use VerifyToken;

	private $postType;

	public function __construct()
	{
		add_action('rest_api_init', [$this, 'registerRouter']);
	}

	public function registerRouter()
	{
		register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION . '/list', 'listings', [
			'methods'             => 'GET',
			'callback'            => [$this, 'getListings'],
			'permission_callback' => '__return_true'
		]);

		register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'get-my-listings', [
			'methods'             => 'GET',
			'callback'            => [$this, 'fetchMyListings'],
			'permission_callback' => '__return_true'
		]);

		register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'me/listings',
			[
				[
					'methods'             => 'GET',
					'callback'            => [$this, 'fetchMyListings'],
					'permission_callback' => '__return_true'
				]
			]
		);

		register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'get-listing-status', [
			'methods'             => 'GET',
			'callback'            => [$this, 'getPostStatus'],
			'permission_callback' => '__return_true'
		]);

		register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'listing/status', [
			'methods'             => 'GET',
			'callback'            => [$this, 'getPostStatus'],
			'permission_callback' => '__return_true'
		]);

		register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'get-listing-types', [
			'methods'             => 'GET',
			'callback'            => [$this, 'getListingTypes'],
			'permission_callback' => '__return_true'
		]);

		register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'listing/types', [
			'methods'             => 'GET',
			'callback'            => [$this, 'getListingTypes'],
			'permission_callback' => '__return_true'
		]);
	}

	public function fetchMyListings(WP_REST_Request $request): array
	{
		WPML::switchLanguageApp();
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}

		$oToken->getUserID();
		$postType = $request->get_param('postType');
		$aListings = General::getPostTypeKeys(false, false);

		if (empty($postType) || $postType == 'all') {
			$aArgs['post_type'] = $aListings;
		} else {
			$aArgs['post_type'] = $postType;
		}

		if (!$postsPerPage = $request->get_param('postsPerPage')) {
			$postsPerPage = 10;
		}

		$aArgs = wp_parse_args(
			[
				'post_type'      => $aArgs['post_type'],
				'posts_per_page' => $postsPerPage,
				'paged'          => $request->get_param('page') ? $request->get_param('page') : 1,
				'author__in'     => $this->userID
			],
			[
				'post_status' => 'publish'
			]
		);

		$postStatus = $request->get_param('postStatus');
		if ($postStatus == 'all') {
			$aArgs['post_status'] = ['pending', 'publish', 'unpaid', 'editing', 'expired'];
		} else {
			$aArgs['post_status'] = $request->get_param('postStatus');
		}

		$query = new \WP_Query(WPML::addFilterLanguagePostArgs($aArgs));
		if (!$query->have_posts()) {
			return [
				'status' => 'error',
				'msg'    => 'doNotHaveAnyArticleYet'
			];
		}

		$aListings = [];
		while ($query->have_posts()) {
			$query->the_post();
			$aListings[] = App::get('ListingGeneralData')->getData($query->post);
		}
		wp_reset_postdata();

		if ($aArgs['paged'] < $query->max_num_pages) {
			$next = $aArgs['paged'] + 1;
		} else {
			$next = false;
		}

		return [
			'status'   => 'success',
			'oResults' => $aListings,
			'maxPages' => $query->max_num_pages,
			'next'     => $next
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

	public function getListings(WP_REST_Request $request): array
	{
		WPML::switchLanguageApp();
		$aData = wp_parse_args(
			$request->get_params(),
			[
				'page'         => 1,
				'postsPerPage' => get_option('posts_per_page')
			]
		);

		if (isset($aData['isGetListingByCat'])) {
			$termId = $request->get_param($request->get_param('taxonomy'));
			$oTerm = get_term($termId);
			if (!empty($oTerm) && !is_wp_error($oTerm)) {
				$postType = TermSetting::getDefaultPostType($termId, $oTerm->taxonomy);
				if (!empty($postType)) {
					$aData['postType'] = $postType;
				}
			}
		}

		if (!isset($aData['postType'])) {
			$aData['postType'] = General::getPostTypeKeys(false, true);
		}

		$this->postType = $aData['postType'];
		if (isset($aData['lat']) && !empty($aData['lat']) && isset($aData['lng']) && !empty($aData['lng'])) {
			$aData['oAddress'] = [
				'lat' => $aData['lat'],
				'lng' => $aData['lng']
			];
			$aData['orderby'] = 'nearby';
			$aData['oAddress']['isMobileApp'] = true;
			unset($aData['lat']);
			unset($aData['lng']);
		}

		$aArgs = SearchFormController::buildQueryArgs($aData);
		$aArgs['post_type'] = $aData['postType'];

		if (isset($aArgs['page']) && !isset($aArgs['paged'])) {
			$aArgs['paged'] = $aArgs['page'];
		}

		$aResponse = apply_filters(
			'wilcity-mobile-app/filter/app/Controllers/Listing/ListingsController/response',
			[],
			WPML::addFilterLanguagePostArgs($aArgs),
			$aData
		);

		if (!has_filter('wilcity-mobile-app/filter/app/Controllers/Listing/ListingsController/response')) {
			$query = new \WP_Query(WPML::addFilterLanguagePostArgs($aArgs));
			if ($query->have_posts()) {
				$aPosts = [];
				while ($query->have_posts()) {
					$query->the_post();
					$aAtts = apply_filters('wilcity/wilcity-mobile-app/filter/get-listing/attributes', []);
					$aGeneralData = App::get('ListingGeneralData')->getData($query->post, [], [], $aAtts);
					$aNavigation = App::get('ListingNavigation')->getData($query->post);
					$aGeneralData['oNavigation'] = $aNavigation;
					$aPosts[] = apply_filters(
						'wilcity/wilcity-mobile-app/filter/Listing/ListingsController/listing', $aGeneralData, $query->post
					);
				}

				$aReturn['status'] = 'success';
				if ($aData['page'] < $query->max_num_pages) {
					$aReturn['next'] = $aData['page'] + 1;
				} else {
					$aReturn['next'] = false;
				}
				$aReturn['totalPage'] = $query->max_num_pages;
				$aReturn['oResults'] = $aPosts;
				$aReturn['data'] = $aData;

				return apply_filters('wilcity/wilcity-mobile-app/filter/get-listings', $aReturn, $aData);
			}
		} else {
			if (!empty($aResponse)) {
				return apply_filters('wilcity/wilcity-mobile-app/filter/get-listings', $aResponse, $aData);
			}
		}

		return [
			'status' => 'error',
			'msg'    => 'noDataFound'
		];
	}
}
