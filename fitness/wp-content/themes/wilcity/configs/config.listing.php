<?php
global $post;
$aButtons = wilcityGetConfig('btn');
return [
    'singleListingNavigation'       => apply_filters(
        'wilcity/filter/single-listing/navigation',
        [
            [
                'type'           => 'li',
                'wrapperClasses' => 'list_item__3YghP',
                'children'       => [
                    [
                        'type'    => 'wilSwitchTabBtn',
                        'icon'    => 'la la-home',
                        'btnName' => esc_html__('Home', 'wilcity'),
                        'tabKey'  => 'home'
                    ]
                ]
            ]
        ]
    ),
    'singleListingRightTopToolsBtn' => apply_filters(
        'wilcity/single-listing/right-top-tools/buttons',
        [
            'wilHaveBeenThereBtn' => [
                'type'           => 'div',
                'wrapperClasses' => 'listing-detail_rightItem__2CjTS wilcity-single-tool-havebeenthere',
                'conditionals'   => [
                    [
                        [
                            'WilokeListingTools\Models\HaveBeenThereModel',
                            'isEnabled'
                        ]
                    ],
                    'relation' => 'AND'
                ],
                'children'       => [
                    [
                        'type'           => 'wilHaveBeenThereBtn',
                        'wrapperClasses' => 'wil-btn wil-btn--round wil-btn--sm',
                        'icon'           => 'la la-map-pin',
                        'btnName'        => esc_html__('Checkin', 'wilcity')
                    ]
                ]
            ],
            'wilFavoriteBtn'      => [
                'type'           => 'div',
                'wrapperClasses' => 'listing-detail_rightItem__2CjTS wilcity-single-tool-favorite',
                'children'       => [
                    [
                        'type'           => 'wilFavoriteBtn',
                        'wrapperClasses' => 'wil-btn wil-btn--border wil-btn--round wil-btn--sm',
                        'icon'           => 'la la-heart-o',
                        'btnName'        => esc_attr__('Favorite', 'wilcity')
                    ]
                ]
            ],
            'wilReportBtn'        => [
                'type'           => 'div',
                'wrapperClasses' => 'listing-detail_rightItem__2CjTS wilcity-single-tool-report',
                'conditional'    => [['WilokeListingTools\Controllers\ReportController', 'isAllowReport']],
                'children'       => [
                    [
                        'type'           => 'wilReportBtn',
                        'icon'           => 'color-tertiary la la-flag-o',
                        'wrapperClasses' => 'wil-btn wil-btn--border wil-btn--round wil-btn--sm',
                        'btnName'        => esc_attr__('Report', 'wilcity')
                    ]
                ]
            ],
            'wilMessageBtn'       => [
                'type'           => 'div',
                'wrapperClasses' => 'listing-detail_rightItem__2CjTS wilcity-single-tool-inbox',
                'conditional'    => [['\WilokeListingTools\Models\PostModel', 'isClaimed']],
                'children'       => [
                    [
                        'type'           => 'wilMessageBtn',
                        'wrapperClasses' => 'wil-btn wil-btn--border wil-btn--round wil-btn--sm',
                        'btnName'        => esc_attr__('Inbox', 'wilcity')
                    ]
                ]
            ],
            'wilSocialSharingBtn' => $aButtons['wilSocialSharingBtn']
        ]
    ),
    'singleListingDropdownBtn'      => apply_filters(
        'wilcity/filter/configs/drop-down-btn',
        [ // The dropdown button on the right of single listing
          'wilReviewBtn' => [
              'type'           => 'li',
              'wrapperClasses' => 'list_item__3YghP wilcity-menu-add-review',
              'conditionals'   => [
                  [
                      [
                          'WilokeListingTools\Models\ReviewModel',
                          'isEnabledReview'
                      ] // the second args is param
                  ],
                  [
                      [
                          'WilokeListingTools\Models\ReviewModel',
                          'isUserReviewed'
                      ]
                  ],
                  'relation' => 'AND'
              ],
              'children'       => [
                  [
                      'type'              => 'wilReviewBtn',
                      'hasWrapperForIcon' => 'yes',
                      'icon'              => 'la la-star-o',
                      'btnName'           => esc_attr__('Write a review', 'wilcity')
                  ]
              ]
          ],
          'wilReportBtn' => [
              'type'           => 'li',
              'wrapperClasses' => 'list_item__3YghP wilcity-menu-report',
              'children'       => [
                  [
                      'type'              => 'wilReportBtn',
                      'hasWrapperForIcon' => 'yes',
                      'icon'              => 'la la-flag-o',
                      'btnName'           => esc_attr__('Report', 'wilcity')
                  ]
              ]
          ]
        ]
    )
];
