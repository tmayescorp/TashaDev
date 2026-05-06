<?php

use WILCITY_SC\SCHelpers;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\General;

function wilcityRenderTexareatField($aAtts){
	$aAtts = shortcode_atts(
		array(
			'key'         => '',
			'is_mobile'   => '',
			'post_id'     => '',
			'description' => '',
			'title'       => '',
			'extra_class' => '',
			'title_tag'   => 'strong'
		),
		$aAtts
	);
	if ( !empty($aAtts['post_id']) ){
		$post = get_post($aAtts['post_id']);
	}else{
		$post = SCHelpers::getPost();
	}

	if (apply_filters(
		'wilcity-shortcodes/default-sc/wilcity-render-listing-textarea-field/hide-item',
		false,
		$aAtts,
		$post->ID
	)) {
		return '';
	}

	if ( empty($aAtts['key']) || !class_exists('WilokeListingTools\Framework\Helpers\GetSettings') || empty($post)){
		return '';
	}


	if ( !GetSettings::isPlanAvailableInListing($post->ID, $aAtts['key']) ){
		return '';
	}
	$content = GetSettings::getPostMeta($post->ID, 'custom_'.$aAtts['key']);
	$content = apply_filters('wilcity_shortcode/wilcity_render_textarea_field/'. $post->post_type .'/'. $aAtts['key'], $content, $aAtts);

	if ( empty($content) ){
		return '';
	}

	$class = $aAtts['key'] . ' wil-wrapper-textarea-field';
	if ( !empty($aAtts['extra_class']) ){
		$class .= ' ' . $aAtts['extra_class'];
	}

	if ( !empty($aAtts['title']) ){
		$content = '<'.$aAtts['title_tag']. ' class="'.apply_filters('wilcity/filter/class-prefix', 'wilcity-textarea-sc-title').'">' . $aAtts['title'] . '</'.$aAtts['title_tag'].'>: ' . nl2br(do_shortcode($content));
	}

	return '<div class="'.$class.'">' . do_shortcode(nl2br($content)) . '</div>';
}

add_shortcode('wilcity_render_textarea_field', 'wilcityRenderTexareatField');
