<?php
return [
    'sidebar_settings' => apply_filters(
        'wilcity/wiloke-listing-tools/filter/configs/listing-settings/navigation/sidebar_settings',
        [
            'aStyles'           => [
                'list'   => 'List',
                'slider' => 'Slider',
                'grid'   => 'Grid'
            ],
            'aRelatedBy'        => [
                'listing_location' => 'In the same Listing Locations',
                'listing_category' => 'In the same Listing Categories',
                'listing_tag'      => 'In the same Listing Tags',
                'google_address'   => 'In the same Google Address',
            ],
            'toggleUseDefaults' => [
                'label' => esc_html__('Use default tabs', 'wiloke-listing-tools'),
                'value' => 'yes'
            ],
            'renderMachine'     => apply_filters('wilcity/wiloke-listing-tools/filter/sidebar-machine', [
                'singlePrice'             => 'wilcity_sidebar_single_price',
                'priceRange'              => 'wilcity_sidebar_price_range',
                'businessInfo'            => 'wilcity_sidebar_business_info',
                'businessHours'           => 'wilcity_sidebar_business_hours',
                'claim'                   => 'wilcity_sidebar_claim',
                'categories'              => 'wilcity_sidebar_categories',
                'taxonomy'                => 'wilcity_sidebar_taxonomy',
                'tags'                    => 'wilcity_sidebar_tags',
                'map'                     => 'wilcity_sidebar_googlemap',
                'statistic'               => 'wilcity_sidebar_statistics',
                'bookingcombannercreator' => 'wilcity_sidebar_bookingcombannercreator',
                'myProducts'              => 'wilcity_sidebar_my_products',
                'woocommerceBooking'      => 'wilcity_sidebar_woocommerce_booking',
                'author'                  => 'wilcity_author_profile',
                'coupon'                  => 'wilcity_sidebar_coupon',
                'relatedListings'         => 'wilcity_sidebar_related_listings',
            ]),
            'items'             => apply_filters('wilcity/wiloke-listing-tools/filter/sidebar-items', [
                'businessHours'           => [
                    'name'    => esc_html__('Business Hours', 'wiloke-listing-tools'),
                    'key'     => 'businessHours',
                    'baseKey' => 'businessHours',
                    'icon'    => 'la la-bookmark',
                    'status'  => 'yes'
                ],
                'priceRange'              => [
                    'name'    => esc_html__('Price Range', 'wiloke-listing-tools'),
                    'key'     => 'priceRange',
                    'baseKey' => 'priceRange',
                    'icon'    => 'la la-bookmark',
                    'status'  => 'yes'
                ],
                'singlePrice'             => [
                    'name'    => esc_html__('Single Price', 'wiloke-listing-tools'),
                    'key'     => 'singlePrice',
                    'baseKey' => 'singlePrice',
                    'icon'    => 'la la-bookmark',
                    'status'  => 'no'
                ],
                'businessInfo'            => [
                    'name'    => esc_html__('Business Info', 'wiloke-listing-tools'),
                    'key'     => 'businessInfo',
                    'baseKey' => 'businessInfo',
                    'icon'    => 'la la-bookmark',
                    'status'  => 'yes'
                ],
                'statistic'               => [
                    'name'    => esc_html__('Statistic', 'wiloke-listing-tools'),
                    'key'     => 'statistic',
                    'baseKey' => 'statistic',
                    'icon'    => 'la la-bookmark',
                    'status'  => 'yes'
                ],
                'categories'              => [
                    'name'    => esc_html__('Categories', 'wiloke-listing-tools'),
                    'key'     => 'categories',
                    'baseKey' => 'categories',
                    'icon'    => 'la la-bookmark',
                    'status'  => 'yes'
                ],
                'taxonomy'                => [
                    'name'     => 'Taxonomy',
                    'key'      => 'taxonomy',
                    'baseKey'  => 'taxonomy',
                    'group'    => 'term',
                    'icon'     => 'la la-bookmark',
                    'isClone'  => 'yes',
                    'taxonomy' => '',
                    'status'   => 'no'
                ],
                'coupon'                  => [
                    'name'    => 'Coupon',
                    'key'     => 'coupon',
                    'baseKey' => 'coupon',
                    'icon'    => 'la la-bookmark',
                    'status'  => 'no'
                ],
                'tags'                    => [
                    'name'    => esc_html__('Tags', 'wiloke-listing-tools'),
                    'key'     => 'tags',
                    'baseKey' => 'tags',
                    'icon'    => 'la la-bookmark',
                    'status'  => 'yes'
                ],
                'map'                     => [
                    'name'    => esc_html__('Map', 'wiloke-listing-tools'),
                    'key'     => 'map',
                    'baseKey' => 'map',
                    'icon'    => 'la la-bookmark',
                    'status'  => 'yes'
                ],
                'author'                  => [
                    'name'    => esc_html__('Author', 'wiloke-listing-tools'),
                    'key'     => 'author',
                    'baseKey' => 'author',
                    'icon'    => 'la la-user',
                    'status'  => 'yes'
                ],
                'claim'                   => [
                    'name'    => esc_html__('Claim Listing', 'wiloke-listing-tools'),
                    'key'     => 'claim',
                    'baseKey' => 'claim',
                    'icon'    => 'la la-bookmark',
                    'status'  => 'yes'
                ],
                'google_adsense'          => [
                    'name'      => 'Google AdSense',
                    'key'       => 'google_adsense',
                    'baseKey'   => 'google_adsense',
                    'icon'      => 'la la-bullhorn',
                    'adminOnly' => 'yes', // Only admin can disable it on the single listing setting
                    'status'    => 'yes'
                ],
                'promotion'               => [
                    'promotionID'        => '',
                    'name'               => 'Promotion',
                    'key'                => 'promotion',
                    'baseKey'            => 'promotion',
                    'style'              => 'slider',
                    'icon'               => 'la la-bullhorn',
                    'adminOnly'          => 'yes', // Only admin can disable it on the single listing setting
                    'status'             => 'yes',
                    'postsPerPage'       => 3,
                    'isMultipleSections' => 'yes',
                    'isClone'            => 'yes'
                ],
                'relatedListings'         => [
                    'name'               => 'Related Listings',
                    'key'                => 'relatedListings',
                    'baseKey'            => 'relatedListings',
                    'conditional'        => 'listing_category',
                    'order'              => 'DESC',
                    'style'              => 'slider',
                    'orderby'            => 'menu_order',
                    'oOrderFallbackBy'   => 'post_date',
                    'icon'               => 'la la-bullhorn',
                    'adminOnly'          => 'yes', // Only admin can disable it on the single listing setting
                    'status'             => 'yes',
                    'postsPerPage'       => 3,
                    'radius'             => 5,
                    'isMultipleSections' => 'yes',
                    'isClone'            => 'yes'
                ],
                'bookingcombannercreator' => [
                    'name'    => 'Booking.com Banner Creator',
                    'key'     => 'bookingcombannercreator',
                    'baseKey' => 'bookingcombannercreator',
                    'icon'    => 'la la-hotel',
                    'status'  => 'yes'
                ],
                'myProducts'              => [
                    'name'    => 'My Products',
                    'key'     => 'myProducts',
                    'baseKey' => 'myProducts',
                    'icon'    => 'la la-shopping-cart',
                    'status'  => 'no'
                ],
                'woocommerceBooking'      => [
                    'name'    => 'My Room',
                    'key'     => 'woocommerceBooking',
                    'baseKey' => 'woocommerceBooking',
                    'icon'    => 'la la-shopping-cart',
                    'status'  => 'no'
                ],
                'custom_section'          => [
                    'name'    => 'Custom Section',
                    'key'     => '',
                    'baseKey' => 'custom_section',
                    'icon'    => 'la la-shopping-cart',
                    'status'  => 'no'
                ]
            ]),
            'fields'            => apply_filters('wilcity/wiloke-listing-tools/filter/sidebar-fields', [
                'common'   => [
                    [
                        'type'  => 'wil-input',
                        'label' => 'Name',
                        'key'   => 'name',
                        'value' => '' // default value
                    ],
                    [
                        'type'  => 'wil-icon',
                        'label' => 'Section Icon',
                        'key'   => 'icon',
                        'value' => '' // default value
                    ],
                    [
                        'type'    => 'wil-select',
                        'label'   => 'Enable this sidebar item',
                        'key'     => 'status',
                        'value'   => 'yes', // default value
                        'options' => [
                            [
                                'name'  => 'Yes',
                                'value' => 'yes'
                            ],
                            [
                                'name'  => 'No',
                                'value' => 'no'
                            ]
                        ]
                    ]
                ],
                'sections' => [
                    'businessHours'           => [
                        'fields' => ['common']
                    ],
                    'priceRange'              => [
                        'fields' => ['common']
                    ],
                    'singlePrice'             => [
                        'fields' => ['common']
                    ],
                    'businessInfo'            => [
                        'fields' => ['common']
                    ],
                    'statistic'               => [
                        'fields' => ['common']
                    ],
                    'categories'              => [
                        'fields' => ['common']
                    ],
                    'taxonomy'                => [
                        'fields' => [
                            'common',
                            [
                                [
                                    'type'  => 'wil-async-search',
                                    'label' => 'Taxonomy Key',
                                    'key'   => 'taxonomy',
                                    'value' => '', // default value
                                ]
                            ]
                        ]
                    ],
                    'coupon'                  => [
                        'fields' => ['common']
                    ],
                    'tags'                    => [
                        'fields' => ['common']
                    ],
                    'map'                     => [
                        'fields' => ['common']
                    ],
                    'author'                  => [
                        'fields' => ['common']
                    ],
                    'claim'                   => [
                        'fields' => ['common']
                    ],
                    'google_adsense'          => [
                        'fields' => ['common']
                    ],
                    'promotion'               => [
                        'fields' => [
                            'common',
                            [
                                [
                                    'type'  => 'wil-auto-complete',
                                    'label' => 'Promotion ID',
                                    'key'   => 'promotionID',
                                    'value' => '', // default value
                                ],
                                [
                                    [
                                        'type'    => 'wil-select',
                                        'label'   => 'Style',
                                        'key'     => 'style',
                                        'value'   => 'slider', // default value
                                        'options' => [
                                            [
                                                'name'  => 'Slider',
                                                'value' => 'slider'
                                            ],
                                            [
                                                'name'  => 'List',
                                                'value' => 'list'
                                            ],
                                            [
                                                'name'  => 'Grid',
                                                'value' => 'grid'
                                            ]
                                        ]
                                    ],
                                    [
                                        'type'  => 'wil-input',
                                        'label' => 'Maximum Listings Can Be Shown',
                                        'key'   => 'postsPerPage',
                                        'value' => 3, // default value
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'relatedListings'         => [
                        'fields' => [
                            'common',
                            [
                                [
                                    'type'    => 'wil-select',
                                    'label'   => 'Related By',
                                    'key'     => 'conditional',
                                    'options' => [
                                        'listing_location' => 'In the same Listing Locations',
                                        'listing_category' => 'In the same Listing Categories',
                                        'listing_tag'      => 'In the same Listing Tags',
                                        'google_address'   => 'In the same Google Address',
                                    ]
                                ],
                                [
                                    'type'    => 'wil-select',
                                    'label'   => 'Style',
                                    'key'     => 'style',
                                    'value'   => 'slider', // default value
                                    'options' => [
                                        [
                                            'name'  => 'Slider',
                                            'value' => 'slider'
                                        ],
                                        [
                                            'name'  => 'List',
                                            'value' => 'list'
                                        ],
                                        [
                                            'name'  => 'Grid',
                                            'value' => 'grid'
                                        ]
                                    ]
                                ],
                                [
                                    'type'    => 'wil-select',
                                    'label'   => 'Order By',
                                    'key'     => 'orderBy',
                                    'value'   => 'post_date', // default value
                                    'options' => wilokeListingToolsRepository()->get('general:aOrderBy')
                                ],
                                [
                                    'type'    => 'wil-select',
                                    'label'   => 'Order By Fallback',
                                    'key'     => 'orderbyFallback',
                                    'value'   => '', // default value
                                    'options' => wilokeListingToolsRepository()->get('general:aOrderBy')
                                ],
                                [
                                    'type'  => 'wil-input',
                                    'label' => 'Maximum Listings Can Be Shown',
                                    'key'   => 'postsPerPage',
                                    'value' => 3, // default value
                                ]
                            ]
                        ]
                    ],
                    'bookingcombannercreator' => [
                        'fields' => ['common']
                    ],
                    'myProducts'              => [
                        'fields' => ['common']
                    ],
                    'woocommerceBooking'      => [
                        'fields' => ['common']
                    ],
                    'custom_section'          => [
                        'fields' => [
                            'common',
                            [
                                [
                                    'type'  => 'wil-auto-complete',
                                    'label' => 'Field Key (*)',
                                    'key'   => 'key',
                                    'value' => '', // default value
                                ],
                                [
                                    'type'  => 'wil-textarea',
                                    'label' => 'Content',
                                    'key'   => 'content'
                                ],
                            ]
                        ]
                    ]
                ]
            ]),
        ]
    )
];
