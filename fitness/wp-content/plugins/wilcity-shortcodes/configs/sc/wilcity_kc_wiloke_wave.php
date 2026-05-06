<?php
return [
  'wilcity_kc_wiloke_wave' => [
    'name'     => 'Wiloke Wave',
    'icon'     => 'sl-paper-plane',
    'css_box'  => true,
    'category' => WILCITY_SC_CATEGORY,
    'params'   => [
      'general' => [
        [
          'name'        => 'heading',
          'label'       => 'Heading',
          'type'        => 'text',
          'admin_label' => true
        ],
        [
          'name'        => 'description',
          'label'       => 'Description',
          'type'        => 'textarea',
          'value'       => '',
          'admin_label' => true
        ],
        [
          'type'  => 'color_picker',
          'name'  => 'left_gradient_color',
          'label' => 'Left Gradient Color',
          'value' => '#f06292',
        ],
        [
          'type'  => 'color_picker',
          'name'  => 'right_gradient_color',
          'label' => 'Right Gradient Color',
          'value' => '#f97f5f'
        ],
        [
          'name'   => 'btn_group',
          'label'  => 'Buttons Group',
          'type'   => 'group',
          'value'  => '',
          'params' => [
            [
              'type'  => 'icon_picker',
              'label' => 'Icon',
              'name'  => 'icon'
            ],
            [
              'type'  => 'text',
              'label' => 'Button name',
              'name'  => 'name'
            ],
            [
              'type'  => 'text',
              'label' => 'Button URL',
              'name'  => 'url'
            ],
            [
              'type'    => 'select',
              'label'   => 'Open Type',
              'name'    => 'open_type',
              'options' => [
                '_self'  => 'In the same window',
                '_blank' => 'In a New Window'
              ]
            ]
          ]
        ],
      ]
    ]
  ]
];
