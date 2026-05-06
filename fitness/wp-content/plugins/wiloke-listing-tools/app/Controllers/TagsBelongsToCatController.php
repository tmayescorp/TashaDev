<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\TermSetting;

class TagsBelongsToCatController
{
    private $aCommonTerms;

    public function __construct()
    {
        add_action('add_term_meta', [$this, 'setCategoryToTagParent'], 10, 3);
        add_action('update_term_meta', [$this, 'updateCategoryToTagParent'], 10, 4);
        add_action('delete_term_meta', [$this, 'deleteParentCategoryFromTag'], 10, 3);
        add_filter('get_terms_args', [$this, 'tagRelevantConditional'], 10, 1);
    }

    private function getCommonTagRevelations($field = 'all', $isParentOnly = false, $hideEmpty = false)
    {
        if (!empty($this->aCommonTerms)) {
            return $this->aCommonTerms;
        }

        global $wpdb;

        if ($field === 'all') {
            $field = '*';
        }

        $field = $wpdb->_real_escape($field);
        $sql   = $wpdb->prepare(
            "SELECT tt.{$field} FROM $wpdb->term_taxonomy as tt LEFT JOIN $wpdb->termmeta as tm ON (tm.meta_value = tt.term_id AND tm.meta_key='wilcity_tag_revelation') WHERE tm.meta_value IS NULL and tt.taxonomy=%s",
            'listing_tag'
        );

        if ($isParentOnly) {
            $sql .= " AND tt.parent=0";
        }

        if ($hideEmpty) {
            $sql .= " AND tt.count!=0";
        }

        $aTerms = $wpdb->get_results($sql);

        if ($field == '*') {
            return $aTerms;
        }

        $this->aCommonTerms = array_reduce($aTerms, function ($aCarry, $oTerm) use ($field) {
            array_push($aCarry, $oTerm->{$field});

            return $aCarry;
        }, []);

        return $this->aCommonTerms;
    }

    public function tagRelevantConditional($aArgs)
    {
        if (!isset($aArgs['listing_cat_revelation'])) {
            return $aArgs;
        }

        $aListingCats = is_array($aArgs['listing_cat_revelation']) ? $aArgs['listing_cat_revelation'] :
            explode(',', $aArgs['listing_cat_revelation']);

        foreach ($aListingCats as $catId) {
            $aTagRevelations = GetSettings::getTermMeta($catId, 'tag_revelation', null, false);
            if (!empty($aTagRevelations)) {
                if (isset($aTagRevelations[0]) && !is_numeric($aTagRevelations[0])) {
                    $aTagRevelations = array_reduce($aTagRevelations, function($aCarry, $tag) {
                        if (is_string($tag)){
                            $oTerm = get_term_by('slug', $tag, 'listing_tag');
                            if (!empty($oTerm) && !is_wp_error($oTerm)) {
                                $aCarry[] = $oTerm->term_id;
                            }
                        } else if (is_numeric($tag)) {
                            $aCarry[] = $tag;
                        }

                        return $aCarry;
                    }, []);
                }
                $aArgs['include'] = isset($aArgs['include']) && is_array($aArgs['include']) ? array_merge($aTagRevelations, $aArgs['include']) : $aTagRevelations;
            }

        }

        $isGetCommonTags = apply_filters('wilcity/filter/wiloke-listing-tools/is-get-common-tags', true);
        if (isset($aArgs['include']) && $isGetCommonTags) {
            $isHideEmpty  = isset($aArgs['hide_empty']) && $aArgs['hide_empty'] == 'yes';
            $isParentOnly = isset($aArgs['parent']) && $aArgs['parent'] === 0;
            $aCommonTerms = $this->getCommonTagRevelations('term_id', $isParentOnly, $isHideEmpty);

            if (!empty($aCommonTerms)) {
                $aArgs['include'] = array_merge($aArgs['include'], $aCommonTerms);
            }
        }

        if (isset($aArgs['include']) && is_array($aArgs['include'])) {
            $aArgs['include'] = array_unique($aArgs['include']);
        }


        unset($aArgs['listing_cat_revelation']);

        return $aArgs;
    }

    private function isUpdateTagsBelongsTo($metaKey)
    {
        return current_user_can('administrator') && $metaKey === 'wilcity_tags_belong_to';
    }

    private function updateTagParent($aTags, $parentID = 0)
    {
        delete_term_meta($parentID, 'wilcity_tag_revelation');

        foreach ($aTags as $tagSlug) {
            $oTag = get_term_by('slug', $tagSlug, 'listing_tag');
            if (!empty($oTag) && !is_wp_error($oTag)) {
                add_term_meta($parentID, 'wilcity_tag_revelation', $oTag->term_id);
            }
        }
    }

    public function deleteParentCategoryFromTag($metaID, $parentID, $metaKey)
    {
        if (!$this->isUpdateTagsBelongsTo($metaKey)) {
            return false;
        }

        delete_term_meta($parentID, 'wilcity_tag_revelation');
    }

    public function setCategoryToTagParent($termID, $metaKey, $metaVal)
    {
        if (!$this->isUpdateTagsBelongsTo($metaKey)) {
            return false;
        }

        $this->updateTagParent($metaVal, $termID);
    }

    public function updateCategoryToTagParent($metaID, $parentID, $metaKey, $metaVal)
    {
        if (!$this->isUpdateTagsBelongsTo($metaKey)) {
            return false;
        }

        if (empty($metaVal)) {
            $metaVal = [];
        }

        $this->updateTagParent($metaVal, $parentID);
    }
}
