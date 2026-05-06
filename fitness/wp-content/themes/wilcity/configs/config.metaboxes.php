<?php
global $wiloke;

use WilokeListingTools\Framework\Helpers\General;

$prefix = 'wilcity_';
$aPostTypes = [];
if (class_exists('WilokeListingTools\Framework\Helpers\General')) {
    $aPostTypes = General::getPostTypeOptions(false, false);
}

return [
    'wilcity_page_general_settings'       => [
        'id'           => 'wilcity_page_general_settings',
        'title'        => esc_html__('General Settings', 'wilcity'),
        'object_types' => ['page'],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type'        => 'file',
                'id'          => $prefix . 'logo',
                'name'        => 'Logo',
                'description' => 'This setting will override Theme Options setting'
            ],
            [
                'type'        => 'file',
                'id'          => $prefix . 'retina_logo',
                'name'        => 'Rentina Logo',
                'description' => 'This setting will override Theme Options setting'
            ],
            [
                'type'    => 'select',
                'id'      => $prefix . 'menu_background',
                'name'    => esc_html__('Menu background', 'wilcity'),
                'default' => 'inherit',
                'options' => [
                    'inherit'     => 'Inherit',
                    'transparent' => 'Transparent',
                    'dark'        => 'Dark',
                    'light'       => 'Light',
                    'custom'      => 'Custom'
                ]
            ],
            [
                'type' => 'colorpicker',
                'id'   => $prefix . 'custom_menu_background',
                'name' => 'Custom Menu background'
            ],
            [
                'type'    => 'select',
                'id'      => $prefix . 'toggle_menu_sticky',
                'name'    => 'Toggle Menu Sticky',
                'default' => 'inherit',
                'options' => [
                    'inherit' => 'Inherit Theme Options',
                    'enable'  => 'Enable',
                    'disable' => 'Disable'
                ]
            ]
        ]
    ],
    'wilcity_reset_password_settings'     => [
        'id'           => 'wilcity_reset_password_settings',
        'title'        => 'Reset Password Settings',
        'show_on'      => ['key' => 'page_template', 'value' => 'templates/reset-password.php'],
        'object_types' => ['page'],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type' => 'file',
                'id'   => $prefix . 'background_image',
                'name' => 'Background Image'
            ]
        ]
    ],
    'wilcity_search_without_map_settings' => [
        'id'           => 'wilcity_search_without_map_settings',
        'title'        => 'Map / Search Without Map Settings',
        'object_types' => ['page'],
        'show_on'      => ['key' => 'page_template', 'value' => 'templates/search-without-map.php'],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type'        => 'text',
                'id'          => $prefix . 'search_img_size',
                'name'        => 'Image Size',
                'description' => 'You can use the defined image sizes like: full, large, medium, wilcity_560x300 or 400,300 to specify the image width and height.',
            ],
            [
                'type'    => 'select',
                'id'      => $prefix . 'style',
                'name'    => 'Style',
                'options' => [
                    'grid'  => 'Grid',
                    'grid2' => 'Grid 2',
                    'list'  => 'List'
                ]
            ]
        ]
    ],
    'wilcity_general_settings'            => [
        'id'           => 'wilcity_general_settings',
        'title'        => esc_html__('General Settings', 'wilcity'),
        'object_types' => class_exists('\WilokeListingTools\Framework\Helpers\General') ?
            General::getPostTypeKeys(false) : ['listing'],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => apply_filters(
            'wilcity/general-settings/fields',
            [
                [
                    'type'        => 'text',
                    'id'          => $prefix . 'tagline',
                    'name'        => esc_html__('Tagline', 'wilcity'),
                    'placeholder' => '',
                    'default'     => '',
                ],
                [
                    'type'        => 'file',
                    'id'          => $prefix . 'logo',
                    'name'        => esc_html__('Logo', 'wilcity'),
                    'placeholder' => '',
                    'default'     => ''
                ],
                [
                    'type'        => 'file',
                    'id'          => $prefix . 'cover_image',
                    'name'        => esc_html__('Cover Image', 'wilcity'),
                    'placeholder' => '',
                    'default'     => ''
                ],
                [
                    'type'        => 'text',
                    'id'          => $prefix . 'timezone',
                    'name'        => esc_html__('Timezone', 'wilcity'),
                    'placeholder' => '',
                    'default'     => ''
                ]
            ],
            $prefix
        )
    ],
    'wilcity_video'                       => [
        'id'             => 'wilcity_video',
        'title'          => esc_html__('Video', 'wilcity'),
        'object_types'   => class_exists('\WilokeListingTools\Framework\Helpers\General') ?
            General::getPostTypeKeys(false) : ['listing'],
        'context'        => 'normal',
        'priority'       => 'low',
        'type'           => 'group',
        'show_names'     => true, // Show field names on the left
        'group_settings' => [
            'id'      => 'wilcity_video_srcs',
            'type'    => 'group',
            'options' => [
                'group_title'   => esc_html__('Video URL', 'wilcity'),
                // since version 1.1.4, {#} gets replaced by row number
                'add_button'    => esc_html__('Add Video', 'wilcity'),
                'remove_button' => esc_html__('Remove Video', 'wilcity'),
                'sortable'      => true,
                'closed'        => true
            ]
        ],
        'group_fields'   => [
            [
                'name' => 'Source',
                'id'   => 'src',
                'type' => 'text',
            ],
            [
                'name' => 'Thumbnail',
                'id'   => 'thumbnail',
                'type' => 'file',
            ]
        ]
    ],
    'wilcity_gallery_settings'            => [
        'id'           => 'wilcity_gallery_settings',
        'title'        => esc_html__('Gallery', 'wilcity'),
        'object_types' => class_exists('\WilokeListingTools\Framework\Helpers\General') ?
            General::getPostTypeKeys(false) : ['listing'],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'name'         => esc_html__('Upload Images', 'wilcity'),
                'id'           => $prefix . 'gallery',
                'type'         => 'file_list',
                'preview_size' => 'thumbnail',
                'query_args'   => ['type' => 'image']
            ]
        ]
    ],
    'wilcity_google_address'              => [
        'id'           => 'wilcity_google_address',
        'title'        => esc_html__('Google Address', 'wilcity'),
        'object_types' => class_exists('\WilokeListingTools\Framework\Helpers\General') ?
            General::getPostTypeKeys(false, false) : ['listing'],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'name'         => esc_html__('Location', 'wilcity'),
                'id'           => $prefix . 'location',
                'type'         => 'wiloke_map',
                'split_values' => true, // Save latitude and longitude as two separate fields
            ]
        ]
    ],
    'wilcity_contact_info'                => [
        'id'           => 'wilcity_contact_info',
        'title'        => esc_html__('Contact Information', 'wilcity'),
        'object_types' => class_exists('\WilokeListingTools\Framework\Helpers\General') ?
            General::getPostTypeKeys(false) : ['listing'],
        'context'      => 'normal',
        'priority'     => 'low',
        'save_field'   => false,
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'name' => esc_html__('Email', 'wilcity'),
                'id'   => $prefix . 'email',
                'type' => 'text_email'
            ],
            [
                'name' => esc_html__('Phone', 'wilcity'),
                'id'   => $prefix . 'phone',
                'type' => 'text'
            ],
            [
                'name' => esc_html__('Website', 'wilcity'),
                'id'   => $prefix . 'website',
                'type' => 'text_url'
            ]
        ]
    ],
    'wilcity_social_networks'             => [
        'id'           => 'wilcity_social_networks',
        'title'        => esc_html__('Social Networks', 'wilcity'),
        'object_types' => class_exists('\WilokeListingTools\Framework\Helpers\General') ?
            General::getPostTypeKeys(false) : ['listing'], // Post type
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'name' => esc_html__('Social Networks', 'wilcity'),
                'id'   => 'wilcity_social_networks',
                'type' => 'wilcity_social_networks'
            ]
        ]
    ],
    'wilcity_single_price'                => [
        'id'           => 'wilcity_single_price',
        'title'        => 'Single Price',
        'object_types' => class_exists('\WilokeListingTools\Framework\Helpers\General') ?
            General::getPostTypeKeys(false, false) : ['listing'],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type'        => 'text',
                'id'          => $prefix . 'single_price',
                'name'        => 'Price',
                'description' => 'It is suitable for Fixed Price purpose like Real Stable, Rent House',
            ]
        ]
    ],
    'wilcity_price_range'                 => [
        'id'           => 'wilcity_price_range',
        'title'        => esc_html__('Price Range', 'wilcity'),
        'object_types' => class_exists('\WilokeListingTools\Framework\Helpers\General') ?
            General::getPostTypeKeys(false, false) : ['listing'],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type'        => 'select',
                'id'          => $prefix . 'price_range',
                'name'        => esc_html__('Price Range', 'wilcity'),
                'description' => 'Eg: You can set Price Range for a Restaurant listing',
                'options'     => apply_filters('wilcity/filter/price-range-options', [
                    'nottosay'   => esc_html__('Not to say', 'wilcity'),
                    'cheap'      => esc_html__('Cheap', 'wilcity'),
                    'moderate'   => esc_html__('Moderate', 'wilcity'),
                    'expensive'  => esc_html__('Expensive', 'wilcity'),
                    'ultra_high' => esc_html__('Ultra High', 'wilcity'),
                ])
            ],
            [
                'type' => 'text',
                'id'   => $prefix . 'price_range_desc',
                'name' => esc_html__('Price Range Description', 'wilcity')
            ],
            [
                'type' => 'text',
                'id'   => $prefix . 'minimum_price',
                'name' => esc_html__('Minimum Price', 'wilcity')
            ],
            [
                'type' => 'text',
                'id'   => $prefix . 'maximum_price',
                'name' => esc_html__('Maximum Price', 'wilcity')
            ]
        ]
    ],
    'wilcity_belongs_to'                  => [
        'id'           => 'wilcity_belongs_to',
        'title'        => esc_html__('Belongs To', 'wilcity'),
        'object_types' => class_exists('\WilokeListingTools\Framework\Helpers\General') ?
            General::getPostTypeKeys(false) : ['listing'],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type'        => 'select2_posts',
                'id'          => $prefix . 'belongs_to',
                'name'        => 'Belongs To Plan',
                'description' => 'If you want to change the Plan manually, please set Listing Expiry to empty. Otherwise, the existing Listing Expiry value will be used.',
                'attributes'  => [
                    'ajax_action' => 'wiloke_fetch_posts',
                    'post_types'  => 'listing_plan'
                ]
            ]
        ]
    ],
    'wilcity_expiry'                      => [
        'id'           => 'wilcity_post_expiry',
        'title'        => esc_html__('Expiration', 'wilcity'),
        'object_types' => class_exists('\WilokeListingTools\Framework\Helpers\General') ?
            General::getPostTypeKeys(false) : ['listing'],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type'        => 'text_datetime_timestamp',
                'id'          => $prefix . 'post_expiry',
                'default_cb'  => ['WilokeListingTools\Controllers\PostController', 'setDefaultExpiration'],
                'name'        => esc_html__('Listing Expiry', 'wilcity'),
                'description' => esc_html__('If you want to change Listing Plan manually, please leave this value for empty.',
                    'wilcity')
            ]
        ]
    ],
    'wilcity_listing_status'              => [
        'id'           => 'wilcity_listing_status',
        'title'        => esc_html__('Directly Change Listing Status', 'wilcity'),
        'description'  => 'Warning: You should change Listing Status to the First Status (----) right after click Publish button',
        'object_types' => class_exists('\WilokeListingTools\Framework\Helpers\General') ?
            General::getPostTypeKeys(false) : ['listing'],
        'context'      => 'normal',
        'priority'     => 'low',
        'save_field'   => false,
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type'    => 'select',
                'id'      => $prefix . 'listing_status',
                'name'    => 'Listing Status',
                'options' => [
                    ''                => esc_html__('-----', 'wilcity'),
                    'publish'         => esc_html__('Publish', 'wilcity'),
                    'unpaid'          => esc_html__('Unpaid', 'wilcity'),
                    'expired'         => esc_html__('Expired', 'wilcity'),
                    'temporary_close' => esc_html__('Temporary Close', 'wilcity'),
                    'editing'         => esc_html__('Editing', 'wilcity'),
                    'rejected'        => esc_html__('Rejected', 'wilcity')
                ]
            ],
            [
                'type' => 'textarea',
                'id'   => $prefix . 'listing_rejected_reason',
                'name' => 'Listing Rejected Reason',
            ]
        ]
    ],
    'wilcity_coupon'                      => [
        'id'           => 'wilcity_coupon',
        'title'        => 'Coupon Settings',
        'object_types' => class_exists('\WilokeListingTools\Framework\Helpers\General') ?
            General::getPostTypeKeys(false, false) : ['listing'],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'save_field'   => false,
        'fields'       => [
            [
                'type'       => 'text',
                'id'         => 'wilcity_coupon[highlight]',
                'name'       => 'Highlight',
                'default_cb' => ['WilokeListingTools\Models\Coupon', 'getHighlight'],
            ],
            [
                'type'       => 'text',
                'id'         => 'wilcity_coupon[title]',
                'name'       => 'Title',
                'default_cb' => ['WilokeListingTools\Models\Coupon', 'getTitle'],
            ],
            [
                'type'       => 'textarea',
                'id'         => 'wilcity_coupon[description]',
                'name'       => 'Description',
                'default_cb' => ['WilokeListingTools\Models\Coupon', 'getDescription'],
            ],
            [
                'type'       => 'text',
                'id'         => 'wilcity_coupon[code]',
                'name'       => 'Code',
                'default_cb' => ['WilokeListingTools\Models\Coupon', 'getCode']
            ],
            [
                'type'       => 'file',
                'id'         => 'wilcity_coupon[popup_image]',
                'name'       => 'Popup Image',
                'default_cb' => ['WilokeListingTools\Models\Coupon', 'getPopupImage'],
            ],
            [
                'type'       => 'textarea',
                'id'         => 'wilcity_coupon[popup_description]',
                'name'       => 'Popup Description',
                'default_cb' => ['WilokeListingTools\Models\Coupon', 'getPopupDescription'],
            ],
            [
                'type'        => 'text',
                'id'          => 'wilcity_coupon[redirect_to]',
                'name'        => 'Redirect To',
                'default_cb'  => ['WilokeListingTools\Models\Coupon', 'getRedirectTo'],
                'description' => 'The popup won\'t show if the coupon is not empty'
            ],
            [
                'type'        => 'text_datetime_timestamp',
                'id'          => 'wilcity_coupon[expiry_date]',
                'name'        => 'Expiry Date',
                'default_cb'  => ['WilokeListingTools\Models\Coupon', 'getExpiry'],
                'description' => 'The popup won\'t show if the coupon is not empty'
            ]
        ]
    ],
    'wilcity_claim'                       => [
        'id'           => 'wilcity_claim',
        'title'        => esc_html__('Claim Listing', 'wilcity'),
        'object_types' => class_exists('\WilokeListingTools\Framework\Helpers\General') ?
            General::getPostTypeKeys(false, true) : ['listing'],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type'    => 'select',
                'id'      => $prefix . 'claim_status',
                'name'    => esc_html__('Listing Status', 'wilcity'),
                'options' => [
                    'not_claim' => 'Not Claim Yet',
                    'claimed'   => 'Claimed'
                ]
            ]
        ]
    ],
    'wilcity_custom_button'               => [
        'id'           => 'wilcity_custom_button',
        'title'        => esc_html__('Add a button to your Page', 'wilcity'),
        'object_types' => class_exists('\WilokeListingTools\Framework\Helpers\General') ?
            General::getPostTypeKeys(false, false) : ['listing', 'event'],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type'    => 'text',
                'id'      => $prefix . 'button_name',
                'name'    => esc_html__('Button Name', 'wilcity'),
                'default' => esc_html__('+ Add a Button', 'wilcity')
            ],
            [
                'type'    => 'text',
                'id'      => $prefix . 'button_link',
                'name'    => esc_html__('Button Link', 'wilcity'),
                'default' => ''
            ],
            [
                'type'        => 'text',
                'id'          => $prefix . 'button_icon',
                'name'        => esc_html__('Button Icon', 'wilcity'),
                'description' => Wiloke::ksesHTML(__(
                    'Go to <a href="https://documentation.wilcity.com/knowledgebase/line-icon/" target="_blank">LineIcon</a> to find your icon',
                    'wilcity'
                ), true),
                'default'     => ''
            ]
        ]
    ],
    'wil_search_settings'                 => [
        'id'           => 'wil_search_settings',
        'title'        => 'Search Settings',
        'object_types' => ['page'],
        'show_on'      => [
            'key'   => 'page_template',
            'value' => ['templates/search-v2.php', 'templates/search-without-map.php', 'templates/map.php']
        ],
        'fields'       => [
            [
                'type'    => 'select',
                'id'      => $prefix . 'default_post_type',
                'name'    => 'Default Post Type',
                'default' => 'default',
                'options' => ['default' => 'Default'] + $aPostTypes
            ],
            [
                'type'        => 'multicheck_inline',
                'id'          => $prefix . 'exclude_post_types_from_map',
                'name'        => 'Exclude Post Types From map',
                'description' => 'The following post types will not show up Map button',
                'options'     => $aPostTypes
            ],
            [
                'type'        => 'select',
                'id'          => $prefix . 'show_content_at',
                'name'        => 'Content Position',
                'description' => 'Display the Content in the WordPress editor to the following option. Warning: Some scripts may cause broken Search Page, so We do not recommend using Widgets that contains Javascript.',
                'default'     => 'default',
                'options'     => [
                    'before' => 'Before Search Result',
                    'after'  => 'After Search Result',
                    'hidden' => 'Hide it'
                ]
            ],
        ]
    ],
    'wilcity_design_template'             => [
        'id'           => 'wilcity_design_template',
        'title'        => esc_html__('Template Settings', 'wilcity'),
        'object_types' => ['page'],
        'show_on'      => [
            'key'   => 'page_template',
            'value' => ['templates/search-v2.php', 'templates/search-without-map.php', 'templates/map.php']
        ],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type'    => 'select',
                'id'      => $prefix . 'maximum_posts_on_lg_screen',
                'name'    => esc_html__('Items / Row (Screen >= 1200px)', 'wilcity'),
                'default' => 'col-lg-2 wil-col-5',
                'options' => [
                    'col-lg-6'           => '2 items / row',
                    'col-lg-4'           => '3 items / row',
                    'col-lg-3'           => '4 items / row',
                    'col-lg-2 wil-col-5' => '5 items / row',
                    'col-lg-2'           => '6 items / row'
                ]
            ],
            [
                'type'    => 'select',
                'id'      => $prefix . 'maximum_posts_on_md_screen',
                'name'    => esc_html__('Items / Row (Screen >= 992px)', 'wilcity'),
                'default' => 'col-md-3',
                'options' => [
                    'col-md-6'           => '2 items / row',
                    'col-md-4'           => '3 items / row',
                    'col-md-3'           => '4 items / row',
                    'col-md-2 wil-col-5' => '5 items / row',
                    'col-md-2'           => '6 items / row'
                ]
            ],
            [
                'type'    => 'select',
                'id'      => $prefix . 'maximum_posts_on_sm_screen',
                'name'    => esc_html__('Items / Row (Screen < 992px)', 'wilcity'),
                'default' => 'col-sm-6',
                'options' => [
                    'col-sm-12' => '1 items / row',
                    'col-sm-6'  => '2 items / row',
                    'col-sm-4'  => '3 items / row',
                    'col-sm-3'  => '4 items / row',
                    'col-sm-2'  => '6 items / row'
                ]
            ],
            [
                'type'    => 'select',
                'id'      => $prefix . 'maximum_posts_on_xs_screen',
                'name'    => esc_html__('Items / Row (Screen < 480px)', 'wilcity'),
                'default' => 'col-xs-6',
                'options' => [
                    'col-xs-12' => '1 items / row',
                    'col-xs-6'  => '2 items / row',
                    'col-xs-4'  => '3 items / row',
                    'col-xs-3'  => '4 items / row',
                    'col-xs-2'  => '6 items / row'
                ]
            ],
            [
                'type'    => 'text',
                'id'      => $prefix . 'posts_per_page',
                'name'    => esc_html__('Posts Per Page', 'wilcity'),
                'default' => 12
            ],
            [
                'type'    => 'select',
                'id'      => $prefix . 'toggle_sidebar',
                'name'    => esc_html__('Is Sidebar', 'wilcity'),
                'default' => 'disable',
                'options' => [
                    'disable' => 'Disable',
                    'enable'  => 'Enable'
                ]
            ],
            [
                'type'    => 'select',
                'id'      => $prefix . 'taxonomy_description',
                'name'    => esc_html__('Display Taxonomy Description?', 'wilcity'),
                'default' => 'disable',
                'options' => [
                    'enable'  => 'Enable',
                    'disable' => 'Disable'
                ]
            ],
        ],
    ]
];
