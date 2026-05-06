<?php
if (defined('KC_PATH')) {
    $live_tmpl = KC_PATH . KDS . 'shortcodes' . KDS . 'live_editor' . KDS;
} else {
    $live_tmpl = '';
}

return [
    'kc_tabs' => [
        'name' => 'Tabs - Sliders',
        'description' => 'Tabbed or Sliders content',
        'category' => 'Content',
        'icon' => 'kc-icon-tabs',
        'title' => 'Tabs - Sliders Settings',
        'is_container' => true,
        'views' => [
            'type' => 'views_sections',
            'sections' => 'kc_tab'
        ],
        'priority' => 120,
        'live_editor' => $live_tmpl . 'kc_tabs.php',
        'params' => [
            'general' => [
                [
                    'name' => 'type',
                    'label' => 'How Display',
                    'type' => 'select',
                    'options' => [
                        'horizontal_tabs' => 'Horizontal Tabs',
                        'vertical_tabs' => 'Vertical Tabs',
                        'slider_tabs' => 'Owl Sliders'
                    ],
                    'description' => 'Use sidebar view of your tabs as horizontal, vertical or slider.',
                    'value' => 'horizontal_tabs'
                ],
                [
                    'name' => 'title_slider',
                    'label' => 'Display Titles?',
                    'type' => 'toggle',
                    'relation' => [
                        'parent' => 'type',
                        'show_when' => 'slider_tabs'
                    ],
                    'description' => 'Display tabs title above of the slider',
                ],
                [
                    'name' => 'items',
                    'label' => 'Number Items?',
                    'type' => 'number_slider',
                    'options' => [
                        'min' => 1,
                        'max' => 10,
                        'show_input' => true
                    ],
                    'relation' => [
                        'parent' => 'type',
                        'show_when' => 'slider_tabs'
                    ],
                    'description' => 'Display number of items per each slide (Desktop Screen)'
                ],
                [
                    'name' => 'tablet',
                    'label' => 'Items on tablet?',
                    'type' => 'number_slider',
                    'options' => [
                        'min' => 1,
                        'max' => 6,
                        'show_input' => true
                    ],
                    'relation' => [
                        'parent' => 'type',
                        'show_when' => 'slider_tabs'
                    ],
                    'description' => 'Display number of items per each slide (Tablet Screen)'
                ],
                [
                    'name' => 'mobile',
                    'label' => 'Items on smartphone?',
                    'type' => 'number_slider',
                    'options' => [
                        'min' => 1,
                        'max' => 4,
                        'show_input' => true
                    ],
                    'relation' => [
                        'parent' => 'type',
                        'show_when' => 'slider_tabs'
                    ],
                    'description' => 'Display number of items per each slide (Smartphone Screen)'
                ],
                [
                    'name' => 'speed',
                    'label' => 'Speed of slider',
                    'type' => 'number_slider',
                    'options' => [
                        'min' => 100,
                        'max' => 1000,
                        'show_input' => true
                    ],
                    'value' => 450,
                    'relation' => [
                        'parent' => 'type',
                        'show_when' => 'slider_tabs'
                    ],
                    'description' => 'The speed of sliders in millisecond'
                ],
                [
                    'name' => 'navigation',
                    'label' => 'Navigation',
                    'type' => 'toggle',
                    'relation' => [
                        'parent' => 'type',
                        'show_when' => 'slider_tabs'
                    ],
                    'description' => 'Display the "Next" and "Prev" buttons.'
                ],
                [
                    'name' => 'pagination',
                    'label' => 'Pagination',
                    'type' => 'toggle',
                    'relation' => [
                        'parent' => 'type',
                        'show_when' => 'slider_tabs'
                    ],
                    'value' => 'yes',
                    'description' => 'Show the pagination.',
                ],
                [
                    'name' => 'autoplay',
                    'label' => 'Auto Play',
                    'type' => 'toggle',
                    'relation' => [
                        'parent' => 'type',
                        'show_when' => 'slider_tabs'
                    ],
                    'description' => 'The slider automatically plays when site loaded'
                ],
                [
                    'name' => 'autoheight',
                    'label' => 'Auto Height',
                    'type' => 'toggle',
                    'relation' => [
                        'parent' => 'type',
                        'show_when' => 'slider_tabs'
                    ],
                    'description' => 'The slider height will change automatically'
                ],
                [
                    'name' => 'effect_option',
                    'label' => 'Enable fadein effect?',
                    'type' => 'toggle',
                    'relation' => [
                        'parent' => 'type',
                        'hide_when' => 'slider_tabs'
                    ],
                    'description' => 'Quickly apply fade in and face out effect when users click on tab.'
                ],
                [
                    'name' => 'tabs_position',
                    'label' => 'Position',
                    'type' => 'select',
                    'options' => [
                        'wil-text-left' => 'Left',
                        'wil-text-center' => 'Center',
                        'wil-text-right' => 'Right'
                    ],
                    'relation' => [
                        'parent' => 'type',
                        'show_when' => ['horizontal_tabs', 'vertical_tabs']
                    ]
                ],
                [
                    'name' => 'nav_item_style',
                    'label' => 'Nav Item Style',
                    'description' => 'The position of the tab name and icon',
                    'type' => 'select',
                    'options' => [
                        '' => 'Horizontal',
                        'wilTab_iconLg__2Ibz5' => 'Vertical '
                    ],
                    'relation' => [
                        'parent' => 'type',
                        'show_when' => ['horizontal_tabs', 'vertical_tabs']
                    ],
                    'value' => ''
                ],
                [
                    'name' => 'open_mouseover',
                    'label' => 'Open on mouseover',
                    'type' => 'toggle',
                    'relation' => [
                        'parent' => 'type',
                        'hide_when' => 'slider_tabs'
                    ],
                ],
                [
                    'name' => 'class',
                    'label' => 'Extra Class',
                    'type' => 'text'
                ]
            ],
            'styling' => [
                [
                    'name' => 'css_custom',
                    'type' => 'css',
                    'options' => [
                        [
                            "screens" => "any,1024,999,767,479",
                            'Tab' => [
                                [
                                    'property' => 'font-family,font-size,line-height,font-weight,text-transform,text-align',
                                    'label' => 'Font family',
                                    'selector' => '.kc_tabs_nav, .kc_tabs_nav > li a,+.kc_vertical_tabs>.kc_wrapper>ul.ui-tabs-nav>li>a'
                                ],
                                [
                                    'property' => 'font-size,color,padding',
                                    'label' => 'Icon Size,Icon Color,Icon Spacing',
                                    'selector' => '.kc_tabs_nav a i,+.kc_vertical_tabs>.kc_wrapper>ul.ui-tabs-nav>li>a i'
                                ],
                                [
                                    'property' => 'color',
                                    'label' => 'Text Color',
                                    'selector' => '.kc_tabs_nav a, .kc_tabs_nav,+.kc_vertical_tabs>.kc_wrapper>ul.ui-tabs-nav>li>a'
                                ],
                                [
                                    'property' => 'background-color',
                                    'label' => 'Background Color',
                                    'selector' => '.kc_tabs_nav,+.kc_vertical_tabs>.kc_wrapper>ul.ui-tabs-nav'
                                ],
                                [
                                    'property' => 'background-color',
                                    'label' => 'Background Color tab item',
                                    'selector' => '.kc_tabs_nav li,+.kc_vertical_tabs>.kc_wrapper>ul.ui-tabs-nav li'
                                ],
                                [
                                    'property' => 'border',
                                    'label' => 'Border',
                                    'selector' => '.kc_tabs_nav > li, .kc_tab.ui-tabs-body-active, .kc_tabs_nav,+.kc_vertical_tabs>.kc_wrapper>ul.ui-tabs-nav>li,+.kc_vertical_tabs>.kc_wrapper>ul.ui-tabs-nav ~ div.kc_tab.ui-tabs-body-active,+.kc_vertical_tabs.tabs_right>.kc_wrapper>ul.ui-tabs-nav ~ div.kc_tab'
                                ],
                                [
                                    'property' => 'border-radius',
                                    'label' => 'Border-radius',
                                    'selector' => '.kc_tabs_nav > li, .kc_tab.ui-tabs-body-active, .kc_tabs_nav,+.kc_vertical_tabs>.kc_wrapper>ul.ui-tabs-nav>li,+.kc_vertical_tabs>.kc_wrapper>ul.ui-tabs-nav ~ div.kc_tab.ui-tabs-body-active,+.kc_vertical_tabs.tabs_right>.kc_wrapper>ul.ui-tabs-nav ~ div.kc_tab'
                                ],
                                [
                                    'property' => 'padding',
                                    'label' => 'Padding',
                                    'selector' => '.kc_tabs_nav > li > a,+.kc_vertical_tabs>.kc_wrapper>ul.ui-tabs-nav>li>a'
                                ],
                                [
                                    'property' => 'margin',
                                    'label' => 'Margin',
                                    'selector' => '.kc_tabs_nav > li > a,+.kc_vertical_tabs>.kc_wrapper>ul.ui-tabs-nav>li'
                                ],
                                [
                                    'property' => 'width',
                                    'label' => 'Width',
                                    'selector' => '.kc_tabs_nav > li,+.kc_vertical_tabs>.kc_wrapper>ul.ui-tabs-nav>li'
                                ],
                            ],

                            'Tab Hover' => [
                                [
                                    'property' => 'color',
                                    'label' => 'Text Color',
                                    'selector' => '.kc_tabs_nav li:hover a, .kc_tabs_nav li:hover, .kc_tabs_nav > .ui-tabs-active:hover a,+.kc_vertical_tabs>.kc_wrapper>ul.ui-tabs-nav>li>a:hover,+.kc_vertical_tabs>.kc_wrapper>ul.ui-tabs-nav>li.ui-tabs-active > a'
                                ],
                                [
                                    'property' => 'color',
                                    'label' => 'Icon Color',
                                    'selector' => '.kc_tabs_nav li:hover a i,+.kc_vertical_tabs>.kc_wrapper>ul.ui-tabs-nav>li>a:hover i,+.kc_vertical_tabs>.kc_wrapper>ul.ui-tabs-nav>li.ui-tabs-active > a i'
                                ],
                                [
                                    'property' => 'background-color',
                                    'label' => 'Background Color',
                                    'selector' => '.kc_tabs_nav > li:hover, .kc_tabs_nav > li:hover a, .kc_tabs_nav > li > a:hover,+.kc_vertical_tabs>.kc_wrapper>ul.ui-tabs-nav>li>a:hover,+.kc_vertical_tabs>.kc_wrapper>ul.ui-tabs-nav>li.ui-tabs-active > a'
                                ],
                            ],
                            'Tab Active' => [
                                [
                                    'property' => 'color',
                                    'label' => 'Text Color',
                                    'selector' => '.kc_tabs_nav li.ui-tabs-active a,+.kc_vertical_tabs>.kc_wrapper>ul.ui-tabs-nav>li.ui-tabs-active > a'
                                ],
                                [
                                    'property' => 'color',
                                    'label' => 'Icon Color',
                                    'selector' => '.kc_tabs_nav li.ui-tabs-active a i, .kc_tabs_nav > .ui-tabs-active:focus a i,+.kc_vertical_tabs>.kc_wrapper>ul.ui-tabs-nav>li.ui-tabs-active > a i'
                                ],
                                [
                                    'property' => 'background-color',
                                    'label' => 'Background Color',
                                    'selector' => '.kc_tabs_nav > .ui-tabs-active:focus, .kc_tabs_nav > .ui-tabs-active, .kc_tabs_nav > .ui-tabs-active > a,+.kc_vertical_tabs>.kc_wrapper>ul.ui-tabs-nav>li.ui-tabs-active > a'
                                ],
                            ],
                            'Tab Body' => [
                                [
                                    'property' => 'background-color',
                                    'label' => 'Background Color',
                                    'selector' => '.kc_tab'
                                ],
                                [
                                    'property' => 'padding',
                                    'label' => 'Spacing',
                                    'selector' => '.kc_tab .kc_tab_content'
                                ],
                                ['property' => 'display', 'label' => 'Display'],
                            ],

                        ]
                    ]
                ]
            ],
            'animate' => [
                [
                    'name' => 'animate',
                    'type' => 'animate'
                ]
            ],
        ],
        'content' => '[kc_tab title="New Tab"][/kc_tab]'
    ]
];
