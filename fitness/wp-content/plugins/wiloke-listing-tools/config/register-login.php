<?php
return [
    'registerFormFields' => apply_filters('wilcity/wiloke-listing-tools/filter/configs/register-login', [
        'register' => [
            [
                'type'        => 'wil-input',
                'label'       => esc_html__('Username', 'wiloke-listing-tools'),
                'translation' => 'username',
                'name'        => 'user_login',
                'isRequired'  => 'yes'
            ],
            [
                'type'        => 'wil-input',
                'inputType'   => 'email',
                'label'       => esc_html__('Email', 'wiloke-listing-tools'),
                'translation' => 'email',
                'name'        => 'user_email',
                'isRequired'  => 'yes'
            ],
            [
                'type'        => 'wil-input',
                'inputType'   => 'password',
                'label'       => esc_html__('Password', 'wiloke-listing-tools'),
                'translation' => 'password',
                'name'        => 'user_password',
                'isRequired'  => 'yes'
            ]
        ],
        'login'    => [
            [
                'type'        => 'wil-input',
                'label'       => esc_html__('Username/Email', 'wiloke-listing-tools'),
                'translation' => 'usernameOrEmail',
                'name'        => 'user_login',
                'isRequired'  => 'yes'
            ],
            [
                'type'        => 'wil-input',
                'inputType'   => 'password',
                'label'       => esc_html__('Password', 'wiloke-listing-tools'),
                'translation' => 'password',
                'name'        => 'user_password',
                'isRequired'  => 'yes'
            ]
        ]
    ])
];
