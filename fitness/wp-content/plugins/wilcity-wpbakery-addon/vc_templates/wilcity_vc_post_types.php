<?php

function wilcityVcPostTypes($atts){
	$atts = shortcode_atts(
		array(
			'heading'                       => '',
            'heading_color'                 => '',
            'desc'                          => '',
            'desc_color'                    => '',
            'header_desc_text_align'        => 'wil-text-center',
            'post_types'                    => '',
            'maximum_posts_on_lg_screen'    => 'wil-col-5 col-lg-2',
			'maximum_posts_on_md_screen'    => 'col-md-3',
			'maximum_posts_on_sm_screen'    => 'col-sm-12',
			'css'                           => '',
			'extra_class'                   => ''
		),
		$atts
	);

	$atts = apply_filters('wilcity/vc/parse_sc_atts', $atts);
	ob_start();
	wilcity_render_post_types($atts);
	$content = ob_get_contents();
	ob_end_clean();
	return $content;
}

add_shortcode('wilcity_vc_post_types', 'wilcityVcPostTypes');