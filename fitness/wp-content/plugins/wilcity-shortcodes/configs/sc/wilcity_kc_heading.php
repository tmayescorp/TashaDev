<?php
return [
  'wilcity_kc_heading' => [
    'name'     => 'Heading',
    'icon'     => 'sl-paper-plane',
    'css_box'  => true,
    'category' => WILCITY_SC_CATEGORY,
    'params'   => [
      'general' => [
        'blur_mark',
        'blur_mark_color',
        'heading',
        'heading_color',
        'desc',
        'desc_color',
        'header_desc_text_align',
        [
          'type'    => 'select',
          'name'    => 'alignment',
          'label'   => 'Alignment',
          'value'   => 'wil-text-center',
          'options' => [
            'wil-text-center' => 'Center',
            'wil-text-right'  => 'Right',
            'wil-text-left'   => 'Left'
          ]
        ]
      ]
    ]
  ]
];
