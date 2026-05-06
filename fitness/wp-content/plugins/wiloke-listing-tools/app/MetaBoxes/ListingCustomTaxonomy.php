<?php

namespace WilokeListingTools\MetaBoxes;

use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\TermSetting;

class ListingCustomTaxonomy
{
    public function __construct()
    {
        add_action('cmb2_admin_init', [$this, 'renderMetaboxFields']);
    }

    public function renderMetaboxFields()
    {
        $aCustomListingTaxonomies = TermSetting::getCustomListingTaxonomies();
        if (empty($aCustomListingTaxonomies)) {
            return false;
        }
        // Getting taxonomy keys
        $aCustomListingTaxonomies = array_keys($aCustomListingTaxonomies);

        if (!empty($aCustomListingTaxonomies)) {
            $aSettings               = wilokeListingToolsRepository()->get('listingcategory:listing_cat_settings');
            $aSettings['id']         = 'listing_custom_taxonomies';
            $aSettings['taxonomies'] = $aCustomListingTaxonomies;
            new_cmb2_box($aSettings);
        }
    }
}
