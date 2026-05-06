<?php
return [
  'wilcity_kc_restaurant_listings' => [
    'name'     => 'Wilcity Restaurant List',
    'icon'     => 'sl-paper-plane',
    'css_box'  => true,
    'category' => WILCITY_SC_CATEGORY,
    'params'   => [
      'general'        => [
        [
          'name'        => 'heading_style',
          'label'       => 'Heading style',
          'type'        => 'select',
          'value'       => 'ribbon',
          'options'     => [
            'ribbon'  => 'Ribbon',
            'default' => 'Default'
          ],
          'admin_label' => true
        ],
        [
          'name'        => 'ribbon',
          'label'       => 'Ribbon',
          'type'        => 'text',
          'value'       => 'Menu',
          'relation'    => [
            'parent'    => 'heading_style',
            'show_when' => 'ribbon'
          ],
          'admin_label' => true
        ],
        [
          'name'        => 'ribbon_color',
          'label'       => 'Ribbon Color',
          'type'        => 'text',
          'value'       => '#fff',
          'relation'    => [
            'parent'    => 'heading_style',
            'show_when' => 'ribbon'
          ],
          'admin_label' => true
        ],
        [
          'name'        => 'heading',
          'label'       => 'Heading',
          'type'        => 'text',
          'value'       => 'Our Special Menu',
          'admin_label' => true
        ],
        [
          'name'        => 'heading_color',
          'label'       => 'Heading Color',
          'type'        => 'color_picker',
          'value'       => '',
          'admin_label' => true
        ],
        [
          'name'        => 'desc',
          'label'       => 'Description',
          'type'        => 'textarea',
          'value'       => 'Explore Delicious Flavour',
          'admin_label' => true
        ],
        [
          'name'        => 'desc_color',
          'label'       => 'Description Color',
          'type'        => 'color_picker',
          'value'       => '',
          'admin_label' => true
        ],
        [
          'name'        => 'header_desc_text_align',
          'label'       => 'Heading and Description Text Alignment',
          'type'        => 'select',
          'options'     => [
            'wil-text-center' => 'Center',
            'wil-text-left'   => 'Left',
            'wil-text-right'  => 'Right'
          ],
          'value'       => 'wil-text-center',
          'relation'    => [
            'parent'    => 'heading_style',
            'show_when' => 'default'
          ],
          'admin_label' => true
        ],
        [
          'name'  => 'excerpt_length',
          'label' => 'Excerpt Length',
          'type'  => 'text',
          'value' => 100
        ],
        [
          'type'    => 'select',
          'label'   => 'Toggle View More',
          'name'    => 'toggle_viewmore',
          'options' => [
            'disable' => 'Disable',
            'enable'  => 'Enable'
          ]
        ],
        [
          'type'     => 'text',
          'label'    => 'Button Name',
          'name'     => 'viewmore_btn_name',
          'relation' => [
            'parent'    => 'toggle_viewmore',
            'show_when' => 'enable'
          ],
          'std'      => 'View more'
        ],
        [
          'type'     => 'icon_picker',
          'label'    => 'View More Icon',
          'name'     => 'viewmore_icon',
          'relation' => [
            'parent'    => 'toggle_viewmore',
            'show_when' => 'enable'
          ]
        ]
      ],
      'query settings' => [
        [
          'name'        => 'post_type',
          'label'       => 'Post Type',
          'description' => 'We recommend using Using Belongs To Setting if this is <a href="https://documentation.wilcity.com/knowledgebase/customizing-listing-location-listing-category-page/" target="_blank">Customizing Taxonomy Page</a>',
          'type'        => 'select',
          'value'       => 'listing',
          'admin_label' => true,
          'options'     => \WILCITY_SC\SCHelpers::getListingPostTypeOptions()
        ],
        [
          'type'        => 'autocomplete',
          'label'       => 'Select Categories',
          'description' => 'Leave empty if you are working on Taxonomy Template',
          'name'        => 'listing_cats'
        ],
        [
          'type'        => 'autocomplete',
          'label'       => 'Select Locations',
          'description' => 'Leave empty if you are working on Taxonomy Template',
          'name'        => 'listing_locations'
        ],
        [
          'type'        => 'autocomplete',
          'label'       => 'Select Tags',
          'description' => 'Leave empty if you are working on Taxonomy Template',
          'name'        => 'listing_tags'
        ],
        [
          'type'        => 'text',
          'label'       => 'Taxonomy Key',
          'description' => 'This feature is useful if you want to use show up your custom taxonomy',
          'name'        => 'custom_taxonomy_key'
        ],
        [
          'type'        => 'text',
          'label'       => 'Your Custom Taxonomies IDs',
          'description' => 'Each taxonomy should separated by a comma, Eg: 1,2,3,4. Leave empty if you are working on Taxonomy Template',
          'name'        => 'custom_taxonomies_id'
        ],
        [
          'type'        => 'autocomplete',
          'label'       => 'Specify Listings',
          'description' => 'Leave empty if you are working on Taxonomy Template',
          'name'        => 'listing_ids'
        ],
        [
          'type'  => 'text',
          'label' => 'Maximum Items',
          'name'  => 'posts_per_page',
          'value' => 6
        ],
        [
          'type'        => 'select',
          'label'       => 'Order By',
          'description' => 'In order to use Order by Random, please disable the cache plugin or exclude this page from cache.',
          'name'        => 'orderby',
          'options'     => [
            'newest'           => 'Listing Date',
            'post_title'       => 'Listing Title',
            'menu_order'       => 'Listing Order',
            'best_viewed'      => 'Popular Viewed',
            'best_rated'       => 'Popular Rated',
            'best_shared'      => 'Popular Shared',
            'post__in'         => 'Like Specify Listing IDs field',
            'rand'             => 'Random',
            'nearbyme'         => 'Near By Me',
            'open_now'         => 'Open now',
            'premium_listings' => 'Premium Listings'
          ]
        ],
        [
          'type'    => 'select',
          'label'   => 'Order',
          'name'    => 'order',
          'options' => [
            'DESC' => 'DESC',
            'ASC'  => 'ASC',
          ]
        ]
      ]
    ]
  ]
];
