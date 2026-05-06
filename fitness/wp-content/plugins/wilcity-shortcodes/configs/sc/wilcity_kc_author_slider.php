<?php
return [
  'wilcity_kc_author_slider' => [
    'name'     => 'Author Slider',
    'icon'     => 'sl-paper-plane',
    'css_box'  => true,
    'category' => WILCITY_SC_CATEGORY,
    'params'   => [
      'general' => [
        [
          'name'        => 'role__in',
          'label'       => 'Role in',
          'description' => 'Limit the returned users that have one of the specified roles',
          'type'        => 'multiple',
          'is_multiple' => true,
          'value'       => 'administrator,contributor',
          'options'     => [
            'administrator' => 'Administrator',
            'editor'        => 'Editor',
            'contributor'   => 'Contributor',
            'subscriber'    => 'Subscriber',
            'seller'        => 'Vendor',
            'author'        => 'Author'
          ]
        ],
        [
          'name'    => 'orderby',
          'label'   => 'Order by',
          'type'    => 'select',
          'value'   => 'post_count',
          'options' => [
            'registered' => 'Registered',
            'post_count' => 'Post Count',
            'ID'         => 'ID'
          ]
        ],
        [
          'name'  => 'number',
          'label' => 'Maximum Users',
          'type'  => 'text',
          'value' => 8
        ]
      ]
    ]
  ]
];
