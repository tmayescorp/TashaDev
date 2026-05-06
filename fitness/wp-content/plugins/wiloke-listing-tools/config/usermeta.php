<?php
$prefix = 'wilcity_';
global $pagenow;

return [
    'id'               => $prefix.'edit',
    'title'            => 'Wilcity User Meta',
    'object_types'     => ['user'],
    'show_names'       => true,
    'new_user_section' => 'add-new-user',
    'fields'           => apply_filters('wilcity-listing-tools/filter/user-meta', [
        [
            'name' => 'Cover Image',
            'id'   => $prefix.'cover_image',
            'type' => 'file',
        ],
        [
            'name' => 'Avatar',
            'id'   => $prefix.'avatar',
            'type' => 'file',
        ],
        [
            'name' => 'Picture',
            'id'   => $prefix.'picture',
            'type' => 'file',
        ],
        [
            'name' => 'Phone',
            'id'   => $prefix.'phone',
            'type' => 'text',
        ],
        [
            'name' => 'Address',
            'id'   => $prefix.'address',
            'type' => 'text',
        ],
        [
            'name' => 'Position',
            'id'   => $prefix.'position',
            'type' => 'text',
        ],
        [
            'name' => 'Social Networks',
            'is'   => 'usermeta',
            'id'   => $prefix.'social_networks',
            'type' => 'wilcity_social_networks'
        ],
        [
            'name' => 'Message Assistant',
            'desc' => 'Send instant replies to anyone who messages you',
            'id'   => $prefix.'instant_message',
            'type' => 'textarea'
        ],
        [
            'name'    => 'Is Confirmed',
            'id'      => $prefix.'confirmed',
            'type'    => 'select',
            'default' => 'user-new.php' === $pagenow && current_user_can('administrator') ? 1 : 0,
            'options' => [
                0 => 'No',
                1 => 'Yes'
            ]
        ],
        [
            'name'    => 'Receive message through email',
            'id'      => $prefix.'send_email_if_reply_message',
            'type'    => 'select',
            'default' => 'yes',
            'options' => [
                'yes' => 'Yes',
                'no'  => 'No'
            ]
        ],
        [
            'name' => 'App Token',
            'id'   => $prefix.'app_token',
            'type' => 'text'
        ],
        [
            'name' => 'Firebase User ID',
            'id'   => $prefix.'firebase_id',
            'type' => 'text'
        ],
        [
            'name'    => 'Lock User from Add Listing feature',
            'id'      => $prefix.'locked_addlisting',
            'type'    => 'select',
            'default' => '',
            'options' => [
                ''                => '---',
                'payment_dispute' => 'Payment Dispute',
                'other'           => 'Other'
            ]
        ]
    ])
];
