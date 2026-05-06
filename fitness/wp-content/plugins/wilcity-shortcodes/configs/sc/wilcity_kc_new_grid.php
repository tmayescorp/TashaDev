<?php
return [
  'wilcity_kc_new_grid' => [
    'name'     => 'New Listing Grid Layout',
    'icon'     => 'sl-paper-plane',
    'css_box'  => true,
    'category' => WILCITY_SC_CATEGORY,
    'params'   => [
      'general'          => [
        'heading',
        'heading_color',
        'desc',
        'desc_color',
        'header_desc_text_align',
        'toggle_viewmore',
        'viewmore_btn_name',
        'post_type',
        'taxonomy_types',
        'listing_locations',
        'listing_cats',
        'listing_location',
        'listing_cat',
        'posts_per_page',
        [
          'type'        => 'select',
          'label'       => 'Order By',
          'description' => 'In order to use Order by Random, please disable the cache plugin or exclude this page from cache.',
          'name'        => 'orderby',
          'options'     => 'listing_orderby'
        ],
        'order'
      ],
      'devices settings' => 'bootstrap_columns'
    ]
  ]
];
