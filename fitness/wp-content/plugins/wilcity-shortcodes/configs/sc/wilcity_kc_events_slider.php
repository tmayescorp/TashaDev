<?php
return [
	'wilcity_kc_events_slider' => [
		'name'     => 'Events Slider',
		'icon'     => 'sl-paper-plane',
		'category' => WILCITY_SC_CATEGORY,
		'css_box'  => true,
		'params'   => [
			'general'               => [
				'heading',
				'heading_color',
				'desc',
				'desc_color',
				'header_desc_text_align',
				'toggle_viewmore',
				'viewmore_btn_name',
				'listing_cats',
				'listing_locations',
				'custom_taxonomy_key',
				'custom_taxonomies_id',
				'event_post_type',
				'event_orderby',
				'order'
			],
			'listings on screens'   => [
				[
					'name'        => 'desktop_image_size',
					'label'       => 'Desktop Image Size',
					'description' => 'You can use the defined image sizes like: full, large, medium, wilcity_560x300 or 400,300 to specify the image width and height.',
					'type'        => 'text',
					'value'       => ''
				],
				[
					'name'        => 'maximum_posts',
					'label'       => 'Maximum Listings',
					'type'        => 'text',
					'value'       => 8,
					'admin_label' => true
				],
				[
					'name'        => 'maximum_posts_on_extra_lg_screen',
					'label'       => 'Items on >=1600px',
					'description' => 'Set number of listings will be displayed when the screen is larger or equal to 1600px ',
					'type'        => 'text',
					'value'       => 6,
					'admin_label' => true
				],
				[
					'name'        => 'maximum_posts_on_lg_screen',
					'label'       => 'Items on >=1400px',
					'description' => 'Set number of listings will be displayed when the screen is larger or equal to 1400px ',
					'type'        => 'text',
					'value'       => 5,
					'admin_label' => true
				],
				[
					'name'        => 'maximum_posts_on_md_screen',
					'label'       => 'Items on >=1200px',
					'description' => 'Set number of listings will be displayed when the screen is larger or equal to 1200px ',
					'type'        => 'text',
					'value'       => 5,
					'admin_label' => true
				],
				[
					'name'        => 'maximum_posts_on_sm_screen',
					'label'       => 'Items on >=992px',
					'description' => 'Set number of listings will be displayed when the screen is larger or equal to 992px ',
					'type'        => 'text',
					'value'       => 2,
					'admin_label' => true
				],
				[
					'name'        => 'maximum_posts_on_extra_sm_screen',
					'label'       => 'Items on >=640px',
					'description' => 'Set number of listings will be displayed when the screen is larger or equal to 640px ',
					'type'        => 'text',
					'value'       => 1,
					'admin_label' => true
				]
			],
			'slider configurations' => [
				[
					'name'        => 'is_auto_play',
					'label'       => 'Is Auto Play',
					'type'        => 'select',
					'options'     => [
						'enable'  => 'Enable',
						'disable' => 'Disable'
					],
					'value'       => 'disable',
					'admin_label' => true
				]
			],
			'styling'               => [
				[
					'name' => 'css_custom',
					'type' => 'css'
				]
			]
		]
	]
];
