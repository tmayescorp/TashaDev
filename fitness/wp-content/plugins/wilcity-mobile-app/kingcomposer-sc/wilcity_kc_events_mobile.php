<?php

use WILCITY_SC\SCHelpers;
use WilokeListingTools\Framework\Helpers\WPML;

$atts                     = shortcode_atts(
  [
    'post_type'         => 'event',
    'orderby'           => 'post_date',
    'order'             => 'DESC',
    'img_size'          => 'medium',
    'posts_per_page'    => 6,
    'listing_tags'      => '',
    'listing_cats'      => '',
    'listing_locations' => '',
    'style'             => 'grid',
    'bg_color'          => '#ffffff'
  ],
  $atts
);
$aArgs                    = SCHelpers::parseArgs($atts);
$aArgs['isAppEventQuery'] = true;
$aArgs                    = \WilokeListingTools\Framework\Helpers\QueryHelper::buildQueryArgs($aArgs);
$aArgs = WPML::addFilterLanguagePostArgs($aArgs);
$query = new WP_Query($aArgs);
if (!$query->have_posts()) {
    wp_reset_postdata();
    
    return '';
}
$aResponse = [];
while ($query->have_posts()) {
    $query->the_post();
    $aResponse[] = apply_filters('wilcity/mobile/render_event_on_mobile', $atts, $query->post);
}
wp_reset_postdata();

echo '%SC%'.base64_encode(json_encode(
    [
      'oSettings' => \WILCITY_APP\Helpers\AppHelpers::removeUnnecessaryParamOnApp($atts),
      'TYPE'      => 'EVENTS',
      'oResults'  => $aResponse
    ]
  )).'%SC%';

return '';
