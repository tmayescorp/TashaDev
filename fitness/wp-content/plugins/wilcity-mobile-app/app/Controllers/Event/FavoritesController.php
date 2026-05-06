<?php

namespace WILCITY_APP\Controllers;

use WILCITY_APP\Helpers\App;
use WilokeListingTools\Controllers\FavoriteStatisticController;
use WilokeListingTools\Controllers\ReviewController;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\WPML;
use WilokeListingTools\Models\ReviewMetaModel;
use WP_REST_Request;

class FavoritesController
{
	use VerifyToken;
	use JsonSkeleton;
	use ParsePost;

	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'me/favorites',
				[
					[
						'methods'             => 'GET',
						'callback'            => [$this, 'getMyFavorites'],
						'permission_callback' => '__return_true',
					],
					[
						'methods'             => 'POST',
						'callback'            => [$this, 'addToMyFavorites'],
						'permission_callback' => '__return_true',
					]
				]
			);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'me/favorites/(?P<id>\d+)',
				[
					[
						'methods'             => 'DELETE',
						'callback'            => [$this, 'removeFavorite'],
						'permission_callback' => '__return_true',
					]
				]
			);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'get-my-favorites', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getMyFavorites'],
				'permission_callback' => '__return_true',
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'get-my-favorites', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getMyFavorites'],
				'permission_callback' => '__return_true',
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'add-to-my-favorites', [
				'methods'             => 'POST',
				'callback'            => [$this, 'addToMyFavorites'],
				'permission_callback' => '__return_true',
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'add-to-my-favorites', [
				'methods'             => 'POST',
				'callback'            => [$this, 'addToMyFavorites'],
				'permission_callback' => '__return_true',
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'remove-from-my-favorites', [
				'methods'             => 'POST',
				'callback'            => [$this, 'addToMyFavorites'],
				'permission_callback' => '__return_true',
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'remove-from-my-favorites', [
				'methods'             => 'POST',
				'callback'            => [$this, 'addToMyFavorites'],
				'permission_callback' => '__return_true',
			]);
		});
	}

	public function removeFavorite(WP_REST_Request $oRequest): array
	{
		WPML::switchLanguageApp();
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}
		$oToken->getUserID();


		if (!\WilokeThemeOptions::isEnable('listing_toggle_favorite')) {
			return [
				'status' => 'error',
				'msg'    => 403
			];
		}

		$postId = $oRequest->get_param('id');
		if (get_post_status($postId) !== 'publish') {
			return [
				'status' => 'error',
				'msg'    => 403
			];
		}

		$is = FavoriteStatisticController::update($postId, $this->userID);
		if ($is === false) {
			return [
				'status' => 'error',
				'msg'    => 403
			];
		}

		return [
			'status' => 'success',
			'msg'    => 'Success',
			'is'     => $is
		];
	}

	public function addToMyFavorites()
	{
		WPML::switchLanguageApp();
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}
		$oToken->getUserID();
		$aData = $this->parsePost();

		if (!isset($aData['postID']) || empty($aData['postID'])) {
			return [
				'status' => 'error',
				'msg'    => 403
			];
		}

		if (!\WilokeThemeOptions::isEnable('listing_toggle_favorite')) {
			return [
				'status' => 'error',
				'msg'    => 403
			];
		}

		if (get_post_status($aData['postID']) !== 'publish') {
			return [
				'status' => 'error',
				'msg'    => 403
			];
		}

		$is = FavoriteStatisticController::update($aData['postID'], $this->userID);
		if ($is === false) {
			return [
				'status' => 'error',
				'msg'    => 403
			];
		}

		return [
			'status' => 'success',
			'msg'    => 'Success',
			'is'     => $is
		];
	}

	public function getMyFavorites()
	{
		WPML::switchLanguageApp();
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}
		$oToken->getUserID();

		$page = isset($_GET['page']) ? abs($_GET['page']) : 1;
		$aRawFavorites = GetSettings::getUserMeta($oToken->userID, 'my_favorites');
		if (empty($aRawFavorites)) {
			return [
				'status' => 'error',
				'msg'    => 'noFavorites'
			];
		}

		$aFavorites = FavoriteStatisticController::getFavoritesByPage($aRawFavorites, $page - 1);
		if (isset($aFavorites['reachedMaximum'])) {
			return [
				'status' => 'error',
				'msg'    => 'gotAllFavorites'
			];
		}

		$aListings = [];
		foreach ($aFavorites['aInfo'] as $order => $aListing) {
			if (get_post_status($aListing['postID']) !== 'publish') {
				continue;
			}

			$postType = get_post_type($aListing['postID']);
			$oRequest = new WP_REST_Request();
			$oRequest->set_method('GET');
			$oRequest->set_param('target', $aListing['postID']);

			if ($postType === 'event') {
				$aResponse = App::get('EventController')->getEvent($oRequest);
			} else {
				$aResponse = App::get('ListingController')->getListing($oRequest);
			}

			if ($aResponse['status'] === 'error') {
				continue;
			}

			$aListing = $aResponse['oResults'];
			$aListing['postType'] = $postType;
			$aListings[] = $aListing;
		}

		if ($page < $aFavorites['maxPages']) {
			$next = $page + 1;
		} else {
			$next = false;
		}

		return [
			'status'   => 'success',
			'oResults' => $aListings,
			'total'    => $aFavorites['total'],
			'maxPages' => $aFavorites['maxPages'],
			'next'     => $next
		];
	}
}
