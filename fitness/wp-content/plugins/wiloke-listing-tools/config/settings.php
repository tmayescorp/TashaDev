<?php

use WilokeListingTools\Framework\Helpers\General;

return [
    'allSections'        => apply_filters('wilcity/filter/wiloke-listing-tools/configs/settings', [
        'header'                     => [
            'isDefault'    => true,
            'listingTypes' => ['listing'],
            'type'         => 'header',
            'key'          => 'header',
            'icon'         => 'la la-certificate',
            'heading'      => 'Header',
            'fieldGroups'  => [
                [
                    'heading'     => 'Listing Name',
                    'type'        => 'input',
                    'desc'        => '',
                    'key'         => 'listing_title',
                    'valueFormat' => 'string',
                    'fields'      => [
                        [
                            'label' => 'Listing Title',
                            'value' => 'Listing Title',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'label'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'yes'
                        ]
                    ]
                ],
                [
                    'heading' => 'Tagline',
                    'type'    => 'input',
                    'desc'    => '',
                    'toggle'  => 'enable',
                    'key'     => 'tagline',
                    'fields'  => [
                        [
                            'label' => 'Label name',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'label',
                            'value' => 'Tagline'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'no'
                        ]
                    ]
                ],
                [
                    'heading'     => 'Logo',
                    'type'        => 'wil-uploader',
                    'maximum'     => 1,
                    'toggle'      => 'enable',
                    'key'         => 'logo',
                    'valueFormat' => 'object',
                    'fields'      => [
                        [
                            'label' => 'Label Name',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'label',
                            'value' => 'Logo'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'yes'
                        ]
                    ]
                ],
                [
                    'heading'     => 'Cover Image',
                    'type'        => 'wil-uploader',
                    'maximum'     => 1,
                    'toggle'      => 'enable',
                    'valueFormat' => 'object',
                    'key'         => 'cover_image',
                    'fields'      => [
                        [
                            'label' => 'Label name',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'label',
                            'value' => 'Cover Image'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'yes'
                        ]
                    ]
                ]
            ]
        ],
        'featured_image'             => [
            'isDefault'           => true,
            'excludeGetBySection' => true,
            'type'                => 'featured_image',
            'icon'                => 'la la-image',
            'key'                 => 'featured_image',
            'heading'             => 'Featured Image',
            'fieldGroups'         => [
                [
                    'heading'     => 'Settings',
                    'type'        => 'wil-uploader',
                    'valueFormat' => 'object',
                    'desc'        => '',
                    'key'         => 'featured_image',
                    'fields'      => [
                        [
                            'label' => 'Label name',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'label',
                            'value' => 'Featured Image'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'yes'
                        ]
                    ]
                ]
            ]
        ],
        'claim_listing_status'       => [
            'isDefault'   => true,
            'type'        => 'claim_listing_status',
            'key'         => 'claim_listing_status',
            'icon'        => 'la la-handshake-o',
            'heading'     => 'Claim Listing',
            'fieldGroups' => [
                [
                    'heading'     => 'Claim Status',
                    'type'        => 'wil-select-tree',
                    'key'         => 'listing_claim_status',
                    'toggle'      => 'enable',
                    'valueFormat' => 'string',
                    'fields'      => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'label',
                            'value' => 'Is your business owner?'
                        ],
                        [
                            'label' => 'Options',
                            'desc'  => 'You can change label only, You can not change they key. key:label',
                            'type'  => 'textarea',
                            'key'   => 'options',
                            'value' => 'not_claim:No,claimed:Yes'
                        ]
                    ]
                ]
            ]
        ],
        'contact_info'               => [
            'isDefault'   => true,
            'type'        => 'contact_info',
            'key'         => 'contact_info',
            'icon'        => 'la la-phone-square',
            'heading'     => 'Contact Information',
            'fieldGroups' => [
                [
                    'heading'   => 'Email',
                    'type'      => 'wil-input',
                    'inputType' => 'email',
                    'desc'      => '',
                    'toggle'    => 'enable',
                    'key'       => 'email',
                    'fields'    => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Email'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'no'
                        ]
                    ]
                ],
                [
                    'heading' => 'Phone',
                    'type'    => 'wil-input',
                    'desc'    => '',
                    'key'     => 'phone',
                    'toggle'  => 'enable',
                    'fields'  => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Phone'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'no'
                        ]
                    ]
                ],
                [
                    'heading' => 'Website',
                    'type'    => 'wil-input',
                    'desc'    => '',
                    'key'     => 'website',
                    'toggle'  => 'enable',
                    'fields'  => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Website'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'no'
                        ]
                    ]
                ],
                [
                    'heading'     => 'Social Network Settings',
                    'type'        => 'wil-pickup-and-set',
                    'key'         => 'social_networks',
                    'valueFormat' => 'array',
                    'fields'      => [
                        [
                            'label'                   => 'Excluding Social networks',
                            'type'                    => 'select',
                            'isMultiple'              => true,
                            'desc'                    => 'Those socials in this field will not shown on Add Listing page',
                            'key'                     => 'excludingSocialNetworks',
                            'options'                 => class_exists('WilokeSocialNetworks') ?
                                array_combine(
                                    WilokeSocialNetworks::$aSocialNetworks,
                                    WilokeSocialNetworks::$aSocialNetworks
                                ) : [],
                            'excludingSocialNetworks' => []
                        ],
                        [
                            'label' => 'Social name label',
                            'type'  => 'input',
                            'key'   => 'socialNameLabel',
                            'value' => 'Social Networks'
                        ],
                        [
                            'label' => 'Social Link Label',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'socialLinkLabel',
                            'value' => 'Social URL'
                        ],
                        [
                            'label' => 'Add Social Button Name',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'btnName',
                            'value' => 'Add Social'
                        ]
                    ]
                ]
            ]
        ],
        'coupon'                     => [
            'isDefault'   => true,
            'type'        => 'coupon',
            'key'         => 'coupon',
            'icon'        => 'la la-tag',
            'heading'     => 'Coupon',
            'fieldGroups' => [
                [
                    'heading' => 'Title',
                    'type'    => 'wil-input',
                    'desc'    => '',
                    'key'     => 'coupon_title',
                    'fields'  => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Title'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'no'
                        ]
                    ]
                ],
                [
                    'heading' => 'Highlight',
                    'type'    => 'wil-input',
                    'desc'    => '',
                    'key'     => 'coupon_highlight',
                    'fields'  => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Highlight'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'no'
                        ]
                    ]
                ],
                [
                    'heading'     => 'Popup Coupon Image',
                    'type'        => 'wil-uploader',
                    'maximum'     => 1,
                    'desc'        => '',
                    'key'         => 'coupon_popup_image',
                    'valueFormat' => 'array',
                    'fields'      => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Popup Coupon Image (210x100 suggested)'
                        ]
                    ]
                ],
                [
                    'heading' => 'Description',
                    'type'    => 'wil-textarea',
                    'desc'    => '',
                    'key'     => 'coupon_description',
                    'fields'  => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Description'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'no'
                        ]
                    ]
                ],
                [
                    'heading' => 'Description',
                    'type'    => 'wil-textarea',
                    'desc'    => '',
                    'key'     => 'coupon_description',
                    'fields'  => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Description'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'no'
                        ]
                    ]
                ],
                [
                    'heading' => 'Coupon Code',
                    'type'    => 'wil-input',
                    'desc'    => '',
                    'key'     => 'coupon_code',
                    'fields'  => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Coupon Code'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'no'
                        ]
                    ]
                ],
                [
                    'heading' => 'Popup Description',
                    'type'    => 'wil-input',
                    'desc'    => '',
                    'key'     => 'coupon_popup_description',
                    'fields'  => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Popup Description'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'no'
                        ]
                    ]
                ],
                [
                    'heading' => 'Redirect To',
                    'type'    => 'wil-input',
                    'desc'    => '',
                    'key'     => 'coupon_redirect_to',
                    'fields'  => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Redirect To'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'no'
                        ]
                    ]
                ],
                [
                    'heading' => 'Coupon Expiry Date',
                    'type'    => 'wil-datepicker',
                    'desc'    => '',
                    'key'     => 'coupon_expiry_date',
                    'fields'  => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Expiry date'
                        ],
                        [
                            'label' => 'Date Format',
                            'type'  => 'input',
                            'desc'  => 'YYYY defines year format (2019). MM defines month format (01). DD defines day format (01)',
                            'key'   => 'dateFormat',
                            'value' => 'YYYY/MM/DD'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'no'
                        ]
                    ]
                ]
            ]
        ],
        'custom_taxonomy'            => [
            'isClone'             => true,
            'isCustomSection'     => true,
            'type'                => 'custom_taxonomy',
            'key'                 => 'custom_taxonomy',
            'category'            => 'taxonomy',
            'excludeGetBySection' => true,
            'icon'                => 'la la-file-text',
            'heading'             => 'Custom Taxonomy',
            'fieldGroups'         => [
                [
                    'heading'           => 'Taxonomy Setting',
                    'type'              => 'wil-select-tree',
                    'isTax'             => true,
                    'isAjax'            => true,
                    'valueFormat'       => 'object',
                    'selectValueFormat' => 'object',
                    'desc'              => '',
                    'restRoute'         => 'taxonomy/{{taxonomy}}',
                    'queryArgs'         => [
                        'mode' => 'select'
                    ],
                    'key'               => 'my_taxonomy',
                    'fields'            => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Taxonomy'
                        ],
                        [
                            'label' => 'Maximum Taxonomy Items',
                            'type'  => 'input',
                            'key'   => 'maximum',
                            'value' => 1
                        ],
                        [
                            'label' => 'Description',
                            'type'  => 'input',
                            'key'   => 'description',
                            'desc'  => 'You can use %maximum% as a placeholder in your description. This text is replaced with real Maximum Items on the front-end',
                            'value' => ''
                        ],
                        [
                            'label' => 'Taxonomy Key',
                            'type'  => 'async-search',
                            'key'   => 'taxonomy',
                            'value' => ''
                        ],
                        [
                            'label' => 'Is required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'yes'
                        ],
                        [
                            'label'   => 'Load Option Mode',
                            'desc'    => 'If you have too many category, We recommend using Dynamically loading mode',
                            'type'    => 'select',
                            'key'     => 'loadOptionMode',
                            'value'   => 'ajaxloadroot',
                            'options' => [
                                'ajaxloadroot' => 'Fetching Parent Categories Through Ajax',
                                'ajax'         => 'Dynamically loading & changing the entries options as the user types',
                                //                                'default'      => 'Default'
                            ]
                        ],
                        [
                            'label'   => 'order by',
                            'type'    => 'select',
                            'key'     => 'orderBy',
                            'value'   => 'term_id',
                            'options' => [
                                'name'           => 'Name',
                                'count'          => 'Count',
                                'slug'           => 'Slug',
                                'term_id'        => 'Term ID',
                                'meta_value_num' => 'Term position'
                            ]
                        ],
                        [
                            'label'   => 'Order',
                            'desc'    => '',
                            'type'    => 'select',
                            'key'     => 'order',
                            'value'   => 'DESC',
                            'options' => [
                                'DESC' => 'DESC',
                                'ASC'  => 'ASC'
                            ]
                        ]
                    ]
                ]
            ]
        ],
        'listing_cat'                => [
            'isDefault'           => true,
            'category'            => 'taxonomy',
            'excludeGetBySection' => true,
            'type'                => 'listing_cat',
            'key'                 => 'listing_cat',
            'icon'                => 'la la-file-text',
            'heading'             => 'Category',
            'fieldGroups'         => [
                [
                    'heading'           => 'Category Setting',
                    'type'              => 'wil-select-tree',
                    'isTax'             => true,
                    'isAjax'            => true,
                    'valueFormat'       => 'array',
                    'selectValueFormat' => 'object',
                    'desc'              => '',
                    'restRoute'         => 'taxonomy/listing_cat',
                    'queryArgs'         => [
                        'mode' => 'select'
                    ],
                    'key'               => 'listing_cat',
                    'fields'            => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Select categories'
                        ],
                        //                        [
                        //                            'label' => 'Placeholder',
                        //                            'type'  => 'input',
                        //                            'key'   => 'placeholder',
                        //                            'value' => 'Select categories'
                        //                        ],
                        [
                            'label' => 'Maximum Categories',
                            'type'  => 'input',
                            'key'   => 'maximum',
                            'value' => 1
                        ],
                        [
                            'label' => 'Description',
                            'type'  => 'input',
                            'desc'  => 'You can use %maximum% as a placeholder in your description. This text is replaced with real Maximum Categories on the front-end',
                            'key'   => 'description',
                            'value' => ''
                        ],
                        [
                            'label' => 'Is required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'yes'
                        ],
                        [
                            'label'   => 'Load Option Mode',
                            'desc'    => 'If you have too many category, We recommend using Dynamically loading mode',
                            'type'    => 'select',
                            'key'     => 'loadOptionMode',
                            'value'   => 'ajaxloadroot',
                            'options' => [
                                'ajaxloadroot' => 'Fetching Parent Categories Through Ajax',
                                'ajax'         => 'Dynamically loading & changing the entries options as the user types',
                                //                                'default'      => 'Default'
                            ]
                        ],
                        [
                            'label'   => 'Order by',
                            'desc'    => 'Get all tags ordered by',
                            'type'    => 'select',
                            'key'     => 'orderBy',
                            'value'   => 'term_id',
                            'options' => [
                                'name'           => 'Name',
                                'count'          => 'Count',
                                'slug'           => 'Slug',
                                'term_id'        => 'Term ID',
                                'meta_value_num' => 'Term position'
                            ]
                        ],
                        [
                            'label'   => 'Order',
                            'desc'    => '',
                            'type'    => 'select',
                            'key'     => 'order',
                            'value'   => 'DESC',
                            'options' => [
                                'DESC' => 'DESC',
                                'ASC'  => 'ASC'
                            ]
                        ]
                    ]
                ]
            ]
        ],
        'listing_tag'                => [
            'isDefault'           => true,
            'category'            => 'taxonomy',
            'excludeGetBySection' => true,
            'type'                => 'listing_tag',
            'icon'                => 'la la-tag',
            'key'                 => 'listing_tag',
            'heading'             => 'Tags',
            'fieldGroups'         => [
                [
                    'heading'           => 'Tag Setting',
                    'type'              => 'wil-select-tree',
                    'isTax'             => true,
                    'isAjax'            => true,
                    'valueFormat'       => 'array',
                    'selectValueFormat' => 'object',
                    'desc'              => '',
                    'restRoute'         => 'taxonomy/listing_tag',
                    'queryArgs'         => [
                        'mode' => 'select'
                    ],
                    'key'               => 'listing_tag',
                    'fields'            => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Set Listing Features'
                        ],
                        [
                            'label' => 'Description',
                            'type'  => 'input',
                            'key'   => 'description',
                            'desc'  => 'You can use %maximum% as a placeholder in your description. This text is replaced with real Maximum Tags on the front-end',
                            'value' => 'You must specify %maximum% or less'
                        ],
                        [
                            'label' => 'Maximum Tags',
                            'desc'  => 'Maximum tags can be used for 1 listing',
                            'type'  => 'input',
                            'key'   => 'maximum',
                            'value' => 4
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'key'   => 'isRequired',
                            'value' => 'no'
                        ],
                        [
                            'label'   => 'Load Option Mode',
                            'desc'    => 'If you have too many category, We recommend using Dynamically loading mode',
                            'type'    => 'select',
                            'key'     => 'loadOptionMode',
                            'value'   => 'ajaxloadroot',
                            'options' => [
                                'ajaxloadroot' => 'Fetching Parent Categories Through Ajax',
                                'ajax'         => 'Dynamically loading & changing the entries options as the user types',
                                //                                'default'      => 'Default'
                            ]
                        ],
                        [
                            'label'   => 'Order by',
                            'desc'    => 'Get all tags ordered by',
                            'type'    => 'select',
                            'key'     => 'orderBy',
                            'value'   => 'term_id',
                            'options' => [
                                'name'           => 'name',
                                'count'          => 'count',
                                'slug'           => 'slug',
                                'term_id'        => 'term_id',
                                'meta_value_num' => 'tax_position'
                            ]
                        ],
                        [
                            'label'   => 'Order',
                            'desc'    => '',
                            'type'    => 'select',
                            'key'     => 'order',
                            'value'   => 'DESC',
                            'options' => [
                                'DESC' => 'DESC',
                                'ASC'  => 'ASC'
                            ]
                        ]
                    ]
                ]
            ]
        ],
        'business_hours'             => [
            'isDefault'    => true,
            'listingTypes' => ['listing'],
            'type'         => 'business_hours',
            'key'          => 'business_hours',
            'icon'         => 'la la-clock-o',
            'heading'      => 'Business Hours',
            'fieldGroups'  => [
                [
                    'heading'     => 'Hour Options',
                    'type'        => 'wil-business-hours',
                    'key'         => 'settings',
                    'desc'        => '',
                    'valueFormat' => 'object',
                    'fields'      => [
                        [
                            'label' => 'Time Format Label',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'timeFormatLabel',
                            'value' => 'Time Format'
                        ],
                        [
                            'label'   => 'Default Opening Time',
                            'type'    => 'select',
                            'desc'    => '',
                            'key'     => 'stdOpeningTime',
                            'value'   => '',
                            'options' => General::generateBusinessHours()
                        ],
                        [
                            'label'   => 'Default Closed Time',
                            'type'    => 'select',
                            'desc'    => '',
                            'key'     => 'stdClosedTime',
                            'value'   => '',
                            'options' => General::generateBusinessHours()
                        ]
                    ]
                ]
            ]
        ],
        'single_price'               => [
            'isDefault'   => true,
            'type'        => 'single_price',
            'key'         => 'single_price',
            'icon'        => 'la la-money',
            'heading'     => 'Single Price',
            'fieldGroups' => [
                [
                    'heading' => 'Single Price',
                    'type'    => 'wil-input',
                    'desc'    => '',
                    'key'     => 'single_price',
                    'fields'  => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Price'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'no'
                        ],
                    ]
                ]
            ]
        ],
        'price_range'                => [
            'isDefault'   => true,
            'type'        => 'price_range',
            'key'         => 'price_range',
            'icon'        => 'la la-money',
            'heading'     => 'Price Range',
            'fieldGroups' => [
                [
                    'heading' => 'Description Field Setting',
                    'type'    => 'wil-input',
                    'desc'    => '',
                    'key'     => 'price_range_desc',
                    'fields'  => [
                        [
                            'label' => 'Description',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'label',
                            'value' => 'Description'
                        ],
                    ],
                ],
                [
                    'heading' => 'Range Field Setting',
                    'type'    => 'wil-select-tree',
                    'desc'    => '',
                    'key'     => 'price_range',
                    'fields'  => [
                        [
                            'label' => 'Range Label',
                            'type'  => 'input',
                            'key'   => 'placeholder',
                            'value' => 'Price Range'
                        ]
                    ],
                ],
                [
                    'heading' => 'Maximum Field Setting',
                    'type'    => 'wil-input',
                    'desc'    => '',
                    'key'     => 'minimum_price',
                    'fields'  => [
                        [
                            'label' => 'Minimum Price',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'label',
                            'value' => 'Minimum Price'
                        ],
                    ]
                ],
                [
                    'heading' => 'Minimum Field Setting',
                    'type'    => 'wil-input',
                    'desc'    => '',
                    'key'     => 'maximum_price',
                    'fields'  => [
                        [
                            'label' => 'Maximum Price',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'label',
                            'value' => 'Maximum Price'
                        ]
                    ]
                ]
            ]
        ],
        'listing_address'            => [
            'isDefault'   => true,
            'type'        => 'listing_address',
            'key'         => 'listing_address',
            'icon'        => 'la la-globe',
            'valueFormat' => 'object',
            'heading'     => 'Listing Address',
            'fieldGroups' => [
                [
                    'heading'           => 'Region (Listing Location) Setting',
                    'type'              => 'wil-select-tree',
                    'isTax'             => true,
                    'isAjax'            => true,
                    'selectValueFormat' => 'object',
                    'desc'              => '',
                    'restRoute'         => 'taxonomy/listing_location',
                    'queryArgs'         => [
                        'mode' => 'select'
                    ],
                    'key'               => 'listing_location',
                    'fields'            => [
                        [
                            'label' => 'Is Enable?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isEnable',
                            'value' => 'yes'
                        ],
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Select Region'
                        ],
                        //                        [
                        //                            'label' => 'Placeholder',
                        //                            'type'  => 'input',
                        //                            'key'   => 'placeholder',
                        //                            'value' => ''
                        //                        ],
                        [
                            'label' => 'Description',
                            'type'  => 'input',
                            'key'   => 'description',
                            'desc'  => 'You can use %maximum% as a placeholder in your description. This text is replaced with real Maximum Tags on the front-end',
                            'value' => 'You must specify %maximum% or less'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'yes'
                        ],
                        [
                            'label'   => 'Load Option Mode',
                            'desc'    => 'If you have too many category, We recommend using Dynamically loading mode',
                            'type'    => 'select',
                            'key'     => 'loadOptionMode',
                            'value'   => 'ajaxloadroot',
                            'options' => [
                                'ajaxloadroot' => 'Fetching Parent Categories Through Ajax',
                                'ajax'         => 'Dynamically loading & changing the entries options as the user types',
                                //                                'default'      => 'Default'
                            ]
                        ],
                        [
                            'label'   => 'Order by',
                            'type'    => 'select',
                            'key'     => 'orderBy',
                            'value'   => 'term_id',
                            'options' => [
                                'name'           => 'name',
                                'count'          => 'count',
                                'slug'           => 'slug',
                                'term_id'        => 'term_id',
                                'meta_value_num' => 'tax_position'
                            ]
                        ],

                        [
                            'label'   => 'Order',
                            'desc'    => '',
                            'type'    => 'select',
                            'key'     => 'order',
                            'value'   => 'ASC',
                            'options' => [
                                'DESC' => 'DESC',
                                'ASC'  => 'ASC'
                            ]
                        ],
                        [
                            'label'   => 'Order',
                            'desc'    => '',
                            'type'    => 'select',
                            'key'     => 'order',
                            'value'   => 'DESC',
                            'options' => [
                                'DESC' => 'DESC',
                                'ASC'  => 'ASC'
                            ]
                        ],
                        [
                            'label' => 'Maximum Regions',
                            'type'  => 'input',
                            'key'   => 'maximum',
                            'value' => 1
                        ]
                    ]
                ],
                [
                    'heading'      => 'Google Address',
                    'type'         => 'wil-search-address',
                    'desc'         => '',
                    'key'          => 'address',
                    'searchTarget' => ['geocoder'],
                    'valueFormat'  => 'object',
                    'fields'       => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Google Address'
                        ],
                        [
                            'label'    => 'Is Enable?',
                            'type'     => 'checkbox',
                            'desc'     => '',
                            'key'      => 'isEnable',
                            'isEnable' => 'yes'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'yes'
                        ],
                        [
                            'label' => 'Display Map Suggestion?',
                            'type'  => 'checkbox',
                            'desc'  => 'As the default, a Customer can enter in Listing Address, Latitude and Longitude directly. But You can also show up MapBox / Google Map on this area, Customer can Drag Map Marker and Wilcity will determine his/her Listing Address automtically',
                            'key'   => 'isMapSuggestion',
                            'value' => 'yes'
                        ],
                        [
                            'label' => 'Set Default Starting Location. EG: 123,456 123 is latitude and 456 is longitude',
                            'type'  => 'input',
                            'desc'  => 'Leave empty to use visitor\'s location as the default',
                            'key'   => 'defaultLocation',
                            'value' => '21.027763,105.834160'
                        ],
                        [
                            'label'       => 'Default Map Zoom',
                            'type'        => 'input',
                            'key'         => 'defaultZoom',
                            'defaultZoom' => 8,
                            'desc'        => 'If you are using mapbox, the default zoom level of the map (0-24).'
                        ],
                        [
                            'label'       => 'Marker Url',
                            'type'        => 'input',
                            'key'         => 'markerUrl',
                            'defaultZoom' => '',
                            'desc'        => 'If you want to replace default icon with your image url, you can put it there (Mapbox only)'
                        ],
                    ]
                ]
            ]
        ],
        'listing_title'              => [
            'isDefault'           => true,
            'type'                => 'listing_title',
            'key'                 => 'listing_title',
            'excludeGetBySection' => true, // @see PrintAddListingSettings::getResults
            'icon'                => 'la la-file-text',
            'heading'             => 'Listing Title',
            'desc'                => 'You should remove this field if you are using Header block field already.',
            'fieldGroups'         => [
                [
                    'heading' => 'Settings',
                    'type'    => 'wil-input',
                    'desc'    => '',
                    'key'     => 'listing_title',
                    'fields'  => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Listing Title'
                        ],
                        [
                            'label' => 'Description',
                            'type'  => 'input',
                            'key'   => 'description',
                            'value' => ''
                        ]
                    ]
                ]
            ]
        ],
        'listing_content'            => [
            'isDefault'           => true,
            'type'                => 'listing_content',
            'key'                 => 'listing_content',
            'excludeGetBySection' => true, // @see PrintAddListingSettings::getResults
            'icon'                => 'la la-file-text',
            'heading'             => 'Listing Content',
            'fieldGroups'         => [
                [
                    'heading' => 'Listing Content',
                    'type'    => 'textarea',
                    'desc'    => '',
                    'key'     => 'listing_content',
                    'fields'  => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Description'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'yes'
                        ]
                    ]
                ]
            ]
        ],
        'new_listing_content'        => [
            'isDefault'           => true,
            'excludeGetBySection' => true,
            'type'                => 'new_listing_content',
            'key'                 => 'listing_content',
            'icon'                => 'la la-file-text',
            'heading'             => 'Listing Content (WYSIWYG)',
            'fieldGroups'         => [
                [
                    'heading' => 'Listing Content',
                    'type'    => 'wil-editor',
                    'desc'    => '',
                    'key'     => 'listing_content',
                    'fields'  => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Listing Content'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'yes'
                        ]
                    ]
                ]
            ]
        ],
        'listing_type_relationships' => [
            'isDefault'           => false,
            'isClone'             => true,
            'excludeGetBySection' => true,
            'isCustomSection'     => 'yes',
            'type'                => 'listing_type_relationships',
            'desc'                => 'Showing up another Listing Type on this listing. You can find Listing Type Key under Wiloke Tools -> Add New Listing Type -> Key',
            'key'                 => 'listing_type_relationships',
            'keyDesc'             => 'Warning: The key must contain relationships in its name. EG: my_restaurant is wrong. The correct is my_restaurant_relationships',
            'icon'                => 'la la-link',
            'heading'             => 'Listing Type Relationships',
            'fieldGroups'         => [
                [
                    'heading'           => 'Settings',
                    'type'              => 'wil-select-tree',
                    'isAjax'            => true,
                    'selectValueFormat' => 'object',
                    'desc'              => '',
                    'loadOptionMode'    => 'ajax',
                    'queryArgs'         => [
                        'action'     => 'wilcity_fetch_listing_type',
                        'post_types' => '{{post_types}}'
                    ],
                    'key'               => 'listing_type_relationships',
                    'fields'            => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'label',
                            'value' => 'Showing Other Listings Types on this listing'
                        ],
                        [
                            'label' => 'Description',
                            'type'  => 'textarea',
                            'desc'  => '',
                            'key'   => 'description'
                        ],
                        [
                            'label' => 'Maximum Listings can be used',
                            'type'  => 'input',
                            'key'   => 'maximum',
                            'value' => 4
                        ],
                        [
                            'label'      => 'Listing Type Key (*)',
                            'type'       => 'input',
                            'component'  => 'wil-async-search',
                            'isMultiple' => 'no',
                            'ajaxUrl'    => add_query_arg(
                                [
                                    'action' => 'listing_post_types'
                                ],
                                rest_url('wiloke/v2/post-types')
                            ),
                            'desc'       => '',
                            'key'        => 'post_types',
                            'value'      => ''
                        ]
                    ]
                ]
            ]
        ],
        'my_advanced_products'       => [
            'isDefault'           => true,
            'excludeGetBySection' => true,
            'type'                => 'my_advanced_products',
            'key'                 => 'my_advanced_products',
            'icon'                => 'la la-shopping-cart',
            'heading'             => 'My Advanced Products',
            'fieldGroups'         => [
                [
                    'heading'           => 'Settings',
                    'key'               => 'my_advanced_products',
                    'type'              => 'wil-select-tree',
                    'isAjax'            => true,
                    'selectValueFormat' => 'object',
                    'loadOptionMode'    => 'ajax',
                    'queryArgs'         => [
                        'action' => 'wilcity_fetch_dokan_products'
                    ],
                    'fields'            => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'label',
                            'value' => 'Showing Products on the Listing'
                        ],
                        [
                            'label' => 'Maximum Listings can be used',
                            'type'  => 'input',
                            'key'   => 'maximum',
                            'value' => 4
                        ]
                    ]
                ]
            ]
        ],
        'my_products'                => [
            'isDefault'           => true,
            'excludeGetBySection' => true,
            'type'                => 'my_products',
            'key'                 => 'my_products',
            'icon'                => 'la la-shopping-cart',
            'heading'             => 'My Products',
            'fieldGroups'         => [
                [
                    'heading'           => 'Mode',
                    'key'               => 'my_product_mode',
                    'type'              => 'wil-select-tree',
                    'selectValueFormat' => 'id',
                    'loadOptionMode'    => 'default',
                    'fields'            => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'label',
                            'value' => 'Mode'
                        ]
                    ]
                ],
                [
                    'heading'           => 'Product Category',
                    'key'               => 'my_product_cats',
                    'type'              => 'wil-select-tree',
                    'dependency'        => [
                        'parent'  => 'my_product_mode',
                        'compare' => '=',
                        'value'   => 'specify_product_cats'
                    ],
                    'isAjax'            => true,
                    'selectValueFormat' => 'object',
                    'loadOptionMode'    => 'ajax',
                    'queryArgs'         => [
                        'action' => 'wilcity_fetch_product_cats'
                    ],
                    'fields'            => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'label',
                            'value' => 'Pickup product categories'
                        ],
                        [
                            'label' => 'Maximum Categories can be used',
                            'type'  => 'input',
                            'key'   => 'maximum',
                            'value' => 4
                        ]
                    ]
                ],
                [
                    'heading'           => 'Product Settings',
                    'key'               => 'my_products',
                    'dependency'        => [
                        'parent'  => 'my_product_mode',
                        'compare' => '=',
                        'value'   => 'specify_products'
                    ],
                    'type'              => 'wil-select-tree',
                    'isAjax'            => true,
                    'selectValueFormat' => 'object',
                    'loadOptionMode'    => 'ajax',
                    'queryArgs'         => [
                        'action' => 'wilcity_fetch_dokan_products'
                    ],
                    'fields'            => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'label',
                            'value' => 'Showing Products on the Listing'
                        ],
                        [
                            'label' => 'Maximum Products can be used',
                            'type'  => 'input',
                            'key'   => 'maximum',
                            'value' => 4
                        ]
                    ]
                ]
            ]
        ],
        'my_room'                    => [
            'isDefault'           => true,
            'excludeGetBySection' => true,
            'type'                => 'my_room',
            'key'                 => 'my_room',
            'icon'                => 'la la-hotel',
            'heading'             => 'My Room',
            'fieldGroups'         => [
                [
                    'heading'           => 'Settings',
                    'type'              => 'wil-select-tree',
                    'desc'              => '',
                    'key'               => 'my_room',
                    'isAjax'            => true,
                    'selectValueFormat' => 'object',
                    'loadOptionMode'    => 'ajax',
                    'queryArgs'         => [
                        'action' => 'wilcity_fetch_my_room'
                    ],
                    'maximum'           => 1,
                    'fields'            => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'label',
                            'value' => 'Showing Products on the Listing'
                        ]
                    ]
                ]
            ]
        ],
        'my_posts'                   => [
            'isDefault'           => false,
            'excludeGetBySection' => true,
            'type'                => 'my_posts',
            'key'                 => 'my_posts',
            'icon'                => 'la la-writter',
            'heading'             => 'My Post',
            'fieldGroups'         => [
                [
                    'heading'           => 'Settings',
                    'type'              => 'wil-select-tree',
                    'desc'              => '',
                    'key'               => 'my_posts',
                    'isAjax'            => true,
                    'selectValueFormat' => 'object',
                    'loadOptionMode'    => 'ajax',
                    'queryArgs'         => [
                        'action' => 'wilcity_fetch_my_posts'
                    ],
                    'fields'            => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'label',
                            'value' => 'Showing Posts on the Listing'
                        ],
                        [
                            'label' => 'Maximum Listings can be used',
                            'type'  => 'input',
                            'key'   => 'maximum',
                            'value' => 4
                        ]
                    ]
                ]
            ]
        ],
        'bookingcombannercreator'    => [
            'isDefault'   => true,
            'type'        => 'bookingcombannercreator',
            'key'         => 'bookingcombannercreator',
            'icon'        => 'la la-hotel',
            'heading'     => 'Booking.com Banner Creator',
            'fieldGroups' => [
                [
                    'heading' => 'Button Name Label',
                    'type'    => 'input',
                    'desc'    => '',
                    'toggle'  => 'enable',
                    'key'     => 'bookingcombannercreator_buttonName',
                    'fields'  => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Button Name',
                        ]
                    ]
                ],
                [
                    'heading' => 'Button Name Color Label',
                    'type'    => 'colorpicker',
                    'desc'    => '',
                    'toggle'  => 'enable',
                    'key'     => 'bookingcombannercreator_buttonColor',
                    'fields'  => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Button Color'
                        ],
                    ]
                ],
                [
                    'heading' => 'Button Background Label',
                    'type'    => 'colorpicker',
                    'desc'    => '',
                    'toggle'  => 'enable',
                    'key'     => 'bookingcombannercreator_buttonBg',
                    'fields'  => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Button Background Color'
                        ],
                    ]
                ],
                [
                    'heading'     => 'Banner Image Settings',
                    'type'        => 'wil-uploader',
                    'maximum'     => 1,
                    'desc'        => '',
                    'key'         => 'bookingcombannercreator_bannerImg',
                    'valueFormat' => 'object',
                    'fields'      => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Banner Image (1920px wide suggested)'
                        ],
                    ]
                ],
                [
                    'heading' => 'Banner Copy',
                    'type'    => 'input',
                    'desc'    => '',
                    'key'     => 'bookingcombannercreator_bannerCopy',
                    'fields'  => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Banner Copy'
                        ],
                    ]
                ],
                [
                    'heading' => 'Banner Copy Color Setting',
                    'type'    => 'colorpicker',
                    'desc'    => '',
                    'toggle'  => 'enable',
                    'key'     => 'bookingcombannercreator_bannerCopyColor',
                    'fields'  => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Banner Copy Color'
                        ],
                    ]
                ],
                [
                    'heading' => 'Banner Link',
                    'type'    => 'input',
                    'desc'    => '',
                    'key'     => 'bookingcombannercreator_bannerLink',
                    'fields'  => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Banner Link'
                        ]
                    ]
                ]
            ]
        ],
        'video'                      => [
            'isDefault'   => true,
            'type'        => 'video',
            'key'         => 'video',
            'icon'        => 'la la-video-camera',
            'heading'     => 'Video Urls',
            'fieldGroups' => [
                [
                    'heading'     => 'Settings',
                    'type'        => 'wil-pickup-and-set',
                    'desc'        => 'You can define the maximum videos user can add for each plan',
                    'key'         => 'video_srcs',
                    'valueFormat' => 'array',
                    'fields'      => [
                        [
                            'label' => 'Add video button name',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'addItemBtnName',
                            'value' => 'Add More'
                        ],
                        [
                            'label' => 'Placeholder',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'label',
                            'value' => 'Video Link'
                        ],
                        [
                            'label' => 'Maximum Videos',
                            'type'  => 'input',
                            'desc'  => 'Specifying maximum videos can be used on a listing',
                            'key'   => 'maximum',
                            'value' => 3
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'yes'
                        ]
                    ]
                ]
            ]
        ],
        'gallery'                    => [
            'isDefault'           => true,
            'type'                => 'gallery',
            'key'                 => 'gallery',
            'excludeGetBySection' => true,
            'icon'                => 'la la-image',
            'heading'             => 'Gallery',
            'fieldGroups'         => [
                [
                    'heading'     => 'Upload images',
                    'type'        => 'wil-uploader',
                    'valueFormat' => 'array',
                    'desc'        => '',
                    'key'         => 'gallery',
                    'fields'      => [
                        [
                            'label' => 'Maximum Images',
                            'type'  => 'input',
                            'desc'  => 'Maximum images can be uploaded on a listing',
                            'key'   => 'maximum',
                            'value' => 4
                        ],
                        [
                            'label' => 'Description',
                            'type'  => 'input',
                            'key'   => 'description',
                            'desc'  => 'You can use %maximum% as a placeholder in your description. This text is replaced with real Maximum Items on the front-end',
                            'value' => 'You can upload maximum %maximum% images'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'yes'
                        ]
                    ]
                ]
            ]
        ],
        'event_calendar'             => [
            'isDefault'    => true,
            'listingTypes' => ['event'],
            'type'         => 'event_calendar',
            'key'          => 'event_calendar',
            'icon'         => 'la la-certificate',
            'heading'      => 'Event Calendar',
            'valueFormat'  => 'object',
            'fieldGroups'  => [
                [
                    'heading'     => 'Settings',
                    'type'        => 'event_calendar',
                    'desc'        => '',
                    'valueFormat' => 'object',
                    'key'         => 'event_calendar',
                    'fields'      => [
                        [
                            'label' => 'Label Name',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'label',
                            'value' => 'Frequency'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'yes'
                        ]
                    ]
                ]
            ]
        ],
        'hosted_by'                  => [
            'isDefault'    => true,
            'listingTypes' => ['event'],
            'type'         => 'hosted_by',
            'key'          => 'hosted_by',
            'icon'         => 'la la-user',
            'heading'      => 'Event Hosted By',
            'fieldGroups'  => [
                [
                    'heading' => 'Host',
                    'type'    => 'input',
                    'desc'    => '',
                    'key'     => 'hosted_by',
                    'fields'  => [
                        [
                            'label' => 'Label Name',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'label',
                            'value' => 'Host'
                        ]
                    ]
                ],
                [
                    'heading' => 'Profile URL',
                    'type'    => 'input',
                    'desc'    => '',
                    'key'     => 'hosted_by_profile_url',
                    'fields'  => [
                        [
                            'label' => 'Label Name',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'label',
                            'value' => 'Profile URL'
                        ]
                    ]
                ]
            ]
        ],
        'event_belongs_to_listing'   => [
            'isDefault'    => true,
            'listingTypes' => ['event'],
            'type'         => 'event_belongs_to_listing',
            'key'          => 'event_belongs_to_listing',
            'icon'         => 'la la-certificate',
            'heading'      => 'Event Belongs To',
            'fieldGroups'  => [
                [
                    'heading'           => 'Settings',
                    //                    'type'      => 'select2',
                    'type'              => 'wil-select-tree',
                    'isAjax'            => true, // new
                    'selectValueFormat' => 'object', // new
                    'loadOptionMode'    => 'ajax', // new
                    'desc'              => '',
                    'key'               => 'event_belongs_to_listing',
                    'queryArgs'         => [
                        'action'     => 'wilcity_fetch_listing_type',
                        'post_types' => '{{post_types}}',
                        'maximum'    => 1
                    ],
                    'fields'            => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'label',
                            'value' => 'Listing Parent'
                        ],
                        [
                            'label' => 'Description',
                            'type'  => 'textarea',
                            'desc'  => '',
                            'key'   => 'description'
                        ],
                        //                        [
                        //                            'heading' => '',
                        //                            'type'    => 'hidden',
                        //                            'desc'    => '',
                        //                            'key'     => 'ajaxAction',
                        //                            'value'   => 'wilcity_fetch_post'
                        //                        ],
                        //                        [
                        //                            'label' => 'Specify Parent Listing Types',
                        //                            'type'  => 'text',
                        //                            'desc'  => 'Each Listing Type is separated by a comma. Eg: listing,education. You can find the Listing Type under Wiloke Tools -> Add Listing Type',
                        //                            'key'   => 'eventParents',
                        //                            'value' => 'listing'
                        //                        ],
                        [
                            'label'      => 'Listing Type Key (*)',
                            'type'       => 'input',
                            'component'  => 'wil-async-search',
                            'isMultiple' => 'yes',
                            'ajaxUrl'    => add_query_arg(
                                [
                                    'action' => 'listing_post_types'
                                ],
                                rest_url('wiloke/v2/post-types')
                            ),
                            'desc'       => '',
                            'key'        => 'post_types',
                            'value'      => ''
                        ],
                        //                        [
                        //                            'heading' => '',
                        //                            'type'    => 'hidden',
                        //                            'desc'    => '',
                        //                            'key'     => 'isAjax',
                        //                            'value'   => 'yes'
                        //                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'no'
                        ],
                    ]
                ]
            ]
        ],
        'restaurant_menu'            => [
            'isDefault'   => false,
            'type'        => 'restaurant_menu',
            'key'         => 'restaurant_menu',
            'icon'        => 'la la-cutlery',
            'heading'     => 'Restaurant Menu',
            'fieldGroups' => [
                [
                    'heading'     => 'Settings',
                    'type'        => 'wil-group',
                    'desc'        => '',
                    'value'       => [],
                    'valueFormat' => 'array',
                    'key'         => 'restaurant_menu',
                    'fields'      => [
                        [
                            'label' => 'Menu Title Label',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'groupTitleLabel',
                            'value' => 'Title'
                        ],
                        [
                            'label' => 'Menu Description Label',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'groupDescriptionLabel',
                            'value' => 'Description'
                        ],
                        [
                            'label' => 'Menu Icon Label',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'groupIconLabel',
                            'value' => 'Icon'
                        ]
                    ]
                ]
            ]
        ],
        'custom_button'              => [
            'type'        => 'custom_button',
            'key'         => 'custom_button',
            'icon'        => 'la la-cog',
            'heading'     => 'Custom Button',
            'fieldGroups' => [
                [
                    'heading' => 'Button Icon Settings',
                    'type'    => 'wil-icon',
                    'desc'    => '',
                    'key'     => 'button_icon',
                    'fields'  => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Button Icon'
                        ]
                    ]
                ],
                [
                    'heading' => 'Button Link Settings',
                    'type'    => 'input',
                    'desc'    => '',
                    'key'     => 'button_link',
                    'fields'  => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Button Link'
                        ]
                    ]
                ],
                [
                    'heading' => 'Button Name Settings',
                    'type'    => 'input',
                    'desc'    => '',
                    'key'     => 'button_name',
                    'fields'  => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'key'   => 'label',
                            'value' => 'Button Name'
                        ]
                    ]
                ]
            ]
        ],
        'file'                       => [
            'isDefault'       => false,
            'isClone'         => true,
            'listingTypes'    => ['listing', 'event'],
            'isCustomSection' => 'yes',
            'type'            => 'file',
            'key'             => 'file',
            'icon'            => 'la la-image',
            'heading'         => 'File',
            'fieldGroups'     => [
                [
                    'heading'     => 'Settings',
                    'type'        => 'wil-upload-file',
                    'desc'        => 'The following files are supported: pdf, docx, doc, dotx, csv',
                    'key'         => 'files',
                    'valueFormat' => 'array',
                    'fields'      => [
                        [
                            'label' => 'Maximum Items',
                            'type'  => 'input',
                            'desc'  => 'Maximum document can be uploaded on a listing',
                            'key'   => 'maximum',
                            'value' => 1
                        ],
                        [
                            'label' => 'Description',
                            'type'  => 'input',
                            'key'   => 'description',
                            'desc'  => 'You can use %maximum% as a placeholder in your description. This text is replaced with real Maximum Items on the front-end',
                            'value' => 'You can upload maximum %maximum% files'
                        ],
                        [
                            'label' => 'Allowed extensions',
                            'type'  => 'input',
                            'key'   => 'allowed_extension',
                            'value' => 'pdf'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'yes'
                        ],
                        [
                            'label' => 'Button name',
                            'type'  => 'input',
                            'key'   => 'btn_name',
                            'value' => 'Upload Document'
                        ]
                    ]
                ]
            ]
        ],
        'image'                      => [
            'isDefault'       => false,
            'isClone'         => true,
            'listingTypes'    => ['listing', 'event'],
            'isCustomSection' => 'yes',
            'type'            => 'image',
            'key'             => 'image',
            'icon'            => 'la la-image',
            'heading'         => 'Image',
            'fieldGroups'     => [
                [
                    'heading'     => 'Image Settings',
                    'type'        => 'wil-uploader',
                    'maximum'     => 1,
                    'valueFormat' => 'object',
                    'desc'        => '',
                    'key'         => 'image',
                    'fields'      => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'label',
                            'value' => 'Upload an image'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'no'
                        ]
                    ]
                ],
                [
                    'heading' => 'Link To Settings',
                    'type'    => 'wil-input',
                    'desc'    => '',
                    'key'     => 'link_to',
                    'fields'  => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'label',
                            'value' => 'Link To'
                        ],
                        [
                            'label' => 'Is Required?',
                            'type'  => 'checkbox',
                            'desc'  => '',
                            'key'   => 'isRequired',
                            'value' => 'no'
                        ]
                    ]
                ]
            ]
        ],
        'input'                      => [
            'isClone'         => true,
            'isCustomSection' => 'yes',
            'type'            => 'input',
            'key'             => 'my_text_field',
            'icon'            => 'la la-magic',
            'heading'         => 'Text field',
            'fieldGroups'     => [
                [
                    'heading' => 'Settings',
                    'type'    => 'input',
                    'desc'    => '',
                    'key'     => 'settings',
                    'fields'  => [
                        [
                            'label' => 'Label',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'label',
                            'value' => 'Label'
                        ],
                        [
                            'label'     => 'Description',
                            'component' => 'wil-textarea',
                            'desc'      => '',
                            'key'       => 'description',
                            'value'     => ''
                        ],
                        [
                            'label'     => 'Is Required?',
                            'component' => 'wil-checkbox',
                            'desc'      => '',
                            'key'       => 'isRequired',
                            'value'     => 'no'
                        ]
                    ]
                ]
            ]
        ],
        'textarea'                   => [
            'isClone'         => true,
            'isCustomSection' => 'yes',
            'type'            => 'textarea',
            'key'             => 'my_textarea_field',
            'icon'            => 'la la-wikipedia-w',
            'heading'         => 'Textarea field',
            'fieldGroups'     => [
                [
                    'heading' => 'Settings',
                    'type'    => 'textarea',
                    'desc'    => '',
                    'key'     => 'settings',
                    'fields'  => [
                        [
                            'label'     => 'Label',
                            'component' => 'wil-input',
                            'desc'      => '',
                            'key'       => 'label',
                            'value'     => 'Label'
                        ],
                        [
                            'label'     => 'Description',
                            'component' => 'wil-textarea',
                            'desc'      => '',
                            'key'       => 'description',
                            'value'     => ''
                        ],
                        [
                            'label'     => 'Is Required?',
                            'component' => 'wil-checkbox',
                            'desc'      => '',
                            'key'       => 'isRequired',
                            'value'     => 'no'
                        ]
                    ]
                ]
            ]
        ],
        'date_time'                  => [
            'isCustomSection' => 'yes',
            'listingTypes'    => ['listing'],
            'type'            => 'date_time',
            'key'             => 'date_time',
            'icon'            => 'la la-clock-o',
            'heading'         => 'Date time',
            'fieldGroups'     => [
                [
                    'heading' => 'Settings',
                    'type'    => 'wil-datepicker',
                    'desc'    => '',
                    'key'     => 'settings',
                    'fields'  => [
                        [
                            'label'     => 'Label',
                            'component' => 'wil-input',
                            'desc'      => '',
                            'key'       => 'label',
                            'value'     => 'Label'
                        ],
                        [
                            'label'     => 'Description',
                            'component' => 'wil-textarea',
                            'desc'      => '',
                            'key'       => 'description',
                            'value'     => ''
                        ],
                        [
                            'label'     => 'Is Show Time Panel?',
                            'component' => 'wil-checkbox',
                            'desc'      => '',
                            'key'       => 'showTimePanel',
                            'value'     => 'no'
                        ],
                        [
                            'label'     => 'Is Required?',
                            'component' => 'wil-checkbox',
                            'desc'      => '',
                            'key'       => 'isRequired',
                            'value'     => 'yes'
                        ]
                    ]
                ]
            ]
        ],
        'select'                     => [
            'isCustomSection' => 'yes',
            'type'            => 'select',
            'key'             => 'my_select_field',
            'icon'            => 'la la-eyedropper',
            'heading'         => 'Select field',
            'isClone'         => true,
            'fieldGroups'     => [
                [
                    'heading'       => 'Settings',
                    'desc'          => '',
                    'type'          => 'wil-select-tree',
                    'key'           => 'settings',
                    'isCustomField' => true,
                    'fields'        => [
                        [
                            'label'     => 'Label',
                            'component' => 'wil-input',
                            'key'       => 'label',
                            'value'     => 'Select'
                        ],
                        [
                            'label'     => 'Description',
                            'component' => 'wil-textarea',
                            'desc'      => '',
                            'key'       => 'description',
                            'value'     => ''
                        ],
                        [
                            'label'         => 'Options',
                            'desc'          => 'Each option separates by a comma. The option should look like this structure: red_color:Red Color,green_color: Green Color',
                            'component'     => 'wil-textarea',
                            'isOptionField' => true,
                            'key'           => 'options',
                            'value'         => ''
                        ],
                        [
                            'label'     => 'Is Required?',
                            'component' => 'wil-checkbox',
                            'desc'      => '',
                            'key'       => 'isRequired',
                            'value'     => 'no'
                        ],
                        [
                            'label' => 'Maximum Items',
                            'desc'  => 'Maximum items can be used for 1 listing',
                            'type'  => 'input',
                            'key'   => 'maximum',
                            'value' => 4
                        ]
                    ]
                ]
            ]
        ],
        'multiple-checkbox'          => [
            // replaced checkbox2 with multiple-checkbox
            'isCustomSection' => 'yes',
            'isClone'         => true,
            'type'            => 'multiple-checkbox',
            'key'             => 'my_checkbox_field',
            'icon'            => 'la la-check',
            'heading'         => 'Checkbox field',
            'valueFormat'     => 'array',
            'fieldGroups'     => [
                [
                    'heading'       => 'Settings',
                    'type'          => 'wil-multiple-checkbox',
                    'valueFormat'   => 'array',
                    'isCustomField' => true,
                    'desc'          => '',
                    'key'           => 'settings',
                    'fields'        => [
                        [
                            'label'     => 'Label',
                            'component' => 'wil-input',
                            'desc'      => '',
                            'key'       => 'label',
                            'value'     => 'Label'
                        ],
                        [
                            'label'     => 'Description',
                            'component' => 'wil-textarea',
                            'desc'      => '',
                            'key'       => 'description',
                            'value'     => ''
                        ],
                        [
                            'label'         => 'Options',
                            'desc'          => 'Each option separates by a comma. The option should look like this structure: red_color:Red Color,green_color: Green Color',
                            'component'     => 'wil-textarea',
                            'isOptionField' => true,
                            'key'           => 'options',
                            'value'         => ''
                        ],
                        [
                            'label'     => 'Is Required?',
                            'component' => 'wil-checkbox',
                            'desc'      => '',
                            'key'       => 'isRequired',
                            'value'     => 'no'
                        ]
                    ]
                ]
            ]
        ],
        'group'                      => [
            'isGroup'         => true,
            'isClone'         => true,
            'isCustomSection' => 'yes',
            'type'            => 'group',
            'key'             => 'my_group_field',
            'icon'            => 'la la-check',
            'heading'         => 'Group Field',
            'valueFormat'     => 'array',
            'fieldGroups'     => [
                [
                    'heading'         => 'Custom Field Settings',
                    'component'       => 'wil-group',
                    'type'            => 'wil-group',
                    'desc'            => '',
                    'key'             => 'settings',
                    'valueFormat'     => 'array',
                    'maximum'         => 1, // this is number of group
                    'maximumChildren' => 5, // this is number of item in a group
                    'fields'          => [
                        [
                            'label' => 'Menu Title Label',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'groupTitleLabel',
                            'value' => 'Title'
                        ],
                        [
                            'label' => 'Menu Description Label',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'groupDescriptionLabel',
                            'value' => 'Description'
                        ],
                        [
                            'label' => 'Menu Icon Label',
                            'type'  => 'input',
                            'desc'  => '',
                            'key'   => 'groupIconLabel',
                            'value' => 'Icon'
                        ],
                        [
                            'label' => 'Maximum Group Items',
                            'desc'  => 'Which means You can add other fields as a repeatable group',
                            'type'  => 'input',
                            'key'   => 'maximumChildren',
                            'value' => 1
                        ],
                        [
                            'label'   => 'Aways Expands?',
                            'desc'    => 'The group field will always expand on the Add Listing page',
                            'type'    => 'select',
                            'key'     => 'alwaysExpand',
                            'value'   => 'no',
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
                            'label'              => 'Custom Fields',
                            'component'          => 'wil-group',
                            'desc'               => '',
                            'key'                => 'fieldsSkeleton',
                            'valueFormat'        => 'array',
                            'fieldsSkeleton'     => [
                                [
                                    'label'     => 'Label',
                                    'component' => 'wil-input',
                                    'desc'      => '',
                                    'key'       => 'label',
                                    'value'     => 'My Label'
                                ],
                                [
                                    'label'     => 'Key',
                                    'component' => 'wil-input',
                                    'desc'      => 'This key must be unique',
                                    'key'       => 'key',
                                    'value'     => uniqid('my_key_')
                                ],
                                [
                                    'label'       => 'Field Type',
                                    'component'   => 'wil-select',
                                    'key'         => 'type',
                                    'value'       => 'wil-input',
                                    'valueFormat' => 'array',
                                    'options'     => [
                                        [
                                            'name'  => 'Text',
                                            'value' => 'wil-input'
                                        ],
                                        [
                                            'name'  => 'Textarea',
                                            'value' => 'wil-textarea'
                                        ],
                                        [
                                            'name'  => 'Select',
                                            'value' => 'wil-select-tree'
                                        ],
                                        [
                                            'name'  => 'Uploader',
                                            'value' => 'wil-uploader'
                                        ]
                                    ]
                                ]
                            ],
                            'fieldValueSkeleton' => [
                                'label'      => 'My Field',
                                'key'        => 'my_key',
                                'type'       => 'wil-input',
                                'isRequired' => 'no',
                                'options'    => '',
                                'maximum'    => 1
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]),
    'productModeOptions' => [
        'author_products'      => esc_html__('My own products', 'wiloke-listing-tools'),
        'specify_products'     => esc_html__('Specify products', 'wiloke-listing-tools'),
        'specify_product_cats' => esc_html__('Specify product categories', 'wiloke-listing-tools'),
        'inherit'              => esc_html__('Inherit Theme Options', 'wiloke-listing-tools'),
    ]
];
