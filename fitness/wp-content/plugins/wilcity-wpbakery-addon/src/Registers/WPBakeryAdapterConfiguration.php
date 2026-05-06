<?php
namespace WilcityWPBakeryAddon\Registers;


use WILCITY_SC\RegisterSC\AbstractRegisterShortcodes;
use WILCITY_SC\SCHelpers;

class WPBakeryAdapterConfiguration extends AbstractRegisterShortcodes
{
    private $aConfiguration;
    private $aCache;
    private $taxonomy;

    public function __construct()
    {
        add_action('vc_before_init', [$this, 'register']);
    }

    private function parseGroupTitle($group)
    {
        $group = str_replace('_', ' ', $group);

        return ucfirst($group);
    }

    private function parseItemType($aItem)
    {
        if (isset($aItem['label'])) {
            $aItem['heading'] = $aItem['label'];
        }

        $aItem['param_name'] = $aItem['name'];
        if (isset($aItem['value'])) {
            $aItem['std'] = $aItem['value'];
        }
        $aItem['save_always'] = true;

        switch ($aItem['type']) {
            case 'multiple':
                $aItem['type']  = 'checkbox';
                $aItem['value'] = array_flip($aItem['options']);
                break;
            case 'select':
                $aItem['type']  = 'dropdown';
                $aItem['value'] = array_flip($aItem['options']);
                break;
            case 'group':
                $aItem['type'] = 'param_group';
                foreach ($aItem['params'] as $order => $aParam) {
                    $aItem['params'][$order] = $this->parseItemType($aParam);
                }
                break;
            case 'textarea':
                $aItem['type'] = 'textarea';
                break;
            case 'color_picker':
                $aItem['type'] = 'colorpicker';
                break;
            case 'icon_picker':
                $aItem['type'] = 'iconpicker';
                break;
            case 'editor':
                $aItem['type'] = 'textarea_html';
                break;
            case 'attach_image_url':
                $aItem['type'] = 'attach_image';
                break;
            case 'autocomplete':
                if (!isset($aItem['multiple']) || $aItem['multiple']) {
                    $aItem['settings'] = [
                      'multiple' => true,
                      'sortable' => true,
                      'groups'   => true,
                    ];
                }
                break;
            case 'text':
                $aItem['type'] = 'textfield';
                break;
        }

        if (isset($aItem['relation'])) {
            if (isset($aItem['relation']['show_when'])) {
                $aItem['dependency'] = [
                  'element' => $aItem['relation']['parent'],
                  'value'   => is_string($aItem['relation']['show_when']) ? $aItem['relation']['show_when'] :
                    $aItem['relation']['show_when'][2]
                ];
            }
            unset($aItem['relation']);
        }

        return $aItem;
    }

    private function parseGroup($aRawParams)
    {
        $aRawParams = $this->prepareShortcodeItem($aRawParams);
        foreach ($aRawParams as $groupKey => $aItems) {
            foreach ($aItems as $order => $aItem) {
                unset($aItem['styling']);

                $aItem['group'] = $this->parseGroupTitle($groupKey);
                $aItem          = array_merge($aItem, $this->parseItemType($aItem));
                unset($aItem['relation']);
                unset($aItem['label']);
                unset($aItem['name']);
                unset($aItem['options']);
                $aParams[] = $aItem;
            }
        }

        return $aParams;
    }

    private function prepareConfiguration()
    {
        $this->aConfiguration = $this->getConfigurations();
        unset($this->aConfiguration['kc_tabs']);

        foreach ($this->aConfiguration as $scKey => $aConfigs) {
            $base = str_replace(
              'wilcity_kc',
              'wilcity_vc',
              $scKey
            );

            $this->aConfiguration[$base] = $aConfigs;
            unset($this->aConfiguration[$scKey]);

            $this->aConfiguration[$base]['base']                    = $base;
            $this->aConfiguration[$base]['category']                = WILCITY_VC_SC;
            $this->aConfiguration[$base]['show_settings_on_create'] = true;
            $this->aConfiguration[$base]['controls']                = true;
            $this->aConfiguration[$base]['icon']                    = '';

            $this->aConfiguration[$base]['params']   = $this->parseGroup($aConfigs['params']);
            $this->aConfiguration[$base]['params'][] = [
              'type'       => 'textfield',
              'heading'    => 'Extra Class',
              'param_name' => 'extra_class',
              'std'        => ''
            ];

            if (!isset($aConfigs['is_remove_css_editor']) || !$aConfigs['is_remove_css_editor']) {
                $this->aConfiguration[$base]['params'][] = [
                  'type'       => 'css_editor',
                  'heading'    => 'Css',
                  'param_name' => 'css',
                  'group'      => 'Design Options'
                ];
            }
        }
    }

    private function assignAcceptChildren()
    {
        foreach ($this->aConfiguration as $scKey => $aConfiguration) {
            if (isset($aConfiguration['accept_child'])) {
                $childScKey = str_replace(
                  'wilcity_kc',
                  'wilcity_vc',
                  $aConfiguration['accept_child']
                );

                unset($this->aConfiguration[$scKey]['accept_child']);
                unset($this->aConfiguration[$scKey]['nested']);

                $this->aConfiguration[$scKey]['controls']                = 'full';
                $this->aConfiguration[$scKey]['is_container']            = true;
                $this->aConfiguration[$scKey]['content_element']         = true;
                $this->aConfiguration[$scKey]['show_settings_on_create'] = false;
                $this->aConfiguration[$scKey]['as_parent']               = ['only' => $childScKey];
                $this->aConfiguration[$scKey]['js_view']                 = 'VcColumnView';
            }
        }
    }

    public function register()
    {
        $this->prepareConfiguration();
        $this->assignAcceptChildren();
        $aAllPostTypes = SCHelpers::getPostTypeKeys(true);
        $aAllPostTypes = array_combine($aAllPostTypes, $aAllPostTypes);

        $this->aConfiguration[] = [
          'name'                    => 'Listings Grid Layout',
          'base'                    => 'wilcity_vc_listing_grip_layout',
          'icon'                    => '',
          'show_settings_on_create' => true,
          'category'                => WILCITY_VC_SC,
          'controls'                => true,
          'params'                  => [
            [
              'type'       => 'textfield',
              'heading'    => 'Heading',
              'param_name' => 'heading'
            ],
            [
              'type'       => 'colorpicker',
              'heading'    => 'Heading Color',
              'param_name' => 'heading_color'
            ],
            [
              'type'       => 'textfield',
              'heading'    => 'Description',
              'param_name' => 'desc'
            ],
            [
              'type'       => 'colorpicker',
              'heading'    => 'Description Color',
              'param_name' => 'desc_color'
            ],
            [
              'type'        => 'dropdown',
              'heading'     => 'Heading and Description Alignment',
              'param_name'  => 'header_desc_text_align',
              'std'         => '',
              'value'       => [
                'Center' => 'wil-text-center',
                'Left'   => 'wil-text-left',
                'Right'  => 'wil-text-right'
              ],
              'save_always' => true,
            ],
            [
              'type'        => 'dropdown',
              'heading'     => 'Toggle Viewmore',
              'param_name'  => 'toggle_viewmore',
              'std'         => '',
              'value'       => [
                'Disable' => 'disable',
                'Enable'  => 'enable'
              ],
              'save_always' => true,
            ],
            [
              'type'        => 'textfield',
              'heading'     => 'Button Name',
              'param_name'  => 'viewmore_btn_name',
              'std'         => 'View more',
              'dependency'  => [
                'element' => 'toggle_viewmore',
                'value'   => ['enable']
              ],
              'save_always' => true
            ],
            [
              'type'        => 'dropdown',
              'heading'     => 'Style',
              'param_name'  => 'style',
              'std'         => '',
              'value'       => [
                'Grid'   => 'grid',
                'Grid 2' => 'grid2',
                'List'   => 'list'
              ],
              'save_always' => true,
            ],
            [
              'type'        => 'dropdown',
              'heading'     => 'Border',
              'description' => 'Adding a border around Listing Grid',
              'param_name'  => 'border',
              'std'         => '',
              'value'       => [
                'Enable'  => 'border-gray-1',
                'Disable' => 'border-gray-0'
              ],
              'save_always' => true
            ],
            [
              'param_name'  => 'post_type',
              'heading'     => 'Post Type',
              'type'        => 'dropdown',
              'std'         => 'listing',
              'save_always' => true,
              'admin_label' => true,
              'value'       => $aAllPostTypes
            ],
            [
              'type'       => 'autocomplete',
              'heading'    => 'Select Tags',
              'param_name' => 'listing_tags',
              'settings'   => [
                'multiple' => true,
                'sortable' => true,
                'groups'   => true,
              ]
            ],
            [
              'type'       => 'autocomplete',
              'heading'    => 'Select Categories',
              'param_name' => 'listing_cats',
              'settings'   => [
                'multiple' => true,
                'sortable' => true,
                'groups'   => true,
              ]
            ],
            [
              'type'       => 'autocomplete',
              'heading'    => 'Select Locations',
              'param_name' => 'listing_locations',
              'settings'   => [
                'multiple' => true,
                'sortable' => true,
                'groups'   => true,
              ]
            ],
            [
              'type'       => 'autocomplete',
              'heading'    => 'Specify Listing IDs',
              'param_name' => 'listing_ids',
              'settings'   => [
                'multiple' => true,
                'sortable' => true,
                'groups'   => true,
              ],
            ],
            [
              'type'        => 'dropdown',
              'heading'     => 'Order By',
              'param_name'  => 'orderby',
              'std'         => 'post_date',
              'value'       => [
                'Listing Date'                   => 'post_date',
                'Listing Title'                  => 'post_title',
                'Popular Viewed'                 => 'best_viewed',
                'Popular Rated'                  => 'best_rated',
                'best_shared'                    => 'best_shared',
                'Random'                         => 'rand',
                'Near By Me'                     => 'nearbyme',
                'Open now'                       => 'open_now',
                'Like Specify Listing IDs field' => 'post__in',
                'Premium Listings'               => 'premium_listings'
              ],
              'save_always' => true,
            ],
            [
              'type'        => 'textfield',
              'heading'     => 'Radius',
              'description' => 'Fetching all listings within x radius',
              'param_name'  => 'radius',
              'std'         => 10,
              'save_always' => true,
              'dependency'  => [
                'element' => 'orderby',
                'value'   => ['nearbyme']
              ]
            ],
            [
              'type'        => 'dropdown',
              'heading'     => 'Unit',
              'param_name'  => 'unit',
              'dependency'  => [
                'element' => 'orderby',
                'value'   => ['orderby', '=', 'nearbyme']
              ],
              'value'       => [
                'KM'    => 'km',
                'Miles' => 'm'
              ],
              'std'         => 'km',
              'save_always' => true
            ],
            [
              'type'        => 'textfield',
              'heading'     => 'Tab Name',
              'description' => 'If the grid layout is inside of a tab, we recommend putting the Tab ID to this field. If the tab is emptied, the listings will be shown after the browser is loaded. Otherwise, it will be shown after someone clicks on the Tab Name.',
              'param_name'  => 'tabname',
              'value'       => '',
              'element'     => [
                'element' => 'orderby',
                'value'   => ['orderby', '=', 'nearbyme']
              ],
              'save_always' => true,
            ],
            [
              'type'        => 'textfield',
              'heading'     => 'Maximum Items',
              'param_name'  => 'posts_per_page',
              'value'       => 6,
              'save_always' => true
            ],
            [
              'type'        => 'textfield',
              'heading'     => 'Image Size',
              'description' => 'For example: 200x300. 200: Image width. 300: Image height',
              'param_name'  => 'img_size',
              'std'         => 'wilcity_360x200',
              'save_always' => true
            ],
            [
              'param_name'  => 'maximum_posts_on_lg_screen',
              'heading'     => 'Items / row on >=1200px',
              'description' => 'Set number of listings will be displayed when the screen is larger or equal to 1400px ',
              'type'        => 'dropdown',
              'std'         => 'col-lg-4',
              'save_always' => true,
              'value'       => [
                '6 Items / row' => 'col-lg-2',
                '4 Items / row' => 'col-lg-3',
                '3 Items / row' => 'col-lg-4',
                '2 Items / row' => 'col-lg-6',
                '1 Items / row' => 'col-lg-12'
              ],
              'group'       => 'Device Settings'
            ],
            [
              'param_name'  => 'maximum_posts_on_md_screen',
              'heading'     => 'Items / row on >=960px',
              'description' => 'Set number of listings will be displayed when the screen is larger or equal to 1200px ',
              'type'        => 'dropdown',
              'value'       => [
                '6 Items / row' => 'col-md-2',
                '4 Items / row' => 'col-md-3',
                '3 Items / row' => 'col-md-4',
                '2 Items / row' => 'col-md-6',
                '1 Items / row' => 'col-md-12'
              ],
              'std'         => 'col-md-3',
              'save_always' => true,
              'group'       => 'Device Settings'
            ],
            [
              'param_name'  => 'maximum_posts_on_sm_screen',
              'heading'     => 'Items / row on >=720px',
              'description' => 'Set number of listings will be displayed when the screen is larger or equal to 640px ',
              'type'        => 'dropdown',
              'value'       => [
                '6 Items / row' => 'col-sm-2',
                '4 Items / row' => 'col-sm-3',
                '3 Items / row' => 'col-sm-4',
                '2 Items / row' => 'col-sm-6',
                '1 Items / row' => 'col-sm-12'
              ],
              'std'         => 'col-sm-12',
              'group'       => 'Device Settings',
              'save_always' => true
            ],
            [
              'type'       => 'css_editor',
              'heading'    => 'CSS',
              'param_name' => 'css',
              'group'      => 'Design Options'
            ]
          ]
        ];

        foreach ($this->aConfiguration as $sc) {
            try {
                vc_map($sc);
                wilcityAddVCShortcode($sc);
            } catch (\Exception $e) {
            }
        }
    }
}
