<?php
return [
	'wilcity_kc_listings_tabs' => [
		'name'     => 'Listings Tabs',
		'icon'     => 'sl-paper-plane',
		'css_box'  => true,
		'category' => WILCITY_SC_CATEGORY,
		'params'   => [
			'general'             => [
				'heading',
				'heading_color',
				[
					'type'        => 'text',
					'label'       => 'Image Size',
					'description' => 'For example: 200x300. 200: Image width. 300: Image height',
					'name'        => 'img_size',
					'value'       => 'medium'
				],
				[
					'type'  => 'text',
					'name'  => 'terms_tab_id',
					'label' => 'Wrapper ID',
					'value' => uniqid('terms_tab_id')
				]
			],
			'filter_options'      => [
				'post_types_filter',
				[
					'type'     => 'multiple',
					'multiple' => true,
					'label'    => 'Order By Options',
					'name'     => 'orderby_options',
					'options'  => 'listing_orderby'
				],
				[
					'type'    => 'select',
					'label'   => 'Default Order By',
					'name'    => 'orderby',
					'options' => 'listing_orderby'
				],
				'order',
				'posts_per_page'
			],
			'navigation_settings' => [
				[
					'name'        => 'taxonomy',
					'label'       => 'Navigation Type',
					'type'        => 'select',
					'value'       => 'listing_cat',
					'options'     => [
						'listing_cat'      => 'Listing Category',
						'listing_location' => 'Listing Location'
					],
					'admin_label' => true
				],
				[
					'type'        => 'select',
					'name'        => 'get_term_type',
					'label'       => 'Get Terms Type',
					'description' => 'Warning: If you want to use Get Term Children mode, You can use select 1 Listing Location / Listing Category only',
					'value'       => 'specify_terms',
					'options'     => [
						'term_children' => 'Get child terms',
						'specify_terms' => 'Specific Terms'
					]
				],
				[
					'type'     => 'text',
					'label'    => 'Maximum Term Children',
					'name'     => 'number_of_term_children',
					'value'    => 6,
					'relation' => [
						'parent'    => 'get_term_type',
						'show_when' => 'term_children'
					],
				],
				[
					'type'    => 'select',
					'label'   => 'Is Navigation ?',
					'name'    => 'is_navigation',
					'description'    => 'Enable to set the order that terms are displayed',
					'options' => [
						'yes' => 'Enable',
						'no'  => 'Disable'
					],
					'value'   => 'yes'
				],
				[
					'type'    => 'select',
					'label'   => 'Select terms displaying order by',
					'description'    => 'Categories/Location displaying order on navigation bar',
					'name'    => 'navigation_orderby',
					'options' => [
						'count'      => 'Number of children',
						'name'       => 'Term Name',
						'slug'       => 'Term Slug',
						'term_order' => 'Term Order',
						'id'         => 'Term ID',
						'include'    => 'Exactly as you type'
					],
					'value'   => 'include'
				],
				[
					'type'    => 'select',
					'label'   => 'Select terms displaying order',
					'name'    => 'navigation_order',
					'options' => [
						'DESC' => 'DESC',
						'ASC'  => 'ASC'
					],
					'value'   => 'DESC'
				],
				[
					'type'        => 'autocomplete',
					'label'       => 'Select Listing Location[s]',
					'description' => '',
					'name'        => 'listing_locations',
					'multiple'    => true,
					'relation'    => [
						'parent'    => 'taxonomy',
						'hide_when' => 'listing_cat'
					]
				],
				'listing_cats'           => [
					'type'        => 'autocomplete',
					'label'       => 'Select Listing Categories',
					'multiple'    => true,
					'description' => '',
					'name'        => 'listing_cats',
					'relation'    => [
						'parent'    => 'taxonomy',
						'hide_when' => 'listing_location'
					]
				],
				'listing_location'       => [
					'type'        => 'autocomplete',
					'multiple'    => false,
					'label'       => 'Select Listing Location',
					'description' => 'This shortcode will show up all listings in the Locations and this category and You can select 1 category only. This feature is not available if you are using Redirect to Term Page',
					'name'        => 'listing_location',
					'relation'    => [
						'parent'    => 'taxonomy',
						'show_when' => 'listing_cat'
					]
				],
				'listing_cat'            => [
					'type'        => 'autocomplete',
					'multiple'    => false,
					'label'       => 'Select Listing Category',
					'description' => 'This shortcode will show up all listings in the Locations and this category and You can select 1 category only. This feature is not available if you are using Redirect to Term Page',
					'name'        => 'listing_cat',
					'relation'    => [
						'parent'    => 'taxonomy',
						'show_when' => 'listing_location'
					]
				],
			],
			'view_more_settings'  => [
				'toggle_viewmore',
				[
					'type'    => 'select',
					'label'   => 'Tab Alignment',
					'name'    => 'tab_alignment',
					'options' => [
						'wil-text-center' => 'wil-text-center',
						'wil-text-right'  => 'wil-text-right'
					],
					'value'   => 'wil-text-right'
				],
			],
			'column settings'     => 'bootstrap_columns'
		]
	]
];
