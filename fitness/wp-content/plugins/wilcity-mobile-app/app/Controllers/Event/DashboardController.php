<?php

namespace WILCITY_APP\Controllers;

use WilokeListingTools\Controllers\DashboardController as ThemeDashboardController;
use WilokeListingTools\Framework\Helpers\WPML;

class DashboardController
{
	use JsonSkeleton;
	use VerifyToken;
	use ParsePost;

	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/v2', 'get-dashboard-navigator', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getDashboardNavigator'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'get-dashboard-navigator', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getDashboardNavigator'],
				'permission_callback' => '__return_true'
			]);
		});
	}

	public function getDashboardNavigator(): array
	{
		WPML::switchLanguageApp();
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}
		$oToken->getUserID();
		$aNavigator = ThemeDashboardController::getNavigation($oToken->userID);

		$aNavigator = array_filter($aNavigator, function ($aItem, $key) {
			return !isset($aItem['isExcludeFromApp']) && isset($aItem['endpoint']) && !empty($aItem['endpoint']);
		}, ARRAY_FILTER_USE_BOTH);
		$aNavigator = array_values($aNavigator);

		$aNavigator = apply_filters('wilcity/wilcity-mobile-app/dashboard-navigator', $aNavigator, $this->userID);

		return [
			'status'   => 'success',
			'oResults' => $aNavigator
		];
	}
}
