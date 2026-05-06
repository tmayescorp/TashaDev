<?php
add_shortcode('wilcity_vc_term_boxes', 'wilcityVCTermBoxes');
function wilcityVCTermBoxes($atts)
{
    $atts = shortcode_atts(
      [
        'TYPE'                       => 'TERM_BOXES',
        'heading'                    => '',
        'heading_color'              => '#252c41',
        'desc'                       => '',
        'desc_color'                 => '#70778b',
        'header_desc_text_align'     => 'wil-text-center',
        'maximum_posts_on_lg_screen' => 'col-lg-3',
        'maximum_posts_on_md_screen' => 'col-md-4',
        'maximum_posts_on_sm_screen' => 'col-sm-6',
        'taxonomy'                   => 'listing_cat',
        'listing_cats'               => '',
        'listing_locations'          => '',
        'listing_tags'               => '',
        'orderby'                    => 'count',
        'toggle_box_gradient'        => 'enable',
        'left_gradient_color'        => '#006bf7',
        'right_gradient_color'       => '#f06292',
        'order'                      => 'DESC',
        'is_show_parent_only'        => 'no',
        'is_hide_empty'              => 'no',
        'number'                     => '',
        'parseCategories'            => ['term'],
        'group'                      => 'term',
        'isRequiredPostType'         => 'yes',
        'description'                => '',
        'description_color'          => '',
        'term_redirect'              => 'search_page',
        'post_type'                  => '',
        'listing_cat'                => '',
        'listing_location'           => '',
        'maximum_posts_on_xs_screen' => 'col-xs-6',
        'css'                        => '',
        'extra_class'                => ''
      ],
      $atts
    );
    $atts = apply_filters('wilcity/vc/parse_sc_atts', $atts);
    
    ob_start();
    wilcity_sc_render_term_boxes($atts);
    $content = ob_get_contents();
    ob_end_clean();
    
    return $content;
}
