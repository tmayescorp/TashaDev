<?php
return [
    'keys'            => [
        'navigation'       => 'navigation_settings',
        'isUsedDefaultNav' => 'using_nav_default',
        'general'          => 'general_settings'
    ],
    'fixed'           => [
        'home'     => [
            'name'        => esc_html__('Home', 'wiloke-listing-tools'),
            'key'         => 'home',
            'isDraggable' => 'no',
            'icon'        => 'la la-home',
            'status'      => 'yes'
        ],
        'insights' => [
            'name'        => esc_html__('Insights', 'wiloke-listing-tools'),
            'key'         => 'insights',
            'isDraggable' => 'no',
            'icon'        => 'la la-bar-chart',
            'status'      => 'yes'
        ],
        'settings' => [
            'name'        => esc_html__('Settings', 'wiloke-listing-tools'),
            'key'         => 'settings',
            'isDraggable' => 'no',
            'icon'        => 'la la-cog',
            'status'      => 'yes'
        ]
    ],
    // The field setting of each section
    'fields'          => [
        'common'   => [
            [
                'type'  => 'wil-input',
                'label' => 'Name',
                'key'   => 'name',
                'value' => '' // default value
            ],
            [
                'type'        => 'wil-auto-complete',
                'label'       => 'Key',
                'key'         => 'key',
                'conditional' => [
                    'includes' => apply_filters(
                        'wilcity/wiloke-listing-tools/filter/configs/single-nav/fields/key/conditional/includes',
                        ['custom_section']
                    )
                ],
                'value'       => '' // default value
            ],
            [
                'type'        => 'wil-input',
                'label'       => 'Key',
                'key'         => 'key',
                'conditional' => [
                    'includes' => apply_filters(
                        'wilcity/wiloke-listing-tools/filter/configs/single-nav/fields/key/conditional/includes',
                        ['taxonomy']
                    )
                ],
                'value'       => '' // default value
            ],
            [
                'type'        => 'wil-input',
                'label'       => 'Key',
                'variant'     => 'readonly',
                'key'         => 'key',
                'conditional' => [
                    'excludes' => apply_filters(
                        'wilcity/wiloke-listing-tools/filter/configs/single-nav/fields/key/conditional/excludes',
                        ['custom_section', 'taxonomy']
                    )
                ],
                'value'       => '' // default value
            ],
            [
                'type'        => 'wil-async-search',
                'label'       => 'Taxonomy',
                'key'         => 'taxonomy',
                'conditional' => [
                    'includes' => apply_filters('wilcity/wiloke-listing-tools/filter/configs/single-nav/fields/taxonomy/conditional/includes',
                        [
                            'taxonomy'
                        ])
                ],
                'value'       => '' // default value
            ],
            [
                'type'        => 'wil-select',
                'label'       => 'Show On Navigation?',
                'key'         => 'status',
                'conditional' => [
                    'excludes' => apply_filters('wilcity/wiloke-listing-tools/filter/configs/single-nav/fields/status/conditional/excludes',
                        [
                            'google_adsense_1',
                            'google_adsense_2',
                            'coupon'
                        ])
                ],
                'options'     => [
                    [
                        'name'  => 'Yes',
                        'value' => 'yes'
                    ],
                    [
                        'name'  => 'No',
                        'value' => 'no'
                    ]
                ]
            ],
            [
                'type'    => 'wil-select',
                'label'   => 'Show On Home?',
                'key'     => 'isShowOnHome',
                'value'   => 'yes',
                //                'conditional' => [
                //                    'excludes' => [
                //                        'google_adsense_1',
                //                        'google_adsense_2'
                //                    ]
                //                ],
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
            ],
            [
                'type'  => 'wil-icon',
                'label' => 'Select Icon',
                'key'   => 'icon',
                'value' => ''
            ],
            [
                'type'        => 'wil-select',
                'label'       => 'Is Show Box Title?',
                'key'         => 'isShowBoxTitle',
                'value'       => 'yes',
                'conditional' => [
                    'excludes' => apply_filters('wilcity/wiloke-listing-tools/filter/configs/single-nav/fields/isShowBoxTitle/conditional/excludes',
                        [
                            'google_adsense_1',
                            'google_adsense_2'
                        ]
                    )
                ],
                'options'     => [
                    [
                        'name'  => 'Yes',
                        'value' => 'yes'
                    ],
                    [
                        'name'  => 'No',
                        'value' => 'no'
                    ]
                ]
            ],
            [
                'type'        => 'wil-textarea',
                'label'       => 'Content',
                'key'         => 'content',
                'value'       => '',
                'conditional' => [
                    'includes' => apply_filters('wilcity/wiloke-listing-tools/filter/configs/single-nav/fields/content/conditional/includes',
                        [
                            'custom_section'
                        ]
                    )
                ],
                'description' => 'If you want to print the Custom Field that has been added
                      on AddListing setting, the key should follow this
                      structure: <i style="color:red"
                        >wilcity_single_navigation_[fieldKey]</i
                      >. Eg: wilcity_single_navigation_my_select_field'
            ]
        ],
        'sections' => apply_filters('wilcity/wiloke-listing-tools/filter/configs/single-nav/fields/sections', [
            'restaurant_menu'  => [
                'fields' => ['common']
            ],
            'coupon'           => [
                'fields' => ['common']
            ],
            'photos'           => [
                'fields' => [
                    'common',
                    [
                        [
                            'type'  => 'wil-input',
                            'label' => 'Maxium Items on Home',
                            'key'   => 'maximumItemsOnHome',
                            'value' => 4
                        ]
                    ]
                ]
            ],
            'content'          => [
                'fields' => ['common']
            ],
            'videos'           => [
                'fields' => [
                    [
                        [
                            'type'  => 'wil-input',
                            'label' => 'Maxium Items on Home',
                            'key'   => 'maximumItemsOnHome',
                            'value' => 4
                        ]
                    ],
                    'common'
                ]
            ],
            'tags'             => [
                'fields' => [
                    'common',
                    [
                        [
                            'type'  => 'wil-input',
                            'label' => 'Maxium Items on Home',
                            'key'   => 'maximumItemsOnHome',
                            'value' => 4
                        ]
                    ],
                ]
            ],
            'my_products'      => [
                'fields' => [
                    'common',
                    [
                        [
                            'type'  => 'wil-input',
                            'label' => 'Maxium Items on Home',
                            'key'   => 'maximumItemsOnHome',
                            'value' => 4
                        ]
                    ]
                ]
            ],
            'events'           => [
                'fields' => [
                    'common',
                    [
                        [
                            'type'  => 'wil-input',
                            'label' => 'Maxium Items on Home',
                            'key'   => 'maximumItemsOnHome',
                            'value' => 4
                        ]
                    ]
                ]
            ],
            'posts'            => [
                'fields' => [
                    'common',
                    [
                        [
                            'type'  => 'wil-input',
                            'label' => 'Maxium Items on Home',
                            'key'   => 'maximumItemsOnHome',
                            'value' => 4
                        ]
                    ]
                ]
            ],
            'reviews'          => [
                'fields' => [
                    'common',
                    [
                        [
                            'type'  => 'wil-input',
                            'label' => 'Maxium Items on Home',
                            'key'   => 'maximumItemsOnHome',
                            'value' => 4
                        ]
                    ]
                ]
            ],
            'google_adsense_1' => [
                'fields' => ['common']
            ],
            'google_adsense_2' => [
                'fields' => ['common']
            ],
            'taxonomy'         => [
                'fields' => [
                    'common',
                    'fields' => [
                        [
                            [
                                'type'  => 'wil-input',
                                'label' => 'Maxium Items on Home',
                                'key'   => 'maximumItemsOnHome',
                                'value' => 4
                            ]
                        ],
                        'common'
                    ]
                ]
            ],
            'custom_section'   => [
                'fields' => ['common']
            ]
        ])
    ],
    'defaultSections' => [
        'custom_section' => [
            'name'            => 'Custom Section',
            'key'             => uniqid('single-nav-item'),
            'icon'            => 'la la-image',
            'isCustomSection' => 'yes',
            'baseKey'         => 'custom_section',
            'isShowOnHome'    => 'yes',
            'isShowBoxTitle'  => 'yes',
            'status'          => 'yes',
            'content'         => ''
        ],
        'taxonomy'       => [
            'name'               => 'Listing Taxonomy',
            'key'                => 'taxonomy',
            'taxonomy'           => '',
            'isDraggable'        => 'yes',
            'icon'               => 'la la-bookmark',
            'isShowBoxTitle'     => 'yes',
            'isShowOnHome'       => 'no',
//            'maximumItemsOnHome' => 3,
            'status'             => 'no',
            'baseKey'            => 'taxonomy',
            'vueKey'             => uniqid('taxonomy')
        ]
    ],
    // The default value of each navigation section. The array key is like a base-key
    // We will get navigation setting based on this key
    // 'key' => key is navigation key. It may the same array key but it can different
    'draggable'       => apply_filters(
        'wilcity/wiloke-listing-tools/filter/configs/listing-settings/navigation/draggable',
        [
            'restaurant_menu'  => [
                'name'           => 'Restaurant Menu',
                'key'            => 'restaurant_menu',
                'isDraggable'    => 'yes',
                'icon'           => 'la la-cutlery',
                'isShowOnHome'   => 'yes',
                'isShowBoxTitle' => 'yes',
                'status'         => 'no',
                'baseKey'        => 'restaurant_menu',
                'vueKey'         => uniqid('restaurant_menu')
            ],
            'coupon'           => [
                'name'           => 'Coupon',
                'key'            => 'coupon',
                'isDraggable'    => 'yes',
                'icon'           => 'la la-tag',
                'isShowOnHome'   => 'yes',
                'isShowBoxTitle' => 'no',
                'status'         => 'no',
                'baseKey'        => 'coupon',
                'vueKey'         => uniqid('coupon')
            ],
            'photos'           => [
                'name'               => 'Photos',
                'key'                => 'photos',
                'isDraggable'        => 'yes',
                'icon'               => 'la la-image',
                'isShowBoxTitle'     => 'yes',
                'isShowOnHome'       => 'yes',
                'maximumItemsOnHome' => 4,
                'status'             => 'yes',
                'baseKey'            => 'photos',
                'vueKey'             => uniqid('photos')
            ],
            'content'          => [
                'name'           => 'Description',
                'key'            => 'content',
                'isDraggable'    => 'yes',
                'icon'           => 'la la-file-text',
                'isShowBoxTitle' => 'yes',
                'isShowOnHome'   => 'yes',
                'status'         => 'yes',
                'baseKey'        => 'content',
                'vueKey'         => uniqid('content')
            ],
            'videos'           => [
                'name'               => 'Videos',
                'key'                => 'videos',
                'isDraggable'        => 'yes',
                'icon'               => 'la la-video-camera',
                'isShowOnHome'       => 'yes',
                'isShowBoxTitle'     => 'yes',
                'maximumItemsOnHome' => 4,
                'status'             => 'yes',
                'baseKey'            => 'videos',
                'vueKey'             => uniqid('videos')
            ],
            'tags'             => [
                'name'               => 'Listing Features',
                'key'                => 'tags',
                'isDraggable'        => 'yes',
                'icon'               => 'la la-list-alt',
                'isShowOnHome'       => 'yes',
                'isShowBoxTitle'     => 'yes',
                'maximumItemsOnHome' => 4,
                'status'             => 'yes',
                'baseKey'            => 'tags',
                'vueKey'             => uniqid('tags')
            ],
            'my_products'      => [
                'name'               => 'My Products',
                'key'                => 'my_products',
                'isDraggable'        => 'yes',
                'icon'               => 'la la-video-camera',
                'isShowOnHome'       => 'no',
                'isShowBoxTitle'     => 'no',
                'maximumItemsOnHome' => 4,
                'status'             => 'no',
                'baseKey'            => 'my_products',
                'vueKey'             => uniqid('my_products')
            ],
            'events'           => [
                'name'               => 'Events',
                'key'                => 'events',
                'icon'               => 'la la-bookmark',
                'isDraggable'        => 'yes',
                'isShowOnHome'       => 'yes',
                'isShowBoxTitle'     => 'yes',
                'maximumItemsOnHome' => 4,
                'status'             => 'yes',
                'baseKey'            => 'events',
                'vueKey'             => uniqid('events')
            ],
            'posts'            => [
                'name'               => 'Posts',
                'key'                => 'posts',
                'icon'               => 'la la-pencil',
                'isDraggable'        => 'yes',
                'isShowBoxTitle'     => 'yes',
                'isShowOnHome'       => 'yes',
                'maximumItemsOnHome' => 4,
                'status'             => 'yes',
                'baseKey'            => 'posts',
                'vueKey'             => uniqid('posts')
            ],
            'reviews'          => [
                'name'               => 'Reviews',
                'key'                => 'reviews',
                'icon'               => 'la la-star-o',
                'isDraggable'        => 'yes',
                'isShowOnHome'       => 'yes',
                'isShowBoxTitle'     => 'yes',
                'maximumItemsOnHome' => 4,
                'status'             => 'yes',
                'baseKey'            => 'reviews',
                'vueKey'             => uniqid('reviews')
            ],
            'google_adsense_1' => [
                'name'           => 'Google AdSense 1',
                'key'            => 'google_adsense_1',
                'icon'           => 'la la-bullhorn',
                'isDraggable'    => 'yes',
                'isShowBoxTitle' => 'no',
                'isShowOnHome'   => 'no',
                'status'         => 'no',
                'adminOnly'      => 'yes',
                'baseKey'        => 'google_adsense_1',
                'vueKey'         => uniqid('google_adsense_1')
            ],
            'google_adsense_2' => [
                'name'           => 'Google AdSense 2',
                'key'            => 'google_adsense_2',
                'icon'           => 'la la-bullhorn',
                'isDraggable'    => 'yes',
                'isShowBoxTitle' => 'no',
                'isShowOnHome'   => 'no',
                'status'         => 'no',
                'adminOnly'      => 'yes',
                'baseKey'        => 'google_adsense_2',
                'vueKey'         => uniqid('google_adsense_2')
            ],
            // 'taxonomy'         => [
            //     'name'               => 'Listing Taxonomy',
            //     'key'                => 'taxonomy',
            //     'taxonomy'           => '',
            //     'isDraggable'        => 'yes',
            //     'icon'               => 'la la-bookmark',
            //     'isShowBoxTitle'     => 'yes',
            //     'isShowOnHome'       => 'no',
            //     'maximumItemsOnHome' => 4,
            //     'status'             => 'no',
            //     'baseKey'            => 'taxonomy',
            //     'vueKey'             => uniqid('taxonomy')
            // ]
        ]
    ),
    'shortcodes'      => apply_filters(
        'wilcity/wiloke-listing-tools/filter/configs/listing-settings/navigation/shortcodes',
        [
            'checkbox'                   => '[wilcity_render_checkbox_field key={{%sectionKey%}} wrapper_classes={{col-sm-6 col-sm-6-clear}} title={{My Title}} description={{My description}}]',
            'checkbox2'                  => '[wilcity_render_checkbox2_field key={{%sectionKey%}} wrapper_classes={{col-sm-6 col-sm-6-clear}} title={{My Title}} description={{My description}}]',
            'multiple-checkbox'          => '[wilcity_render_multiple-checkbox_field key={{%sectionKey%}} wrapper_classes={{col-sm-6 col-sm-6-clear}} title={{My Title}} description={{My description}}]',
            'select'                     => '[wilcity_render_select_field key={{%sectionKey%}} wrapper_classes={{col-sm-6 col-sm-6-clear}} title={{My Title}} description={{My description}}]',
            'group'                      => '[wilcity_group_properties group_key={{%sectionKey%}}]',
            'listing_type_relationships' => '[wilcity_render_listing_type_relationships key={{%sectionKey%}}]',
            'default'                    => '[wilcity_render_%sectionType%_field key={{%sectionKey%}}]'
        ]
    )
];
