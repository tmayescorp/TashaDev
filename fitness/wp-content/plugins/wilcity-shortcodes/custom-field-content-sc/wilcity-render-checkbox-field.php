<?php

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\General;
use WILCITY_SC\SCHelpers;

function wilcityRenderCheckboxField($aAtts)
{
	$aAtts = shortcode_atts(
		[
			'key'             => '',
			'is_grid'         => 'no',
			'is_mobile'       => '',
			'post_id'         => '',
			'description'     => '',
			'extra_class'     => '',
			'wrapper_classes' => 'col-md-6 col-lg-4',
			'title'           => '',
			'print_unchecked' => 'yes',
			'return_format'   => 'html'
		],
		$aAtts
	);

	if (!empty($aAtts['post_id'])) {
		$post = get_post($aAtts['post_id']);
	} else {
		$post = SCHelpers::getPost();
	}

	if (apply_filters(
		'wilcity-shortcodes/default-sc/wilcity-render-checkbox-field/hide-item',
		false,
		$aAtts,
		$post->ID
	)) {
		return '';
	}

	if (!GetSettings::isPlanAvailableInListing($post->ID, $aAtts['key'])) {
		return '';
	}

	if (empty($aAtts['key']) || !class_exists('WilokeListingTools\Framework\Helpers\GetSettings') || empty($post)) {
		return '';
	}

	$aSettings = General::findField($post->post_type, $aAtts['key']);

	if (empty($aSettings)) {
		return '';
	}
	$options = $aSettings['fieldGroups']['settings']['options'];
	if (!empty($options)) {
		$aOptions = General::parseSelectFieldOptions($options, 'full');
	}

	if (empty($aOptions)) {
		return '';
	}

	$aRawValues = GetSettings::getPostMeta($post->ID, 'custom_' . $aAtts['key']);
	if (empty($aRawValues)) {
		return '';
	}

	$aRawValues = is_array($aRawValues) ? $aRawValues : explode(',', $aRawValues);

	foreach ($aRawValues as $index => $rawValue) {
		$pos = strpos($rawValue, '|');
		if ($pos != false) {
			$aRawValues[$index] = explode('|', $rawValue)[0];
		}
	}

	$aValues = [];
	foreach ($aOptions as $aOption) {
		if (!in_array($aOption['key'], $aRawValues) && $aAtts['print_unchecked'] == 'no') {
			continue;
		}

		if ($aAtts['is_mobile'] == 'yes') {
			$aItem = array_merge($aOption, ['type' => 'icon']);
		} else {
			$aItem = [
				'name'  => $aOption['name'],
				'oIcon' => array_merge($aOption, ['type' => 'icon'])
			];
		}

		$aItem['unChecked'] = in_array($aOption['key'], $aRawValues) ? 'no' : 'yes';
		$aValues[] = $aItem;
	}

	if (empty($aValues)) {
		return false;
	}

	if ($aAtts['is_mobile'] == 'yes' || $aAtts['is_grid'] == 'yes' || $aAtts['return_format'] === 'json') {
		return json_encode($aValues);
	}

	$class = $aAtts['key'];
	if (!empty($aAtts['extra_class'])) {
		$class .= ' ' . $aAtts['extra_class'];
	}

	$aAtts = wp_parse_args($aAtts, [
		'options'     => json_encode($aValues),
		'extra_class' => $class
	]);
	ob_start();
	wilcityListFeaturesSC($aAtts);
	$content = ob_get_contents();
	ob_end_clean();

	return $content;
}

add_shortcode('wilcity_render_checkbox2_field', 'wilcityRenderCheckboxField');
add_shortcode('wilcity_render_multiple-checkbox_field', 'wilcityRenderCheckboxField');
