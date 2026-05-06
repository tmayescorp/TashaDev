<?php

use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\Time;

//var_export(General::getPostTypeKeysGroup('listing'));
$timeFormat = get_option('time_format');
$dateFormat = Time::convertBackendEventDateFormat();

$prefix = 'wilcity_';

return [
    'hosted_by'         => [
        'id'           => 'hosted_by',
        'title'        => 'Hosted By',
        'object_types' => General::getPostTypeKeysGroup('event'),
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type'        => 'text',
                'id'          => 'wilcity_hosted_by',
                'description' => 'If this field is emptied, the event author will be used.',
                'name'        => 'Name'
            ],
            [
                'type' => 'text',
                'id'   => 'wilcity_hosted_by_profile_url',
                'name' => 'Profile URL'
            ]
        ]
    ],
    'event_time_format' => [
        'id'           => 'event_time_format',
        'title'        => 'Time Format',
        'object_types' => General::getPostTypeKeysGroup('event'),
        'context'      => 'normal',
        'priority'     => 'low',
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'name'    => 'Time Format',
                'type'    => 'select',
                'id'      => 'wilcity_event_time_format',
                'options' => [
                    'inherit' => 'Inherit General Settings',
                    12        => '12h Format',
                    24        => '24h Format',
                ]
            ]
        ]
    ],
    'event_settings'    => [
        'id'           => 'event_settings',
        'title'        => 'Event Settings',
        'object_types' => General::getPostTypeKeysGroup('event'),
        'context'      => 'normal',
        'priority'     => 'low',
        'save_fields'  => false,
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'name'       => 'Frequency',
                'type'       => 'select',
                'id'         => 'frequency',
                'default_cb' => ['WilokeListingTools\MetaBoxes\Event', 'getFrequency'],
                'options'    => [
                    'occurs_once' => 'Occurs Once',
                    'daily'       => 'Daily',
                    'weekly'      => 'Weekly'
                ]
            ],
            [
                'name'       => 'Day',
                'type'       => 'select',
                'id'         => 'specifyDays',
                'default_cb' => ['WilokeListingTools\MetaBoxes\Event', 'getSpecifyDay'],
                'options'    => [
                    'sunday'    => 'Sunday',
                    'monday'    => 'Monday',
                    'tuesday'   => 'Tuesday',
                    'wednesday' => 'Wednesday',
                    'thursday'  => 'Thursday',
                    'friday'    => 'Friday',
                    'saturday'  => 'Saturday'
                ]
            ],
            [
                'name'        => 'Starts',
                'id'          => 'starts',
                'type'        => 'text_date',
                'default_cb'  => ['WilokeListingTools\MetaBoxes\Event', 'startsOn'],
                'date_format' => $dateFormat
            ],
            [
                'name'        => 'Ends On',
                'id'          => 'endsOn',
                'type'        => 'text_date',
                'default_cb'  => ['WilokeListingTools\MetaBoxes\Event', 'endsOn'],
                'date_format' => $dateFormat
            ],
            [
                'name'        => 'Opening At',
                'id'          => 'openingAt',
                'type'        => 'text_time',
                'default_cb'  => ['WilokeListingTools\MetaBoxes\Event', 'openingAt'],
                'time_format' => $timeFormat
            ],
            [
                'name'        => 'Closed At',
                'id'          => 'closedAt',
                'type'        => 'text_time',
                'default_cb'  => ['WilokeListingTools\MetaBoxes\Event', 'closedAt'],
                'time_format' => $timeFormat
            ],
            [
                'name' => 'isFormChanged',
                'id'   => 'isFormChanged',
                'type' => 'hidden'
            ]
        ]
    ],
    'event_parent'      => [
        'id'           => 'event_parent',
        'title'        => 'Event Parent',
        'object_types' => General::getPostTypeKeysGroup('event'),
        'context'      => 'normal',
        'priority'     => 'low',
        'save_fields'  => false,
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type'        => 'select2_posts',
                'description' => 'The parent id is required. If you have not selected a parent id yet, please Select one and then click Publish button. The Review Category will be displayed after that.',
                'post_types'  => General::getPostTypeKeys(false, true),
                'attributes'  => [
                    'ajax_action' => 'wiloke_fetch_posts',
                    'post_types'  => implode(',', General::getPostTypeKeys(false))
                ],
                'id'          => 'parent_id',
                'name'        => 'Parent ID',
                'default_cb'  => ['WilokeListingTools\MetaBoxes\Event', 'getParentID']
            ]
        ]
    ],
    'my_tickets'        => [
        'id'           => 'my_tickets',
        'title'        => 'Event Tickets',
        'object_types' => General::getPostTypeKeysGroup('event'),
        'context'      => 'normal',
        'priority'     => 'low',
        'save_fields'  => false,
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type'        => 'select2_posts',
                'description' => 'Showing WooCommerce Products on this Event page',
                'post_types'  => ['product'],
                'attributes'  => [
                    'ajax_action' => 'wilcity_fetch_dokan_products',
                    'post_types'  => 'product'
                ],
                'id'          => 'wilcity_my_products',
                'multiple'    => true,
                'name'        => 'My Tickets',
                'default_cb'  => ['WilokeListingTools\MetaBoxes\Event', 'getMyProducts']
            ]
        ]
    ],
    'tickets'           => [
        'id'           => 'tickets',
        'title'        => 'Tickets',
        'object_types' => General::getPostTypeKeysGroup('event'),
        'context'      => 'normal',
        'priority'     => 'low',
        'save_fields'  => false,
        'show_names'   => true, // Show field names on the left
        'fields'       => [
            [
                'type' => 'select',
                'id'   => 'ticket_url',
                'name' => 'Ticket url'
            ]
        ]
    ]
];
