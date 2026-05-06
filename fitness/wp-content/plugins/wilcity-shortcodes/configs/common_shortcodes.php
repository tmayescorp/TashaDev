<?php

use WilokeListingTools\Framework\Helpers\General;
use WILCITY_SC\SCHelpers;

$aPricingOptions = ['flexible' => 'Depends on Listing Type Request'];
$aPostTypes = General::getPostTypeKeys(false, false);
if (!empty($aPostTypes)) {
	$aPricingOptions = array_merge($aPricingOptions, array_combine($aPostTypes, $aPostTypes));
}

return apply_filters(
	'wilcity/filter/wilcity-shortcodes/config/commom_shortcodes',
	[
		'item'   => [
			'blur_mark'              => [
				'type'  => 'text',
				'name'  => 'blur_mark',
				'label' => 'Blur Mark',
				'value' => ''
			],
			'blur_mark_color'        => [
				'type'  => 'color_picker',
				'name'  => 'blur_mark_color',
				'label' => 'Blur Mark Color',
				'value' => ''
			],
			'heading'                => [
				'name'        => 'heading',
				'label'       => 'Heading',
				'type'        => 'text',
				'value'       => 'The Latest Listings',
				'admin_label' => true
			],
			'heading_color'          => [
				'type'  => 'color_picker',
				'name'  => 'heading_color',
				'label' => 'Heading Color',
				'value' => ''
			],
			'desc'                   => [
				'name'        => 'desc',
				'label'       => 'Description',
				'type'        => 'textarea',
				'admin_label' => true
			],
			'desc_color'             => [
				'type'  => 'color_picker',
				'name'  => 'desc_color',
				'label' => 'Description Color',
				'value' => ''
			],
			'header_desc_text_align' => [
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
			],
			'toggle_viewmore'        => [
				'type'    => 'select',
				'label'   => 'Toggle View More',
				'name'    => 'toggle_viewmore',
				'options' => [
					'disable' => 'Disable',
					'enable'  => 'Enable'
				],
				'std'     => 'enable'
			],
			'viewmore_btn_name'      => [
				'type'     => 'text',
				'label'    => 'Button Name',
				'name'     => 'viewmore_btn_name',
				'relation' => [
					'parent'    => 'toggle_viewmore',
					'show_when' => 'enable'
				],
				'std'      => 'View more'
			],
			'image_size'             => [
				'name'        => 'image_size',
				'label'       => 'Image Size',
				'description' => 'You can use the defined image sizes like: full, large, medium, wilcity_560x300 or 400,300 to specify the image width and height.',
				'type'        => 'text',
				'value'       => 'wilcity_560x300'
			],
			'term_redirect'          => [
				'name'        => 'term_redirect',
				'label'       => 'Term Redirect',
				'description' => 'Defines what page should be redirected when clicking on a term',
				'type'        => 'select',
				'value'       => 'search_page',
				'options'     => [
					'search_page' => 'Search Page',
					'_self'       => 'Self Term page'
				]
			],
			'term_orderby'           => [
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
			],
			'is_hide_empty'          => [
				'name'    => 'is_hide_empty',
				'label'   => 'Hide Empty Term',
				'type'    => 'select',
				'value'   => 'no',
				'options' => [
					'no'  => 'No',
					'yes' => 'Yes'
				]
			],
			'is_show_parent_only'    => [
				'name'    => 'is_show_parent_only',
				'label'   => 'Show Parent Only',
				'type'    => 'select',
				'value'   => 'no',
				'options' => [
					'no'  => 'No',
					'yes' => 'Yes'
				]
			],
			'taxonomy_types'         => [
				'name'        => 'taxonomy',
				'label'       => 'Taxonomy Type',
				'description' => 'Children of self term means when using this shortcode on Taxonomy Template, it will show up all sub-terms of parent term',
				'type'        => 'select',
				'value'       => 'listing_location',
				'options'     => [
					'listing_cat'      => 'Listing Category',
					'listing_tag'      => 'Listing Tag',
					'listing_location' => 'Listing Location',
					//        '_self'            => 'Children of self term'
				],
				'admin_label' => true
			],
			'listing_locations'      => [
				'type'        => 'autocomplete',
				'label'       => 'Select Listing Location[s]',
				'description' => '',
				'name'        => 'listing_locations',
				'multiple'    => true,
				'relation'    => [
					'parent'    => 'taxonomy',
					'hide_when' => 'listing_location'
				]
			],
			'listing_location'       => [
				'type'        => 'autocomplete',
				'multiple'    => false,
				'label'       => 'Select Listing Location',
				'description' => 'This shortcode will show up all listings in the Locations and this category and You can select 1 category only. This feature is not available if you are using Redirect to Term Page',
				'name'        => 'listing_location',
				'relation'    => [
					'parent'    => 'taxonomy',
					'show_when' => 'listing_location'
				]
			],
			'listing_cats'           => [
				'type'        => 'autocomplete',
				'label'       => 'Select Listing Categories',
				'multiple'    => true,
				'description' => '',
				'name'        => 'listing_cats',
				'relation'    => [
					'parent'    => 'taxonomy',
					'hide_when' => 'listing_cat'
				]
			],
			'listing_cat'            => [
				'type'        => 'autocomplete',
				'multiple'    => false,
				'label'       => 'Select Listing Category',
				'description' => 'This shortcode will show up all listings in the Locations and this category and You can select 1 category only. This feature is not available if you are using Redirect to Term Page',
				'name'        => 'listing_cat',
				'relation'    => [
					'parent'    => 'taxonomy',
					'show_when' => 'listing_cat'
				]
			],
			'listing_tags'           => [
				'type'        => 'autocomplete',
				'label'       => 'Select Listing Tags',
				'multiple'    => true,
				'description' => '',
				'name'        => 'listing_tags',
				'relation'    => [
					'parent'    => 'taxonomy',
					'hide_when' => 'listing_tag'
				]
			],
			'listing_tag'            => [
				'type'        => 'autocomplete',
				'multiple'    => false,
				'label'       => 'Select Listing Tag',
				'description' => 'This shortcode will show up all listings in the Locations and this category and You can select 1 category only. This feature is not available if you are using Redirect to Term Page',
				'name'        => 'listing_tag',
				'relation'    => [
					'parent'    => 'taxonomy',
					'show_when' => 'listing_tag'
				]
			],
			'post_types_filter'      => [
				'name'        => 'post_types_filter',
				'label'       => 'Post Types Filter',
				'description' => 'This feature is not available if you are using Redirect to Term Page',
				'type'        => 'multiple',
				'multiple'    => true,
				'value'       => '',
				'relation'    => [
					'parent'    => 'term_redirect',
					'hide_when' => '_self',
				],
				'options'     => SCHelpers::getListingPostTypeKeys(false, false)
			],
			'post_type'              => [
				'name'        => 'post_type',
				'label'       => 'Post Type',
				'type'        => 'select',
				'description' => 'Depends on term means We will use the main Listing Type of the term',
				'value'       => '',
				'options'     => array_merge(
					['' => '----'],
					SCHelpers::getListingPostTypeKeys(),
					['flexible' => 'Depends on Term']
				)
			],
			'event_post_type'        => [
				'name'        => 'post_type',
				'label'       => 'Post Type',
				'type'        => 'select',
				'description' => 'Depends on term means We will use the main Listing Type of the term',
				'value'       => '',
				'options'     => array_merge(
					['' => '----'],
					SCHelpers::getEventPostTypeOptions()
				)
			],
			'event_orderby'          => [
				'type'    => 'select',
				'label'   => 'Order By',
				'name'    => 'orderby',
				'value'   => '',
				'options' => [
					'wilcity_event_starts_on'            => 'Event Date',
					'post_date'                          => 'Event Post Date',
					'post_title'                         => 'Event Title',
					'menu_order'                         => 'Premium Listings',
					'menu_order wilcity_event_starts_on' => 'Premium Listings Then Event Date',
					'upcoming_event'                     => 'Upcoming Events',
					'happening_event'                    => 'Happening Events',
					'starts_from_ongoing_event'          => 'Upcoming + Happening'
				]
			],
			'listing_orderby'        => [
				'type'    => 'select',
				'label'   => 'Order By',
				'name'    => 'orderby',
				'options' => [
					'post_date'               => 'Listing Date',
					'post_title'              => 'Listing Title',
					'menu_order'              => 'Listing Order',
					'best_viewed'             => 'Popular Viewed',
					'best_rated'              => 'Popular Rated',
					'best_shared'             => 'Popular Shared',
					'rand'                    => 'Random',
					'nearbyme'                => 'Near By Me',
					'open_now'                => 'Open now',
					'discount'                => 'Discount',
					'premium_listings'        => 'Premium Listings',
					'wilcity_event_starts_on' => 'Event Date'
				]
			],
			'order'                  => [
				'type'    => 'select',
				'label'   => 'Order',
				'name'    => 'order',
				'options' => [
					'DESC' => 'DESC',
					'ASC'  => 'ASC'
				],
				'value'   => 'ASC'
			],
			'col_gap'                => [
				'name'  => 'col_gap',
				'label' => 'Col Gap',
				'type'  => 'text',
				'value' => 20
			],
			'number'                 => [
				'name'  => 'number',
				'label' => 'Maximum Items',
				'type'  => 'text',
				'value' => 6
			],
			'posts_per_page'         => [
				'type'  => 'text',
				'label' => 'Maximum Items',
				'name'  => 'posts_per_page',
				'value' => 6
			],
			'custom_taxonomy_key'    => [
				'type'        => 'text',
				'label'       => 'Taxonomy Key',
				'description' => 'This feature is useful if you want to use show up your custom taxonomy',
				'name'        => 'custom_taxonomy_key'
			],
			'custom_taxonomies_id'   => [
				'type'        => 'text',
				'label'       => 'Select Your Custom Taxonomies',
				'description' => 'Each taxonomy should separated by a comma, Eg: 1,2,3,4. Leave empty if you are working on Taxonomy Template',
				'name'        => 'custom_taxonomies_id'
			],
			'listing_ids'            => [
				'type'        => 'autocomplete',
				'label'       => 'Specify Listings',
				'description' => 'Leave empty if you are working on Taxonomy Template',
				'name'        => 'listing_ids'
			]
		],
		'group'  => [
			'items_on_screen'   => [
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
			],
			'bootstrap_columns' => [
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
						'wil-md-2 wil-col-5' => '5 Items / row',
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
					'label'       => 'Items / row on >=640px',
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
				],
				[
					'name'        => 'maximum_posts_on_xs_screen',
					'label'       => 'Items / row on < 720px',
					'description' => 'Set number of listings will be displayed when the screen is smaller than 640px',
					'type'        => 'select',
					'options'     => [
						'col-xs-2'  => '6 Items / row',
						'col-xs-4'  => '3 Items / row',
						'col-xs-6'  => '2 Items / row',
						'col-xs-12' => '1 Item / row'
					],
					'value'       => 'col-sm-12',
					'admin_label' => true
				]
			],
		],
		'option' => [
			'listing_orderby' => [
				'post_date'               => 'Listing Date',
				'post_title'              => 'Listing Title',
				'menu_order'              => 'Listing Order',
				'best_viewed'             => 'Popular Viewed',
				'best_rated'              => 'Popular Rated',
				'best_shared'             => 'Popular Shared',
				'post__in'                => 'Like Specify Listing IDs field',
				'rand'                    => 'Random',
				'nearbyme'                => 'Near By Me',
				'discount'                => 'Discount',
				'open_now'                => 'Open now',
				'premium_listings'        => 'Premium Listings',
				'wilcity_event_starts_on' => 'Event Date'
			],
			'pricing_options' => $aPricingOptions
		]
	]
);
