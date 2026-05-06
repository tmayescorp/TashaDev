<?php

namespace WILCITY_APP\Controllers;
use WilokeListingTools\Framework\Helpers\WPML;

class Filter
{
	use JsonSkeleton;

	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/v2', 'get-listing-filters', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getListingFilters'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'get-listing-filters', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getListingFilters'],
				'permission_callback' => '__return_true'
			]);
		});
	}

	public function getListingFilters()
	{
		WPML::switchLanguageApp();
		return [
			'status'   => 'success',
			'oResults' => [
				[
					'type'  => 'input',
					'key'   => 's',
					'name'  => esc_html__('What are you looking for?', WILCITY_MOBILE_APP),
					'value' => ''
				],
				[
					'type'  => 'checkbox',
					'key'   => 'best_rated',
					'name'  => esc_html__('Best Rating', WILCITY_MOBILE_APP),
					'value' => 'no'
				],
				[
					'type'  => 'checkbox',
					'key'   => 'open_now',
					'name'  => esc_html__('Open Now', WILCITY_MOBILE_APP),
					'value' => 'no'
				],
				[
					'type'    => 'select',
					'key'     => 'price_range',
					'name'    => esc_html__('Price Range', WILCITY_MOBILE_APP),
					'value'   => 'nottosay',
					'options' => [
						[
							'name'     => esc_html__('All Range', WILCITY_MOBILE_APP),
							'id'       => 'nottosay',
							'selected' => true
						],
						[
							'name'     => esc_html__('Cheap', WILCITY_MOBILE_APP),
							'id'       => 'cheap',
							'selected' => false
						],
						[
							'name'     => esc_html__('Moderate', WILCITY_MOBILE_APP),
							'id'       => 'moderate',
							'selected' => false
						],
						[
							'name'     => esc_html__('Expensive', WILCITY_MOBILE_APP),
							'id'       => 'expensive',
							'selected' => false
						],
						[
							'name'     => esc_html__('Ultra High', WILCITY_MOBILE_APP),
							'id'       => 'ultra_high',
							'selected' => false
						]
					]
				],
				[
					'type'  => 'google_auto_complete',
					'key'   => 'address',
					'name'  => esc_html__('Where do you want to go?', WILCITY_MOBILE_APP),
					'value' => ''
				]
			]
		];
	}
}
