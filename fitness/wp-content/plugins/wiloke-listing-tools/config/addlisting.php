<?php

/**
 * Configurating add listing settings
 */
return [
    'tokenSessionIDRelationship' => 'paypal_token_id_relationship',
    'isPayViaDirectBankTransfer' => 'pay_via_direct_bank_transfer',
    'customPostTypesKey'         => 'custom_posttypes',
    'storeIsTrial'               => 'paypal_is_trial',
    'productIDPaymentID'         => 'product_id_payment_id',
    'orderIDPaymentID'           => 'order_id_payment_id',
    'paypalAgreementID'          => 'paypal_agreement_id',
    'nextBillingDateGMT'         => 'next_billing_date_gmt',
    'planStartedAtGMT'           => 'plan_started_at_gmt',
    'isUsingTrial'               => 'is_using_trial',
    'sessionStore'               => 'session_store',
    'customMetaBoxPrefix'        => 'wilcity_custom_',
    'usedSectionKey'             => 'add_listing_sections',
    'usedSectionSavedAtKey'      => 'add_listing_saved_at',
    'isAddingListingSession'     => 'wilcity_is_adding_listing_session',
    'restaurantItem'             => apply_filters(
        'wilcity/filter/addlisting/restaurant-item-skeleton',
        [
            'title'              => [
                'type'  => 'wil-input',
                'key'   => 'title',
                'label' => esc_html__("Title", 'wiloke-listing-tools'),
                'value' => ''
            ],
            'description'        => [
                'type'  => 'wil-input',
                'key'   => 'description',
                'label' => esc_html__("Description", 'wiloke-listing-tools'),
                'value' => ''
            ],
            'gallery'            => [
                'type'        => 'wil-uploader',
                'maximum'     => 8,
                'key'         => 'gallery',
                'label'       => esc_html__("Gallery", 'wiloke-listing-tools'),
                'value'       => [],
                'valueFormat' => 'array'
            ],
            'document'            => [
                'type'        => 'wil-document',
                'maximum'     => 8,
                'key'         => 'document',
                'label'       => esc_html__("Document", 'wiloke-listing-tools'),
                'value'       => [],
                'valueFormat' => ''
            ],
            'price'              => [
                'type'  => 'wil-input',
                'key'   => 'price',
                'label' => esc_html__("Price", 'wiloke-listing-tools'),
                'value' => ''
            ],
            'link_to'            => [
                'type'  => 'wil-input',
                'key'   => 'link_to',
                'label' => esc_html__("Link To", 'wiloke-listing-tools'),
                'value' => ''
            ],
            'is_open_new_window' => [
                'type'         => 'wil-select-tree',
                'key'          => 'is_open_new_window',
                'selectFormat' => 'id',
                'label'        => esc_html__("Is Open New Window?", 'wiloke-listing-tools'),
                'options'      => [
                    [
                        'id'    => 'yes',
                        'label' => esc_html__('Yes', 'wiloke-listing-tools')
                    ],
                    [
                        'id'    => 'no',
                        'label' => esc_html__('No', 'wiloke-listing-tools')
                    ]
                ],
                'value'        => 'no'
            ]
        ]
    ),
    'aPriceRange'                => apply_filters('wilcity/filter/addlisting/price-range-options', [
        [
            'label' => esc_html__('Not to say', 'wiloke-listing-tools'),
            'id'    => 'nottosay'
        ],
        [
            'label' => esc_html__('Cheap', 'wiloke-listing-tools'),
            'id'    => 'cheap'
        ],
        [
            'label' => esc_html__('Moderate', 'wiloke-listing-tools'),
            'id'    => 'moderate'
        ],
        [
            'label' => esc_html__('Expensive', 'wiloke-listing-tools'),
            'id'    => 'expensive'
        ],
        [
            'label' => esc_html__('Ultra high', 'wiloke-listing-tools'),
            'id'    => 'ultra_high'
        ]
    ]),
    'aTimeRange'                 => [
        'from' => esc_html__('From', 'wiloke-listing-tools'),
        'to'   => esc_html__('To', 'wiloke-listing-tools')
    ],
    'aFormBusinessHour'          => [
        [
            'value' => '00::00:00',
            'name'  => '00:00 AM'
        ],
        [
            'value' => '00::15:00',
            'name'  => '00:15 AM'
        ],
        [
            'value' => '00::30:00',
            'name'  => '00:30 AM'
        ],
        [
            'value' => '00::45:00',
            'name'  => '00:45 AM'
        ]
    ],
    'businessHours'              => [
        'sunday'    => [
            [
                'open'   => '',
                'close'  => '',
                'id'     => '5ca5578b0c5c7',
                'isOpen' => false
            ]
        ],
        'monday'    => [
            [
                'open'   => '0800',
                'close'  => '1700',
                'id'     => '5ca5578b0c5c1',
                'isOpen' => true
            ]
        ],
        'tuesday'   => [
            [
                'open'   => '',
                'close'  => '',
                'id'     => '5ca5578b0c5c7',
                'isOpen' => false
            ]
        ],
        'wednesday' => [
            [
                'open'   => '',
                'close'  => '',
                'id'     => '5ca5578b0c5cm',
                'isOpen' => false
            ]
        ],
        'thursday'  => [
            [
                'open'   => '',
                'close'  => '',
                'id'     => '5ca5578b0c5cg',
                'isOpen' => false
            ]
        ],
        'friday'    => [
            [
                'open'   => '',
                'close'  => '',
                'id'     => '5ca5578b0c5c2',
                'isOpen' => false
            ]
        ],
        'saturday'  => [
            [
                'open'   => '',
                'close'  => '',
                'id'     => '5ca5578b0c5f8',
                'isOpen' => false
            ]
        ]
    ]
];
