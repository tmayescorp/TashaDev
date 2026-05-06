<?php
add_shortcode('wilcity_vc_terms_slider', 'wilcityVcTermsSlider');
function wilcityVcTermsSlider($atts){
	$atts = shortcode_atts(
		array(
			'TYPE'                          => 'TERMS_SLIDER',
			'heading'                       => '',
			'heading_color'                 => '#252c41',
			'desc'                          => '',
			'desc_color'                    => '#70778b',
			'header_desc_text_align'        => 'wil-text-center',
			'items_on_lg_screen'            => 4,
			'items_on_md_screen'            => 4,
            'items_on_sm_screen'            => 2,
            'term_redirect'                 => 'search_page',
			'taxonomy'                      => 'listing_cat',
			'listing_cats'                  => '',
			'listing_locations'             => '',
            'listing_tags'                  => '',
            'post_type'                     => '',
			'css'                           => '',
			'extra_class'                   => ''
		),
        $atts
	);
	$atts = apply_filters('wilcity/vc/parse_sc_atts', $atts);

	ob_start();
	wilcity_render_terms_slider($atts);
	$content = ob_get_contents();
	ob_end_clean();
	return $content;
}