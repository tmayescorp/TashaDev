<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\QueryHelper;
use WilokeListingTools\Framework\Helpers\TermSetting;
use WilokeThemeOptions;

class TermController
{
    public function __construct()
    {
        add_filter('term_link', [$this, 'maybeFilterTermLink'], 10, 3);
    }

    public function maybeFilterTermLink($termLink, $oTerm, $taxonomy)
    {
        if (apply_filters('wiloke-listing-tools/filter/app/Controllers/TermController/maybeFilterTermLink', true,
            $termLink, $oTerm)) {
            $aTaxonomies = TermSetting::getListingTaxonomyKeys();
            if (!in_array($taxonomy, $aTaxonomies)) {
                return $termLink;
            }

            $aPostTypes = General::getPostTypeKeys(false);
            if (is_singular($aPostTypes)) {
                return QueryHelper::buildSearchPageURL([
                    'postType' => get_post_type(get_the_ID()),
                    $taxonomy  => $oTerm->term_id
                ]);
            }

            $taxonomyType = WilokeThemeOptions::getOptionDetail('listing_taxonomy_page_type');

            if (empty($taxonomyType) || $taxonomyType === 'default') {
                return $termLink;
            }

            $customPageId = WilokeThemeOptions::getOptionDetail($taxonomy . '_page');
            $postType = TermSetting::getDefaultPostType($oTerm->term_id, $taxonomy);
            if ($taxonomyType == 'searchpage' || empty($customPageId) || get_post_status($customPageId) !== 'publish') {
                return QueryHelper::buildSearchPageURL([
                    'postType' => $postType,
                    $taxonomy  => $oTerm->term_id
                ]);
            }
        }
        return $termLink;
    }
}
