<?php

namespace WILCITY_APP\Controllers\Listing;

use WILCITY_APP\Controllers\BuildQuery;
use WILCITY_APP\Controllers\JsonSkeleton;
use WILCITY_APP\Controllers\VerifyToken;
use WILCITY_APP\Helpers\App;
use WilokeListingTools\Controllers\DashboardController;
use WilokeListingTools\Controllers\SearchFormController;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Frontend\BusinessHours;
use WilokeListingTools\Frontend\SingleListing;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Framework\Helpers\WPML;
use WP_Query;
use WP_REST_Request;

class ListingController extends ListingSkeleton
{
	use BuildQuery;
	use VerifyToken;

	private $postType;

	public function __construct()
	{
		add_action('rest_api_init', [$this, 'registerRouter']);
		add_filter('wilcity/mobile/render_listings_on_mobile', [$this, 'getListingSkeletonOnHomepage'], 10, 2);
	}

	public function registerRouter()
	{
		register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'listings/(?P<target>\d+)', [
			'methods'             => 'GET',
			'callback'            => [$this, 'getListing'],
			'permission_callback' => '__return_true'
		]);

		register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'listings/(?P<id>\d+)/sidebar', [
			'methods'             => 'GET',
			'callback'            => [$this, 'getSidebar'],
			'permission_callback' => '__return_true',
		]);

		register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'listing/sidebar/(?P<id>\d+)', [
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => [$this, 'getSidebar']
		]);

		register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'listing-detail/(?P<target>\d+)', [
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => [$this, 'getListing'],
		]);

		register_rest_route(
			WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION,
			'listings/(?P<target>\w+)/(?P<metaKey>[^/]+)',
			[
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getListingMeta'],
			]
		);

		register_rest_route(
			WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION,
			'listing-meta/(?P<target>\d+)/(?P<metaKey>[^/]+)',
			[
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getListingMeta'],
			]
		);
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function getSidebar(WP_REST_Request $request): array
	{
		WPML::switchLanguageApp();
		return App::get('ListingSidebar')->getData(get_post($request->get_param('id')));
	}

	/**
	 * Note that the listing meta does not mean post meta. It's larger than post meta, it's including my_posts,
	 * listing_cat, listing_location, etc.. In short, it's everything that relates to posts
	 *
	 * @param WP_REST_Request $request
	 */
	public function getListingMeta(WP_REST_Request $request): array
	{
		WPML::switchLanguageApp();
		$postTarget = $request->get_param('target');

		if (!is_numeric($postTarget)) {
			$postID = $this->getPostIDBySlug($postTarget);
		} else {
			$postID = abs($postTarget);
		}

		$response = App::get('ListingMeta')
			->getData(get_post($postID), $request);

		if (empty($response)) {
			return [
				'status' => 'error',
				'msg'    => 'noDataFound'
			];
		} else {
			return [
				'status'   => 'success',
				'oResults' => $response
			];
		}
	}

	public function getListingSkeletonOnHomepage($atts, $post)
	{
		$this->setPost($post);
		return App::get('ListingGeneralData')->getData(
			$post,
			[],
			[
				'oGallery',
				'oSocialNetworks',
				'oVideos',
				'oNavigation'
			]
		);
	}

	/**
	 * @param WP_REST_Request $request target is required, it's listing id
	 *
	 * @return array|mixed|void
	 */
	public function getListing(WP_REST_Request $request): array
	{
		Session::sessionStart();
		WPML::switchLanguageApp();
		$aArgs = $this->buildSingleQuery($request->get_params());
		$isAuthor = false;
		if ($oToken = $this->verifyPermanentToken()) {
			$oToken->getUserID();
			if (get_post_field('post_author', $request->get_param('target')) == $this->userID) {
				$aArgs['post_status'] = ['pending', 'publish', 'unpaid', 'editing', 'expired'];
				$isAuthor = true;
			}
		}

		$query = new WP_Query(WPML::addFilterLanguagePostArgs($aArgs));

		if ($query->have_posts()) {
			while ($query->have_posts()) {
				$query->the_post();
				$aGeneralData = App::get('ListingGeneralData')->getData($query->post);
				$aHomeData = App::get('ListingHomeController')->getData($query->post, $request->get_params());
				$aNavigation = App::get('ListingNavigation')->getData($query->post, $request->get_params());
				$aButton = App::get('ListingExternalButton')->getData($query->post);

				$aGeneralData['tagLine'] = isset($aGeneralData['tagLine']) ? strip_tags($aGeneralData['tagLine']) : '';
				$aGeneralData['oNavigation'] = $aNavigation;
				$aGeneralData['oHomeSections'] = $aHomeData;

				if (!empty($aButton)) {
					$aGeneralData = array_merge($aGeneralData, $aButton);
				}

				$postID = $query->post->ID;

				if ($isAuthor) {
					$aGeneralData['isEditable'] = true;
					$aGeneralData['isSubmittable'] = apply_filters(
						'wilcity/filter/wilcity-listing-tools/app/Controllers/AddListingButtonController/has-submit-btn',
						Session::getPaymentObjectID() == $postID && $query->post->post_author == $this->userID &&
						Session::getSession('test') === 'oke',
						$query->post,
						true
					);
				}

				return apply_filters('wilcity/wilcity-mobile-app/filter/listing-detail', [
					'status'   => 'success',
					'oResults' => $aGeneralData
				], $request->get_params(), $postID);
			}
		} else {
			return [
				'status' => 'error',
				'msg'    => esc_html__('No Post found', WILCITY_MOBILE_APP)
			];
		}
	}
}
