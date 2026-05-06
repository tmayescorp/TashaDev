<?php

namespace WilokeListingTools\Controllers;

use Wiloke;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Routing\Controller;
use WP_Term;

class PermalinksController extends Controller
{
    public function __construct()
    {
        add_filter('wilcity/filter/register-post-types/listings', [$this, 'filterRewriteRule'], 10, 2);
        add_filter('wilcity/filter/register-post-types/event', [$this, 'filterRewriteRule'], 10, 2);
        add_filter('post_type_link', [$this, 'modifyLink'], 1, 2);
        add_filter('post_type_archive_link', [$this, 'resolveSiteMapIssue'], 1, 2);
        add_filter('rewrite_rules_array', [$this, 'addRewriteRulesArray'], 999);
    }

    public function resolveSiteMapIssue($link, $postType)
    {
        if (str_contains($link, '%listingLocation%') || str_contains($link, '%listingCat%')) {
            $link = str_replace(
                [
                    '%listingLocation%',
                    '%listingCat%',
                    '%listingLocation%/%listingCat%',
                    '%listingCat%/%listingLocation%'
                ],
                [
                    $postType,
                    $postType,
                    $postType,
                    $postType
                ],
                $link
            );
        }

        return $link;
    }

    private function isPostTypeAllowable($postType): bool
    {
        $aOptions = Wiloke::getThemeOptions(true);
        if (!isset($aOptions['custom_link_on'])) {
            return false;
        }

        $allowedPostTypeMode = $aOptions['custom_link_on'];
        if ($allowedPostTypeMode == 'both') {
            return true;
        }

        if ($allowedPostTypeMode == 'listing') {
            if (General::isPostTypeInGroup($postType, 'listing')) {
                return true;
            }
        } else if ($allowedPostTypeMode == 'event') {
            if (General::isPostTypeInGroup($postType, 'event')) {
                return true;
            }
        }

        return false;
    }

    public function addRewriteRulesArray($aRules): array
    {
        if (!class_exists('Wiloke')) {
            return $aRules;
        }

        $new = [];
        $aThemeOptions = Wiloke::getThemeOptions(true);

        if (empty($aThemeOptions['listing_permalink_settings'])) {
            return $aRules;
        }

        $lCase = 0;
        $cCase = 0;

        if ('%listingLocation%' == $aThemeOptions['listing_permalink_settings']) {
            $lCase = 3;
        } else if ('%listingCat%' == $aThemeOptions['listing_permalink_settings']) {
            $cCase = 3;
        } else if (strpos($aThemeOptions['listing_permalink_settings'], '%listingLocation%/%listingCat%') !== false) {
            $lCase = 1;
            $cCase = 2;
        } else if (strpos($aThemeOptions['listing_permalink_settings'], '%listingCat%/%listingLocation%') !== false) {
            $lCase = 2;
            $cCase = 1;
        }

        $aCustomPostTypes
            = GetSettings::getOptions(wilokeListingToolsRepository()->get('addlisting:customPostTypesKey'));

        foreach ($aCustomPostTypes as $aPostType) {
            if ($this->isPostTypeAllowable($aPostType['key'])) {
                if ($lCase == 1 || $cCase == 1) {
                    if ($lCase == 1) {
                        $new[$aPostType['slug'] . '/(.+)/(.+)/(.+)/?$'] = 'index.php?post_type=' . $aPostType['key'] .
                            '&listing_location=$matches[1]&listing_cat=$matches[2]&name=$matches[3]';
                    } else if ($lCase == 2) {
                        $new[$aPostType['slug'] . '/(.+)/(.+)/(.+)/?$'] = 'index.php?post_type=' . $aPostType['key'] .
                            '&listing_cat=$matches[1]&listing_location=$matches[2]&name=$matches[3]';
                    }
                } else if ($lCase == 3 || $cCase == 3) {
                    if ($lCase == 3) {
                        $new[$aPostType['slug'] . '/(.+)/(.+)/?$'] = 'index.php?post_type=' . $aPostType['key'] .
                            '&listing_location=$matches[1]&name=$matches[2]';
                    } else if ($cCase == 3) {
                        $new[$aPostType['slug'] . '/(.+)/(.+)/?$'] = 'index.php?post_type=' . $aPostType['key'] .
                            '&listing_cat=$matches[1]&name=$matches[2]';
                    }
                }
            }
        }

        return array_merge($new, $aRules); // Ensure our rules come first
    }

    protected function getChildCategory($aTerms)
    {
        foreach ($aTerms as $oTerm) {
            if ($oTerm->parent != 0) {
                return $oTerm;
            }
        }

        return end($aTerms);
    }

    private function getPrimaryCategory($postId, $taxonomy): ?WP_Term
    {
        if (class_exists('RankMath')) {
            $rankMathPrimaryCatId = get_post_meta($postId, 'rank_math_primary_' . $taxonomy, true);
            if (!empty($rankMathPrimaryCatId)) {
                $oTerm = get_term($rankMathPrimaryCatId, $taxonomy);
                return is_wp_error($oTerm) || empty($oTerm) ? null : $oTerm;
            }
        }

        if (function_exists('yoast_get_primary_term_id')) {
            $yoastSeoTermID = yoast_get_primary_term_id( $taxonomy, $postId );

            if (!empty($yoastSeoTermID)) {
                $oTerm = get_term($yoastSeoTermID, $taxonomy);
                return is_wp_error($oTerm) || empty($oTerm) ? null : $oTerm;
            }
        }

        $aTerms = wp_get_object_terms($postId, $taxonomy);
        if (!empty($aTerms) && isset($aTerms[0])) {
            return $aTerms[0];
        }

        return null;
    }

    public function modifyLink($postLink, $post)
    {
        if (!class_exists('Wiloke')) {
            return $postLink;
        }

        $aThemeOptions = Wiloke::getThemeOptions(true);
        if (empty($aThemeOptions['listing_permalink_settings'])) {
            return $postLink;
        }

        $aPostTypes = General::getPostTypeKeys(false);

        if (is_object($post) && in_array($post->post_type, $aPostTypes)) {
            if ($this->isPostTypeAllowable($post->post_type)) {
                if (strpos($aThemeOptions['listing_permalink_settings'], '%listingLocation%') !== false) {
                    $oTerm = $this->getPrimaryCategory($post->ID, 'listing_location');

                    if (!empty($oTerm)) {
                        $slug = $oTerm->slug;
                    } else {
                        $aTerms = wp_get_object_terms($post->ID, 'listing_location');

                        if (!empty($aTerms) && !is_wp_error($aTerms)) {
                            $oTerm = $this->getChildCategory($aTerms);
                            $slug = $oTerm->slug;
                        } else {
                            $slug = apply_filters('wilcity/wiloke-listing-tools/custom-permalinks/unlocation',
                                'unlocation');
                        }
                    }


                    if (!empty($oTerm) && $oTerm->parent !== 0 &&
                        $aThemeOptions['taxonomy_add_parent_to_permalinks'] == 'enable' &&
                        strpos($aThemeOptions['listing_permalink_settings'], '%listingCat%') !== false) {
                        $oParent = get_term_by('term_taxonomy_id', $oTerm->parent);
                        if (!empty($oParent) && !is_wp_error($oParent)) {
                            $slug = $oParent->slug . '/' . $slug;
                        }
                    }

                    $postLink = str_replace('%listingLocation%', $slug, $postLink);
                }

                if (strpos($aThemeOptions['listing_permalink_settings'], '%listingCat%') !== false) {
                    $oTerm = $this->getPrimaryCategory($post->ID, 'listing_cat');

                    if (!empty($oTerm)) {
                        $slug = $oTerm->slug;
                    } else {
                        $aTerms = wp_get_object_terms($post->ID, 'listing_cat');
                        if (!empty($aTerms) && !is_wp_error($aTerms)) {
                            $oTerm = $this->getChildCategory($aTerms);
                            $slug = $oTerm->slug;
                        } else {
                            $slug = 'uncategory';
                        }
                    }

                    $postLink = str_replace('%listingCat%', $slug, $postLink);
                }
            }
        }

        return $postLink;
    }

    public function filterRewriteRule($aConfiguration, $postType): array
    {
        if (!class_exists('Wiloke')) {
            return $aConfiguration;
        }

        if (!$this->isPostTypeAllowable($postType)) {
            return $aConfiguration;
        }

        if (current_user_can('administrator') && defined('WPSEO_VERSION')) {
            $currentPageUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") .
                "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            if (preg_match('/\.xml$/', $currentPageUrl, $aMatches)) {
                return $aConfiguration;
            }
        }

        $aThemeOptions = Wiloke::getThemeOptions(true);
        if (isset($aThemeOptions['listing_permalink_settings']) &&
            !empty($aThemeOptions['listing_permalink_settings'])) {
            $aConfiguration['rewrite']['slug']
                = $aConfiguration['rewrite']['slug'] . '/' . $aThemeOptions['listing_permalink_settings'];
            $aConfiguration['has_archive'] = true;
            $aConfiguration['hierarchical'] = true;
        }

        return $aConfiguration;
    }
}
