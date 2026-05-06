<?php

namespace WilcityWPBakeryAddon\Controllers;

use WP_Query;

class FilterVcController
{
	private $aFilterTaxonomySC
		= [
			'hero',
			'new_grid',
			'term_boxes',
			'listings_tabs',
			'masonry_term_boxes',
			'terms_slider',
			'rectangle_term_boxes',
			'modern_term_boxes',
			'listings_slider',
			'event_slider',
			'events_slider',
			'events_grid',
			'grid',
			'listing_grip_layout'
		];

	public function __construct()
	{
		$this->filterTaxonomyAutoComplete();
		add_filter(
			'vc_autocomplete_wilcity_vc_contact_us_contact_form_7_callback',
			[$this, 'searchContactForm'],
			10,
			3
		);
		add_filter(
			'vc_autocomplete_wilcity_vc_contact_us_contact_form_7_render',
			[$this, 'renderContactForm7Selected'], 10,
			2
		);
	}

	public function renderContactForm7Selected($currentVal, $aParamSettings)
	{
		if (empty($currentVal) || empty($currentVal['value'])) {
			return false;
		}

		return [
			'value' => $currentVal['value'],
			'label' => get_the_title($currentVal['value'])
		];
	}

	public function searchContactForm($s, $tag, $param_name)
	{
		$query = new WP_Query([
			's'              => $s,
			'post_type'      => 'wpcf7_contact_form',
			'posts_per_page' => 100
		]);

		if (!$query->have_posts()) {
			return false;
		}

		$aPosts = [];
		while ($query->have_posts()) {
			$query->the_post();
			$aPosts[] = [
				'value' => $query->post->ID,
				'label' => $query->post->post_title
			];
		}

		return $aPosts;
	}

	public function filterRenderTermName($currentVal, $aParamSettings)
	{

		if (empty($currentVal) || empty($currentVal['value'])) {
			return false;
		}

		$taxonomy = substr($aParamSettings['param_name'], 0, strlen($aParamSettings['param_name']) - 1);

		$oTerm = get_term_by('id', absint($currentVal['value']), $taxonomy);

		if (empty($oTerm) || is_wp_error($oTerm)) {
			return false;
		}

		return [
			'value' => $oTerm->term_id,
			'label' => $oTerm->name,
		];
	}

	public function handleFilterTaxonomy($query, $tag, $param_name)
	{
		global $wpdb;
		$taxonomy = trim($param_name, 's');

		$taxonomyTbl = $wpdb->term_taxonomy;
		$termsTbl = $wpdb->terms;

		$sql
			= "SELECT $termsTbl.term_id, $termsTbl.name FROM $termsTbl LEFT JOIN $taxonomyTbl ON ($termsTbl.term_id=$taxonomyTbl.term_id) WHERE $termsTbl.name LIKE '%" .
			esc_sql(trim($query)) . "%' AND $taxonomyTbl.taxonomy=%s LIMIT 20";

		$aRawResults = $wpdb->get_results(
			$wpdb->prepare(
				$sql,
				$taxonomy
			)
		);

		if (empty($aRawResults)) {
			return false;
		}

		$aResults = [];
		foreach ($aRawResults as $oTerm) {
			$aResults[] = [
				'label' => $oTerm->name,
				'value' => $oTerm->term_id
			];
		}

		return $aResults;
	}

	private function filterTaxonomyAutoComplete()
	{
		foreach ($this->aFilterTaxonomySC as $sc) {
			add_filter(
				'vc_autocomplete_wilcity_vc_' . $sc . '_listing_locations_callback',
				[$this, 'handleFilterTaxonomy'], 10,
				3
			);

			add_filter(
				'vc_autocomplete_wilcity_vc_' . $sc . '_listing_cats_callback',
				[$this, 'handleFilterTaxonomy'],
				10,
				3);

			add_filter(
				'vc_autocomplete_wilcity_vc_' . $sc . '_listing_tags_callback',
				[$this, 'handleFilterTaxonomy'],
				10,
				3
			);

			add_filter(
				'vc_autocomplete_wilcity_vc_' . $sc . '_listing_location_callback',
				[$this, 'handleFilterTaxonomy'], 10,
				3
			);

			add_filter(
				'vc_autocomplete_wilcity_vc_' . $sc . '_listing_cat_callback',
				[$this, 'handleFilterTaxonomy'],
				10,
				3);

			add_filter(
				'vc_autocomplete_wilcity_vc_' . $sc . '_listing_tag_callback',
				[$this, 'handleFilterTaxonomy'],
				10,
				3
			);

			add_filter('vc_autocomplete_wilcity_vc_' . $sc . '_listing_locations_render',
				[$this, 'filterRenderTermName'], 10,
				2);
			add_filter('vc_autocomplete_wilcity_vc_' . $sc . '_listing_cats_render', [$this, 'filterRenderTermName'],
				10, 2);
			add_filter('vc_autocomplete_wilcity_vc_' . $sc . '_listing_tags_render', [$this, 'filterRenderTermName'],
				10, 2);

			add_filter('vc_autocomplete_wilcity_vc_' . $sc . '_listing_location_render',
				[$this, 'filterRenderTermName'], 10,
				2);
			add_filter('vc_autocomplete_wilcity_vc_' . $sc . '_listing_cat_render', [$this, 'filterRenderTermName'],
				10, 2);
			add_filter('vc_autocomplete_wilcity_vc_' . $sc . '_listing_tag_render', [$this, 'filterRenderTermName'],
				10, 2);
		}
	}
}
