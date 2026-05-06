<?php
$prefix = 'wilcity_';

return [
    'listing_plan_category'           => [
        'id'           => 'listing_plan_category',
        'title'        => esc_html__('Listing Plan Category', 'wiloke-listing-tools'),
        'object_types' => ['listing_plan'],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => apply_filters(
            'wilcity/filter/wiloke-listing-tools/config/listing-plan/listing_plan_category',
            [
                [
                    'type'    => 'select',
                    'id'      => 'listing_plan_category',
                    'name'    => esc_html__('Category', 'wiloke-listing-tools'),
                    'options' => apply_filters(
                        'wilcity/filter/wiloke-listing-tools/config/listing-plan/listing_plan_category/options',
                        [
                            'addlisting' => esc_html__('Add Listing / Event', 'wiloke-listing-tools')
                        ]
                    )
                ]
            ]
        )
    ],
    'listing_is_recommended'          => [
        'id'           => 'listing_is_recommended-addlisting',
        'title'        => esc_html__('Is Recommended', 'wiloke-listing-tools'),
        'object_types' => ['listing_plan'],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type' => 'checkbox',
                'id'   => 'wilcity_is_recommended',
                'name' => esc_html__('Yes', 'wiloke-listing-tools')
            ],
            [
                'type'    => 'text',
                'id'      => 'wilcity_recommend_text',
                'name'    => esc_html__('Description', 'wiloke-listing-tools'),
                'default' => esc_html__('Popular', 'wiloke-listing-tools')
            ],
        ]
    ],
    'exclude_from_claim_plans'        => [
        'id'           => 'exclude_from_claim_plans-addlisting',
        'title'        => 'Exclude From Paid Claim',
        'description'  => 'This plan won\'t be shown if you are using Paid Claim feature',
        'object_types' => ['listing_plan'],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type' => 'checkbox',
                'id'   => 'wilcity_exclude_from_claim_plans',
                'name' => 'Yes'
            ]
        ]
    ],
    'plan-basic-settings'        => [
        'id'           => 'plan-basic-settings',
        'title'        => 'Basic Settings',
        'description'  => 'This plan won\'t be shown if you are using Paid Claim feature',
        'object_types' => ['listing_plan'],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type'      => 'wiloke_field',
                'fieldType' => 'input',
                'id'        => 'add_listing_plan:compare_at_price',
                'name'      => 'Compare At Price'
            ],
	        [
		        'type'      => 'wiloke_field',
		        'fieldType' => 'input',
		        'id'        => 'add_listing_plan:regular_price',
		        'name'      => 'Regular Price'
	        ],
//	        [
//		        'type'      => 'wiloke_field',
//		        'fieldType' => 'input',
//		        'id'        => 'add_listing_plan:banner_oncetime_payment_price',
//		        'name'      => 'Banner Once Time Payment Price'
//	        ],
            [
                'type'      => 'wiloke_field',
                'fieldType' => 'input',
                'id'        => 'add_listing_plan:availability_items',
                'name'      => 'Availability Listings'
            ],
            [
                'type'      => 'wiloke_field',
                'fieldType' => 'input',
                'id'        => 'add_listing_plan:trial_period',
                'name'      => 'Trial Period (Unit: Day) - This is for Recurring Payment only'
            ],
            [
                'type'      => 'wiloke_field',
                'fieldType' => 'input',
                'id'        => 'add_listing_plan:regular_period',
                'name'      => 'Period Day'
            ]
        ]
    ],
    'listing_plan_settings'           => [
        'id'           => 'listing_plan_settings-addlisting',
        'title'        => esc_html__('Plan Settings', 'wiloke-listing-tools'),
        'object_types' => ['listing_plan'],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => apply_filters(
            'wilcity/filter/wiloke-listing-tools/config/listing-plan/listing_plan_settings',
            [
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_featured_image',
                    'name'      => 'Toggle Featured Image',
                    'options'   => [
                        'enable'  => 'Enable',
                        'disable' => 'Disable'
                    ]
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_cover_image',
                    'name'      => 'Toggle Cover Image',
                    'options'   => [
                        'enable'  => 'Enable',
                        'disable' => 'Disable'
                    ]
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_logo',
                    'name'      => 'Toggle Logo',
                    'options'   => [
                        'enable'  => 'Enable',
                        'disable' => 'Disable'
                    ]
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_sidebar_statistics',
                    'name'      => 'Toggle Sidebar Statistics',
                    'options'   => [
                        'enable'  => 'Enable',
                        'disable' => 'Disable'
                    ]
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_schema_markup',
                    'name'      => 'Toggle Schema Markup',
                    'options'   => [
                        'enable'  => 'Enable',
                        'disable' => 'Disable'
                    ]
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_business_hours',
                    'name'      => 'Toggle Business Hours',
                    'options'   => [
                        'enable'  => 'Enable',
                        'disable' => 'Disable'
                    ]
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_price_range',
                    'name'      => 'Toggle Price Range',
                    'options'   => [
                        'enable'  => 'Enable',
                        'disable' => 'Disable'
                    ]
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_single_price',
                    'name'      => 'Toggle Single Price',
                    'options'   => [
                        'enable'  => 'Enable',
                        'disable' => 'Disable'
                    ]
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_email',
                    'name'      => 'Toggle Email Address',
                    'options'   => [
                        'enable'  => 'Enable',
                        'disable' => 'Disable'
                    ]
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_phone',
                    'name'      => 'Toggle Phone',
                    'options'   => [
                        'enable'  => 'Enable',
                        'disable' => 'Disable'
                    ]
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_website',
                    'name'      => 'Toggle Website',
                    'options'   => [
                        'enable'  => 'Enable',
                        'disable' => 'Disable'
                    ]
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_social_networks',
                    'name'      => 'Toggle Social Networks',
                    'options'   => [
                        'enable'  => 'Enable',
                        'disable' => 'Disable'
                    ]
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_listing_tag',
                    'name'      => 'Toggle Listing Tags',
                    'options'   => [
                        'enable'  => 'Enable',
                        'disable' => 'Disable'
                    ]
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_gallery',
                    'name'      => 'Toggle Gallery',
                    'options'   => [
                        'enable'  => 'Enable',
                        'disable' => 'Disable'
                    ]
                ],
                [
                    'type'       => 'wiloke_field',
                    'fieldType'  => 'input',
                    'id'         => 'add_listing_plan:maximumGalleryImages',
                    'name'       => 'Maximum Gallery images can be uploaded in a listing.',
                    'default_cb' => ['WilokeListingTools\MetaBoxes\ListingPlan', 'getMaximumGalleryImagesAllowed']
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_videos',
                    'name'      => 'Toggle Video',
                    'options'   => [
                        'enable'  => 'Enable',
                        'disable' => 'Disable'
                    ]
                ],
                [
                    'type'       => 'wiloke_field',
                    'fieldType'  => 'input',
                    'id'         => 'add_listing_plan:maximumVideos',
                    'name'       => 'Maximum Videos can be added in a listing. Leave empty means unlimited',
                    'default_cb' => ['WilokeListingTools\MetaBoxes\ListingPlan', 'getMaximumVideosAllowed']
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_restaurant_menu',
                    'name'      => 'Toggle Restaurant Menus',
                    'options'   => [
                        'enable'  => 'Enable',
                        'disable' => 'Disable'
                    ]
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'input',
                    'id'        => 'add_listing_plan:maximumRestaurantMenus',
                    'name'      => 'Maximum Restaurant Menus can be added in a listing. Leave empty means unlimited'
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'input',
                    'id'        => 'add_listing_plan:maximumItemsInMenu',
                    'name'      => 'Maximum Items can be added in a menu. Leave empty means unlimited'
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'input',
                    'id'        => 'add_listing_plan:maximum_restaurant_gallery_images',
                    'name'      => 'Maximum images can be used in a Menu Item',
                    'default'   => 4
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_google_ads',
                    'name'      => 'Showing Google Ads',
                    'options'   => [
                        'enable'  => 'Enable',
                        'disable' => 'Disable'
                    ]
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_bookingcombannercreator',
                    'name'      => 'Toggle Booking.com Banner on The Single Sidebar',
                    'options'   => [
                        'enable'  => 'Enable',
                        'disable' => 'Disable'
                    ]
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_admob',
                    'name'      => 'Showing AdMob On Mobile',
                    'options'   => [
                        'enable'  => 'Enable',
                        'disable' => 'Disable'
                    ]
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_coupon',
                    'name'      => 'Toggle Coupon',
                    'options'   => [
                        'enable'  => 'Enable',
                        'disable' => 'Disable'
                    ]
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'text',
                    'id'        => 'add_listing_plan:maximum_listing_tag',
                    'name'      => 'Maximum Tags can be used on this plan',
                    'value'     => ''
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_promotion',
                    'name'      => 'Showing Promotion Listings',
                    'options'   => [
                        'enable'  => 'Enable',
                        'disable' => 'Disable'
                    ]
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_my_products',
                    'name'      => 'My Products',
                    'options'   => [
                        'enable'  => 'Enable',
                        'disable' => 'Disable'
                    ]
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_my_posts',
                    'name'      => 'My Posts',
                    'options'   => [
                        'enable'  => 'Enable',
                        'disable' => 'Disable'
                    ]
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_my_advanced_products',
                    'name'      => 'My Advanced Products',
                    'options'   => [
                        'enable'  => 'Enable',
                        'disable' => 'Disable'
                    ]
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_custom_button',
                    'name'      => 'Toggle Custom Button',
                    'options'   => [
                        'enable'  => 'Enable',
                        'disable' => 'Disable'
                    ]
                ],
                [
                    'type'        => 'wiloke_field',
                    'fieldType'   => 'input',
                    'id'          => 'add_listing_plan:menu_order',
                    'name'        => 'Listing Order',
                    'description' => 'The the default order to the Listing. The higher order will get higher priority on the Search page'
                ],
                [
                    'type'      => 'wiloke_field',
                    'fieldType' => 'select',
                    'id'        => 'add_listing_plan:toggle_claim',
                    'name'      => 'Listing Claim Automatically Approved',
                    'options'   => [
                        'yes' => 'Yes',
                        'no'  => 'No'
                    ],
                    'description' => 'Select Yes to set Claimed status to listing that belongs to this plan. Select No to set Pending status to listing that belongs to this plan.'
                ],
            ]
        )
    ],
    'listing_woocommerce_association-addlisting' => [
        'id'           => 'listing_woocommerce_association',
        'title'        => esc_html__('WooCommerce Alias', 'wiloke-listing-tools'),
        'object_types' => ['listing_plan'],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type'       => 'select',
                'id'         => $prefix.'woocommerce_association',
                'name'       => esc_html__('Product Alias', 'wiloke-listing-tools'),
                'options_cb' => ['WilokeListingTools\MetaBoxes\ListingPlan', 'renderProductAlias']
            ]
        ]
    ]
];
