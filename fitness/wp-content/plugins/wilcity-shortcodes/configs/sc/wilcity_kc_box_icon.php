<?php
return [
  'wilcity_kc_box_icon' => [
    'name'     => 'Box Icon',
    'icon'     => 'sl-paper-plane',
    'css_box'  => true,
    'category' => WILCITY_SC_CATEGORY,
    'params'   => [
      'general' => [
        [
          'name'        => 'icon',
          'label'       => 'Icon',
          'type'        => 'icon_picker',
          'value'       => '',
          'admin_label' => true
        ],
        [
          'name'        => 'heading',
          'label'       => 'Heading',
          'type'        => 'text',
          'value'       => '',
          'admin_label' => true
        ],
        [
          'name'  => 'description',
          'label' => 'Description',
          'type'  => 'textarea',
          'value' => ''
        ],
      ]
    ]
  ]
];
