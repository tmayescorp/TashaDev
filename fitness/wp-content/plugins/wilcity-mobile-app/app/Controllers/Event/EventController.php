<?php

namespace WILCITY_APP\Controllers\Event;

use WILCITY_APP\Controllers\BuildQuery;
use WILCITY_APP\Controllers\Listing\ListingSkeleton;
use WILCITY_APP\Controllers\VerifyToken;
use WILCITY_APP\Helpers\App;
use WilokeListingTools\Controllers\DashboardController;
use WilokeListingTools\Framework\Store\Session;
use WP_Query;
use WilokeListingTools\Framework\Helpers\WPML;

class EventController extends ListingSkeleton
{
	use BuildQuery;
	use VerifyToken;

	private   $aUnnecessaryItems = ['businessStatus', 'oTerm', 'oIcon'];
	protected $aPluck
	                             = [
			'ID',
			'postTitle',
			'isAds',
			'postLink',
			'tagLine',
			'timezone',
			'oCalendar',
			'oFeaturedImg',
			'isMyFavorite',
			'totalFavorites',
			'hostedBy',
			'headerBlock',
			'bodyBlock'
		];

	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, '/events/(?P<target>\w+)', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getEvent'],
				'permission_callback' => '__return_true'
			]);
		});

		add_filter(
			'wilcity/filter/wiloke-listing-tools/app/Framework/Helpers/PostSkeleton/getEvents',
			[$this, 'addOldVersionData']
		);
	}

	public function addOldVersionData($aData)
	{
		$aData[] = 'oFavorite';

		return $aData;
	}

	public function getEvent(\WP_REST_Request $request)
	{
		Session::sessionStart();
		WPML::switchLanguageApp();
		$postID = $request->get_param('target');
		$aArgs = [
			'p'           => $postID,
			'post_status' => 'publish',
			'post_type'   => get_post_type($postID)
		];

		$isAuthor = false;
		if ($oToken = $this->verifyPermanentToken()) {
			$oToken->getUserID();
			if (get_post_field('post_author', $postID) == $this->userID) {
				$aArgs['post_status'] = ['pending', 'publish', 'unpaid', 'editing', 'expired'];
				$isAuthor = true;
			}
		}

		$query = new WP_Query(WPML::addFilterLanguagePostArgs($aArgs));

		if (!$query->have_posts()) {
			wp_reset_postdata();
			return [
				'status' => 'error',
				'msg'    => $aArgs
			];
		}

		while ($query->have_posts()) {
			$query->the_post();

			$aEventData = App::get('EventGeneralData')->getData($query->post, $this->aPluck);

			if ($isAuthor) {
				$post = get_post($postID);
				$aEventData['isEditable'] = true;
				$aEventData['isSubmittable'] = apply_filters(
					'wilcity/filter/wilcity-listing-tools/app/Controllers/AddListingButtonController/has-submit-btn',
					Session::getPaymentObjectID() == $query->post->ID && $post->post_author == $this->userID &&
					Session::getSession('test') === 'oke',
					$query->post,
					true
				);
			}
		}

		wp_reset_postdata();

		return apply_filters('wilcity/wilcity-mobile-app/filter/listing-detail', [
			'status'   => 'success',
			'oResults' => $aEventData
		], $request->get_params(), $postID);
	}
}
