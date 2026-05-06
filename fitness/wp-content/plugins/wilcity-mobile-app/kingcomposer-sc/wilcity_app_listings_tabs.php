<?php

use WILCITY_APP\Helpers\AppHelpers;
use \WILCITY_SC\SCHelpers;
use \WilokeListingTools\Framework\Helpers\GetSettings;

$atts = shortcode_atts(
	[
		'TYPE'                    => 'APP_LISTINGS_TABS',
		'heading'                 => '',
		'heading_color'           => '',
		'taxonomy'                => '',
		'get_term_type'           => '',
		'listing_cat'             => '',
		'listing_cats'            => '',
		'listing_location'        => '',
		'listing_locations'       => '',
		'orderby_options'         => '',
		'orderby'                 => 'post_date',
		'order'                   => 'DESC',
		'posts_per_page'          => 6,
		'show_parent_only'        => 'no',
		'is_navigation'           => '',
		'navigation_orderby'      => '',
		'navigation_order'        => '',
		'post_type'               => 'listing',
		'number_of_term_children' => 6,
		'terms_tab_id'            => '',
	],
	$atts
);
$aAtts = SCHelpers::mergeIsAppRenderingAttr($atts);


$aQueryArgs = [
	'taxonomy'   => $atts['taxonomy'],
	'hide_empty' => false
];
if ($aAtts['show_parent_only'] == "yes") {
	$aQueryArgs['parent'] = 0;
}

$aRawTermIDs = $aAtts[$aAtts['taxonomy'] . 's'];
$aTermIDs = [];

if (!empty($aRawTermIDs)) {
	$aRawTermIDs = explode(',', $aRawTermIDs);
	foreach ($aRawTermIDs as $rawTerm) {
		$aParse = explode(':', $rawTerm);
		$aTermIDs[] = $aParse[0];
	}
}
if (!empty($aTermIDs)) {
	if ($aAtts['get_term_type'] == 'specify_terms') {
		$aQueryArgs['include'] = $aTermIDs;
	} elseif ($aAtts['get_term_type'] == 'term_children') {
		$aQueryArgs['include'] = [];
		foreach ($aTermIDs as $iTermID) {
			$aTermChilds = get_terms(
				[
					'hide_empty' => false,
					'parent'     => $iTermID,
					'taxonomy'   => $aAtts['taxonomy'],
					'count'      => $aAtts['number_of_term_children'],
					'orderby'    => $aAtts['navigation_orderby'],
					'order'      => $aAtts['navigation_order'],
				]
			);
			if (!empty($aTermChilds)) {
				foreach ($aTermChilds as $oTerm) {
					$aQueryArgs['include'][] = $oTerm->term_id;
				}
			}
		}
	}
} else {
	$aQueryArgs['orderby'] = $atts['orderby'];
	$aQueryArgs['order'] = $atts['order'];
}
$aRestAPIParm = [];

if ($aAtts['taxonomy'] === 'listing_location') {
	if (!empty($aAtts['listing_cat'])) {
		$aListingCatIds = SCHelpers::getAutoCompleteVal($aAtts['listing_cat']);
		$aListingCats = SCHelpers::getAutoCompleteVal($aAtts['listing_cat'], 'both');
		$aQueryArgs['listing_cat'] = wilcityFallbackFindTermBySlug(
			$aListingCatIds,
			$aListingCats,
			'listing_cat'
		);
		if (!empty($aQueryArgs['listing_cat'])) {
			$aRestAPIParm['listing_cat'] = $aQueryArgs['listing_cat'] = $aQueryArgs['listing_cat'][0];
		}
	}
} elseif ($aAtts['taxonomy'] === 'listing_cat') {
	if (!empty($aAtts['listing_location'])) {
		$aQueryArgs['listing_location'] = SCHelpers::getAutoCompleteVal($aAtts['listing_location']);
		$aListingLocationIds = SCHelpers::getAutoCompleteVal($aAtts['listing_location']);
		$aListingLocations = SCHelpers::getAutoCompleteVal($aAtts['listing_location'], 'both');
		$aQueryArgs['listing_location'] = wilcityFallbackFindTermBySlug(
			$aListingLocationIds,
			$aListingLocations,
			'listing_location'
		);
		if (!empty($aQueryArgs['listing_location'])) {
			$aRestAPIParm['listing_location'] = $aQueryArgs['listing_location'] = $aQueryArgs['listing_location'][0];
		}
	}
}
$aTerms = get_terms($aQueryArgs);
if (empty($aTerms) || is_wp_error($aTerms)) {
	return '';
}

$aListing = [];
$aTermIDs = [];
foreach ($aTerms as $oTerm) {
	$aTermIDs[] = $oTerm->term_id;
	$aPostFeaturedImgs = GetSettings::getPostFeaturedImgsByTerm($oTerm->term_id, $atts['taxonomy']);
	$aTerm = get_object_vars($oTerm);
	$featuredImgID = GetSettings::getTermMeta($oTerm->term_id, 'featured_image_id');
	if (!empty($featuredImgID)) {
		$img = wp_get_attachment_image_url($featuredImgID, 'large');
		$aTerm['featuredImg'] = $img ? $img : WILCITY_APP_IMG_PLACEHOLDER;
	} else {
		$aTerm['featuredImg'] = GetSettings::getTermMeta($oTerm->term_id, 'featured_image');
	}
	$aBelongsTo = GetSettings::getTermMeta($oTerm->term_id, 'belongs_to');
	$aListing[] = [
		'oTerm'            => $aTerm,
		'aPostFeaturedImg' => $aPostFeaturedImgs,
		'oCount'           => [
			'number' => $oTerm->count,
			'text'   => $oTerm->count > 1
				? esc_html__('Listings', 'wilcity-shortcodes')
				:
				esc_html__('Listing', 'wilcity-shortcodes')
		],
		'oIcon'            => WilokeHelpers::getTermOriginalIcon($oTerm),
		'restAPI'          => add_query_arg(
			$aRestAPIParm, empty($aBelongsTo) || !in_array('event', $aBelongsTo) ? 'list/listings' : 'events'
		)
	];
}
$aResponse['listing'] = $aListing;
$aParsedOrderBy = is_array($aAtts['orderby_options'])
	? $aAtts['orderby_options']
	: explode(
		',', $aAtts['orderby_options']
	);
$aOrderByOptions = [];

if (count($aParsedOrderBy) > 1) {
	$aAllOrderByType = wilcityShortcodesRepository()->get('configs/orderby', true)->sub('listing');
	foreach ($aParsedOrderBy as $orderby) {
		$aOrderByOptions[] = [
			'id'    => $orderby,
			'label' => $aAllOrderByType[$orderby]
		];
	}
}

$aResponse['orderby'] = $aOrderByOptions;

echo '%SC%' . base64_encode(json_encode(
		[
			'oSettings' => AppHelpers::removeUnnecessaryParamOnApp($aAtts),
			'TYPE'      => $atts['TYPE'],
			'oResults'  => $aResponse,
		]
	)) . '%SC%';
return '';
