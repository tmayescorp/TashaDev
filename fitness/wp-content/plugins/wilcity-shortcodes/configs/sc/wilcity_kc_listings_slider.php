<?php
return [
  'wilcity_kc_listings_slider' => [
    'name'     => 'Listings Slider',
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
        'post_type',
        'listing_cats',
        'listing_locations',
        'custom_taxonomy_key',
        'custom_taxonomies_id',
        'listing_ids',
        'listing_orderby',
        'order',
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
      'listings on screens'   => [
        [
          'name'        => 'desktop_image_size',
          'label'       => 'Desktop Image Size',
          'description' => 'You can use the defined image sizes like: full, large, medium, wilcity_560x300 or 400,300 to specify the image width and height.',
          'type'        => 'text',
          'value'       => ''
        ],
        [
          'name'  => 'mobile_image_size',
          'label' => 'Mobile Image Size',
          'type'  => 'text',
          'value' => ''
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
          'name'        => 'autoplay',
          'label'       => 'Auto Play (In ms)',
          'type'        => 'text',
          'value'       => 100000,
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
