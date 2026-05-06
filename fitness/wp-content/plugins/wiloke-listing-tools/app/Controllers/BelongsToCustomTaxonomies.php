<?php

namespace WilokeListingTools\Controllers;


trait BelongsToCustomTaxonomies
{
    protected function belongsToCustomTaxonomies()
    {
        if (empty($this->aCustomTaxonomies)) {
            return false;
        }

        foreach ($this->aCustomTaxonomies as $taxonomy => $aTerms) {
            if (empty($aTerms)) {
                wp_set_post_terms($this->listingID, [], $taxonomy, false);
            } else {
                wp_set_post_terms($this->listingID, $aTerms, $taxonomy, false);
            }
        }
    }
}
