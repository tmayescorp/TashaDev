<?php

namespace WilokeListingTools\Framework\Helpers;

use WilokeThemeOptions;
use WP_Term;

class TermSetting
{
    private static $aHasTermChildren  = [];
    private static $aCache            = [];
    private static $aTermPostTypeKeys = [];

    public static function getTermField($term, $taxonomy, $field = 'term_id')
    {
        if (isset(self::$aCache[$term . $taxonomy])) {
            $oTerm = self::$aCache[$term . $taxonomy];
        } else {
            $target = is_numeric($term) ? 'term_id' : 'slug';
            $oTerm = get_term_by($target, $term, $taxonomy);
            self::$aCache[$term . $taxonomy] = $oTerm;
        }

        if (empty($oTerm) || is_wp_error($oTerm)) {
            return null;
        }

        return isset($oTerm->$field) ? $oTerm->$field : null;
    }

    public static function getTagsBelongsTo($tagId, $isFocus = false)
    {
        global $wpdb;
        if (!$isFocus && isset(self::$aCache['tag_parents_' . $tagId])) {
            return self::$aCache['tag_parents_' . $tagId];
        }

        $aRawParents = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT term_id FROM $wpdb->termmeta WHERE meta_key='wilcity_tag_revelation' AND meta_value=%d",
                $tagId
            ),
            ARRAY_A
        );

        if (empty($aRawParents) || is_wp_error($aRawParents)) {
            return [];
        }

        $aParents = [];
        foreach ($aRawParents as $aParent) {
            $aParents[] = absint($aParent['term_id']);
        }

        self::$aCache['tag_parents_' . $tagId] = $aParents;

        return $aParents;
    }

    public static function hasTagRevelation($termId)
    {
        $tagID = GetSettings::getTermMeta($termId, 'tag_revelation');

        return !empty($tagID);
    }

    public static function termRedirectType()
    {
        return WilokeThemeOptions::getOptionDetail('listing_taxonomy_page_type');
    }

    public static function isTermRedirectToSearch()
    {
        return self::termRedirectType() === 'searchpage';
    }

    public static function getTermIconInfo($oTerm, $aAtts = [])
    {
        $aIcon = \WilokeHelpers::getTermOriginalIcon($oTerm, false);

        $aBoxIcon = [];
        if (is_single()) {
            if (self::isTermRedirectToSearch()) {
                if (isset($aAtts['taxonomy_post_type']) && $aAtts['taxonomy_post_type'] === 'currentPostType') {
                    $aBoxIcon['link'] = add_query_arg(
                        [
                            'postType' => get_post_type(get_the_ID())
                        ],
                        get_term_link($oTerm)
                    );
                } else {
                    $aBoxIcon['link'] = GetSettings::getTermLink($oTerm);
                }
            } else {
                $aBoxIcon['link'] = GetSettings::getTermLink($oTerm);
            }
        }

        return array_merge($aBoxIcon, ['icon' => $aIcon]);
    }

    public static function getTermBox($oTerm, $aAtts = [])
    {
        return wp_parse_args(
            self::getTermIconInfo($oTerm, $aAtts),
            [
                'link' => get_term_link($oTerm),
                'name' => $oTerm->name,
                'slug' => $oTerm->slug
            ]
        );
    }

    public static function getTermBoxes($postID, $taxonomy, $aAtts = [])
    {
        $aTerms = GetSettings::getPostTerms($postID, $taxonomy);
        if (empty($aTerms)) {
            return [];
        }

        $aLists = [];

        foreach ($aTerms as $oTerm) {
            $aLists[] = self::getTermBox($oTerm, $aAtts);
        }

        return $aLists;
    }

    /**
     * @param string|array $postTypes
     *
     * @return array
     */
    public static function getCustomListingTaxonomies($postTypes = [])
    {
        if (!function_exists('cptui_get_taxonomy_data')) {
            return [];
        }

        $aTaxonomies = cptui_get_taxonomy_data();
        if (empty($aTaxonomies)) {
            return [];
        }

        if (empty($postTypes) || $postTypes === 'all') {
            $aListingPostTypes = General::getPostTypeKeys(false, false);
        } else {
            if (is_string($postTypes)) {
                $aListingPostTypes = [$postTypes];
            } else {
                $aListingPostTypes = $postTypes;
            }
        }

        $cacheKey = md5(serialize($aListingPostTypes));
        if (array_key_exists('custom_tax_' . $cacheKey, self::$aCache)) {
            return self::$aCache['custom_tax_' . $cacheKey];
        }

        $aCustomListingTaxonomies = [];
        foreach ($aTaxonomies as $taxonomy => $aTaxonomy) {
            if (array_intersect($aTaxonomy['object_types'], $aListingPostTypes)) {
                $aCustomListingTaxonomies[$taxonomy] = $aTaxonomy;
            }
        }

        self::$aCache['custom_tax_' . $cacheKey] = $aCustomListingTaxonomies;

        return self::$aCache['custom_tax_' . $cacheKey];
    }

    public static function getListingTaxonomies($postTypes = [])
    {
        $aDefaultTaxonomies = ['listing_location', 'listing_cat', 'listing_tag'];
        $aTaxonomies = [];
        if (!empty($aDefaultTaxonomies)) {
            $aConfiguration = wilokeListingToolsRepository()->get('posttypes:taxonomies');
            foreach ($aDefaultTaxonomies as $taxonomy) {
                $aTaxonomies[$taxonomy] = $aConfiguration[$taxonomy];
            }
        }

        $aCustomTaxonomies = self::getCustomListingTaxonomies($postTypes);

        if (!empty($aCustomTaxonomies)) {
            $aTaxonomies = array_merge($aTaxonomies, $aCustomTaxonomies);
        }

        return $aTaxonomies;
    }

    public static function getListingTaxonomyKeys($postTypes = [])
    {
        $aCustomPostTypes = self::getCustomListingTaxonomies($postTypes);
        $aKeys = ['listing_location', 'listing_cat', 'listing_tag'];
        if (!empty($aCustomPostTypes)) {
            $aKeys = array_merge(array_keys($aCustomPostTypes), $aKeys);
        }

        return $aKeys;
    }

    public static function getPrimaryCategory($postId, $taxonomy): ?WP_Term
    {
        if (class_exists('RankMath')) {
            $rankMathPrimaryCatId = get_post_meta($postId, 'rank_math_primary_' . $taxonomy, true);
            if (!empty($rankMathPrimaryCatId)) {
                $oTerm = get_term($rankMathPrimaryCatId, $taxonomy);
                return is_wp_error($oTerm) || empty($oTerm) ? null : $oTerm;
            }
        }

        if (class_exists('WPSEO_Primary_Term')) {
            $yoastSeoTermID = get_post_meta($postId, '_yoast_wpseo_primary_' . $taxonomy, true);
            if (!empty($yoastSeoTermID)) {
                $oTerm = get_term($yoastSeoTermID, $taxonomy);
                return is_wp_error($oTerm) || empty($oTerm) ? null : $oTerm;
            }
        }

        $aTerms = wp_get_post_terms($postId, $taxonomy);
        if (!empty($aTerms) && !is_wp_error($aTerms)) {
            return $aTerms[0] ?? null;
        }

        return null;
    }

    public static function getTerms($aArgs)
    {
        if (!isset($aArgs['isFocus']) || !$aArgs['isFocus']) {
            /**
             * @hooked WilcityRedis\Controllers\TaxonomyController::getTerms
             */
            $result = apply_filters(
                'wilcity/filter/wiloke-listing-tools/app/Framework/TermSettings/beforeGetTerms',
                null,
                $aArgs
            );

            if (!empty($result)) {
                return $result;
            }
        }

        $aTerms = get_terms($aArgs);

        /**
         * hooked WilcityRedis\Controllers\TaxonomyController@cacheTerms
         */
        return apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Framework/TermSettings/afterGetTerms',
            $aTerms,
            $aArgs
        );
    }

    public static function hasTermChildren($parentID, $taxonomy)
    {
        $cacheKey = $taxonomy . $parentID;
        if (isset(self::$aHasTermChildren[$cacheKey])) {
            return self::$aHasTermChildren[$cacheKey];
        }

        $aTerms = self::getTerms([
            'taxonomy'   => $taxonomy,
            'parent'     => $parentID,
            'count'      => 1,
            'hide_empty' => false
        ]);

        self::$aHasTermChildren[$cacheKey] = !empty($aTerms) && !is_wp_error($aTerms);

        return self::$aHasTermChildren[$cacheKey];
    }

    /**
     * @param $oTerm
     *
     * @return mixed|string
     */
    public static function getTermIconIcon($oTerm)
    {
        $icon = GetSettings::getTermMeta($oTerm->term_id, 'icon');

        if (empty($icon)) {
            $icon = WilokeThemeOptions::getOptionDetail($oTerm->taxonomy . '_icon');
        }

        return $icon;
    }

    public static function getTermIcon($oTerm)
    {
        return self::getTermImageIcon($oTerm);
    }

    public static function getTermIconColor($oTerm)
    {
        $iconColor = GetSettings::getTermMeta($oTerm->term_id, 'icon_color');

        if (empty($iconColor)) {
            $iconColor = WilokeThemeOptions::getOptionDetail($oTerm->taxonomy . '_icon_color');

            if (is_array($iconColor)) {
                return $iconColor['color'];
            }
        }

        return $iconColor;
    }

    public static function hasRealTermIconImage($oTerm)
    {
        $iconURL = '';
        $termIconID = GetSettings::getTermMeta($oTerm->term_id, 'icon_img_id');
        if (!empty($termIconID)) {
            $iconURL = GetSettings::getAttachmentURL($termIconID, 'full', true);
        }

        if (!empty($iconURL)) {
            return true;
        }

        $iconURL = GetSettings::getTermMeta($oTerm->term_id, 'icon_img');

        return !empty($iconURL);
    }

    public static function hasRealTermIconIcon($oTerm)
    {
        $icon = GetSettings::getTermMeta($oTerm->term_id, 'icon');

        return !empty($icon);
    }

    /**
     * @param        $oTerm
     * @param string $thumbnailSize
     *
     * @return false|mixed|string|void
     */
    public static function getTermImageIcon($oTerm, $thumbnailSize = 'thumbnail')
    {
        $iconURL = '';
        $termIconID = GetSettings::getTermMeta($oTerm->term_id, 'icon_img_id');
        if (!empty($termIconID)) {
            $iconURL = GetSettings::getAttachmentURL($termIconID, $thumbnailSize, true);
        }

        if (!empty($iconURL)) {
            return $iconURL;
        }

        $iconURL = GetSettings::getTermMeta($oTerm->term_id, 'icon_img');

        if (!empty($iconURL)) {
            return $iconURL;
        }

        return apply_filters('wiloke-listing-tools/map-icon-url-default',
            get_template_directory_uri() . '/assets/img/map-icon.png', $oTerm);
    }

    /**
     * @param $termID
     * @param $taxonomy
     *
     * @return mixed
     */
    public static function getDefaultPostType($termID, $taxonomy)
    {
        if ($termID instanceof WP_Term) {
            $termID = $termID->term_id;
        } else if (!is_numeric($termID)) {
            $oTerm = get_term_by('slug', $termID, $taxonomy);
            $termID = $oTerm->slug;
        }

        $postType = GetSettings::getTermMeta($termID, 'default_belongs_to');
        if (!empty($postType)) {
            return $postType;
        }

        $aPostTypes = GetSettings::getTermMeta($termID, 'belongs_to');
        if (!empty($aPostTypes)) {
            return $aPostTypes[0];
        }

        return GetSettings::getDefaultPostType(true, false);
    }

    public static function getTermPostTypes($termID)
    {
        self::$aTermPostTypeKeys = GetSettings::getTermMeta($termID, 'belongs_to');
        $aAllPostTypes = General::getPostTypes(false, false);

        if (!empty(self::$aTermPostTypeKeys) && is_array(self::$aTermPostTypeKeys)) {
            $aAllPostTypes = array_filter($aAllPostTypes, function ($aPostType) {
                return in_array($aPostType['key'], self::$aTermPostTypeKeys);
            });
        }

        return $aAllPostTypes;
    }

    public static function getTermPostTypeKeys($termID)
    {
        $aPostTypes = self::getTermPostTypes($termID);

        return array_keys($aPostTypes);
    }
}
