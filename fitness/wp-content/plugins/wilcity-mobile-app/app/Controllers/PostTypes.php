<?php

namespace WILCITY_APP\Controllers;

use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\WPML;

class PostTypes
{
	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, '/post-types', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getPostTypes'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/post-types', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getPostTypes'],
				'permission_callback' => '__return_true'
			]);
		});
	}

	public function getPostTypes()
	{
		WPML::switchLanguageApp();
		$aPostTypes = General::getPostTypes(false);
		$aResponse = [];

		foreach ($aPostTypes as $postType => $aData) {
			$aData['rest_base'] = $postType . 's';
			$oCountPosts = wp_count_posts($postType);
			$aData['found_posts'] = abs($oCountPosts->publish);
			$aResponse[] = $aData;
		}

		return [
			'oResults' => $aResponse,
			'status'   => 'success'
		];
	}
}
