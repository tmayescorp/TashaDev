<?php
return [
  'wilcity_kc_custom_login' => [
    'name'     => 'Custom Login',
    'icon'     => 'sl-paper-plane',
    'css_box'  => true,
    'category' => WILCITY_SC_CATEGORY,
    'params'   => [
      'general'       => [
        [
          'name'  => 'login_section_title',
          'label' => 'Login Title',
          'type'  => 'text',
          'value' => 'Welcome back, please login to your account'
        ],
        [
          'name'  => 'register_section_title',
          'label' => 'Register Title',
          'type'  => 'text',
          'value' => 'Create an account! It\'s free and always will be.'
        ],
        [
          'name'  => 'rp_section_title',
          'label' => 'Reset Password Title',
          'type'  => 'text',
          'value' => 'Find Your Account'
        ],
        [
          'name'    => 'social_login_type',
          'label'   => 'Social Login',
          'type'    => 'select',
          'options' => [
            'fb_default'       => 'Using Facebook Login as Default',
            'custom_shortcode' => 'Inserting External Shortcode',
            'off'              => 'I do not want to use this feature'
          ],
          'value'   => 'fb_default'
        ],
        [
          'name'     => 'social_login_shortcode',
          'label'    => 'Social Login Shortcode',
          'type'     => 'textarea',
          'relation' => [
            'parent'    => 'social_login_type',
            'show_when' => 'custom_shortcode'
          ],
          'value'    => ''
        ]
      ],
      'intro_section' => [
        [
          'name'  => 'login_bg_img',
          'label' => 'Background Image',
          'type'  => 'attach_image_url',
          'value' => ''
        ],
        [
          'name'  => 'login_bg_color',
          'label' => 'Background Color',
          'type'  => 'color_picker',
          'value' => 'rgba(216, 35, 112, 0.1)'
        ],
        [
          'name'   => 'login_boxes',
          'label'  => 'Intro Box',
          'type'   => 'group',
          'value'  => '',
          'params' => [
            [
              'type'  => 'icon_picker',
              'label' => 'Icon',
              'name'  => 'icon'
            ],
            [
              'type'  => 'textarea',
              'label' => 'Description',
              'name'  => 'description'
            ],
            [
              'type'  => 'color_picker',
              'label' => 'Icon Color',
              'name'  => 'icon_color',
              'value' => '#fff'
            ],
            [
              'type'  => 'color_picker',
              'label' => 'Text Color',
              'name'  => 'text_color',
              'value' => '#fff'
            ]
          ]
        ]
      ]
    ]
  ]
];
