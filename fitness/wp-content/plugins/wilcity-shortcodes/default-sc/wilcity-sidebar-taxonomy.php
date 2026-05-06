<?php

use WILCITY_SC\SCHelpers;

add_shortcode('wilcity_sidebar_taxonomy', 'wilcitySidebarTaxonomy');

function wilcitySidebarTaxonomy($aArgs)
{
    $aAtts = SCHelpers::decodeAtts($aArgs['atts']);
    
    $aAtts = wp_parse_args(
      $aAtts,
      [
        'name'               => '',
        'icon'               => 'la la-sitemap',
        'postID'             => '',
        'taxonomy'           => $aAtts['taxonomy'],
        'item_wrapper'       => 'col-sm-6 col-sm-6-clear',
        'taxonomy_post_type' => 'flexible'
      ]
    );
    
    global $post;
    $aAtts['postID'] = $post->ID;
    
    if (isset($aAtts['isMobile'])) {
        return apply_filters('wilcity/mobile/sidebar/taxonomy', $post, $aAtts);
    }
    
    ob_start();

    echo do_shortcode("[wilcity_sidebar_terms_box name='".$aAtts['name']."' item_wrapper='".$aAtts['item_wrapper']."' atts='".json_encode($aAtts)."' /]");

    $content = ob_get_contents();
    ob_end_clean();
    
    return $content;
}
