<?php

use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Frontend\PriceRange;
use WilokeListingTools\Framework\Helpers\GetSettings;
use \WilokeListingTools\Controllers\SearchFormController;
use \WILCITY_SC\SCHelpers;

add_shortcode('wilcity_sidebar_related_listings', 'wilcitySidebarRelatedListings');
function wilcitySidebarRelatedListings($aArgs)
{

    global $post;
    $aAtts = is_array($aArgs['atts']) ? $aArgs['atts'] : SCHelpers::decodeAtts($aArgs['atts']);
    $baseKey = '';
    if (isset($aAtts['baseKey'])) {
        $baseKey = $aAtts['baseKey'];
    } else if (isset($aAtts['key'])) {
        $baseKey = $aAtts['key'];
    }

    $aAtts = wp_parse_args(
      $aAtts,
      [
        'name'        => 'Related Listings',
        'icon'        => 'la la-qq',
        'style'       => 'slider',
        'conditional' => ''
      ]
    );

    if (isset($aAtts['isMobile'])) {
        return apply_filters('wilcity/mobile/sidebar/related_listings', '', $post, $aAtts);
    }

    $isFalse = false;

    $aAdditionalArgs = [];
    switch ($aAtts['conditional']) {
        case 'listing_location':
        case 'listing_category':
        case 'listing_tag':
            $taxonomy = $aAtts['conditional'];
            if ($taxonomy == 'listing_category') {
                $taxonomy = 'listing_cat';
            }
            $aTerms = GetSettings::getPostTerms($post->ID, $taxonomy);

            if (empty($aTerms)) {
                $isFalse = true;
            } else {
                $aLocations = [];
                foreach ($aTerms as $oRawLocation) {
                    $aLocations[] = $oRawLocation->term_id;
                }

                $aAdditionalArgs['tax_query'] = [
                  [
                    'taxonomy' => $taxonomy,
                    'field'    => 'term_id',
                    'terms'    => $aLocations
                  ]
                ];
            }
            break;
        case 'google_address':
            $aLatLng = GetSettings::getLatLng($post->ID);
            if (empty($aLatLng)) {
                $isFalse = true;
            } else {
                $aAtts['oAddress']           = json_encode($aLatLng);
            }
            break;
        case 'everywhere':
            $aAtts['aArgs']['post_type'] = GetSettings::getAllDirectoryTypes(true);
            break;
        default:
            break;
    }

    if ($isFalse) {
        return '';
    }

    if ($baseKey == 'relatedListings') {
        $aAdditionalArgs['post_type'] = $post->post_type;
    }

    $postsPerPage             = $aAtts['postsPerPage'] ?? 30;
    $aAtts['aAdditionalArgs'] = empty($aAtts['aAdditionalArgs']) ? $aAdditionalArgs :
        array_merge($aAtts['aAdditionalArgs'], $aAdditionalArgs);
    $aAtts['postsPerPage']    = $postsPerPage;
    if (isset($aAtts['orderby']) && !empty($aAtts['orderby'])) {
        if (in_array($aAtts['orderby'], ['best_rated', 'best_viewed', 'recommended'])) {
            $aAtts[$aAtts['orderby']] = 'yes';
        }
    }

    $aArgs = SearchFormController::buildQueryArgs($aAtts);

    if (!empty($aAtts['aAdditionalArgs'])) {
        $aArgs = array_merge((array)$aArgs, $aAtts['aAdditionalArgs']);
        unset($aAtts['aAdditionalArgs']);
    }

    if (isset($aArgs['isIgnorePostNotIn']) && $aArgs['isIgnorePostNotIn'] == 'yes') {
        unset($aArgs['postNotIn']);
    }

    $aArgs['post__not_in'] =
      isset($aArgs['postNotIn']) && is_array($aArgs['postNotIn']) ? array_merge($aArgs['postNotIn'], [$post->ID]) :
        [$post->ID];

    $aAtts['aArgs'] = $aArgs;
    ob_start();
    switch ($aAtts['style']) {
        case 'list':
            echo wilcityRenderSidebarList([
              'atts' => $aAtts
            ]);
            break;
        case 'grid':
            echo wilcityRenderSidebarGrid([
              'atts' => $aAtts
            ]);
            break;
        case 'slider':
            echo wilcityRenderSidebarSlider([
              'atts' => $aAtts
            ]);
            break;
    }
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
}
