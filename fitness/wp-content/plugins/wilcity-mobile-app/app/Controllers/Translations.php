<?php

namespace WILCITY_APP\Controllers;

use WilokeListingTools\Framework\Helpers\GetSettings;

class Translations
{
	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'translations', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getTranslations'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'translations', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getTranslations'],
				'permission_callback' => '__return_true'
			]);
		});
	}

	public function getTranslations()
	{
		return [
			'status'   => 'success',
			'oResults' => GetSettings::getTranslation('', true)
		];
	}
}
