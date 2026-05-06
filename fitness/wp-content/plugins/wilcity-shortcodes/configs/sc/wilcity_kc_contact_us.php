<?php
return [
  'wilcity_kc_contact_us' => [
    'name'     => 'Contact Us',
    'icon'     => 'sl-paper-plane',
    'css_box'  => true,
    'category' => WILCITY_SC_CATEGORY,
    'params'   => [
      'general'      => [
        [
          'name'  => 'contact_info_heading',
          'label' => 'Heading',
          'type'  => 'text',
          'value' => 'Contact Info'
        ],
        [
          'name'   => 'contact_info',
          'label'  => 'Contact Info',
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
              'label' => 'Info',
              'name'  => 'info'
            ],
            [
              'type'        => 'text',
              'label'       => 'link',
              'description' => 'Enter in # if it is not a real link.',
              'name'        => 'link'
            ],
            [
              'type'    => 'select',
              'label'   => 'Type',
              'name'    => 'type',
              'value'   => 'default',
              'options' => [
                'default' => 'Default',
                'phone'   => 'Phone',
                'mail'    => 'mail'
              ]
            ],
            [
              'type'        => 'select',
              'label'       => 'Open Type',
              'description' => 'After clicking on this link, it will be opened in',
              'name'        => 'target',
              'value'       => '_self',
              'options'     => [
                '_self'  => 'Self page',
                '_blank' => 'New Window'
              ]
            ]
          ]
        ]
      ],
      'Contact Form' => [
        [
          'name'  => 'contact_form_heading',
          'label' => 'Heading',
          'type'  => 'text',
          'value' => 'Contact Us'
        ],
        [
          'type'    => 'autocomplete',
          'name'    => 'contact_form_7',
          'label'   => 'Contact Form 7',
          'options' => [
            'post_type' => 'wpcf7_contact_form',
          ]
        ],
        [
          'type'        => 'textarea',
          'name'        => 'contact_form_shortcode',
          'label'       => 'Contact Form Shortcode',
          'description' => 'If you are using another contact form plugin, please enter its own shortcode here.'
        ]
      ]
    ]
  ]
];
