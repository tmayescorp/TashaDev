<?php

namespace WILCITY_APP\Controllers;

use WILCITY_APP\Helpers\App;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WP_User;

class AppleLoginController
{
	use VerifyToken;
	use JsonSkeleton;
	use BuildToken;
	use ParsePost;

	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, '/apple-signin', [
				'methods'             => 'POST',
				'callback'            => [$this, 'signinWithApple'],
				'permission_callback' => '__return_true'
			]);
		});

		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/v2', '/apple-signin', [
				'methods'             => 'POST',
				'callback'            => [$this, 'signinWithApple'],
				'permission_callback' => '__return_true'
			]);
		});
	}

	public function signinWithApple(\WP_REST_Request $oRequest)
	{
		$aStatus = apply_filters(
			'wilcity/filter/wiloke-listing-tools/app/Controllers/AppleLoginController/handleAppleLogin',
			[
				'status' => 'error'
			],
			$oRequest->get_param('identityToken'),
			$oRequest->get_params()
		);

		if ($aStatus['status'] === 'success') {
			$aStatus['token'] = GetSettings::getUserMeta($aStatus['userID'], 'app_token');
			if (empty($aStatus['token']) || !$this->verifyToken($aStatus['token'])) {
				$aResponse = $this->buildPermanentLoginToken(new WP_User($aStatus['userID']));
				if ($aResponse['status'] == 'error'){
					return $aResponse;
				}

				$aStatus['token'] = $aResponse['token'];
			}
		}

		$oUser = new WP_User($aStatus['userID']);
		$aResponse = $this->buildPermanentLoginToken($oUser);
		if ($aResponse['status'] == 'error'){
			return $aResponse;
		}

		return apply_filters(
			'wilcity/filter/wilcity-mobile-app/app/Controllers/LoginRegister/authentication',
			App::get('UserInfo')->buildUserInfo($oUser, $aResponse['token'])
		);
	}
}
