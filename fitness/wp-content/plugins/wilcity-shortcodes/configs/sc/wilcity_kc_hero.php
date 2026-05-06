<?php
return [
	'wilcity_kc_hero' => [
		'name'         => esc_html__('Hero', 'wilcity-shortcodes'),
		'nested'       => true,
		'icon'         => 'sl-paper-plane',
		'accept_child' => 'wilcity_kc_search_form',
		'css_box'      => true,
		'category'     => WILCITY_SC_CATEGORY,
		'params'       => [
			'general'              => [
				[
					'name'        => 'heading',
					'label'       => 'Title',
					'type'        => 'text',
					'value'       => 'Explore This City',
					'admin_label' => false
				],
				[
					'name'  => 'heading_color',
					'label' => 'Title Color',
					'type'  => 'color_picker',
					'value' => ''
				],
				[
					'name'        => 'heading_font_size',
					'label'       => 'Desktop Title Font Size',
					'description' => 'Eg: 50px',
					'type'        => 'text',
					'value'       => '50px'
				],
				[
					'name'        => 'mobile_heading_font_size',
					'label'       => 'Mobile Title Font Size',
					'description' => 'Eg: 50px',
					'type'        => 'text',
					'value'       => '30px'
				],
				[
					'name'        => 'description',
					'label'       => 'Description',
					'type'        => 'textarea',
					'admin_label' => false
				],
				[
					'name'  => 'description_color',
					'label' => 'Description Color',
					'type'  => 'color_picker',
					'value' => ''
				],
				[
					'name'        => 'description_font_size',
					'label'       => 'Desktop Description Font Size',
					'description' => 'Eg: 17px',
					'type'        => 'text',
					'value'       => '17px'
				],
				[
					'name'        => 'mobile_description_font_size',
					'label'       => 'Mobile Description Font Size',
					'description' => 'Eg: 17px',
					'type'        => 'text',
					'value'       => '10px'
				],
				[
					'name'        => 'toggle_button',
					'label'       => 'Toggle Button',
					'type'        => 'select',
					'admin_label' => false,
					'value'       => 'enable',
					'options'     => [
						'enable'  => 'Enable',
						'disable' => 'Disable'
					]
				],
				[
					'name'        => 'button_icon',
					'label'       => 'Button Icon',
					'value'       => 'la la-pencil-square',
					'type'        => 'icon_picker',
					'admin_label' => false,
					'relation'    => [
						'parent'    => 'toggle_button',
						'show_when' => 'enable'
					]
				],
				[
					'name'        => 'button_name',
					'label'       => 'Button Name',
					'value'       => 'Check out',
					'type'        => 'text',
					'admin_label' => false,
					'relation'    => [
						'parent'    => 'toggle_button',
						'show_when' => 'enable'
					]
				],
				[
					'name'        => 'button_link',
					'label'       => 'Button Link',
					'type'        => 'text',
					'value'       => '#',
					'admin_label' => false,
					'relation'    => [
						'parent'    => 'toggle_button',
						'show_when' => 'enable'
					]
				],
				[
					'name'     => 'button_text_color',
					'label'    => 'Button Text Color',
					'type'     => 'color_picker',
					'value'    => '#fff',
					'relation' => [
						'parent'    => 'toggle_button',
						'show_when' => 'enable'
					]
				],
				[
					'name'     => 'button_background_color',
					'label'    => 'Button Background Color',
					'type'     => 'color_picker',
					'value'    => '',
					'relation' => [
						'parent'    => 'toggle_button',
						'show_when' => 'enable'
					]
				],
				[
					'name'     => 'button_size',
					'label'    => 'Button Size',
					'type'     => 'select',
					'relation' => [
						'parent'    => 'toggle_button',
						'show_when' => 'enable'
					],
					'value'    => 'wil-btn--sm',
					'options'  => [
						'wil-btn--sm' => 'Small',
						'wil-btn--md' => 'Medium',
						'wil-btn--lg' => 'Large'
					]
				],
				[
					'name'    => 'toggle_dark_and_white_background',
					'label'   => 'Toggle Dark and White Background',
					'type'    => 'select',
					'default' => 'disable',
					'options' => [
						'enable'  => 'Enable',
						'disable' => 'Disable'
					]
				],
				[
					'name'    => 'bg_overlay',
					'label'   => 'Background Overlay',
					'type'    => 'color_picker',
					'default' => ''
				],
				[
					'name'    => 'bg_type',
					'label'   => 'Is Using Slider Background?',
					'type'    => 'select',
					'default' => 'image',
					'options' => [
						'image'  => 'Image Background',
						'slider' => 'Slider Background'
					]
				],
				[
					'name'     => 'image_bg',
					'label'    => 'Background Image',
					'type'     => 'attach_image_url',
					'relation' => [
						'parent'    => 'bg_type',
						'show_when' => 'image'
					]
				],
				[
					'name'     => 'slider_bg',
					'label'    => 'Background Slider',
					'type'     => 'attach_images',
					'relation' => [
						'parent'    => 'bg_type',
						'show_when' => 'slider'
					]
				],
				[
					'name'        => 'img_size',
					'label'       => 'Image Size',
					'type'        => 'text',
					'value'       => 'large',
					'description' => 'Entering full keyword to display the original size',
					'admin_label' => false
				]
			],
			'search form'          => [
				[
					'name'        => 'toggle_search_form',
					'label'       => 'Toggle Search Form',
					'type'        => 'select',
					'admin_label' => false,
					'value'       => 'enable',
					'options'     => [
						'enable'  => 'Enable',
						'disable' => 'Disable'
					]
				],
				[
					'name'        => 'search_form_position',
					'label'       => 'Search Form Style',
					'type'        => 'select',
					'admin_label' => false,
					'value'       => 'bottom',
					'options'     => [
						'right'  => 'Right of Screen',
						'bottom' => 'Bottom',
						'center' => 'Center'
					]
				],
				[
					'name'        => 'search_form_background',
					'label'       => 'Search Form Background',
					'type'        => 'select',
					'admin_label' => false,
					'value'       => 'hero_formDark__3fCkB',
					'options'     => [
						'hero_formWhite__3fCkB' => 'White',
						'hero_formDark__3fCkB'  => 'Black'
					]
				],
			],
			'list of suggestions ' => [
				[
					'name'        => 'toggle_list_of_suggestions',
					'label'       => 'Toggle The List Of Suggestions',
					'description' => 'A list of suggestion locations/categories will be shown on the Hero section if this feature is enabled.',
					'type'        => 'select',
					'options'     => [
						'enable'  => 'Enable',
						'disable' => 'Disable'
					],
					'value'       => 'enable'
				],
				[
					'name'  => 'maximum_terms_suggestion',
					'label' => 'Maximum Locations / Categories',
					'type'  => 'text',
					'value' => 6
				],
				[
					'name'    => 'taxonomy',
					'label'   => 'Get By',
					'type'    => 'select',
					'options' => [
						'listing_cat'      => 'Listing Category',
						'listing_location' => 'Listing Location',
						'custom'           => 'Custom Taxonomy'
					],
					'value'   => 'listing_cat'
				],
				[
					'name'    => 'orderby',
					'label'   => 'Order By',
					'type'    => 'select',
					'options' => [
						'count'         => 'Number of children',
						'id'            => 'ID',
						'slug'          => 'Slug',
						'specify_terms' => 'Specific Locations/Categories'
					],
					'value'   => 'count'
				],
				[
					'type'        => 'autocomplete',
					'label'       => 'Select Categories',
					'description' => 'This feature is available for Order By Specify Categories',
					'name'        => 'listing_cats',
					'relation'    => [
						'parent'    => 'taxonomy',
						'show_when' => 'listing_cat'
					]
				],
				[
					'type'        => 'autocomplete',
					'label'       => 'Select Locations (Optional)',
					'description' => 'This feature is available for Order By Specify Locations',
					'name'        => 'listing_locations',
					'relation'    => [
						'parent'    => 'taxonomy',
						'show_when' => 'listing_location'
					]
				],
				[
					'type'     => 'text',
					'label'    => 'Custom Taxonomy Key',
					'name'     => 'custom_taxonomy_key',
					'relation' => [
						'parent'    => 'taxonomy',
						'show_when' => 'custom'
					]
				],
				[
					'type'        => 'text',
					'label'       => 'Custom Term Ids',
					'description' => 'Please enter in custom taxonomy ids. Each id is separated by a command. EG: 1,2,3',
					'name'        => 'custom_taxonomy_ids',
					'relation'    => [
						'parent'    => 'taxonomy',
						'show_when' => 'custom'
					]
				]
			]
		]
	]
];
