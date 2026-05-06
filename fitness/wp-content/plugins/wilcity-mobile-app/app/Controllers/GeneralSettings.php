<?php

namespace WILCITY_APP\Controllers;

use WILCITY_APP\Helpers\AppHelpers;
use Wiloke;
use WilokeListingTools\Controllers\RegisterLoginController;
use WilokeListingTools\Framework\Helpers\Firebase;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Framework\Helpers\WPML;
use WilokeThemeOptions;

class GeneralSettings
{
	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'general-settings', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getColorPrimary']
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'general-settings', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getColorPrimary']
			]);
		});
	}

	public function getColorPrimary()
	{
		$aThemeOptions = Wiloke::getThemeOptions(true);
		$themeColor = $aThemeOptions['advanced_main_color'];
		if ($themeColor == 'custom') {
			if (isset($aThemeOptions['advanced_custom_main_color']['rgba'])) {
				$themeColor = $aThemeOptions['advanced_custom_main_color']['rgba'];
			}
		} else {
			$themeColor = '#f06292';
		}

		$googleAPI = isset($aThemeOptions['general_google_api']) && !empty($aThemeOptions['general_google_api']) ?
			$aThemeOptions['general_google_api'] : '';
		$googleLang
			= isset($aThemeOptions['general_google_language']) && !empty($aThemeOptions['general_google_language']) ?
			$aThemeOptions['general_google_language'] : '';

		if (!isset($aThemeOptions['content_position'])) {
			$contentPosition = 'above_sidebar';
		} else {
			$contentPosition = $aThemeOptions['content_position'];
		}

		$customLoginPageID = WilokeThemeOptions::getOptionDetail('custom_login_page');
		if (!empty($customLoginPageID)) {
			$resetPasswordURL = get_permalink($customLoginPageID);
		} else {
			$resetPasswordURL = get_permalink(WilokeThemeOptions::getOptionDetail('reset_password_page'));
		}

		$isFBLogin = WilokeThemeOptions::getOptionDetail('fb_toggle_login') == 'enable';
		$unit = WilokeThemeOptions::getOptionDetail('unit_of_distance');

		if (WilokeThemeOptions::isEnable('toggle_custom_login_page') &&
			!empty(WilokeThemeOptions::getOptionDetail('custom_login_page'))) {
			$resetPasswordURL = get_permalink(WilokeThemeOptions::getOptionDetail('custom_login_page'));
			$resetPasswordURL = add_query_arg(
				[
					'action' => 'rp'
				],
				$resetPasswordURL
			);
		} else {
			$resetPasswordURL = get_permalink(WilokeThemeOptions::getOptionDetail('reset_password_page'));
		}

		$aAdMod = AppHelpers::getAdMobConfiguration();
		//        if ($type = \WilokeThemeOptions::getOptionDetail('app_google_fullwidth_admob_type')) {
		//            $aAdMod = [
		//              'oFullWidth' => [
		//                'adUnitID' => \WilokeThemeOptions::getOptionDetail('app_google_fullwidth_unit_id'),
		//                'variant'  => $type,
		//                'timeout'  => \WilokeThemeOptions::getOptionDetail('app_google_set_timeout', 0)
		//              ]
		//            ];
		//        }
		//
		$settings = [
			'colorPrimary'          => $themeColor,
			'oGoogleMapAPI'         => [
				'key'      => $googleAPI,
				'language' => $googleLang,
				'types'    => 'geocode'
			],
			'defaultZoom'           => 39.5,
			'oSingleListing'        => [
				'contentPosition' => $contentPosition
			],
			'isAllowRegistering'    => RegisterLoginController::canRegister() ? 'yes' : 'no',
			'oAdMob'                => $aAdMod,
			'oFacebook'             => [
				'isEnableFacebookLogin' => apply_filters('wilcity/wilcity-mobile-app/filter/is-fb-login', $isFBLogin),
				'appID'                 => WilokeThemeOptions::getOptionDetail('fb_api_id')
			],
			'unit'                  => empty($unit) ? 'km' : $unit,
			'resetPasswordURL'      => $resetPasswordURL,
			'oFirebase'             => Firebase::getFirebaseChatConfiguration(),
			'appUpdateAnnouncement' => [
				'version'     => WilokeThemeOptions::getOptionDetail('app_current_version'),
				'desc'        => sprintf(
					WilokeThemeOptions::getOptionDetail('app_update_description'),
					WilokeThemeOptions::getOptionDetail('app_current_version')
				),
				'readmore'    => WilokeThemeOptions::getOptionDetail('app_update_read_more'),
				'appStoreUrl' => WilokeThemeOptions::getOptionDetail('app_iso_url'),
				'chplayUrl'   => WilokeThemeOptions::getOptionDetail('app_chplay_url'),
			]
		];
		//WPML
		if (WPML::isActive()) {
			$aLang = apply_filters('wpml_active_languages', NULL, 'skip_missing=0&orderby=id&order=desc');
		} else {
			$aLang = [];
		}

		if (!empty($aLang)) {
			$aLang = array_map(function ($lang) {
				unset($lang['active']);
				return array(
					'code'           => $lang['code'],
					'id'             => $lang['id'],
					'nativeName'     => $lang['native_name'],
					'major'          => $lang['major'],
					'defaultLocale'  => $lang['default_locale'],
					'encodeUrl'      => $lang['encode_url'],
					'tag'            => $lang['tag'],
					'translatedName' => $lang['translated_name'],
					'url'            => $lang['url'],
					'countryFlagUrl' => $lang['country_flag_url'],
					'languageCode'   => $lang['language_code']
				);

			}, $aLang);

			$settings['WPML'] = [
				'defaultLang' => WPML::getDefaultLanguage(),
				'lang'        => $aLang
			];
		}

		return apply_filters('wilcity/wilcity-mobile-app/filter/general-settings', $settings);
	}
}
