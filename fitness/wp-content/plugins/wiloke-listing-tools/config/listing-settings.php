<?php

use WilokeListingTools\Framework\Helpers\General;

$aEventFilters = [
    'any_event'               => esc_html__('Any', 'wiloke-listing-tools'),
    'upcoming_event'          => esc_html__('Upcoming', 'wiloke-listing-tools'),
    'ongoing_event'           => esc_html__('Ongoing', 'wiloke-listing-tools'),
    'today_event'             => esc_html__('Today', 'wiloke-listing-tools'),
    'tomorrow_event'          => esc_html__('Tomorrow', 'wiloke-listing-tools'),
    'this_week_event'         => esc_html__('This week', 'wiloke-listing-tools'),
    'next_week_event'         => esc_html__('Next week', 'wiloke-listing-tools'),
    'this_month_event'        => esc_html__('This month', 'wiloke-listing-tools'),
    'pick_a_date_event'       => esc_html__('Pick a date', 'wiloke-listing-tools'),
    'wilcity_event_starts_on' => esc_html__('Event Date', 'wiloke-listing-tools'),
    'menu_order'              => esc_html__('Recommended', 'wiloke-listing-tools')
];

return [
    'eventFilterOptions'      => $aEventFilters,
    'order'                   => [
        'ASC'  => esc_html__('ASC', 'wiloke-listing-tools'),
        'DESC' => esc_html__('DESC', 'wiloke-listing-tools'),
    ],
    'orderby'                 => apply_filters(
        'wilcity/filter/wiloke-listing-tools/config/listing-settings/orderby',
        [
            'post_date'   => esc_html__('Post Date', 'wiloke-listing-tools'),
            'newest'      => esc_html__('Newest', 'wiloke-listing-tools'),
            'post_title'  => esc_html__('Post Title', 'wiloke-listing-tools'),
            'menu_order'  => esc_html__('Recommended', 'wiloke-listing-tools'),
            'best_rated'  => esc_html__('Best Rated', 'wiloke-listing-tools'),
            'best_viewed' => esc_html__('Best Viewed', 'wiloke-listing-tools'),
            'best_shared' => esc_html__('Popular Shared', 'wiloke-listing-tools'),
            'rand'        => esc_html__('Random', 'wiloke-listing-tools'),
            'best_sales'  => esc_html__('Best Sales', 'wiloke-listing-tools'),
        ]
    ),
    'timezone'                => [
        'id'         => 'listing_timezone',
        'title'      => 'Timezone',
        'context'    => 'normal',
        'priority'   => 'low',
        'show_names' => true, // Show field names on the left
        'fields'     => [
            [
                'name' => 'Timezone',
                'id'   => 'wilcity_timezone',
                'type' => 'text'
            ]
        ]
    ],
    'myProducts'              => [
        'id'           => 'my_products',
        'title'        => 'My Products',
        'object_types' => General::getPostTypeKeys(false, false),
        'context'      => 'normal',
        'priority'     => 'low',
        'save_fields'  => false,
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type'       => 'select',
                'id'         => 'wilcity_my_product_mode',
                'name'       => 'Mode',
                'default_cb' => ['WilokeListingTools\MetaBoxes\Listing', 'getMyProductMode'],
                'options'    => [
                    'author_products'      => 'Get all author products',
                    'specify_products'     => 'Specify products',
                    'specify_product_cats' => 'Specify Product Categories',
                    'inherit'              => 'Inherit Theme Options'
                ]
            ],
            [
                'name'       => 'Product Categories',
                'id'         => 'wilcity_my_product_cats',
                'type'       => 'term_ajax_search',
                'multiple'   => true,
                'limit'      => 10,
                'query_args' => [
                    'taxonomy' => 'product_cat'
                ],
                'default_cb' => ['WilokeListingTools\MetaBoxes\Listing', 'getMyProductCats']
            ],
            [
                'type'        => 'select2_posts',
                'description' => 'Showing WooCommerce Products on this Listing page',
                'post_types'  => ['product'],
                'attributes'  => [
                    'ajax_action' => 'wilcity_fetch_dokan_products',
                    'post_types'  => 'product'
                ],
                'id'          => 'wilcity_my_products',
                'multiple'    => true,
                'name'        => 'My Products',
                'default_cb'  => ['WilokeListingTools\MetaBoxes\Listing', 'getMyProducts']
            ]
        ]
    ],
    'myNumberRestaurantMenus' => [
        'id'           => 'wilcity_number_restaurant_menus_wrapper',
        'title'        => 'Restaurant Menu Control',
        'object_types' => General::getPostTypeKeys(false, true),
        'context'      => 'normal',
        'priority'     => 'low',
        'save_fields'  => true,
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type'    => 'text',
                'id'      => 'wilcity_number_restaurant_menus',
                'name'    => '',
                'after'   => '<button id="wilcity-add-menu-restaurant" class="button button-primary">' .
                    esc_html__('Add New Menu', 'wiloke-listing-tools') . '</button>',
                'default' => 1
            ],
            [
                'type'        => 'hidden',
                'description' => '',
                'id'          => 'wilcity_changed_menu_restaurant',
                'name'        => 'Has Changed',
                'save_field'  => false,
                'default'     => ''
            ],
            [
                'type'        => 'hidden',
                'description' => '',
                'id'          => 'wilcity_menu_restaurant_keys',
                'name'        => 'Keys',
                'save_field'  => false,
                'default'     => ''
            ]
        ]
    ],
    'myRestaurantMenu'        => [
        'id'               => 'wilcity_restaurant_menu_group',
        'title'            => 'Restaurant Menu Settings',
        'object_types'     => General::getPostTypeKeys(false, true),
        'context'          => 'normal',
        'priority'         => 'low',
        'save_fields'      => true,
        'show_names'       => true, // Show field names on the left
        'general_settings' => [
            'general_settings' => [
                [
                    'name' => 'Menu Title',
                    'id'   => 'wilcity_group_title',
                    'type' => 'text'
                ],
                [
                    'name' => 'Menu Description',
                    'id'   => 'wilcity_group_description',
                    'type' => 'text'
                ],
                [
                    'name'        => 'Menu Icon',
                    'description' => 'You can use <a href="https://fontawesome.com/v4.7.0/" target="_blank">FontAwesome 4</a> or <a href="https://documentation.wilcity.com/knowledgebase/line-icon/" target="_blank">Line Awesome</a>',
                    'id'          => 'wilcity_group_icon',
                    'type'        => 'text'
                ]
            ],
            'group_settings'   => [
                'id'          => 'wilcity_restaurant_menu_group',
                'type'        => 'group',
                'description' => 'Setting up Menu',
                'repeatable'  => true, // use false if you want non-repeatable group
                'after_group' => '<a class="wilcity-delete-menu-restaurant" style="position: absolute; color: red; bottom: 10px; right: 10px; padding: 10px;" href="#">Delete Menu</a>',
                'options'     => [
                    'group_title'    => __('Menu', 'cmb2'),
                    'add_button'     => __('Add new Item', 'wiloke-listing-tools'),
                    'remove_button'  => __('Remove Item', 'wiloke-listing-tools'),
                    'sortable'       => true,
                    'closed'         => true,
                    'remove_confirm' => esc_html__('Are you sure you want to remove?', 'wiloke-listing-tools')
                ]
            ]
        ],
        'group_fields'     => [
            [
                'name' => 'Gallery',
                'id'   => 'gallery',
                'type' => 'file_list'
            ],
            [
                'name' => 'Title',
                'id'   => 'title',
                'type' => 'text'
            ],
            [
                'name' => 'Description',
                'id'   => 'description',
                'type' => 'textarea'
            ],
            [
                'name' => 'Price',
                'id'   => 'price',
                'type' => 'text'
            ],
            [
                'name' => 'Link To',
                'id'   => 'link_to',
                'type' => 'text'
            ],
            [
                'name'    => 'Is open new window?',
                'id'      => 'is_open_new_window',
                'type'    => 'select',
                'options' => [
                    'yes' => 'Yes',
                    'no'  => 'No'
                ]
            ]
        ]
    ],
    'myRoom'                  => [
        'id'           => 'wilcity_my_room',
        'title'        => 'My Room',
        'object_types' => General::getPostTypeKeys(false, true),
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type'        => 'select2_posts',
                'description' => 'Showing Rooms on this Listing page',
                'post_types'  => ['product'],
                'attributes'  => [
                    'ajax_action' => 'wilcity_fetch_my_room',
                    'post_types'  => 'product'
                ],
                'id'          => 'wilcity_my_room',
                'multiple'    => false,
                'default_cb'  => ['WilokeListingTools\MetaBoxes\Listing', 'getMyRoom']
            ]
        ]
    ],
    'myPosts'                 => [
        'id'           => 'wilcity_posts',
        'save_fields'  => false,
        'title'        => 'My Posts',
        'object_types' => General::getPostTypeKeys(false, true),
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type'        => 'select2_posts',
                'description' => 'Showing Posts on this Listing page',
                'post_types'  => ['post'],
                'attributes'  => [
                    'ajax_action' => 'wilcity_fetch_my_posts',
                    'post_types'  => 'post'
                ],
                'id'          => 'wilcity_my_posts',
                'multiple'    => true,
                'default_cb'  => ['WilokeListingTools\MetaBoxes\Listing', 'getMyPosts']
            ]
        ]
    ],
    'sidebars'                => [
        [
            'id'        => 'general',
            'icon'      => 'la la-check-square',
            'name'      => esc_html__('General', 'wiloke-listing-tools'),
            'component' => 'WilokeSingleGeneral'
        ],
        [
            'id'        => 'edit-navigation',
            'icon'      => 'la la-check-square',
            'name'      => esc_html__('Edit Navigation', 'wiloke-listing-tools'),
            'component' => 'WilokeSingleEditNavigation'
        ],
        [
            'id'        => 'edit-sidebar',
            'icon'      => 'la la-database',
            'name'      => esc_html__('Edit Sidebar', 'wiloke-listing-tools'),
            'component' => 'WilokeSingleEditSidebar'
        ]
    ],
    'heroSearchFields'        => [
        'date_range'       => [
            'adminCategory' => 'wil-date-range',
            'type'          => 'wil-date-range',
            'label'         => 'Event Date',
            'key'           => 'date_range',
            'valueFormat'   => 'array',
            'inPostTypes'   => ['event'],
            'isDefault'     => true
        ],
        'event_filter'     => [
            'adminCategory' => 'wil-event-filter',
            'desc'          => 'We recommend using this option on Event Search page instead of Order By ',
            'type'          => 'wil-select-tree',
            'isMultiple'    => 'no',
            'isAjax'        => 'no',
            'label'         => 'Event Filter',
            'key'           => 'event_filter',
            'valueFormat'   => 'string',
            'inPostTypes'   => ['event'],
            'isDefault'     => true,
            'options'       => $aEventFilters
        ],
        'complex'          => [
            'adminCategory' => 'wil-other',
            'type'          => 'wil-auto-complete',
            'label'         => 'Where are you looking for? (Complex Search))',
            'key'           => 'complex',
            'originalKey'   => 'complex',
            'valueFormat'   => 'String',
            'isDefault'     => true
        ],
        'google_place'     => [
            'adminCategory' => 'wil-other',
            'type'          => 'wil-auto-complete',
            'label'         => 'Where to look?',
            'radiusLabel'   => 'Radius',
            'key'           => 'google_place',
            'maxRadius'     => 500,
            'defaultRadius' => 200,
            'unit'          => 'km',
            'isDefault'     => true
        ],
        'wp_search'        => [
            'adminCategory' => 'wil-other',
            'type'          => 'wil-auto-complete',
            'label'         => 'Search By Listing Title?',
            'key'           => 'wp_search',
            'valueFormat'   => 'String',
            'searchTarget'  => 'listing',
            'isDefault'     => true
        ],
        'listing_location' => [
            'adminCategory'    => 'wil-term',
            'type'             => 'wil-select-tree',
            'label'            => 'Region',
            'group'            => 'term',
            'key'              => 'listing_location',
            'ajaxAction'       => 'wilcity_select2_fetch_term',
            'isAjax'           => 'no',
            'isShowParentOnly' => 'no',
            'orderBy'          => 'count',
            //            'isMultiple'       => 'yes',
            'order'            => 'DESC',
            'isHideEmpty'      => 'no',
            'isDefault'        => true
        ],
        'listing_cat'      => [
            'adminCategory'    => 'wil-term',
            'type'             => 'wil-select-tree',
            'label'            => 'Category',
            'group'            => 'term',
            'isAjax'           => 'no',
            //            'isMultiple'       => 'no',
            'ajaxAction'       => 'wilcity_select2_fetch_term',
            'key'              => 'listing_cat',
            'orderBy'          => 'count',
            'order'            => 'DESC',
            'isShowParentOnly' => 'no',
            'isHideEmpty'      => 'no',
            'isDefault'        => true
        ]
    ],
    'searchFields'            => [
        'date_range'       => [
            'adminCategory'       => 'wil-date-range',
            'type'                => 'wil-search-dropdown',
            'childType'           => 'wil-date-range',
            'oldType'             => 'wil-date-range',
            'hasFooterController' => 'no',
            'isInitialOpen'       => 'yes',
            'isConfirm'           => 'yes',
            'isInline'            => 'yes',
            'label'               => 'Event Date',
            'key'                 => 'date_range',
            'valueFormat'         => 'array',
            'inPostTypes'         => ['event'],
            'isDefault'           => true
        ],
        'event_filter'     => [
            'adminCategory' => 'wil-event-filter',
            'desc'          => 'We recommend using this option on Event Search page instead of Order By',
            'type'          => 'wil-search-dropdown',
            'childType'     => 'wil-radio',
            'oldType'       => 'wil-select-tree',
            'label'         => 'Event Filter',
            'key'           => 'event_filter',
            'valueFormat'   => 'string',
            'inPostTypes'   => ['event'],
            'options'       => array_merge(
                $aEventFilters,
                ['pick_a_date_event' => esc_html__('Pick a date', 'wiloke-listing-tools')]
            )
        ],
        'wp_search'        => [
            'adminCategory' => 'wil-other',
            'type'          => 'wil-search-dropdown',
            'oldType'       => 'wil-input',
            'childType'     => 'wil-input',
            'label'         => 'What are you looking for?',
            'key'           => 'keyword', // wp_search
            'originalKey'   => 'wp_search',
            'isDefault'     => true
        ],
        'google_place'     => [
            'adminCategory' => 'wil-other',
            'type'          => 'wil-search-dropdown',
            'oldType'       => 'wil-auto-complete',
            'childType'     => 'wil-auto-complete',
            'label'         => 'Where to look?',
            'searchTarget'  => ['geocoder'],
            'radiusLabel'   => 'Radius',
            'key'           => 'google_place',
            'maxRadius'     => 500,
            'defaultRadius' => 200,
            'unit'          => 'km',
            'isDefault'     => true
        ],
        'listing_location' => [
            'adminCategory'    => 'wil-term',
            //            'type'             => 'select2',
            'type'             => 'wil-search-dropdown', // select2
            'childType'        => 'wil-checkbox',
            'oldType'          => 'wil-select-tree',
            'label'            => 'Region',
            'group'            => 'term',
            'key'              => 'listing_location',
            'ajaxAction'       => 'wilcity_select2_fetch_term',
            'isAjax'           => 'no',
            'isShowParentOnly' => 'no',
            'orderBy'          => 'count',
            'isMultiple'       => 'yes',
            'order'            => 'DESC',
            'isHideEmpty'      => 0,
            'isDefault'        => true
        ],
        'listing_cat'      => [
            'adminCategory'    => 'wil-term',
            //            'type'             => 'select2',
            'type'             => 'wil-search-dropdown', // select2
            'childType'        => 'wil-checkbox', // it may switch to wil-radio if custom want to disable multiple
            // option
            'oldType'          => 'wil-select-tree',
            'label'            => 'Category',
            'group'            => 'term',
            'isAjax'           => 'no',
            'isMultiple'       => 'no',
            'ajaxAction'       => 'wilcity_select2_fetch_term',
            'key'              => 'listing_cat',
            'orderBy'          => 'count',
            'order'            => 'DESC',
            'isShowParentOnly' => 'no',
            'isHideEmpty'      => 'no',
            'isDefault'        => true
        ],
        'listing_tag'      => [
            'adminCategory'    => 'wil-term',
            'type'             => 'wil-search-dropdown', // checkbox 2
            'childType'        => 'wil-checkbox',
            'oldType'          => 'wil-select-tree',
            //            'type'             => 'checkbox2',
            'label'            => 'Tags',
            'group'            => 'term',
            'ajaxAction'       => 'wilcity_select2_fetch_term',
            'isAjax'           => 'no',
            'key'              => 'listing_tag',
            'isHideEmpty'      => 'no',
            'orderBy'          => 'count',
            'order'            => 'DESC',
            'isShowParentOnly' => 'no',
            'isDefault'        => true,
            'isMultiple'       => 'no'
        ],
        'custom_taxonomy'  => [
            'adminCategory'    => 'wil-term',
            'type'             => 'wil-search-dropdown', // select
            'childType'        => 'wil-checkbox', // select 2
            'oldType'          => 'wil-select-tree',
            'label'            => 'Custom Taxonomy',
            'group'            => 'term',
            'key'              => 'custom_taxonomy',
            'originalKey'      => 'custom_taxonomy',
            'ajaxAction'       => 'wilcity_select2_fetch_term',
            'isAjax'           => 'no',
            'isShowParentOnly' => 'no',
            'orderBy'          => 'count',
            'isMultiple'       => 'yes',
            'order'            => 'DESC',
            'isHideEmpty'      => 'no',
            'isDefault'        => true,
            'isCustom'         => true,
            'isClone'          => true
        ],
        'price_range'      => [
            'adminCategory'  => 'wil-other',
            'type'           => 'wil-search-dropdown', // select2
            'childType'      => 'wil-radio',
            'oldType'        => 'wil-select-tree',
            'label'          => 'Price range',
            'notInPostTypes' => ['event'],
            'key'            => 'price_range',
            'isDefault'      => true
        ],
        'new_price_range'  => [
            'adminCategory' => 'wil-new-price-range',
            'type'          => 'wil-search-dropdown',
            'childType'     => 'wil-price-range',
            'valueFormat'   => 'object',
            //            'value'         => [
            //                'min' => 0,
            //                'max' => 0
            //            ],
            'label'         => 'New Price range',
            'key'           => 'price_range',
            'originalKey'   => 'new_price_range',
            'isDefault'     => true
        ],
        'orderby'          => [
            'adminCategory' => 'wil-orderby',
            'type'          => 'wil-search-dropdown',
            'oldType'       => 'wil-select-tree',
            'childType'     => 'wil-radio',
            'label'         => 'Order By',
            'key'           => 'orderby',
            'isDefault'     => true
        ],
        'order'            => [
            'adminCategory' => 'wil-order',
            'type'          => 'wil-search-dropdown',
            'oldType'       => 'wil-select-tree',
            'childType'     => 'wil-radio',
            'label'         => 'Sort By',
            'key'           => 'order',
            'isDefault'     => true
        ],
        'open_now'         => [
            'adminCategory'  => 'wil-other',
            //            'type'           => 'checkbox',
            'type'           => 'wil-toggle-btn', // checkbox
            'label'          => 'Open Now',
            'oldType'        => 'wil-switch',
            'key'            => 'open_now',
            'notInPostTypes' => ['event'],
            'isDefault'      => true
        ],
        'best_viewed'      => [
            'adminCategory' => 'wil-other',
            'type'          => 'wil-toggle-btn',  // checkbox
            'oldType'       => 'wil-switch',
            'label'         => 'Most Viewed',
            'key'           => 'best_viewed',
            'isDefault'     => true
        ],
        'best_shared'      => [
            'adminCategory' => 'wil-other',
            'type'          => 'wil-toggle-btn',  // checkbox
            'oldType'       => 'wil-switch',
            'label'         => 'Popular Shared',
            'key'           => 'best_shared',
            'isDefault'     => true
        ],
        'newest'           => [
            'adminCategory' => 'wil-other',
            'type'          => 'wil-toggle-btn',  // checkbox
            'oldType'       => 'wil-switch',
            'label'         => 'Newest',
            'key'           => 'newest',
            'isDefault'     => true
        ],
        'menu_order'       => [
            'adminCategory' => 'wil-other',
            'type'          => 'wil-toggle-btn', // dropdown
            'oldType'       => 'wil-switch',
            //            'type'          => 'checkbox',
            'label'         => 'Recommended',
            'key'           => 'menu_order',
            'isDefault'     => true
        ],
        'best_rated'       => [
            'adminCategory'  => 'wil-other',
            'type'           => 'wil-toggle-btn', // checkbox
            'oldType'        => 'wil-switch',
            'label'          => 'Rating',
            'key'            => 'best_rated',
            'notInPostTypes' => ['event'],
            'isDefault'      => true
        ],
        'discount'         => [
            'adminCategory' => 'wil-other',
            'type'          => 'wil-toggle-btn', // checkbox
            'oldType'       => 'wil-switch',
            'label'         => 'Discount',
            'key'           => 'discount',
            'isDefault'     => true
        ],
        'post_type'        => [
            'adminCategory'  => 'wil-other',
            'type'           => 'wil-search-dropdown',
            'childType'      => 'wil-select-tree',
            'oldType'        => 'wil-select-tree',
            'loadOptionMode' => 'default',
            'label'          => 'Type',
            'desc'           => 'The visitors can search other post types on the search form',
            'originalKey'    => 'post_type',
            'key'            => 'postType', // post_type
            'isDefault'      => true
        ],
        'nearbyme'         => [
            'adminCategory' => 'wil-other',
            'type'          => 'wil-toggle-btn',
            'oldType'       => 'wil-switch',
            'label'         => 'Near By Me',
            'desc'          => 'To setup radius and unit, please go to Appearance -> Theme Options -> Listing Type',
            'key'           => 'nearbyme',
            'isDefault'     => true
        ],
        'custom_dropdown'  => [
            'isClone'       => true,
            'isMultiple'    => 'yes',
            'adminCategory' => 'wil-custom-dropdown',
            'type'          => 'wil-search-dropdown',
            'childType'     => 'wil-checkbox',
            'label'         => 'Custom Dropdown',
            'originalKey'   => 'custom_dropdown',
            'key'           => 'custom_search_field'
        ]
    ],
    'navigation'              => [
        'fixed'     => [
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
        'draggable' => apply_filters(
            'wilcity/wiloke-listing-tools/filter/configs/listing-settings/navigation/draggable',
            [
                'restaurant_menu'  => [
                    'name'         => 'Restaurant Menu',
                    'key'          => 'restaurant_menu',
                    'isDraggable'  => 'yes',
                    'icon'         => 'la la-cutlery',
                    'isShowOnHome' => 'yes',
                    'status'       => 'no'
                ],
                'coupon'           => [
                    'name'         => 'Coupon',
                    'key'          => 'coupon',
                    'isDraggable'  => 'yes',
                    'icon'         => 'la la-tag',
                    'isShowOnHome' => 'yes',
                    'status'       => 'no'
                ],
                'photos'           => [
                    'name'               => 'Photos',
                    'key'                => 'photos',
                    'isDraggable'        => 'yes',
                    'icon'               => 'la la-image',
                    'isShowOnHome'       => 'yes',
                    'maximumItemsOnHome' => 4,
                    'status'             => 'yes'
                ],
                'content'          => [
                    'name'         => 'Description',
                    'key'          => 'content',
                    'isDraggable'  => 'yes',
                    'icon'         => 'la la-file-text',
                    'isShowOnHome' => 'yes',
                    'status'       => 'yes'
                ],
                'videos'           => [
                    'name'               => 'Videos',
                    'key'                => 'videos',
                    'isDraggable'        => 'yes',
                    'icon'               => 'la la-video-camera',
                    'isShowOnHome'       => 'yes',
                    'maximumItemsOnHome' => 4,
                    'status'             => 'yes'
                ],
                'tags'             => [
                    'name'               => 'Listing Features',
                    'key'                => 'tags',
                    'isDraggable'        => 'yes',
                    'icon'               => 'la la-list-alt',
                    'isShowOnHome'       => 'yes',
                    'maximumItemsOnHome' => 4,
                    'status'             => 'yes'
                ],
                'my_products'      => [
                    'name'               => 'My Products',
                    'key'                => 'my_products',
                    'isDraggable'        => 'yes',
                    'icon'               => 'la la-video-camera',
                    'isShowOnHome'       => 'no',
                    'maximumItemsOnHome' => 4,
                    'status'             => 'no'
                ],
                //                'my_advanced_products'      => [
                //                    'name'               => 'My Advanced Products',
                //                    'key'                => 'my_advanced_products',
                //                    'isDraggable'        => 'yes',
                //                    'icon'               => 'la la-video-camera',
                //                    'isShowOnHome'       => 'no',
                //                    'maximumItemsOnHome' => 4,
                //                    'status'             => 'no'
                //                ],
                'events'           => [
                    'name'               => 'Events',
                    'key'                => 'events',
                    'icon'               => 'la la-bookmark',
                    'isDraggable'        => 'yes',
                    'isShowOnHome'       => 'yes',
                    'maximumItemsOnHome' => 4,
                    'status'             => 'yes'
                ],
                'posts'            => [
                    'name'               => 'Posts',
                    'key'                => 'posts',
                    'icon'               => 'la la-pencil',
                    'isDraggable'        => 'yes',
                    'isShowOnHome'       => 'yes',
                    'maximumItemsOnHome' => 4,
                    'status'             => 'yes'
                ],
                'reviews'          => [
                    'name'               => 'Reviews',
                    'key'                => 'reviews',
                    'icon'               => 'la la-star-o',
                    'isDraggable'        => 'yes',
                    'isShowOnHome'       => 'yes',
                    'maximumItemsOnHome' => 4,
                    'status'             => 'yes'
                ],
                'google_adsense_1' => [
                    'name'           => 'Google AdSense 1',
                    'key'            => 'google_adsense_1',
                    'icon'           => 'la la-bullhorn',
                    'isDraggable'    => 'yes',
                    'isShowOnHome'   => 'no',
                    'isShowBoxTitle' => 'no',
                    'status'         => 'no'
                ],
                'google_adsense_2' => [
                    'name'           => 'Google AdSense 2',
                    'key'            => 'google_adsense_2',
                    'icon'           => 'la la-bullhorn',
                    'isDraggable'    => 'yes',
                    'isShowOnHome'   => 'no',
                    'isShowBoxTitle' => 'no',
                    'status'         => 'no'
                ],
                'taxonomy'         => [
                    'name'               => 'Listing Taxonomy',
                    'key'                => 'taxonomy',
                    'taxonomy'           => '',
                    'isDraggable'        => 'yes',
                    'icon'               => 'la la-bookmark',
                    'isShowOnHome'       => 'no',
                    'maximumItemsOnHome' => 3,
                    'status'             => 'no'
                ]
            ]
        )
    ],
    'sidebar_settings'        => apply_filters(
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
                    'name'   => esc_html__('Business Hours', 'wiloke-listing-tools'),
                    'key'    => 'businessHours',
                    'icon'   => 'la la-bookmark',
                    'status' => 'yes'
                ],
                'priceRange'              => [
                    'name'   => esc_html__('Price Range', 'wiloke-listing-tools'),
                    'key'    => 'priceRange',
                    'icon'   => 'la la-bookmark',
                    'status' => 'yes'
                ],
                'singlePrice'             => [
                    'name'   => esc_html__('Single Price', 'wiloke-listing-tools'),
                    'key'    => 'singlePrice',
                    'icon'   => 'la la-bookmark',
                    'status' => 'no'
                ],
                'businessInfo'            => [
                    'name'   => esc_html__('Business Info', 'wiloke-listing-tools'),
                    'key'    => 'businessInfo',
                    'icon'   => 'la la-bookmark',
                    'status' => 'yes'
                ],
                'statistic'               => [
                    'name'   => esc_html__('Statistic', 'wiloke-listing-tools'),
                    'key'    => 'statistic',
                    'icon'   => 'la la-bookmark',
                    'status' => 'yes'
                ],
                'categories'              => [
                    'name'   => esc_html__('Categories', 'wiloke-listing-tools'),
                    'key'    => 'categories',
                    'icon'   => 'la la-bookmark',
                    'status' => 'yes'
                ],
                'taxonomy'                => [
                    'name'     => 'Taxonomy',
                    'key'      => 'taxonomy',
                    'group'    => 'term',
                    'icon'     => 'la la-bookmark',
                    'isClone'  => 'yes',
                    'taxonomy' => '',
                    'status'   => 'no'
                ],
                'coupon'                  => [
                    'name'   => 'Coupon',
                    'key'    => 'coupon',
                    'icon'   => 'la la-bookmark',
                    'status' => 'no'
                ],
                'tags'                    => [
                    'name'   => esc_html__('Tags', 'wiloke-listing-tools'),
                    'key'    => 'tags',
                    'icon'   => 'la la-bookmark',
                    'status' => 'yes'
                ],
                'map'                     => [
                    'name'   => esc_html__('Map', 'wiloke-listing-tools'),
                    'key'    => 'map',
                    'icon'   => 'la la-bookmark',
                    'status' => 'yes'
                ],
                'author'                  => [
                    'name'   => esc_html__('Author', 'wiloke-listing-tools'),
                    'key'    => 'author',
                    'icon'   => 'la la-user',
                    'status' => 'yes'
                ],
                'claim'                   => [
                    'name'   => esc_html__('Claim Listing', 'wiloke-listing-tools'),
                    'key'    => 'claim',
                    'icon'   => 'la la-bookmark',
                    'status' => 'yes'
                ],
                'googleads'               => [
                    'name'      => 'Google AdSense',
                    'key'       => 'google_adsense',
                    'icon'      => 'la la-bullhorn',
                    'adminOnly' => 'yes', // Only admin can disable it on the single listing setting
                    'status'    => 'yes'
                ],
                'promotion'               => [
                    'promotionID'        => '',
                    'name'               => 'Promotion',
                    'key'                => 'promotion',
                    'style'              => 'slider',
                    'icon'               => 'la la-bullhorn',
                    'adminOnly'          => 'yes', // Only admin can disable it on the single listing setting
                    'status'             => 'yes',
                    'postsPerPage'       => 3,
                    'isMultipleSections' => 'yes'
                ],
                'relatedListings'         => [
                    'name'               => 'Related Listings',
                    'key'                => 'relatedListings',
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
                    'isMultipleSections' => 'yes'
                ],
                'bookingcombannercreator' => [
                    'name'   => 'Booking.com Banner Creator',
                    'key'    => 'bookingcombannercreator',
                    'icon'   => 'la la-hotel',
                    'status' => 'yes'
                ],
                'myProducts'              => [
                    'name'   => 'My Products',
                    'key'    => 'myProducts',
                    'icon'   => 'la la-shopping-cart',
                    'status' => 'no'
                ],
                'woocommerceBooking'      => [
                    'name'   => 'My Room',
                    'key'    => 'woocommerceBooking',
                    'icon'   => 'la la-shopping-cart',
                    'status' => 'no'
                ]
            ]),
            'updates'           => [
                'businessInfo' => [
                    'name'   => esc_html__('Business Info', 'wiloke-listing-tools'),
                    'key'    => 'businessInfo',
                    'icon'   => 'la la-bookmark',
                    'status' => 'yes'
                ]
            ],
            'deprecated'        => [
                'addressInfo'
            ]
        ]
    ),
    'defines'                 => [
        'layout'            => esc_html__('Layout', 'wiloke-listing-tools'),
        'layoutDesc'        => esc_html__('Customize your page layout', 'wiloke-listing-tools'),
        'addButton'         => esc_html__('Add a Button', 'wiloke-listing-tools'),
        'addButtonDesc'     => esc_html__(
            'The button at the top of your Page helps people take an action.',
            'wiloke-listing-tools'
        ),
        'websiteLink'       => esc_html__('Website / Phone / Email', 'wiloke-listing-tools'),
        'icon'              => esc_html__('Icon', 'wiloke-listing-tools'),
        'buttonName'        => esc_html__('Button Name', 'wiloke-listing-tools'),
        'rightSidebar'      => esc_html__('Right Sidebar', 'wiloke-listing-tools'),
        'leftSidebar'       => esc_html__('Left Sidebar', 'wiloke-listing-tools'),
        'navigation'        => esc_html__('Edit Navigation', 'wiloke-listing-tools'),
        'navigationDesc'    => esc_html__(
            'Click and drag a tab name to rearrange the order of the navigation.',
            'wiloke-listing-tools'
        ),
        'sidebar'           => esc_html__('Sidebar', 'wiloke-listing-tools'),
        'sidebarDesc'       => esc_html__(
            'Click and drag a sidebar item to rearrange the order',
            'wiloke-listing-tools'
        ),
        'isUseDefaultLabel' => esc_html__('Use Default?', 'wiloke-listing-tools')
    ],
    'keys'                    => [
        'navigation'           => 'navigation_settings',
        'sidebar'              => 'sidebar_settings',
        'isUsedDefaultSidebar' => 'using_sidebar_default',
        'isUsedDefaultNav'     => 'using_nav_default',
        'general'              => 'general_settings',
        'highlightBoxes'       => 'highlight_boxes',
        'card'                 => 'listing_card',
        'footer_card'          => 'listing_footer_card',
        'header_card'          => 'listing_header_card'
    ],
    'listingCard'             => [
        'aButtonInfoOptions' => [
            [
                'name'  => 'Call Us',
                'value' => 'call_us',
                'key'   => 'call_us'
            ],
            [
                'name'  => 'Email Us',
                'value' => 'email_us',
                'key'   => 'email_us'
            ],
            [
                'name'  => 'Total Views',
                'value' => 'total_views',
                'key'   => 'total_views'
            ]
        ],
        'bodyFields'         => [
            'google_address'   => [
                'name'    => 'Google Address',
                'hasIcon' => 'yes',
                'icon'    => 'la la-map-marker',
                'key'     => 'google_address',
                'type'    => 'google_address'
            ],
            'phone'            => [
                'name'    => 'Phone',
                'hasIcon' => 'yes',
                'icon'    => 'la la-mobile',
                'key'     => 'phone',
                'type'    => 'phone'
            ],
            'email'            => [
                'name'    => 'Email',
                'hasIcon' => 'yes',
                'icon'    => 'la la-envelope',
                'key'     => 'email',
                'type'    => 'email'
            ],
            'website'          => [
                'name'    => 'Website',
                'hasIcon' => 'yes',
                'icon'    => 'la la-link',
                'key'     => 'website',
                'type'    => 'website'
            ],
            'price_range'      => [
                'name'    => 'Price Range',
                'hasIcon' => 'yes',
                'icon'    => 'la la-money',
                'key'     => 'price_range',
                'type'    => 'price_range'
            ],
            'single_price'     => [
                'name'    => 'Single Range',
                'hasIcon' => 'yes',
                'icon'    => 'la la-money',
                'key'     => 'single_price',
                'type'    => 'single_price'
            ],
            'listing_cat'      => [
                'name'    => 'Listing Category',
                'hasIcon' => 'yes',
                'icon'    => 'la la-book',
                'key'     => 'listing_cat',
                'type'    => 'listing_cat'
            ],
            'listing_location' => [
                'name'    => 'Listing Location',
                'hasIcon' => 'yes',
                'icon'    => 'la la-globe',
                'key'     => 'listing_location',
                'type'    => 'listing_location'
            ],
            'listing_tag'      => [
                'name'    => 'Listing Tag',
                'hasIcon' => 'yes',
                'icon'    => 'la la-tag',
                'key'     => 'listing_tag',
                'type'    => 'listing_tag'
            ],
            //            'listing_coupon'      => [
            //                'name'    => 'Listing Coupon',
            //                'hasIcon' => 'yes',
            //                'icon'    => 'la la-flash',
            //                'key'     => 'coupon',
            //                'type'    => 'coupon'
            //            ],
            'custom_taxonomy'  => [
                'name'    => 'Custom Taxonomy',
                'isClone' => 'yes',
                'hasIcon' => 'yes',
                'type'    => 'custom_taxonomy',
                'key'     => ''
            ],
            'custom_field'     => [
                'name'             => 'Custom Field',
                'hasIcon'          => 'yes',
                'type'             => 'custom_field',
                'hasCustomContent' => 'yes',
                'key'              => ''
            ]
        ],
        'aBodyItems'         => [
            [
                'type' => 'google_address',
                'icon' => 'la la-map-marker',
                'key'  => 'google_address'
            ],
            [
                'type' => 'phone',
                'icon' => 'la la-phone',
                'key'  => 'phone'
            ],
            [
                'type' => 'email',
                'icon' => 'la la-envelope',
                'key'  => 'email'
            ],
            [
                'type' => 'website',
                'icon' => 'la la-link',
                'key'  => 'website'
            ],
            [
                'type' => 'price_range',
                'icon' => '',
                'key'  => 'price_range'
            ],
            [
                'type' => 'listing_tag',
                'icon' => '',
                'key'  => 'listing_tag'
            ],
            [
                'type' => 'listing_location',
                'icon' => '',
                'key'  => 'listing_location'
            ],
            [
                'type' => 'listing_cat',
                'icon' => '',
                'key'  => 'listing_cat'
            ],
            [
                'type'       => 'custom_taxonomy',
                'isFixedKey' => 'no',
                'icon'       => '',
                'key'        => ''
            ],
            [
                'type'       => 'custom_field',
                'isFixedKey' => 'no',
                'icon'       => '',
                'key'        => ''
            ],
        ],
        'aFooter'            => [
            'taxonomy' => 'listing_cat'
        ]
    ]
];
