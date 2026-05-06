<?php
$atts = shortcode_atts(
	[
		'TYPE'              => 'POSTS',
		'heading'           => '',
		'heading_color'     => '',
		'description'       => '',
		'description_color' => '',
		'bg_type'           => 'image',
		'overlay_color'     => '',
		'image_bg'          => '',
		'slider_bg'         => '',
		'bg_color'          => '#ffffff',
		'extra_class'       => '',
		'posts_per_page'    => '',
		'order_by'          => '',
		'order'             => '',
	],
	$atts
);

$aAtts = \WILCITY_SC\SCHelpers::mergeIsAppRenderingAttr($atts);

if (empty($aAtts['overlay_color'])) {
	unset($aAtts['overlay_color']);
}

if (empty($aAtts['heading_color'])) {
	unset($aAtts['heading_color']);
}

if (empty($aAtts['description_color'])) {
	unset($aAtts['description_color']);
}

$aResults = [];
$aResponse = \WILCITY_APP\Helpers\HsBlog::fetchPosts($aAtts);
if ($aResponse['status'] == 'success') {
	$aResults = $aResponse['oResults'];
}

echo '%SC%' . base64_encode(json_encode(
		[
			'oSettings' => \WILCITY_APP\Helpers\AppHelpers::removeUnnecessaryParamOnApp($atts),
			'TYPE'      => $atts['TYPE'],
			'oResults'  => $aResults,
		]
	)) . '%SC%';
