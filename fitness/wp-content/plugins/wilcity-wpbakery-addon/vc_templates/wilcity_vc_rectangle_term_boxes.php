<?php
function wilcityVCRectangleTermBoxes($atts)
{
    $atts = shortcode_atts(
      [
        'TYPE'                       => 'RECTANGLE_TERM_BOXES',
        'heading'                    => '',
        'heading_color'              => '',
        'desc'                       => '',
        'desc_color'                 => '',
        'header_desc_text_align'     => '',
        'maximum_posts_on_lg_screen' => 'wil-col-5 col-lg-2',
        'maximum_posts_on_md_screen' => 'col-md-3',
        'maximum_posts_on_sm_screen' => 'col-md-12',
        'taxonomy'                   => 'listing_cat',
        'listing_cats'               => '',
        'is_show_parent_only'        => 'no',
        'listing_locations'          => '',
        'listing_tags'               => '',
        'is_hide_empty'              => 'no',
        'image_size'                 => 'image_size',
        'orderby'                    => 'count',
        'order'                      => 'DESC',
        'number'                     => 4,
        'parseCategories'            => ['term'],
        'group'                      => 'term',
        'isRequiredPostType'         => 'yes',
        'description'                => '',
        'description_color'          => '',
        'items_per_row'              => 'col-lg-3',
        'maximum_posts_on_xs_screen' => 'col-xs-6',
        'term_redirect'              => 'search_page',
        'post_type'                  => '',
        'listing_cat'                => '',
        'listing_location'           => '',
        'toggle_box_gradient'        => 'enable',
        'left_gradient_color'        => '#006bf7',
        'right_gradient_color'       => '#f06292',
        'extra_class'                => '',
        'css_custom'                 => ''
      ],
      $atts
    );
    
    $atts = apply_filters('wilcity/vc/parse_sc_atts', $atts);
    
    ob_start();
    wilcity_render_rectangle_term_boxes($atts);
    $content = ob_get_contents();
    ob_end_clean();
    
    return $content;
}

add_shortcode('wilcity_vc_rectangle_term_boxes', 'wilcityVCRectangleTermBoxes');
