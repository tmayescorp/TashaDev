<?php
return [
  'wilcity_kc_map'                  => [
    'name'     => 'Wilcity Map',
    'icon'     => 'sl-paper-plane',
    'css_box'  => true,
    'category' => WILCITY_SC_CATEGORY,
    'params'   => [
      'general' => [
        [
          'name'    => 'type',
          'label'   => 'Default Listing Type',
          'type'    => 'select',
          'value'   => '',
          'options' => \WilokeListingTools\Framework\Helpers\General::getPostTypeOptions(false, false)
        ],
        [
          'name'    => 'style',
          'label'   => 'Listing Style',
          'type'    => 'select',
          'value'   => 'grid',
          'options' => [
            'grid'  => 'Grid',
            'list'  => 'List',
            'grid2' => 'Grid2'
          ]
        ],
        [
          'name'        => 'latlng',
          'label'       => 'Set Map Center',
          'type'        => 'text',
          'description' => 'Enter in the Latitude & Longitude value. EG: 123,456. 123 is latitude, 456 is longitude',
          'value'       => ''
        ],
        [
          'name'        => 'img_size',
          'label'       => 'Image Size',
          'type'        => 'text',
          'description' => 'For example: 200x300. 200: Image width. 300: Image height',
          'value'       => 'medium'
        ],
        [
          'name'        => 'img_size',
          'label'       => 'Image Size',
          'type'        => 'text',
          'description' => 'For example: 200x300. 200: Image width. 300: Image height',
          'value'       => 'medium'
        ],
        [
          'name'        => 'max_zoom',
          'type'        => 'text',
          'label'       => 'Maximum Zoom Value',
          'description' => 'If you are using a cache plugin, please flush cache to this setting take effect on your site.',
          'default'     => 21
        ],
        [
          'name'    => 'min_zoom',
          'type'    => 'text',
          'label'   => 'Minimum Zoom Value',
          'default' => 1
        ],
        [
          'name'    => 'default_zoom',
          'type'    => 'text',
          'label'   => 'Default Zoom Value',
          'default' => 2
        ],
        [
          'name'    => 'orderby',
          'label'   => 'Order By',
          'type'    => 'select',
          'value'   => 'menu_order',
          'options' => [
            'menu_order'  => 'Premium Listings',
            'post_date'   => 'Listing Date',
            'post_title'  => 'Listing Title',
            'best_viewed' => 'Popular Viewed',
            'best_rated'  => 'Popular Rated',
            'best_shared' => 'Popular Shared',
            'rand'        => 'Random',
            'nearbyme'    => 'Near By Me',
            'open_now'    => 'Open now'
          ]
        ],
        [
          'name'    => 'order',
          'label'   => 'Order',
          'type'    => 'select',
          'value'   => 'DESC',
          'options' => [
            'DESC' => 'DESC',
            'ASC'  => 'ASC'
          ]
        ]
      ]
    ]
  ]
];
