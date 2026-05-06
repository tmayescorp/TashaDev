<?php

use WILCITY_SC\SCHelpers;
use WilokeListingTools\Framework\Helpers\General;

$aPricingOptions = ['flexible' => 'Depends on Listing Type Request'];
$aPostTypes      = General::getPostTypeKeys(false, false);
if (class_exists('WilokeListingTools\Framework\Helpers\General')) {
    //	$aPricingOptions = $aPricingOptions + array_combine($aPostTypes, $aPostTypes);
    if (!empty($aPostTypes)) {
        $aPricingOptions = array_merge($aPricingOptions, array_combine($aPostTypes, $aPostTypes));
    }
}

$aImageSize = [
  'name'        => 'image_size',
  'label'       => 'Image Size',
  'description' => 'You can use the defined image sizes like: full, large, medium, wilcity_560x300 or 400,300 to specify the image width and height.',
  'type'        => 'text',
  'value'       => 'wilcity_560x300'
];

$aTermRedirection = [
  'name'        => 'term_redirect',
  'label'       => 'Term Redirect',
  'description' => 'Defines what page should be redirected when clicking on a term',
  'type'        => 'select',
  'value'       => 'search_page',
  'options'     => [
    'search_page' => 'Search Page',
    '_self'       => 'Self Term page'
  ]
];

$aTermOrderby = [
  'name'        => 'orderby',
  'label'       => 'Order By',
  'description' => 'This feature is not available if the "Select Locations/Select Categories" is not empty',
  'type'        => 'select',
  'value'       => 'count',
  'options'     => [
    'count'      => 'Number of children',
    'name'       => 'Term Name',
    'term_order' => 'Term Order',
    'id'         => 'Term ID',
    'slug'       => 'Term Slug',
    'none'       => 'None',
    'include'    => 'Include'
  ]
];

$aItemOnScreenSettings = [
  [
    'name'        => 'items_on_lg_screen',
    'label'       => 'Items / row on >=1200px',
    'description' => 'Set number of listings will be displayed when the screen is larger or equal to 1400px ',
    'type'        => 'select',
    'value'       => 4,
    'options'     => [
      2 => 2,
      3 => 3,
      4 => 4,
      5 => 5,
      6 => 6
    ],
    'admin_label' => true
  ],
  [
    'name'        => 'items_on_md_screen',
    'label'       => 'Items / row on >=960px',
    'description' => 'Set number of listings will be displayed when the screen is larger or equal to 960px ',
    'type'        => 'select',
    'value'       => 4,
    'options'     => [
      2 => 2,
      3 => 3,
      4 => 4,
      5 => 5,
      6 => 6
    ],
    'admin_label' => true
  ],
  [
	  'name'        => 'items_on_sm_screen',
	  'label'       => 'Items / row on >=720px',
	  'description' => 'Set number of listings will be displayed when the screen is larger or equal to 640px ',
	  'type'        => 'select',
	  'value'       => 2,
	  'options'     => [
		  2 => 2,
		  3 => 3,
		  4 => 4,
		  5 => 5,
		  6 => 6
	  ],
	  'admin_label' => true
  ],
  [
	  'name'        => 'maximum_posts_on_xs_screen',
	  'label'       => 'Items / row on < 720px',
	  'description' => 'Set number of listings will be displayed when the screen is larger or equal to 640px ',
	  'type'        => 'select',
	  'value'       => 2,
	  'options'     => [
		  2 => 2,
		  3 => 3,
		  4 => 4,
		  5 => 5,
		  6 => 6
	  ],
	  'admin_label' => true
  ],
];

$aGridSettings = [
  [
    'name'        => 'maximum_posts_on_lg_screen',
    'label'       => 'Items / row on >=1200px',
    'description' => 'Set number of listings will be displayed when the screen is larger or equal to 1400px ',
    'type'        => 'select',
    'value'       => 'wil-col-5 col-lg-2',
    'options'     => [
      'col-lg-2'           => '6 Items / row',
      'wil-col-5 col-lg-2' => '5 Items / row',
      'col-lg-3'           => '4 Items / row',
      'col-lg-4'           => '3 Items / row',
      'col-lg-6'           => '2 Items / row',
      'col-lg-12'          => '1 Item / row'
    ],
    'admin_label' => true
  ],
  [
    'name'        => 'maximum_posts_on_md_screen',
    'label'       => 'Items / row on >=960px',
    'description' => 'Set number of listings will be displayed when the screen is larger or equal to 1200px ',
    'type'        => 'select',
    'options'     => [
      'col-md-2'           => '6 Items / row',
      'col-md-2 wil-col-5' => '5 Items / row',
      'col-md-3'           => '4 Items / row',
      'col-md-4'           => '3 Items / row',
      'col-md-6'           => '2 Items / row',
      'col-md-12'          => '1 Item / row'
    ],
    'value'       => 'col-md-3',
    'admin_label' => true
  ],
  [
    'name'        => 'maximum_posts_on_sm_screen',
    'label'       => 'Items / row on >=720px',
    'description' => 'Set number of listings will be displayed when the screen is larger or equal to 640px ',
    'type'        => 'select',
    'options'     => [
      'col-sm-2'  => '6 Items / row',
      'col-sm-4'  => '3 Items / row',
      'col-sm-6'  => '2 Items / row',
      'col-sm-12' => '1 Item / row'
    ],
    'value'       => 'col-sm-12',
    'admin_label' => true
  ]
];

$aTaxonomyTypes = [
  'name'        => 'taxonomy',
  'label'       => 'Taxonomy Type',
  'description' => 'Children of self term means when using this shortcode on Taxonomy Template, it will show up all sub-terms of parent term',
  'type'        => 'select',
  'value'       => 'listing_cat',
  'options'     => [
    'listing_cat'      => 'Listing Category',
    'listing_location' => 'Listing Location',
    '_self'            => 'Children of self term'
  ],
  'admin_label' => true
];

$aSelectLocations = [
  'type'        => 'autocomplete',
  'label'       => 'Select Listing Location[s]',
  'description' => '',
  'name'        => 'listing_locations',
  'relation'    => [
    'parent'    => 'taxonomy',
    'hide_when' => ['taxonomy', '=', 'listing_cat']
  ]
];

$aSelectCats     = [
  'type'        => 'autocomplete',
  'label'       => 'Select Listing Categories',
  'description' => '',
  'name'        => 'listing_cats',
  'relation'    => [
    'parent'    => 'taxonomy',
    'hide_when' => ['taxonomy', '=', 'listing_location']
  ]
];
$aSelectCat      = [
  'type'        => 'autocomplete',
  'label'       => 'Select Listing Category',
  'description' => 'This shortcode will show up all listings in the Locations and this category and You can select 1 category only. This feature is not available if you are using Redirect to Term Page',
  'name'        => 'listing_cat',
  'relation'    => [
    'parent'    => 'taxonomy',
    'show_when' => ['taxonomy', '=', 'listing_location'],
    'hide_when' => ['term_redirect', '=', 'term_page']
  ]
];
$aSelectLocation = [
  'type'        => 'autocomplete',
  'label'       => 'Select Listing Location',
  'description' => 'This shortcode will show up all listings in the Locations and this category and You can select 1 category only. This feature is not available if you are using Redirect to Term Page',
  'name'        => 'listing_location',
  'relation'    => [
    'parent'    => 'taxonomy',
    'show_when' => ['taxonomy', '=', 'listing_cat'],
    'hide_when' => ['term_redirect', '=', 'term_page']
  ]
];

$aTaxonomiesSettings = [
  $aSelectLocations,
  $aSelectCats,
  $aSelectCat,
  $aSelectLocation
];

$aWilcitySCHeading = [
  [
    'name'        => 'heading',
    'label'       => 'Heading',
    'type'        => 'text',
    'value'       => 'The Latest Listings',
    'admin_label' => true
  ],
  [
    'type'  => 'color_picker',
    'name'  => 'heading_color',
    'label' => 'Heading Color',
    'value' => ''
  ],
  [
    'name'        => 'desc',
    'label'       => 'Description',
    'type'        => 'textarea',
    'admin_label' => true
  ],
  [
    'type'  => 'color_picker',
    'name'  => 'desc_color',
    'label' => 'Description Color',
    'value' => ''
  ],
  [
    'name'        => 'header_desc_text_align',
    'label'       => 'Heading and Description Text Alignment',
    'type'        => 'select',
    'options'     => [
      'wil-text-center' => 'Center',
      'wil-text-left'   => 'Left',
      'wil-text-right'  => 'Right'
    ],
    'value'       => 'wil-text-center',
    'admin_label' => true
  ]
];

$aPostTypeFilters = [
  'name'        => 'post_types_filter',
  'label'       => 'Post Types Filter',
  'description' => 'This feature is not available if you are using Redirect to Term Page',
  'type'        => 'multiple',
  'value'       => '',
  'hide_when'   => ['term_redirect', '=', 'term_page'],
  'options'     => SCHelpers::getListingPostTypeKeys(false, false)
];

$aPostTypeSelections = [
  'name'    => 'post_type',
  'label'   => 'Post Type',
  'type'    => 'select',
  'value'   => '',
  'options' => array_merge(['' => '----'], SCHelpers::getListingPostTypeKeys())
];

$aOrderBy = include WILCITY_SC_DIR.'configs/orderby.php';

$aSC = [];
foreach (glob(plugin_dir_path(__FILE__).'configs/kc/*.php') as $filename) {
    $aConfig = include $filename;
    $aSC     = array_merge($aSC, $aConfig);
}

return [
  'shortcodes'  => $aSC,
  'aDaysOfWeek' => [
    'monday'    => esc_html__('Monday', 'wilcity-shortcodes'),
    'tuesday'   => esc_html__('Tuesday', 'wilcity-shortcodes'),
    'wednesday' => esc_html__('Wednesday', 'wilcity-shortcodes'),
    'thursday'  => esc_html__('Thursday', 'wilcity-shortcodes'),
    'friday'    => esc_html__('Friday', 'wilcity-shortcodes'),
    'saturday'  => esc_html__('Saturday', 'wilcity-shortcodes'),
    'sunday'    => esc_html__('Sunday', 'wilcity-shortcodes'),
  ]
];
