<?php

namespace WilokeListingTools\Controllers;

use Stripe\Util\Set;
use WilokeListingTools\Controllers\Retrieve\RestRetrieve;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\TermSetting;
use WilokeListingTools\Framework\Routing\Controller;

class TaxonomiesControllers extends Controller
{
    public function __construct()
    {
        add_action('edited_terms', [$this, 'hasChangedTerms'], 40);
        add_action('created_term', [$this, 'hasChangedTerms'], 10);
        add_action('delete_term', [$this, 'hasChangedTerms'], 10);
        add_action('updated_term_meta', [$this, 'hasChangedTermOrder'], 10, 3);
        add_action('wp_ajax_wilcity_get_tags_options', [$this, 'getTags']);
        add_filter(
            'wilcity/filter/wiloke-listing-tools/config/listing-plan/listing_plan_settings',
            [$this, 'addToggleCustomTaxonomies']
        );
    }

    private function getListingPlanBelongsTo($planID)
    {
        $aPostTypes = General::getPostTypes(false, false);
        foreach ($aPostTypes as $postType => $aPostType) {
            $aPlanIDs = GetWilokeSubmission::getAddListingPlans($postType . '_plans');

            if (is_array($aPlanIDs) && in_array($planID, $aPlanIDs)) {
                return $postType;
            }
        }

        return false;
    }

    public function addToggleCustomTaxonomies($aFields)
    {
        if (!isset($_GET['post'])) {
            return $aFields;
        }

        $postType = $this->getListingPlanBelongsTo($_GET['post']);
        if (empty($postType)) {
            return $aFields;
        }

        $aTaxonomies = TermSetting::getCustomListingTaxonomies($postType);

        if (empty($aTaxonomies)) {
            return $aFields;
        }

        foreach ($aTaxonomies as $taxonomy => $aTaxonomy) {
            $aFields[] = [
                'type'      => 'wiloke_field',
                'fieldType' => 'select',
                'id'        => 'add_listing_plan:toggle_'.$taxonomy,
                'name'      => 'Toggle ' . $aTaxonomy['name'],
                'options'   => [
                    'enable'  => 'Enable',
                    'disable' => 'Disable'
                ]
            ];
        }

        return $aFields;
    }

    public function getTags()
    {
        global $wpdb;
        $s = trim($_POST['s']);
        $aResults = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $wpdb->terms LEFT JOIN $wpdb->term_taxonomy ON ($wpdb->terms.term_id = $wpdb->term_taxonomy.term_id) WHERE $wpdb->term_taxonomy.taxonomy = 'listing_tag' AND  $wpdb->terms.name LIKE %s ORDER BY $wpdb->terms.term_id DESC LIMIT 20",
                '%' . esc_sql($s) . '%'
            )
        );

        if (empty($aResults) || is_wp_error($aResults)) {
            wp_send_json_error();
        }

        $aOptions = [];
        foreach ($aResults as $key => $oResult) {
            $aOptions[$key]['text'] = $oResult->name;
            $aOptions[$key]['id'] = $oResult->slug;
        }

        wp_send_json_success([
            'results' => $aOptions
        ]);
    }

    public function hasChangedTerms()
    {
        SetSettings::setOptions('get_taxonomy_saved_at', current_time('timestamp', 1));
        $aPostTypes = General::getPostTypeKeys(false, false);
        foreach ($aPostTypes as $postType) {
            SetSettings::setOptions(General::mainSearchFormSavedAtKey($postType), current_time('timestamp', 1),true);
            SetSettings::setOptions(General::heroSearchFormSavedAt($postType), current_time('timestamp', 1), true);
        }
    }

    public function hasChangedTermOrder($metaID, $objectID, $metaKey)
    {
        if ($metaKey != 'tax_position') {
            return false;
        }

        $this->hasChangedTerms();
    }
}
