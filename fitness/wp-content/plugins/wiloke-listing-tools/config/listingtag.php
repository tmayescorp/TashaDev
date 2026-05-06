<?php
$prefix = 'wilcity_';
return [
    'listing_tag_settings' => [
        'id'           => 'listing_tag_settings',
        'title'        => esc_html__('Settings', 'wiloke-listing-tools'),
        'object_types' => ['term'],
        'taxonomies'   => ['listing_tag'],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type' => 'text',
                'id'   => $prefix . 'tagline',
                'name' => 'Tagline',
            ],
            [
                'type' => 'text',
                'id'   => $prefix . 'icon',
                'name' => 'Icon',
                'desc' => 'Warning: You have to use <a href="https://fontawesome.com/v4.7.0/" target="_blank">FontAwesome</a> or <a target="_blank" href="https://documentation.wilcity.com/knowledgebase/line-icon/">Line Awesome</a>. If you use another one, it will broken your App'
            ],
            [
                'type' => 'colorpicker',
                'id'   => $prefix . 'icon_color',
                'name' => 'Icon Color'
            ],
            [
                'type'         => 'file',
                'id'           => $prefix . 'icon_img',
                'name'         => esc_html__('Upload Your Icon', 'wiloke-listing-tools'),
                'desc'         => esc_html__('The icon image will get higher priority than LineAwesome Icon',
                    'wiloke-listing-tools'),
                'preview_size' => 'full'
            ],
            [
                'type'     => 'file',
                'taxonomy' => 'featured_image',
                'id'       => $prefix . 'featured_image',
                'name'     => 'Featured Image'
            ],
            [
                'type'     => 'file_list',
                'taxonomy' => 'gallery',
                'id'       => $prefix . 'gallery',
                'name'     => 'Gallery',
                'desc'     => 'If the gallery is not empty, it be used on this category page'
            ],
            [
                'type'        => 'multicheck_inline',
                'id'          => $prefix . 'belongs_to',
                'name'        => esc_html__('Belongs To', 'wiloke-listing-tools'),
                'description' => 'Select Listing Types that this term should belong to. Leave empty to set the tag for all',
                'options_cb'  => ['WilokeListingTools\MetaBoxes\Listing', 'setListingTypesOptions']
            ],
            [
                'type'        => 'select',
                'id'          => $prefix . 'default_belongs_to',
                'name'        => esc_html__('Default Listing Type', 'wiloke-listing-tools'),
                'description' => 'When clicking on a tag box, this listing type is assigned as the default.',
                'options_cb'  => ['WilokeListingTools\MetaBoxes\Listing', 'setListingTypesOptions']
            ],
            [
                'type' => 'colorpicker',
                'id'   => $prefix . 'left_gradient_bg',
                'name' => 'Left Gradient Background',
                'desc' => 'This setting is for Term Boxes shortcode'
            ],
            [
                'type' => 'colorpicker',
                'id'   => $prefix . 'right_gradient_bg',
                'name' => 'Right Gradient Background',
                'desc' => 'This setting is for Term Boxes shortcode'
            ],
            [
                'type'    => 'text',
                'id'      => $prefix . 'gradient_tilted_degrees',
                'name'    => 'Gradient tilted degrees',
                'desc'    => 'Eg: A gradient tilted 45 degrees, starting Left Background and finishing Right Background',
                'default' => -10
            ]
        ]
    ]
];
