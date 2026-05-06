<?php

use WilokeListingTools\Framework\Helpers\GetSettings;
use Wilcity\Map\FactoryMap;
use WILCITY_SC\SCHelpers;
use WilokeListingTools\Frontend\SingleListing;

add_shortcode('wilcity_sidebar_googlemap', 'wicitySidebarGoogleMap');
function wicitySidebarGoogleMap($aArgs)
{
    $aAtts = SCHelpers::decodeAtts($aArgs['atts']);
    $aAtts = wp_parse_args(
      $aAtts,
      [
        'name' => '',
        'icon' => 'la la-map-marker'
      ]
    );

    if (!empty($aArgs['post_id'])) {
        $post = get_post($aArgs['post_id']);
    } else {
        global $post;
    }

    if (isset($aAtts['isMobile'])) {
        return apply_filters('wilcity/mobile/sidebar/googlemap', '', $post, $aAtts);
    }

    $aLatLng = GetSettings::getLatLng($post->ID);

    if (empty($aLatLng) || ($aLatLng['lat'] == $aLatLng['lng'])) {
        return '';
    }
    $oMap         = new FactoryMap();
    $aMapSettings = $oMap->set()->getAllConfig();

    ob_start();
    ?>
    <div class="content-box_module__333d9">
        <header class="content-box_header__xPnGx clearfix">
            <div class="wil-float-left">
                <h4 class="content-box_title__1gBHS"><i class="<?php echo esc_attr($aAtts['icon']); ?>"></i>
                    <span><?php echo esc_html($aAtts['name']); ?></span>
                </h4>
            </div>
        </header>
        <div class="content-box_body__3tSRB pos-r">
            <div class="pos-r" style="background-color:#f3f3f6; max-height: 400px;">
                <component marker-url="<?php echo esc_url(SingleListing::getMapIcon($post)); ?>"
                           :is-multiple="false"
                           access-token="<?php echo esc_attr($aMapSettings['accessToken']); ?>"
                           map-style="<?php echo esc_attr($aMapSettings['style']); ?>"
                           :max-zoom="<?php echo abs($aMapSettings['singleMaxZoom']); ?>"
                           :min-zoom="<?php echo abs($aMapSettings['singleMinimumZoom']); ?>"
                           :default-zoom="<?php echo abs($aMapSettings['singleDefaultZoom']); ?>"
                           listing-ggmap-url="<?php echo esc_url(GetSettings::getAddress($post->ID, true)); ?>"
                           wrapper-classes="wil-map-show"
                           style="height: 100%"
                           :lat-lng='<?php echo json_encode($aLatLng); ?>'
                           language="<?php echo esc_attr(WilokeThemeOptions::getOptionDetail('general_google_language', 'en')); ?>"
                           is="<?php echo $aMapSettings['vueComponent']; ?>"
                >
                </component>
            </div>
        </div>
    </div>
    <?php
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
}
