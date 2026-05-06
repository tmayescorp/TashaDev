<?php
return [
    'aNavigation'  => [
        'dashboard'     => [
            'name'             => esc_html__('Dashboard', 'wiloke-listing-tools'),
            'icon'             => 'la la-home',
            'endpoint'         => 'dashboard',
            'isExcludeFromApp' => false
        ],
        'profile'       => [
            'name' => esc_html__('Profile', 'wiloke-listing-tools'),
            'icon' => 'la la-user'
        ],
        'listings'      => [
            'name' => esc_html__('Listings', 'wiloke-listing-tools'),
            'icon' => 'la la-th-list'
        ],
        'reviews'       => [
            'name'   => esc_html__('Reviews', 'wiloke-listing-tools'),
            'icon'   => 'la la-star',
            'params' => [
                'routeName' => 'reviews'
            ]
        ],
        'messages'      => [
            'name' => esc_html__('Messages', 'wiloke-listing-tools'),
            'icon' => 'la la-envelope'
        ],
        'notifications' => [
            'name'   => esc_html__('Notifications', 'wiloke-listing-tools'),
            'icon'   => 'la la-bell',
            'params' => [
                'routeName' => 'notifications'
            ]
        ],
        'billings'      => [
            'name'             => esc_html__('Billings', 'wiloke-listing-tools'),
            'icon'             => 'la la-money',
            'endpoint'         => 'billings',
            'isExcludeFromApp' => false
        ],
        'favorites'     => [
            'name' => esc_html__('Favorites', 'wiloke-listing-tools'),
            'icon' => 'la la-heart-o'
        ]
    ],
    'themeoptions' => [
        'title'            => 'Dashboard Settings',
        'id'               => 'dashboard_settings',
        'subsection'       => false,
        'icon'             => 'dashicons dashicons-list-view',
        'customizer_width' => '500px',
        'fields'           => [
            [
                'id'      => 'favorite_chart_color',
                'type'    => 'color_rgba',
                'title'   => 'Favorite Chart Color',
                'default' => '#f06292'
            ],
            [
                'id'      => 'rating_chart_color',
                'type'    => 'color_rgba',
                'title'   => 'Rating Chart Color',
                'default' => '#f06292'
            ],
            [
                'id'      => 'share_chart_color',
                'type'    => 'color_rgba',
                'title'   => 'Share Chart Color',
                'default' => '#f06292'
            ],
            [
                'id'      => 'view_chart_color',
                'type'    => 'color_rgba',
                'title'   => 'View Chart Color',
                'default' => '#f06292'
            ],
        ]
    ]
];
