<?php
return [
	'wilcity_kc_term_boxes' => [
		'name'     => esc_html__('Term Boxes', 'wilcity-shortcodes'),
		'icon'     => 'sl-paper-plane',
		'group'    => 'term',
		'css_box'  => true,
		'category' => WILCITY_SC_CATEGORY,
		'params'   => [
			'general'   => [
				'heading',
				'heading_color',
				'desc',
				'desc_color',
				'header_desc_text_align',
				'term_redirect',
				'taxonomy_types',
				'listing_locations',
				'listing_cats',
				'listing_location',
				'listing_cat',
				'listing_tags',
				[
					'common'     => 'post_type',
					'additional' => [
						'description' => 'It is required if you are using Term Redirect: Search page Page.'
					]
				],
				'number',
				'is_show_parent_only',
				'is_hide_empty',
				'term_orderby',
				'order',
				'image_size'
			],
			'design'    => 'bootstrap_columns',
			'box style' => [
				[
					'name'        => 'toggle_box_gradient',
					'label'       => 'Toggle Box Gradient',
					'description' => 'In order to use this feature, please upload a Featured Image to each Listing Location/Category: Listings -> Listing Locations / Categories -> Your Location/Category -> Featured Image.',
					'type'        => 'select',
					'value'       => 'disable',
					'options'     => [
						'enable'  => 'Enable',
						'disable' => 'Disable'
					]
				]
			],
			'styling'   => [
				[
					'name' => 'css_custom',
					'type' => 'css'
				]
			]
		]
	]
];
