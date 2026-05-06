<?php
return [
    'prefix'           => 'wiloke_',
    'metaboxPrefix'    => 'wilcity_',
    'aDayOfWeek'       => [
        'sunday'    => esc_html__('Sunday', 'wiloke-listing-tools'),
        'monday'    => esc_html__('Monday', 'wiloke-listing-tools'),
        'tuesday'   => esc_html__('Tuesday', 'wiloke-listing-tools'),
        'wednesday' => esc_html__('Wednesday', 'wiloke-listing-tools'),
        'thursday'  => esc_html__('Thursday', 'wiloke-listing-tools'),
        'friday'    => esc_html__('Friday', 'wiloke-listing-tools'),
        'saturday'  => esc_html__('Saturday', 'wiloke-listing-tools')
    ],
    'priceRange'       => apply_filters('wilcity/filter/price-range-options', [
        'nottosay'   => esc_html__('Not to say', 'wiloke-listing-tools'),
        'cheap'      => esc_html__('Cheap', 'wiloke-listing-tools'),
        'moderate'   => esc_html__('Moderate', 'wiloke-listing-tools'),
        'expensive'  => esc_html__('Expensive', 'wiloke-listing-tools'),
        'ultra_high' => esc_html__('Ultra High', 'wiloke-listing-tools')
    ]),
    'aOrderBy'         => [
        'post_title'  => 'Listing Title',
        'post_date'   => 'Listing Date',
        'menu_order'  => 'Listing Order',
        'best_rated'  => 'Top Rated',
        'best_viewed' => 'Top Viewed',
        'rand'        => 'Random'
    ],
    'aOrderByFallback' => [
        'post_title' => 'Listing Title',
        'post_date'  => 'Listing Date'
    ]
];
