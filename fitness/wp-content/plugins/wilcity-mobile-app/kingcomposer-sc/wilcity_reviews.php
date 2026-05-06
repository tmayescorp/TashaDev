<?php
$atts = shortcode_atts(
	[
		'style'             => 'grid',
		'number_of_reviews' => 4,
		'items_per_row'     => 2,
		'orderby'           => 'top_liked',
		'review_ids'        => '',
		'offset'            => 0,
		'bg_color'          => '#ffffff'
	],
	$atts
);
$aResponse = apply_filters('wilcity/wilcity-mobile-app/filter/wilcity-reviews', [], $atts);

echo '%SC%' . base64_encode(json_encode(
		[
			'oSettings' => $atts,
			'TYPE'      => 'REVIEWS',
			'oResults'  => $aResponse
		]
	)) . '%SC%';

return '';
