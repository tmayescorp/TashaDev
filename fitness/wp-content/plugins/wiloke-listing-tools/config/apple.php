<?php
return [
    'script' => 'https://appleid.cdn-apple.com/appleauth/static/jsapi/appleid/1/en_US/appleid.auth.js',
    'login'  => [
        'fields' => [
            [
                'id'     => 'general_apple_login',
                'title'  => 'Apple Login',
                'type'   => 'section',
                'indent' => true
            ],
            [
                'id'      => 'general_apple_login',
                'type'    => 'select',
                'title'   => 'Toggle Apple Login',
                'default' => 'disable',
                'options' => [
                    'enable'  => 'Enable',
                    'disable' => 'Disable'
                ]
            ],
            [
                'id'          => 'apple_client_id',
                'type'        => 'text',
                'title'       => 'Client ID',
                'description' => ''
            ],
            [
                'id'          => 'apple_client_secret',
                'type'        => 'password',
                'title'       => 'Client Secret',
                'description' => ''
            ],
//            [
//                'id'          => 'apple_login_scope',
//                'type'        => 'text',
//                'title'       => 'Scope',
//                'default'     => 'email',
//                'description' => ''
//            ],
//            [
//                'id'          => 'apple_login_redirect_uri',
//                'type'        => 'text',
//                'title'       => 'Redirect URI',
//                'description' => ''
//            ],
//            [
//                'id'          => 'apple_login_state',
//                'type'        => 'text',
//                'title'       => 'State',
//                'description' => ''
//            ]
        ]
    ]
];
