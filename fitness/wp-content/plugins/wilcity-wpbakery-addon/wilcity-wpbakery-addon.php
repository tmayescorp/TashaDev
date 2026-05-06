<?php
/*
 * Plugin Name: Wilcity WP Bakery Addon
 * Plugin URI: https://wilcity.com
 * Author: Wiloke
 * Author URI: https://wiloke.com
 * Version: 1.2.2
 */

if (!defined('ABSPATH')) {
    die();
}

if (!function_exists('vc_map')) {
    return '';
}

define('WILCITY_VC_SC', 'Wilcity');
define('WILCITY_VC_SC_DIR', plugin_dir_path(__FILE__));

add_action('wilcity-shortcode/run-extension', function () {
    require_once plugin_dir_path(__FILE__).'vendor/autoload.php';

    function wilcityAddVCShortcode($aParams)
    {
        $path    = WILCITY_VC_SC_DIR.'vc_templates/';
        $fileDir = $path.$aParams['base'].'.php';
        if (is_file($fileDir)) {
            include $fileDir;
        }
    }

    function wilcityVCParseExtraClass($atts)
    {
        if (isset($atts['css'])) {
            $atts['extra_class'] = $atts['extra_class'].' '.vc_shortcode_custom_css_class($atts['css'], ' ');
        }

        return $atts;
    }

    add_filter('wilcity/vc/parse_sc_atts', 'wilcityVCParseExtraClass');

    function wilcityFilterTaxonomyAutoComplete($query, $tag, $param_name)
    {
        global $wpdb;
        $taxonomy = substr($param_name, 0, -1);

        $taxonomyTbl = $wpdb->term_taxonomy;
        $termsTbl    = $wpdb->terms;

        $sql =
          "SELECT $termsTbl.term_id, $termsTbl.name FROM $termsTbl LEFT JOIN $taxonomyTbl ON ($termsTbl.term_id=$taxonomyTbl.term_id) WHERE $termsTbl.name LIKE '%".
          esc_sql(trim($query))."%' AND $taxonomyTbl.taxonomy=%s LIMIT 20";

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

    function wilcityFilterEvent($query, $tag, $param_name)
    {
        global $wpdb;
        $query = '%'.$wpdb->_real_escape($query).'%';

        $sql = "SELECT ID, post_title FROM $wpdb->posts WHERE post_type = 'event' AND post_title LIKE %s";

        $aRawResults = $wpdb->get_results(
          $wpdb->prepare(
            $sql,
            $query
          )
        );

        if (empty($aRawResults)) {
            return false;
        }

        $aResults = [];
        foreach ($aRawResults as $oPost) {
            $aResults[] = [
              'label' => $oPost->post_title,
              'value' => $oPost->ID
            ];
        }

        return $aResults;
    }

    function wilcityFilterListing($query, $tag, $param_name)
    {
        global $wpdb;

        $aDirectoryTypes = \WilokeListingTools\Framework\Helpers\General::getPostTypeKeys(false, true);
        $aDirectoryTypes = array_map(function ($type) {
            global $wpdb;

            return $wpdb->_real_escape($type);
        }, $aDirectoryTypes);
        $types           = '("'.implode('","', $aDirectoryTypes).'")';

        $query = '%'.$wpdb->_real_escape($query).'%';

        $sql = "SELECT ID, post_title FROM $wpdb->posts WHERE post_type IN $types AND post_title LIKE %s";

        $aRawResults = $wpdb->get_results(
          $wpdb->prepare(
            $sql,
            $query
          )
        );

        if (empty($aRawResults)) {
            return false;
        }

        $aResults = [];
        foreach ($aRawResults as $oPost) {
            $aResults[] = [
              'label' => $oPost->post_title,
              'value' => $oPost->ID
            ];
        }

        return $aResults;
    }

    function wilcityRenderListingName($currentVal, $aParamSettings)
    {
        $value = trim($currentVal);
        if (empty($value)) {
            return '';
        }

        $aParse = explode(',', $value);
        $aParse = array_map(function ($val) {
            return trim($val);
        }, $aParse);

        $aVals = [];
        foreach ($aParse as $postID) {
            $aVals[] = get_the_title($postID);
        }

        return implode(',', $aVals);
    }

    function wilcityRenderTermName($currentVal, $aParamSettings)
    {
        $value = trim($currentVal);
        if (empty($value)) {
            return '';
        }

        $aParse   = explode(',', $value);
        $aParse   = array_map(function ($val) {
            return trim($val);
        }, $aParse);
        $taxonomy = substr($aParamSettings['param_name'], 0, strlen($aParamSettings['param_name']) - 1);

        $aTerms = get_terms(
          [
            'taxonomy' => $taxonomy,
            'include'  => $aParse,
            'orderby'  => 'include'
          ]
        );

        if (empty($aTerms) || is_wp_error($aTerms)) {
            return '';
        }

        $aVals = [];
        foreach ($aTerms as $oTerm) {
            $aVals[] = $oTerm->name;
        }

        return implode(',', $aVals);
    }

    add_filter('vc_autocomplete_wilcity_vc_listing_grip_layout_listing_ids_callback', 'wilcityFilterListing', 10, 3);
    add_filter('vc_autocomplete_wilcity_vc_events_grid_listing_ids_callback', 'wilcityFilterEvent', 10, 3);
    add_filter('vc_autocomplete_wilcity_vc_events_slider_listing_ids_callback', 'wilcityFilterEvent', 10, 3);
    add_filter('vc_autocomplete_wilcity_vc_listings_slider_listing_ids_callback', 'wilcityFilterEvent', 10, 3);

    add_filter('vc_form_fields_render_field_wilcity_vc_listing_grip_layout_listing_ids_param_value',
      'wilcityRenderListingName', 10, 2);
    add_filter('vc_form_fields_render_field_wilcity_vc_listing_grip_layout_listing_ids_param_value',
      'wilcityRenderListingName', 10, 2);
    add_filter('vc_form_fields_render_field_wilcity_vc_listings_slider_param_value',
      'wilcityRenderListingName', 10, 2);

    if (class_exists('WPBakeryShortCodesContainer')) {
        class WPBakeryShortCode_Wilcity_Vc_Hero extends WPBakeryShortCodesContainer
        {
        }
    }
    if (class_exists('WPBakeryShortCode')) {
        class WPBakeryShortCode_Wilcity_Vc_Search_Form extends WPBakeryShortCode
        {
        }
    }

    // Print Custom CSS To Taxonomy page
    add_action('wp_head', function () {
        if ($pageID = \WilokeListingTools\Framework\Helpers\GetSettings::isTaxonomyUsingCustomPage()) {
            $shortcodes_custom_css = get_post_meta($pageID, '_wpb_shortcodes_custom_css', true);
            if (!empty($shortcodes_custom_css)) {
                $shortcodes_custom_css = strip_tags($shortcodes_custom_css);
                echo '<style type="text/css" data-type="vc_shortcodes-custom-css">';
                echo $shortcodes_custom_css;
                echo '</style>';
            }
        }
    });

    new \WilcityWPBakeryAddon\Registers\WPBakeryAdapterConfiguration();
    new \WilcityWPBakeryAddon\Controllers\FilterVcController();
}, 99);
