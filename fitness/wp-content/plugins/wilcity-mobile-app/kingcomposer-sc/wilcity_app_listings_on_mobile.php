<?php

use WILCITY_APP\Helpers\AppHelpers;
use \WILCITY_SC\SCHelpers;
use WilokeListingTools\Framework\Helpers\WPML;

$atts = shortcode_atts(
	[
		'TYPE'              => 'LISTINGS',
		'post_type'         => 'listing',
		'orderby'           => '',
		'posts_per_page'    => 6,
		'listing_cats'      => '',
		'listing_locations' => '',
		'listing_tags'      => '',
		'bg_color'          => '#ffffff',
		'style'             => 'grid'
	],
	$atts
);
if (!trait_exists('WILCITY_APP\Controllers\JsonSkeleton')) {
	return '';
}

$aArgs = SCHelpers::parseArgs($atts);
$aArgs = WPML::addFilterLanguagePostArgs($aArgs);

$aResponse = apply_filters(
	'wilcity-mobile-app/filter/kingcomposer-sc/wilcity_app_listing_on_mobile/response',
	[],
	$aArgs,
	$atts
);

if (!has_filter('wilcity-mobile-app/filter/kingcomposer-sc/wilcity_app_listing_on_mobile/response')) {
	$query = new WP_Query($aArgs);
	if (!$query->have_posts()) {
		wp_reset_postdata();

		return '';
	}
	$aResponse = [];
	while ($query->have_posts()) {
		$query->the_post();
		$aListing = apply_filters('wilcity/mobile/render_listings_on_mobile', $atts, $query->post);
		$aResponse[] = $aListing;
	}
	wp_reset_postdata();
}

if (empty($aResponse)) {
	return '';
}

echo '%SC%' . base64_encode(json_encode(
		[
			'oSettings' => AppHelpers::removeUnnecessaryParamOnApp($atts),
			'TYPE'      => $atts['TYPE'],
			'oResults'  => $aResponse,
			'oViewMore' => AppHelpers::getViewMoreArgs($atts),
		]
	)) . '%SC%';

return '';
