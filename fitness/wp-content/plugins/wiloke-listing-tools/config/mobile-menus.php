<?php
return [
    'aMainMenu'      => [
        'homeStack'    => [
            'oGeneral' => [
                'class'     => 'fields five',
                'isDefault' => 'yes',
                'key'       => 'homeStack',
                'heading'   => 'Home'
            ],
            'aFields'  => [
                [
                    'adminCategory' => 'wil-input',
                    'isReadOnly'    => 'yes',
                    'name'          => 'screen',
                    'key'           => 'screen',
                    'label'         => 'Screen',
                    'value'         => 'homeStack'
                ],
                [
                    'component'     => 'wiloke-input-read-only',
                    'adminCategory' => 'wil-input',
                    'isReadOnly'    => 'yes',
                    'name'          => 'key', // old
                    'key'           => 'key', // old
                    'label'         => 'Key',
                    'desc'          => 'This key is fixed.',
                    'value'         => 'home'
                ],
                [
                    'component'     => 'wiloke-input',
                    'adminCategory' => 'wil-input',
                    'key'           => 'name',
                    'name'          => 'name',
                    'label'         => 'Name',
                    'value'         => 'Home'
                ],
                [
                    'component'     => 'wiloke-icon',
                    'name'          => 'iconName',
                    'key'           => 'iconName',
                    'adminCategory' => 'wil-icon',
                    'label'         => 'Icon',
                    'value'         => 'home'
                ],
                [
                    'component'     => 'wiloke-select',
                    'adminCategory' => 'wil-select',
                    'name'          => 'status',
                    'key'           => 'status',
                    'value'         => 'enable',
                    'label'         => 'Toggle Menu',
                    'aOptions'      => [
                        [
                            'name'  => 'Disable',
                            'value' => 'disable'
                        ],
                        [
                            'name'  => 'Enable',
                            'value' => 'enable'
                        ]
                    ]
                ]
            ]
        ],
        'pageStack'    => [
	        'oGeneral' => [
		        'class'     => 'fields five',
		        'isDefault' => 'yes',
		        'key'       => 'pageStack',
		        'heading'   => 'Page Stack'
	        ],
	        'aFields'  => [
		        [
			        'adminCategory' => 'wil-input',
			        'isReadOnly'    => 'yes',
			        'name'          => 'screen',
			        'key'           => 'screen',
			        'label'         => 'Screen',
			        'value'         => 'pageStack'
		        ],
		        [
			        'component'     => 'wiloke-ajax-search-field',
			        'adminCategory' => 'wil-input',
			        'isReadOnly'    => 'yes',
			        'name'          => 'key',
			        'key'           => 'key',
			        'value'         => 'page',
			        'label'         => 'Key',
		        ],
		        [
			        'component'     => 'wiloke-ajax-search-field',
			        'adminCategory' => 'wil-ajax-search-field',
			        'name'          => 'id',
			        'key'           => 'id',
			        'value'         => '',
			        'label'         => 'Page Name',
			        'action'        => 'wilcity_get_page_id'
		        ],
		        [
			        'component'     => 'wiloke-input',
			        'adminCategory' => 'wil-input',
			        'name'          => 'name',
			        'key'           => 'name',
			        'value'         => 'Page Stack'
		        ],
		        [
			        'component'     => 'wiloke-icon',
			        'adminCategory' => 'wil-icon',
			        'name'          => 'iconName',
			        'key'           => 'iconName',
			        'label'         => 'Icon',
			        'value'         => 'map-pin'
		        ],
		        [
			        'component'     => 'wiloke-select',
			        'adminCategory' => 'wil-select',
			        'name'          => 'status',
			        'key'           => 'status',
			        'value'         => 'enable',
			        'label'         => 'Toggle Menu',
			        'aOptions'      => [
				        [
					        'name'  => 'Disable',
					        'value' => 'disable'
				        ],
				        [
					        'name'  => 'Enable',
					        'value' => 'enable'
				        ]
			        ]
		        ]
	        ]
        ],
        'accountStack' => [
            'oGeneral' => [
                'class'     => 'fields five',
                'isDefault' => 'yes',
                'key'       => 'accountStack',
                'heading'   => 'Account'
            ],
            'aFields'  => [
                [
                    'adminCategory' => 'wil-input',
                    'isReadOnly'    => 'yes',
                    'name'          => 'screen',
                    'key'           => 'screen',
                    'label'         => 'Screen',
                    'value'         => 'accountStack'
                ],
                [
                    'component'     => 'wiloke-input-read-only',
                    'adminCategory' => 'wil-input',
                    'isReadOnly'    => 'yes',
                    'name'          => 'key',
                    'key'           => 'key',
                    'label'         => 'Key',
                    'desc'          => 'This key is fixed.',
                    'value'         => 'account'
                ],
                [
                    'component'     => 'wiloke-input',
                    'adminCategory' => 'wil-input',
                    'name'          => 'name',
                    'key'           => 'name',
                    'label'         => 'Name',
                    'value'         => 'Account'
                ],
                [
                    'component'     => 'wiloke-icon',
                    'adminCategory' => 'wil-icon',
                    'key'           => 'iconName',
                    'name'          => 'iconName',
                    'label'         => 'Icon',
                    'value'         => 'user'
                ],
                [
                    'component'     => 'wiloke-select',
                    'adminCategory' => 'wil-select',
                    'name'          => 'status',
                    'key'           => 'status',
                    'value'         => 'enable',
                    'label'         => 'Toggle Menu',
                    'aOptions'      => [
                        [
                            'name'  => 'Disable',
                            'value' => 'disable'
                        ],
                        [
                            'name'  => 'Enable',
                            'value' => 'enable'
                        ]
                    ]
                ]
            ]
        ],
        'listingStack' => [
            'oGeneral' => [
                'class'   => 'fields five',
                'isClone' => 'yes',
                'key'     => 'listingStack',
                'heading' => 'Listing'
            ],
            'aFields'  => [
                [
                    'adminCategory' => 'wil-input',
                    'isReadOnly'    => 'yes',
                    'key'           => 'key',
                    'name'          => 'screen',
                    'label'         => 'Screen',
                    'value'         => 'listingStack'
                ],
                [
                    'component'     => 'wiloke-ajax-search-field',
                    'adminCategory' => 'wil-ajax-search-field',
                    'isReadOnly'    => 'yes',
                    'name'          => 'key',
                    'key'           => 'key',
                    'value'         => 'listing',
                    'label'         => 'Directory Key',
                    'action'        => 'wilcity_get_listing_directory_key'
                ],
                [
                    'component'     => 'wiloke-input',
                    'adminCategory' => 'wil-input',
                    'name'          => 'name',
                    'key'           => 'name',
                    'label'         => 'Name',
                    'value'         => 'Listing'
                ],
                [
                    'component'     => 'wiloke-icon',
                    'adminCategory' => 'wil-icon',
                    'name'          => 'iconName',
                    'key'           => 'iconName',
                    'label'         => 'Icon',
                    'value'         => 'map-pin'
                ],
                [
                    'component'     => 'wiloke-select',
                    'adminCategory' => 'wil-select',
                    'name'          => 'status',
                    'key'           => 'status',
                    'value'         => 'enable',
                    'label'         => 'Toggle Menu',
                    'aOptions'      => [
                        [
                            'name'  => 'Disable',
                            'value' => 'disable'
                        ],
                        [
                            'name'  => 'Enable',
                            'value' => 'enable'
                        ]
                    ]
                ]
            ]
        ],
        'eventStack'   => [
            'oGeneral' => [
                'class'   => 'fields five',
                'key'     => 'eventStack',
                'heading' => 'Event'
            ],
            'aFields'  => [
                [
                    'adminCategory' => 'wil-input',
                    'isReadOnly'    => 'yes',
                    'key'           => 'key',
                    'name'          => 'screen',
                    'label'         => 'Screen',
                    'value'         => 'eventStack'
                ],
                [
                    'component'     => 'wiloke-ajax-search-field',
                    'adminCategory' => 'wil-ajax-search-field',
                    'name'          => 'key',
                    'key'           => 'key',
                    'value'         => 'event',
                    'label'         => 'Directory Key',
                    'action'        => 'wilcity_get_event_directory_key'
                ],
                [
                    'component'     => 'wiloke-input',
                    'adminCategory' => 'wil-input',
                    'name'          => 'name',
                    'key'           => 'name',
                    'label'         => 'Name',
                    'value'         => 'Event'
                ],
                [
                    'component'     => 'wiloke-icon',
                    'adminCategory' => 'wil-icon',
                    'name'          => 'iconName',
                    'key'           => 'iconName',
                    'label'         => 'Icon',
                    'value'         => 'calendar'
                ],
                [
                    'component'     => 'wiloke-select',
                    'adminCategory' => 'wil-select',
                    'name'          => 'status',
                    'key'           => 'status',
                    'value'         => 'enable',
                    'label'         => 'Toggle Menu',
                    'aOptions'      => [
                        [
                            'name'  => 'Disable',
                            'value' => 'disable'
                        ],
                        [
                            'name'  => 'Enable',
                            'value' => 'enable'
                        ]
                    ]
                ]
            ]
        ],
        'blogStack'    => [
            'oGeneral' => [
                'class'   => 'fields five',
                'screen'  => 'blogStack',
                'key'     => 'blogStack',
                'heading' => 'Blog Stack'
            ],
            'aFields'  => [
                [
                    'adminCategory' => 'wil-input',
                    'isReadOnly'    => 'yes',
                    'key'           => 'key',
                    'name'          => 'screen',
                    'label'         => 'Screen',
                    'value'         => 'blogStack'
                ],
                [
                    'component'     => 'wiloke-input-read-only',
                    'adminCategory' => 'wil-input',
                    'isReadOnly'    => 'yes',
                    'name'          => 'key',
                    'key'           => 'key',
                    'value'         => 'posts',
                    'label'         => 'Page Name',
                    'action'        => 'wilcity_get_page_id'
                ],
                [
                    'component'     => 'wiloke-input',
                    'adminCategory' => 'wil-input',
                    'name'          => 'name',
                    'key'           => 'name',
                    'label'         => 'Name',
                    'value'         => 'Blog Stack'
                ],
                [
                    'component'     => 'wiloke-icon',
                    'adminCategory' => 'wil-icon',
                    'name'          => 'iconName',
                    'key'           => 'iconName',
                    'label'         => 'Icon',
                    'value'         => 'map-pin'
                ],
                [
                    'component'     => 'wiloke-select',
                    'adminCategory' => 'wil-select',
                    'name'          => 'status',
                    'key'           => 'status',
                    'value'         => 'enable',
                    'label'         => 'Toggle Menu',
                    'aOptions'      => [
                        [
                            'name'  => 'Disable',
                            'value' => 'disable'
                        ],
                        [
                            'name'  => 'Enable',
                            'value' => 'enable'
                        ]
                    ]
                ]
            ]
        ],
        'menuStack'    => [
            'oGeneral' => [
                'class'   => 'fields five',
                'key'     => 'menuStack',
                'heading' => 'Secondary Menu Stack'
            ],
            'aFields'  => [
                [
                    'adminCategory' => 'wil-input',
                    'isReadOnly'    => 'yes',
                    'name'          => 'screen',
                    'key'           => 'key',
                    'label'         => 'Screen',
                    'value'         => 'menuStack'
                ],
                [
                    'component'     => 'wiloke-input-read-only',
                    'adminCategory' => 'wil-input',
                    'isReadOnly'    => 'yes',
                    'name'          => 'key',
                    'key'           => 'key',
                    'label'         => 'Key',
                    'desc'          => 'This key is fixed.',
                    'value'         => 'menu'
                ],
                [
                    'component'     => 'wiloke-input',
                    'adminCategory' => 'wil-input',
                    'name'          => 'name',
                    'key'           => 'name',
                    'label'         => 'Name',
                    'value'         => 'Menu'
                ],
                [
                    'component'     => 'wiloke-icon',
                    'adminCategory' => 'wil-icon',
                    'name'          => 'iconName',
                    'key'           => 'iconName',
                    'label'         => 'Icon',
                    'value'         => 'la la-bars'
                ],
                [
                    'component'     => 'wiloke-select',
                    'adminCategory' => 'wil-select',
                    'name'          => 'status',
                    'key'           => 'status',
                    'value'         => 'enable',
                    'label'         => 'Toggle Menu',
                    'aOptions'      => [
                        [
                            'name'  => 'Disable',
                            'value' => 'disable'
                        ],
                        [
                            'name'  => 'Enable',
                            'value' => 'enable'
                        ]
                    ]
                ]
            ]
        ]
    ],
    'aSecondaryMenu' => [
        'homeStack'    => [
            'oGeneral' => [
                'class'   => 'fields five',
                'screen'  => 'homeStack',
                'heading' => 'Home Stack'
            ],
            'aFields'  => [
                [
                    'adminCategory' => 'wil-input',
                    'isReadOnly'    => 'yes',
                    'key'           => 'screen',
                    'name'          => 'screen',
                    'label'         => 'Screen',
                    'value'         => 'homeStack'
                ],
                [
                    'component'     => 'wiloke-input-read-only',
                    'adminCategory' => 'wil-input',
                    'isReadOnly'    => 'yes',
                    'key'           => 'key',
                    'name'          => 'key',
                    'label'         => 'Key',
                    'desc'          => 'This key is fixed.',
                    'value'         => 'key'
                ],
                [
                    'component'     => 'wiloke-input',
                    'adminCategory' => 'wil-input',
                    'label'         => 'Name',
                    'key'           => 'name',
                    'name'          => 'name',
                    'value'         => 'Home'
                ],
                [
                    'component'     => 'wiloke-icon',
                    'adminCategory' => 'wil-icon',
                    'key'           => 'iconName',
                    'name'          => 'iconName',
                    'label'         => 'Icon',
                    'value'         => 'home'
                ],
                [
                    'component'     => 'wiloke-select',
                    'adminCategory' => 'wil-select',
                    'key'           => 'status',
                    'name'          => 'status',
                    'value'         => 'enable',
                    'label'         => 'Toggle Menu',
                    'aOptions'      => [
                        [
                            'name'  => 'Disable',
                            'value' => 'disable'
                        ],
                        [
                            'name'  => 'Enable',
                            'value' => 'enable'
                        ]
                    ]
                ]
            ]
        ],
        'listingStack' => [
            'oGeneral' => [
                'class'   => 'fields five',
                'screen'  => 'listingStack',
                'heading' => 'Listing Stack'
            ],
            'aFields'  => [
                [
                    'adminCategory' => 'wil-input',
                    'isReadOnly'    => 'yes',
                    'key'           => 'screen',
                    'name'          => 'screen',
                    'label'         => 'Screen',
                    'value'         => 'listingStack'
                ],
                [
                    'component'     => 'wiloke-ajax-search-field',
                    'adminCategory' => 'wil-ajax-search-field',
                    'key'           => 'key',
                    'name'          => 'key',
                    'value'         => 'listing',
                    'label'         => 'Directory Key',
                    'action'        => 'wilcity_get_listing_directory_key'
                ],
                [
                    'component'     => 'wiloke-input',
                    'adminCategory' => 'wil-input',
                    'name'          => 'name',
                    'key'           => 'name',
                    'value'         => 'Listing'
                ],
                [
                    'component'     => 'wiloke-icon',
                    'adminCategory' => 'wil-icon',
                    'key'           => 'iconName',
                    'name'          => 'iconName',
                    'label'         => 'Icon',
                    'value'         => 'map-pin'
                ],
                [
                    'component'     => 'wiloke-select',
                    'adminCategory' => 'wil-select',
                    'name'          => 'status',
                    'key'           => 'status',
                    'value'         => 'enable',
                    'label'         => 'Toggle Menu',
                    'aOptions'      => [
                        [
                            'name'  => 'Disable',
                            'value' => 'disable'
                        ],
                        [
                            'name'  => 'Enable',
                            'value' => 'enable'
                        ]
                    ]
                ]
            ]
        ],
        'eventStack'   => [
            'oGeneral' => [
                'class'   => 'fields five',
                'screen'  => 'eventStack',
                'heading' => 'Event Stack'
            ],
            'aFields'  => [
                [
                    'adminCategory' => 'wil-input',
                    'isReadOnly'    => 'yes',
                    'name'          => 'screen',
                    'key'           => 'screen',
                    'label'         => 'Screen',
                    'value'         => 'eventStack'
                ],
                [
                    'component'     => 'wiloke-input-read-only',
                    'adminCategory' => 'wil-input',
                    'isReadOnly'    => 'yes',
                    'name'          => 'key',
                    'key'           => 'key',
                    'value'         => 'event',
                    'label'         => 'Event Key',
                    'desc'          => 'This key is fixed'
                ],
                [
                    'component'     => 'wiloke-input',
                    'adminCategory' => 'wil-input',
                    'name'          => 'name',
                    'key'           => 'name',
                    'value'         => 'Event'
                ],
                [
                    'component'     => 'wiloke-icon',
                    'adminCategory' => 'wil-icon',
                    'name'          => 'iconName',
                    'key'           => 'iconName',
                    'label'         => 'Icon',
                    'value'         => 'calendar'
                ],
                [
                    'component'     => 'wiloke-select',
                    'adminCategory' => 'wil-select',
                    'name'          => 'status',
                    'key'           => 'status',
                    'value'         => 'enable',
                    'label'         => 'Toggle Menu',
                    'aOptions'      => [
                        [
                            'name'  => 'Disable',
                            'value' => 'disable'
                        ],
                        [
                            'name'  => 'Enable',
                            'value' => 'enable'
                        ]
                    ]
                ]
            ]
        ],
        'pageStack'    => [
            'oGeneral' => [
                'class'   => 'fields five',
                'screen'  => 'pageStack',
                'heading' => 'Page Stack'
            ],
            'aFields'  => [
                [
                    'adminCategory' => 'wil-input',
                    'isReadOnly'    => 'yes',
                    'name'          => 'screen',
                    'key'           => 'screen',
                    'label'         => 'Screen',
                    'value'         => 'pageStack'
                ],
                [
                    'component'     => 'wiloke-ajax-search-field',
                    'adminCategory' => 'wil-ajax-search-field',
                    'name'          => 'id',
                    'key'           => 'id',
                    'value'         => '',
                    'label'         => 'Page Name',
                    'action'        => 'wilcity_get_page_id'
                ],
                [
                    'component'     => 'wiloke-input',
                    'adminCategory' => 'wil-input',
                    'name'          => 'name',
                    'key'           => 'name',
                    'value'         => 'Page Stack'
                ],
                [
                    'component'     => 'wiloke-icon',
                    'adminCategory' => 'wil-icon',
                    'name'          => 'iconName',
                    'key'           => 'iconName',
                    'label'         => 'Icon',
                    'value'         => 'map-pin'
                ],
                [
                    'component'     => 'wiloke-select',
                    'adminCategory' => 'wil-select',
                    'name'          => 'status',
                    'key'           => 'status',
                    'value'         => 'enable',
                    'label'         => 'Toggle Menu',
                    'aOptions'      => [
                        [
                            'name'  => 'Disable',
                            'value' => 'disable'
                        ],
                        [
                            'name'  => 'Enable',
                            'value' => 'enable'
                        ]
                    ]
                ]
            ]
        ],
        'blogStack'    => [
            'oGeneral' => [
                'class'   => 'fields five',
                'screen'  => 'blogStack',
                'heading' => 'Blog Stack'
            ],
            'aFields'  => [
                [
                    'adminCategory' => 'wil-input',
                    'isReadOnly'    => 'yes',
                    'name'          => 'screen',
                    'key'           => 'screen',
                    'label'         => 'Screen',
                    'value'         => 'blogStack'
                ],
                [
                    'component'     => 'wiloke-input-read-only',
                    'adminCategory' => 'wil-input',
                    'name'          => 'key',
                    'key'           => 'key',
                    'value'         => 'posts',
                    'label'         => 'Page Name',
                    'action'        => 'wilcity_get_page_id'
                ],
                [
                    'component'     => 'wiloke-input',
                    'adminCategory' => 'wil-input',
                    'name'          => 'name',
                    'key'           => 'name',
                    'value'         => 'Blog Stack'
                ],
                [
                    'component'     => 'wiloke-icon',
                    'adminCategory' => 'wil-icon',
                    'name'          => 'iconName',
                    'key'           => 'iconName',
                    'label'         => 'Icon',
                    'value'         => 'map-pin'
                ],
                [
                    'component'     => 'wiloke-select',
                    'adminCategory' => 'wil-select',
                    'name'          => 'status',
                    'key'           => 'status',
                    'value'         => 'enable',
                    'label'         => 'Toggle Menu',
                    'aOptions'      => [
                        [
                            'name'  => 'Disable',
                            'value' => 'disable'
                        ],
                        [
                            'name'  => 'Enable',
                            'value' => 'enable'
                        ]
                    ]
                ]
            ]
        ],
    ]
];
