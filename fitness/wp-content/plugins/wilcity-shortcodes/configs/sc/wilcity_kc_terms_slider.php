<?php
return [
  'wilcity_kc_terms_slider' => [
    'name'     => 'Terms Slider',
    'icon'     => 'sl-paper-plane',
    'css_box'  => true,
    'category' => WILCITY_SC_CATEGORY,
    'params'   => [
      'general' => [
        'heading',
        'heading_color',
        'desc',
        'desc_color',
        'header_desc_text_align',
        'term_redirect',
        'taxonomy_types',
        'listing_locations',
        'listing_cats',
        'listing_location',
        'listing_cat',
        'post_type'
      ],
      'design'  => 'items_on_screen',
      'styling' => [
        [
          'name' => 'css_custom',
          'type' => 'css'
        ]
      ]
    ]
  ]
];
