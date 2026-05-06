<?php
return [
	'wilcity_kc_events_grid' => apply_filters(
		'wilcity/filter/wilcity-shortcodes/configs/sc/events_grid',
		[
			'name'     => 'Events Grid Layout',
			'icon'     => 'sl-paper-plane',
			'css_box'  => true,
			'category' => WILCITY_SC_CATEGORY,
			'params'   => [
				'general'         => [
					'heading',
					'heading_color',
					'desc',
					'desc_color',
					'header_desc_text_align',
					'toggle_viewmore',
					'viewmore_btn_name',
					'listing_cats',
					'listing_locations',
					'event_post_type',
					'event_orderby',
					'order',
					[
						'type'  => 'text',
						'label' => 'Maximum Items',
						'name'  => 'posts_per_page',
						'value' => 6
					],
					[
						'type'        => 'text',
						'label'       => 'Image Size',
						'description' => 'For example: 200x300. 200: Image width. 300: Image height',
						'name'        => 'img_size',
						'value'       => 'medium'
					],
					[
						'type'  => 'text',
						'label' => 'Mobile Image Size',
						'name'  => 'mobile_img_size',
						'value' => ''
					],
					[
						'type'    => 'select',
						'label'   => 'Toggle Gradient',
						'name'    => 'toggle_gradient',
						'options' => [
							'enable'  => 'Enable',
							'disable' => 'Disable'
						],
						'value'   => 'enable'
					],
					[
						'type'     => 'color_picker',
						'label'    => 'Left Gradient',
						'name'     => 'left_gradient',
						'value'    => '#006bf7',
						'relation' => [
							'parent'    => 'toggle_gradient',
							'show_when' => 'enable'
						]
					],
					[
						'type'     => 'color_picker',
						'label'    => 'Right Gradient',
						'name'     => 'right_gradient',
						'value'    => '#ed6392',
						'relation' => [
							'parent'    => 'toggle_gradient',
							'show_when' => 'enable'
						]
					],
					[
						'type'        => 'text',
						'label'       => 'Opacity',
						'parent'      => [],
						'description' => 'The value must equal to or smaller than 1',
						'name'        => 'gradient_opacity',
						'value'       => '0.3',
						'relation'    => [
							'parent'    => 'toggle_gradient',
							'show_when' => 'enable'
						]
					]
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
	)
];
