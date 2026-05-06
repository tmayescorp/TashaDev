<?php
return [
    'claim_status'          => [
        'id'           => 'claim_status',
        'title'        => esc_html__('Claim Status', 'wiloke-listing-tools'),
        'object_types' => ['claim_listing'],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type'    => 'select',
                'id'      => 'wilcity_claim_status',
                'name'    => 'Claimer Status',
                'options' => [
                    'pending'   => 'Pending',
                    'cancelled' => 'Cancelled',
                    'approved'  => 'Approved'
                ]
            ]
        ]
    ],
    'claimer_id'            => [
        'id'           => 'wilcity_claimer_id',
        'title'        => esc_html__('Claimer', 'wiloke-listing-tools'),
        'object_types' => ['claim_listing'],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type'       => 'select2_user',
                'id'         => 'wilcity_claimer_id',
                'name'       => 'Claimer Username',
                'attributes' => [
                    'ajax_action' => 'wiloke_select_user'
                ]
            ]
        ]
    ],
    'claimed_listing_id'    => [
        'id'           => 'claimed_listing_id',
        'title'        => esc_html__('Listing Name', 'wiloke-listing-tools'),
        'object_types' => ['claim_listing'],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type'       => 'select2_posts',
                'id'         => 'wilcity_claimed_listing_id',
                'name'       => 'Listing Name',
                'attributes' => [
                    'ajax_action' => 'wiloke_fetch_posts',
                    'post_types'  => implode(',', \WilokeListingTools\Framework\Helpers\General::getPostTypeKeys(false))
                ]
            ]
        ]
    ],
    'claim_plan_id'         => [
        'id'           => 'claim_plan_id',
        'title'        => 'Claim Plan',
        'object_types' => ['claim_listing'],
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type'       => 'select2_posts',
                'id'         => 'wilcity_claim_plan_id',
                'name'       => 'Claim Plan',
                'attributes' => [
                    'ajax_action' => 'wiloke_fetch_posts',
                    'post_types'  => 'listing_plan'
                ]
            ]
        ]
    ],
    'attribute_post_author' => [
        'id'           => 'attribute_post_author',
        'title'        => esc_html__('Attribute this listing to', 'wiloke-listing-tools'),
        'object_types' => ['claim_listing'],
        'context'      => 'normal',
        'priority'     => 'low',
        'save_fields'  => false,
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type'       => 'select2_user',
                'id'         => 'attribute_post_author',
                'name'       => 'Attribute this listing to',
                'desc'       => 'This setting is required if you want to switch this claim from Approved to another status.',
                'attributes' => [
                    'ajax_action' => 'wiloke_select_user'
                ]
            ]
        ]
    ]
];
