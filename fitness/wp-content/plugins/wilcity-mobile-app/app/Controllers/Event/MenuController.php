<?php

namespace WILCITY_APP\Controllers;

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\WPML;

class MenuController
{
	private $aStackNavigationRelationship
		= [
			'homeStack'    => 'MenuHomeScreen',
			'listingStack' => 'MenuListingScreen',
			'blogStack'    => 'MenuBlogScreen',
			'pageStack'    => 'MenuPageScreen',
			'eventStack'   => 'MenuEventScreen',
			'menuStack'    => 'MenuScreen'
		];

	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'navigators', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'fetchNavigators']
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'navigators/(?P<menuID>\w+)', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getNavigator']
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'navigators', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'fetchNavigators']
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'navigators/(?P<menuID>\w+)', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getNavigator']
			]);
		});
	}

	private function parseMenuNavigation($aMenuItem)
	{
		$aMenuItem['navigation'] = '';
		if (isset($this->aStackNavigationRelationship[$aMenuItem['screen']])) {
			$aMenuItem['navigation'] = $this->aStackNavigationRelationship[$aMenuItem['screen']];
		}

		if ($aMenuItem['screen'] == 'pageStack') {
			$aMenuItem['link'] = add_query_arg([
				'iswebview' => 'yes'
			], get_permalink($aMenuItem['id']));
		}

		return $aMenuItem;
	}

	private function getMainMenu()
	{
		$aRawMainMenus = GetSettings::getOptions('mobile_main_menu', false, true);
		if (empty($aRawMainMenus)) {
			return false;
		}

		$aMainMenu = [];
		foreach ($aRawMainMenus as $aMenuItem) {
			if (isset($aMenuItem['status']) && $aMenuItem['status'] == 'disable') {
				continue;
			}
			$aMenuItem = $this->parseMenuNavigation($aMenuItem);
			$aMainMenu[] = $aMenuItem;
		}

		return $aMainMenu;
	}

	private function getSecondaryMenu()
	{
		$aMenus = [];
		$aRawSecondaryMenus = GetSettings::getOptions('mobile_secondary_menu', false, true);
		if (empty($aRawSecondaryMenus)) {
			return false;
		}

		foreach ($aRawSecondaryMenus as $aMenuItem) {
			if (isset($aMenuItem['status']) && $aMenuItem['status'] == 'disable') {
				continue;
			}
			$aMenuItem = $this->parseMenuNavigation($aMenuItem);
			$aMenus[] = $aMenuItem;
		}

		return $aMenus;
	}

	public function fetchNavigators(): array
	{
		WPML::switchLanguageApp();
		$aMainMenu = $this->getMainMenu();
		$aSecondaryMenu = $this->getSecondaryMenu();

		if (empty($aMainMenu) && empty($aSecondaryMenu)) {
			return [
				'status' => 'error'
			];
		}

		return [
			'status'   => 'success',
			'oResults' => [
				'aTabNavigator'   => $aMainMenu,
				'aStackNavigator' => $aSecondaryMenu,
			]
		];
	}

	public function getNavigator($aData)
	{
		WPML::switchLanguageApp();
		if ($aData['menuID'] == 'stackNavigator') {
			$aMenu = $this->getSecondaryMenu();
		} else {
			$aMenu = $this->getMainMenu();
		}

		if (empty($aMenu)) {
			return [
				'status' => 'error'
			];
		}

		return [
			'status'   => 'success',
			'oResults' => $aMenu
		];
	}
}
