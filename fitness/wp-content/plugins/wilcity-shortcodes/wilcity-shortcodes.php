<?php
/*
 * Plugin Name: WilCity Shortcodes
 * Author: wiloke
 * Plugin URI: https://wiloke.com
 * Author URI: https://wiloke.com
 * Version: 1.5.4
 * Text Domain: wilcity-shortcodes
 * Domain Path: /languages/
 */


use WILCITY_SC\RegisterSC\RegisterKCShortcodes;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WILCITY_SC\Controllers\KCFrontEndController;



add_action('wiloke-listing-tools/run-extension', function () {


	define('WILCITY_SC_VERSION', '1.5.4');
	define('WILCITY_SC_CATEGORY', 'Wilcity');
	define('WILCITY_SC_DOMAIN', 'wilcity-shortcodes');
	define('WILCITY_SC_URL', plugin_dir_url(__FILE__));
	define('WILCITY_SC_DIR', plugin_dir_path(__FILE__));

	require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

	add_action('init', 'wilcity_sc_load_textdomain');
	function wilcity_sc_load_textdomain()
	{
		load_plugin_textdomain('wilcity-shortcodes', false, basename(dirname(__FILE__)) . '/languages');
	}

	function wilcitySCElClass($aAtts)
	{
		$aClasses = [];
		if (defined('KC_VERSION')) {
			$aClasses = apply_filters('kc-el-class', $aAtts);
		}

		if (function_exists('vc_shortcode_custom_css_class')) {
			$aAtts['css'] = isset($aAtts['css']) ? $aAtts['css'] : '';
			$aClasses = [vc_shortcode_custom_css_class($aAtts['css'], ' ')];
		}

		return $aClasses;
	}

	add_filter('wilcity-el-class', 'wilcitySCElClass');

	function wiloke_kc_process_tab_title($matches)
	{

		if (!empty($matches[0])) {
			global $wilcityHasActivatedTab, $wilTabId;

			if (empty($wilcityHasActivatedTab)) {
				$tabStatus = 'active';
				$wilcityHasActivatedTab = 'yes';
			} else {
				$tabStatus = '';
			}

			$tab_atts = shortcode_parse_atts($matches[0]);

			$title = '';
			$adv_title = '';
			$tab_id = '';
			if (isset($tab_atts['title'])) {
				$title = $tab_atts['title'];
			}

			if (isset($tab_atts['tab_id'])) {
				$tab_id = strtolower(trim($tab_atts['title']));
				$tab_id = str_replace(['&', 'amp;'], ['', ''], $tab_id);
				$tab_id = preg_replace_callback('/\s+/', function ($aMatched) {
					return '-';
				}, $tab_id);
				$tab_atts['tab_id'] = $tab_id;
			}

			if (isset($tab_atts['advanced']) && $tab_atts['advanced'] === 'yes') {
				if (isset($tab_atts['adv_title']) && !empty($tab_atts['adv_title'])) {
					$adv_title = base64_decode($tab_atts['adv_title']);

					preg_match('/(href=[\"\']#)([^"].+)([\"|\'])/', $adv_title, $aMatched);

					if (isset($aMatched[2])) {
						$tab_id = $aMatched[2];
					}
				}

				$icon = $icon_class =
				$image = $image_id = $image_url = $image_thumbnail = $image_medium = $image_large = $image_full = '';

				if (isset($tab_atts['adv_icon']) && !empty($tab_atts['adv_icon'])) {
					$icon_class = $tab_atts['adv_icon'];
					$icon = '<i class="' . $tab_atts['adv_icon'] . '"></i>';
				}

				if (isset($tab_atts['adv_image']) && !empty($tab_atts['adv_image'])) {
					$image_id = $tab_atts['adv_image'];
					$image_url = wp_get_attachment_image_src($image_id, 'full');
					$image_medium = wp_get_attachment_image_src($image_id, 'medium');
					$image_large = wp_get_attachment_image_src($image_id, 'large');
					$image_thumbnail = wp_get_attachment_image_src($image_id, 'thumbnail');

					if (!empty($image_url) && isset($image_url[0])) {
						$image_url = $image_url[0];
						$image_full = $image_url;
					}
					if (!empty($image_medium) && isset($image_medium[0])) {
						$image_medium = $image_medium[0];
					}

					if (!empty($image_large) && isset($image_large[0])) {
						$image_large = $image_large[0];
					}

					if (!empty($image_thumbnail) && isset($image_thumbnail[0])) {
						$image_thumbnail = $image_thumbnail[0];
					}
					if (!empty($image_url)) {
						$image = '<img src="' . $image_url . '" alt="" />';
					}
				}

				$adv_title = str_replace([
					'{title}',
					'{icon}',
					'{icon_class}',
					'{image}',
					'{image_id}',
					'{image_url}',
					'{image_thumbnail}',
					'{image_medium}',
					'{image_large}',
					'{image_full}',
					'{tab_id}'
				], [
					$title,
					$icon,
					$icon_class,
					$image,
					$image_id,
					$image_url,
					$image_thumbnail,
					$image_medium,
					$image_large,
					$image_full,
					$tab_id
				], $adv_title);

				echo '<li>' . $adv_title . '</li>';

			} else {
				if (isset($tab_atts['icon_option']) && $tab_atts['icon_option'] == 'yes') {
					if (empty($tab_atts['icon'])) {
						$tab_atts['icon'] = 'fa-leaf';
					}
					$title = '<i class="' . $tab_atts['icon'] . '"></i> ' . $title;
				}
				echo '<li><a class="' . esc_attr($tabStatus) . '" href="#' .
					(isset($tab_atts['tab_id']) ? $tab_atts['tab_id'] : '') . '" data-prevent="scroll">' . $title .
					'</a></li>';
			}

		}

		return $matches[0];
	}

	function wilcitySCSearchTerms($s, $taxonomy)
	{
		global $wpdb;

		$termTaxonomyTbl = $wpdb->term_taxonomy;
		$termsTbl = $wpdb->terms;

		$sql =
			"SELECT $termsTbl.term_id, $termsTbl.name FROM $termsTbl LEFT JOIN $termTaxonomyTbl ON ($termsTbl.term_id = $termTaxonomyTbl.term_id) WHERE $termTaxonomyTbl.taxonomy='" .
			esc_sql($taxonomy) . "' AND $termsTbl.name LIKE '%" . esc_sql($s) . "%' ORDER BY $termsTbl.term_id DESC LIMIT 100";

		$aRawTerms = $wpdb->get_results($sql);

		if (empty($aRawTerms)) {
			return false;
		}

		$aTerms = [];
		foreach ($aRawTerms as $oTerm) {
			$aTerms[] = $oTerm->term_id . ':' . $oTerm->name;
		}

		return $aTerms;
	}

	if (!function_exists('wilcityAdvancedGridTemplatePath')) {
		function wilcityAdvancedGridTemplatePath()
		{
			global $kc, $wilcityKcTemplateRepository;

			$wilcityKcTemplateRepository = wilcityShortcodesRepository(WILCITY_SC_DIR . 'configs/sc-attributes/');

			if (!function_exists('kc_add_map')) {
				return false;
			}
			$kc->set_template_path(WILCITY_SC_DIR . 'kingcomposer-sc/');
		}

		add_action('init', 'wilcityAdvancedGridTemplatePath', 99);
	}

	if (!function_exists('kc_modify_listing_location_children_query')) {
		add_filter('kc_autocomplete_listing_location_children', 'kc_modify_listing_location_children_query');

		function kc_modify_listing_location_children_query($data)
		{
			$aTerms = wilcitySCSearchTerms($_POST['s'], 'listing_location');
			if (!$aTerms) {
				return false;
			}

			return ['Select Terms' => $aTerms];
		}
	}

	if (!function_exists('kc_modify_listing_cat_children_query')) {
		add_filter('kc_autocomplete_listing_cat_children', 'kc_modify_listing_cat_children_query');

		function kc_modify_listing_cat_children_query($data)
		{
			$aTerms = wilcitySCSearchTerms($_POST['s'], 'listing_cat');
			if (!$aTerms) {
				return false;
			}

			return ['Select Terms' => $aTerms];
		}
	}

	if (!function_exists('kc_modify_listing_cats_query')) {
		add_filter('kc_autocomplete_listing_cats', 'kc_modify_listing_cats_query');

		function kc_modify_listing_cats_query($data)
		{
			$aTerms = wilcitySCSearchTerms($_POST['s'], 'listing_cat');
			if (!$aTerms) {
				return false;
			}

			return ['Select Terms' => $aTerms];
		}
	}

	if (!function_exists('kc_modify_listing_locations_query')) {
		add_filter('kc_autocomplete_listing_locations', 'kc_modify_listing_locations_query');

		function kc_modify_listing_locations_query($data)
		{
			$aTerms = wilcitySCSearchTerms($_POST['s'], 'listing_location');
			if (!$aTerms) {
				return false;
			}

			return ['Select Terms' => $aTerms];
		}
	}

	if (!function_exists('kc_modify_listing_location_query')) {
		add_filter('kc_autocomplete_listing_location', 'kc_modify_listing_location_query');

		function kc_modify_listing_location_query($data)
		{
			$aTerms = wilcitySCSearchTerms($_POST['s'], 'listing_location');
			if (!$aTerms) {
				return false;
			}

			return ['Select Terms' => $aTerms];
		}
	}

	if (!function_exists('kc_modify_listing_cat_query')) {
		add_filter('kc_autocomplete_listing_cat', 'kc_modify_listing_cat_query');

		function kc_modify_listing_cat_query($data)
		{
			$aTerms = wilcitySCSearchTerms($_POST['s'], 'listing_cat');
			if (!$aTerms) {
				return false;
			}

			return ['Select Terms' => $aTerms];
		}
	}

	if (!function_exists('kc_modify_listing_tags_query')) {
		add_filter('kc_autocomplete_listing_tags', 'kc_modify_listing_tags_query');

		function kc_modify_listing_tags_query($data)
		{
			$aTerms = wilcitySCSearchTerms($_POST['s'], 'listing_tag');
			if (!$aTerms) {
				return false;
			}

			return ['Select Terms' => $aTerms];
		}
	}

	add_filter('kc_autocomplete_listing_ids', 'wilcityModifyListingIDsQuery');
	// filter id: kc_autocomplete_{field-name}
	// in this case the field-name is "post-ids"

	function wilcityModifyListingIDsQuery($aData)
	{
		$query = new WP_Query(
			[
				'post_type' => $aData['post_type'],
				'posts_per_page' => 20,
				's' => $aData['s'],
				'post_status' => 'publish'
			]
		);
		$aListings = [];
		if ($query->have_posts()) {
			while ($query->have_posts()) {
				$query->the_post();
				$aListings[] = $query->post->ID . ':' . $query->post->post_title;
			}
		}
		wp_reset_postdata();

		return ['Select Listings' => $aListings];
	}

	new RegisterKCShortcodes;
	require_once plugin_dir_path(__FILE__) . 'WilcityShortcodeRepository.php';

	function wilcityIncludeCoreFiles()
	{
		foreach (glob(plugin_dir_path(__FILE__) . 'default-sc/wilcity-*.php') as $filename) {
			include $filename;
		}

		foreach (glob(plugin_dir_path(__FILE__) . 'core/wilcity_*.php') as $filename) {
			include $filename;
		}

		foreach (glob(plugin_dir_path(__FILE__) . 'custom-field-content-sc/wilcity-*.php') as $filename) {
			include $filename;
		}
	}

	wilcityIncludeCoreFiles();

	/**
	 * @param string $configDir
	 *
	 * @return WilcityShortcodeRepository
	 */
	function wilcityShortcodesRepository($configDir = '')
	{
		$oInstance = new WilcityShortcodeRepository();
		if (!empty($configDir)) {
			$oInstance->setConfigDir($configDir);
		}

		return $oInstance;
	}

	function wilcityFilterPostIDForListingTaxonomyPage($postID)
	{
		$pageID = GetSettings::isTaxonomyUsingCustomPage();
		if (empty($pageID)) {
			return $postID;
		}

		return $pageID;
	}

	function wilcityAllowKCRenderOnTaxonomyPage($allow)
	{
		return GetSettings::isTaxonomyUsingCustomPage() ? true : $allow;
	}

	function wilcityFilterKCRawContentOnTaxonomyPage($post)
	{
		if ($pageID = GetSettings::isTaxonomyUsingCustomPage()) {
			$post = get_post($pageID);
		}

		return $post;
	}

	add_filter('kc_get_dynamic_css', 'wilcityFilterPostIDForListingTaxonomyPage');
	add_filter('kc_allows', 'wilcityAllowKCRenderOnTaxonomyPage');
	add_filter('kc_raw_post', 'wilcityFilterKCRawContentOnTaxonomyPage');

	function wilcityAllowUsingKCIfIsTaxonomyPage($return)
	{
		return GetSettings::isTaxonomyUsingCustomPage() ? true : $return;
	}

	add_filter('kc_is_using', 'wilcityAllowUsingKCIfIsTaxonomyPage');

	add_action('init', 'wilcitySCSetSCTemplatePath', 99);
	function wilcitySCSetSCTemplatePath()
	{
		global $kc;
		if (!function_exists('kc_add_map')) {
			return false;
		}
		$kc->set_template_path(WILCITY_SC_DIR . 'kingcomposer-sc/');
	}

	require_once plugin_dir_path(__FILE__) . 'wilcity-general-shortcodes.php';
	new KCFrontEndController;

	do_action('wilcity-shortcode/run-extension');
}, 10);
