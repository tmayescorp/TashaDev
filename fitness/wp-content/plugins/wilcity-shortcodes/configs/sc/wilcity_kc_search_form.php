<?php

use WilokeListingTools\Framework\Helpers\General;

return [
  'wilcity_kc_search_form' => [
    'name'     => esc_html__('Search Form', 'wilcity-shortcodes'),
    'icon'     => 'sl-paper-plane',
    'css_box'  => true,
    'category' => WILCITY_SC_CATEGORY,
    'params'   => [
      'general' => [
        [
          'name'        => 'style',
          'label'       => 'Style',
          'type'        => 'select',
          'value'       => 'default',
          'options'     => [
            'default' => 'Default',
            'creative'   => 'Creative'
          ],
          'admin_label' => true
        ],
        [
          'name'   => 'items',
          'label'  => 'Search Tab',
          'type'   => 'group',
          'value'  => [],
          'params' => [
            [
              'name'  => 'name',
              'label' => 'Tab Name',
              'type'  => 'text',
              'value' => 'Listing'
            ],
            [
              'name'    => 'post_type',
              'label'   => 'Listing Type',
              'type'    => 'select',
              'value'   => 'listing',
              'options' => General::getPostTypeOptions(false, false)
            ]
          ]
        ]
      ]
    ]
  ]
];
