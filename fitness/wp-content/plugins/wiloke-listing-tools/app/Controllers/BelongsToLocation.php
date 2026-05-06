<?php

namespace WilokeListingTools\Controllers;

trait BelongsToLocation
{
    protected function belongsToLocation()
    {
        if (empty($this->location)) {
            return false;
        }

        if (is_array($this->location)) {
            foreach ($this->location as $termID) {
                $oTerm = get_term($termID, 'listing_location');
                if (!empty($oTerm) && !is_wp_error($oTerm)) {
                    if (!empty($oTerm->parent)) {
                        array_push($this->location, $oTerm->parent);
                    }
                }
            }
        }
//        else {
//            $oTerm = get_term($this->location, 'listing_location');
//            if (!empty($oTerm) && !is_wp_error($oTerm)) {
//                if (!empty($oTerm->parent)) {
//                    $this->location = [];
//                    array_push($this->location, $oTerm->parent);
//                }
//            }
//        }

        wp_set_post_terms($this->listingID, $this->location, 'listing_location');
    }
}


