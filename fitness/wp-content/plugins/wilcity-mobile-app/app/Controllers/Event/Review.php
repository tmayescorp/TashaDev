<?php

namespace WILCITY_APP\Controllers;

use WilokeListingTools\Controllers\ReviewController;
use WilokeListingTools\Controllers\ShareController;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Framework\Helpers\WPML;

class Review
{
	use JsonSkeleton;
	use VerifyToken;

	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, '/post-review', [
				'methods'             => 'POST',
				'callback'            => [$this, 'postReview'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/post-review', [
				'methods'             => 'POST',
				'callback'            => [$this, 'postReview'],
				'permission_callback' => '__return_true'
			]);
		});
	}

	public function postReview()
	{
		WPML::switchLanguageApp();
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}
		$oToken->getPayLoad();

		if (!$this->oPayLoad) {
			return [
				'status' => 'error',
				'msg'    => 403
			];
		}

		return [
			'status' => 'success',
			'msg'    => 'success'
		];
	}
}
