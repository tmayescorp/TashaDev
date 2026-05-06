<?php
return [
	'wilcity_kc_modern_term_boxes' => [
		'name'     => esc_html__('Modern Term Boxes', 'wilcity-shortcodes'),
		'icon'     => 'sl-paper-plane',
		'group'    => 'term',
		'css_box'  => true,
		'category' => WILCITY_SC_CATEGORY,
		'params'   => [
			'general'         => [
				'heading',
				'heading_color',
				'desc',
				'desc_color',
				'header_desc_text_align',
				'term_redirect',
				'taxonomy_types',
				'listing_locations'   => [
					'type'        => 'autocomplete',
					'label'       => 'Select Listing Location[s]',
					'description' => '',
					'name'        => 'listing_locations',
					'multiple'    => true,
					'relation'    => [
						'parent'    => 'taxonomy',
						'show_when' => 'listing_location'
					]
				],
				'listing_cats'        => [
					'type'        => 'autocomplete',
					'label'       => 'Select Listing Categories',
					'multiple'    => true,
					'description' => '',
					'name'        => 'listing_cats',
					'relation'    => [
						'parent'    => 'taxonomy',
						'show_when' => 'listing_cat'
					]
				],
				'listing_location'    => [
					'type'        => 'autocomplete',
					'multiple'    => false,
					'label'       => 'Select Listing Location',
					'description' => 'This shortcode will show up all listings in the Locations and this category and You can select 1 category only. This feature is not available if you are using Redirect to Term Page',
					'name'        => 'listing_location',
					'relation'    => [
						'parent'    => 'taxonomy',
						'hide_when' => 'listing_location'
					]
				],
				'listing_cat'         => [
					'type'        => 'autocomplete',
					'multiple'    => false,
					'label'       => 'Select Listing Category',
					'description' => 'This shortcode will show up all listings in the Locations and this category and You can select 1 category only. This feature is not available if you are using Redirect to Term Page',
					'name'        => 'listing_cat',
					'relation'    => [
						'parent'    => 'taxonomy',
						'hide_when' => 'listing_cat'
					]
				],
				'listing_tags'        => [
					'type'        => 'autocomplete',
					'label'       => 'Select Listing Tags',
					'multiple'    => true,
					'description' => '',
					'name'        => 'listing_tags',
					'relation'    => [
						'parent'    => 'taxonomy',
						'show_when' => 'listing_tag'
					]
				],
				[
					'common'     => 'post_type',
					'additional' => [
						'description' => 'It is required if you are using Term Redirect: Search page Page.'
					]
				],
				'col_gap'             => 'col_gap',
				'number'              => 'number',
				'image_size'          => 'image_size',
				'is_show_parent_only' => 'is_show_parent_only',
				'is_hide_empty'       => 'is_hide_empty',
				'term_orderby'        => 'term_orderby',
				'order'               => 'order'
			],
			'device settings' => 'bootstrap_columns',
			'styling'         => [
				[
					'name' => 'css_custom',
					'type' => 'css'
				]
			]
		]
	]
];
