<?php
return [
  'wilcity_kc_intro_box' => [
    'name'     => 'Intro Box',
    'icon'     => 'sl-paper-plane',
    'css_box'  => true,
    'category' => WILCITY_SC_CATEGORY,
    'params'   => [
      'general' => [
        [
          'name'  => 'bg_img',
          'label' => 'Background Image',
          'type'  => 'attach_image_url',
          'value' => ''
        ],
        [
          'name'  => 'video_intro',
          'label' => 'Video Intro',
          'type'  => 'text',
          'value' => ''
        ],
        [
          'name'  => 'intro',
          'label' => 'Intro',
          'type'  => 'editor',
          'value' => ''
        ]
      ]
    ]
  ]
];
