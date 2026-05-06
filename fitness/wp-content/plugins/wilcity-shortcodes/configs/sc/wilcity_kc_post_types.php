<?php

use \WilokeListingTools\Framework\Helpers\General;

return [
  'wilcity_kc_post_types' => [
    'name'     => 'Wilcity Post Types',
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
        [
          'name'        => 'post_types',
          'label'       => 'Select Listing Types',
          'description' => 'Leave empty means get all listing types',
          'type'        => 'multiple',
          'value'       => '',
          'options'     => General::getPostTypeOptions(false, false)
        ],
        [
	        'name'        => 'image_size',
	        'label'       => 'Image Size',
	        'description' => 'You can use the defined image sizes like: full, large, medium',
	        'type'        => 'text',
	        'value'       => ''
        ]
      ],
      'device settings' => 'bootstrap_columns'
    ]
  ]
];
