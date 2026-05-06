<?php
return [
  'wilcity_kc_pricing' => [
    'name'        => esc_html__('Pricing Table', 'wilcity-shortcodes'),
    'description' => esc_html__('Display single icon', 'wilcity-shortcodes'),
    'icon'        => 'sl-paper-plane',
    'category'    => WILCITY_SC_CATEGORY,
    'params'      => [
      'general' => [
        [
          'name'        => 'items_per_row',
          'label'       => esc_html__('Items / Row', 'wilcity-shortcodes'),
          'type'        => 'select',
          'admin_label' => true,
          'options'     => [  // THIS FIELD REQUIRED THE PARAM OPTIONS
            'col-md-2 col-lg-2'   => '6 Items / Row',
            'wil-col-5 col-lg-2'  => '5 Items / Row',
            'col-md-3 col-lg-3'   => '4 Items / Row',
            'col-md-4 col-lg-4'   => '3 Items / Row',
            'col-md-6 col-lg-6'   => '2 Items / Row',
            'col-md-12 col-lg-12' => '1 Item / Row'
          ]
        ],
        [
          'name'        => 'listing_type',
          'label'       => 'Listing Type',
          'type'        => 'select',
          'admin_label' => true,
          'options'     => 'pricing_options'
        ],
        [
          'name'        => 'toggle_nofollow',
          'label'       => 'Add rel="nofollow" to Plan URL',
          'type'        => 'select',
          'admin_label' => true,
          'options'     => [
            'disable' => 'Disable',
            'enable'  => 'Enable'
          ],
          'default'     => 'disable'
        ]
      ],
      'styling' => [
        [
          'name' => 'css_custom',
          'type' => 'css'
        ]
      ]
    ]
  ]
];
