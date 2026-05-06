<?php
return [
  'wilcity_kc_grid' => [
    'name'     => 'Listings Grid Layout',
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
          'type'    => 'select',
          'label'   => 'Style',
          'name'    => 'style',
          'options' => [
            'grid'  => 'Grid 1 (Default)',
            'grid2' => 'Grid 2',
            'list'  => 'List'
          ],
          'std'     => 'style1'
        ],
        [
          'type'        => 'select',
          'label'       => 'Toggle Grid Border?',
          'description' => 'Adding a order around grid listing',
          'name'        => 'border',
          'options'     => [
            'border-gray-1' => 'Enable',
            'border-gray-0' => 'Disable'
          ],
          'std'         => 'border-gray-0'
        ],
        'toggle_viewmore'      => 'toggle_viewmore',
        'viewmore_btn_name'    => 'viewmore_btn_name',
        'post_type'            => 'post_type',
        'taxonomy_types'       => 'taxonomy_types',
        'listing_locations'    => 'listing_locations',
        'listing_cats'         => 'listing_cats',
        'listing_tags'         => 'listing_tags',
        'listing_location',
        'listing_cat',
//        'listing_tag',
        'custom_taxonomy_key'  => 'custom_taxonomy_key',
        'custom_taxonomies_id' => 'custom_taxonomies_id',
        'listing_ids'          => 'listing_ids',
        'posts_per_page'       => 'posts_per_page',
        [
          'type'        => 'select',
          'label'       => 'Order By',
          'description' => 'In order to use Order by Random, please disable the cache plugin or exclude this page from cache.',
          'name'        => 'orderby',
          'options'     => 'listing_orderby'
        ],
        'order'                => 'order',
        [
          'type'        => 'text',
          'label'       => 'Radius',
          'description' => 'Fetching all listings within x radius',
          'name'        => 'radius',
          'value'       => 10,
          'relation'    => [
            'parent'    => 'orderby',
            'show_when' => 'nearbyme'
          ]
        ],
        [
          'type'     => 'select',
          'label'    => 'Unit',
          'name'     => 'unit',
          'relation' => [
            'parent'    => 'orderby',
            'show_when' => 'nearbyme'
          ],
          'options'  => [
            'km' => 'KM',
            'm'  => 'Miles'
          ],
          'value'    => 'km'
        ],
        [
          'type'        => 'text',
          'label'       => 'Tab Name',
          'description' => 'If the grid layout is inside of a tab, we recommend putting the Tab ID to this field. If the tab is emptied, the listings will be shown after the browser is loaded. Otherwise, it will be shown after someone clicks on the Tab Name.',
          'name'        => 'tabname',
          'value'       => uniqid('nearbyme_'),
          'relation'    => [
            'parent'    => 'orderby',
            'show_when' => 'nearbyme'
          ]
        ]
      ],
      'image size'      => [
        [
          'type'        => 'text',
          'label'       => 'Desktop Image Size',
          'description' => 'For example: 200x300. 200: Image width. 300: Image height',
          'name'        => 'img_size',
          'value'       => 'medium'
        ],
        [
          'type'        => 'text',
          'label'       => 'Mobile Image Size',
          'description' => 'For example: 200x300. 200: Image width. 300: Image height',
          'name'        => 'mobile_img_size',
          'value'       => 'medium'
        ],
      ],
      'column settings' => 'bootstrap_columns',
      'styling'         => [
        [
          'name' => 'css_custom',
          'type' => 'css'
        ]
      ]
    ]
  ]
];
