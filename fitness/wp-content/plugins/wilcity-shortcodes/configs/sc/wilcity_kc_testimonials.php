<?php
return [
  'wilcity_kc_testimonials' => [
    'name'     => 'Testimonials',
    'icon'     => 'sl-paper-plane',
    'css_box'  => true,
    'category' => WILCITY_SC_CATEGORY,
    'params'   => [
      'general' => [
        [
          'name'  => 'icon',
          'label' => 'Icon',
          'type'  => 'icon_picker',
          'value' => 'la la-quote-right'
        ],
        [
          'name'        => 'autoplay',
          'label'       => 'Auto Play',
          'description' => 'Leave empty to disable this feature. Or specify auto-play each x seconds',
          'type'        => 'text',
          'value'       => ''
        ],
        [
          'name'   => 'testimonials',
          'label'  => 'Testimonials',
          'type'   => 'group',
          'value'  => [],
          'params' => [
            [
              'type'  => 'text',
              'label' => 'Customer Name',
              'name'  => 'name'
            ],
            [
              'type'  => 'textarea',
              'label' => 'Testimonial',
              'name'  => 'testimonial'
            ],
            [
              'type'  => 'text',
              'label' => 'Customer Profesional',
              'name'  => 'profesional'
            ],
            [
              'type'  => 'attach_image_url',
              'label' => 'Avatar',
              'name'  => 'avatar'
            ]
          ]
        ]
      ]
    ]
  ]
];
