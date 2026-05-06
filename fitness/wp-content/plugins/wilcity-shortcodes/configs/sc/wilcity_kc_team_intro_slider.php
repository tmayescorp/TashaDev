<?php
return [
  'wilcity_kc_team_intro_slider' => [
    'name'     => 'Team Intro Slider',
    'icon'     => 'sl-paper-plane',
    'css_box'  => true,
    'category' => WILCITY_SC_CATEGORY,
    'params'   => [
      'general' => [
        [
          'name'    => 'get_by',
          'label'   => 'Get users who are',
          'type'    => 'select',
          'value'   => 'administrator',
          'options' => [
            'administrator' => 'Administrator',
            'editor'        => 'Editor',
            'contributor'   => 'Contributor',
            'custom'        => 'Custom',
          ]
        ],
        [
          'name'        => 'members',
          'label'       => 'Members',
          'type'        => 'group',
          'description' => 'Eg: facebook:https://facebook.com,google-plus:https://googleplus.com',
          'params'      => [
            [
              'type'  => 'attach_image_url',
              'label' => 'Avatar',
              'name'  => 'avatar'
            ],
            [
              'type'  => 'attach_image_url',
              'label' => 'Picture',
              'name'  => 'picture'
            ],
            [
              'type'  => 'text',
              'label' => 'Name',
              'name'  => 'display_name'
            ],
            [
              'type'  => 'text',
              'label' => 'Position',
              'name'  => 'position'
            ],
            [
              'type'  => 'textarea',
              'label' => 'Intro',
              'name'  => 'intro'
            ],
            [
              'name'  => 'social_networks',
              'label' => 'Social Networks',
              'type'  => 'textarea'
            ]
          ]
        ]
      ]
    ]
  ]
];
