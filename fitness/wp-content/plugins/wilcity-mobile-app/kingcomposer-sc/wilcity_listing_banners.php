<?php

use WILCITY_APP\Helpers\AppHelpers;
use WILCITY_SC\SCHelpers;

$atts = shortcode_atts(
  [
    'TYPE'           => 'LISTING_BANNERS',
    'banners'        => '',
    'bg_color'       => '#ffffff',
    'slide_interval' => 3000
  ],
  $atts
);

if (empty($atts['banners'])) {
    return '';
}

$aBanners = $atts['banners'];
unset($atts['banners']);

foreach ($aBanners as $oBanner) {
    $aParsePostID    = explode(':', $oBanner->postID);
    $oBanner->postID = absint($aParsePostID[0]);
    $post            = get_post($oBanner->postID);
    if (empty($post) || is_wp_error($post)) {
        continue;
    }

    $oBanner->oListing = apply_filters('wilcity/mobile/render_listings_on_mobile', $atts, $post);
    $atts['banners'][] = $oBanner;
}

$atts['slider_interval'] = empty($atts['slider_interval']) ? 3000 : abs($atts['slider_interval']);

$aAtts = SCHelpers::mergeIsAppRenderingAttr($atts);
echo '%SC%'.json_encode(AppHelpers::removeUnnecessaryParamOnApp($aAtts)).'%SC%';
