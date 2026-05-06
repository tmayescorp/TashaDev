<?php
function wilcityVCMasonryTermBoxes($atts)
{
    $atts = shortcode_atts(
      [
        'TYPE'                   => 'MASONRY_TERM_BOXES',
        'heading'                => '',
        'heading_color'          => '#252c41',
        'desc'                   => '',
        'col_gap'                => 20,
        'desc_color'             => '#70778b',
        'header_desc_text_align' => 'wil-text-center',
        'taxonomy'               => 'listing_cat',
        'listing_cats'           => '',
        'listing_locations'      => '',
        'image_size'             => 'wilcity_560x300',
        'listing_tags'           => '',
        'orderby'                => 'count',
        'number'                 => '',
        'order'                  => 'DESC',
        'is_show_parent_only'    => 'no',
        'is_hide_empty'          => 'no',
        'parseCategories'        => ['term'],
        'group'                  => 'term',
        'isRequiredPostType'     => 'yes',
        '_id'                    => '',
        'description'            => '',
        'description_color'      => '',
        'term_redirect'          => 'search_page',
        'post_type'              => '',
        'listing_cat'            => '',
        'listing_location'       => '',
        'css'                    => '',
        'extra_class'            => ''
      ],
      $atts
    );
    
    $atts = apply_filters('wilcity/vc/parse_sc_atts', $atts);
    
    ob_start();
    wilcity_render_term_masonry_items($atts);
    $content = ob_get_contents();
    ob_end_clean();
    
    return $content;
}

add_shortcode('wilcity_vc_masonry_term_boxes', 'wilcityVCMasonryTermBoxes');
