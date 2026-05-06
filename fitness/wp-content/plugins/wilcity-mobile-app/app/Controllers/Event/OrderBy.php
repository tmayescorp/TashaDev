<?php

namespace WILCITY_APP\Controllers;

class OrderBy
{
	use JsonSkeleton;

	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/v2', 'get-orderby', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'response']
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'get-orderby', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'response']
			]);
		});
	}

	public function response()
	{
		return [
			'status'   => 'success',
			'oResults' => $this->getOrderBy()
		];
	}
}
