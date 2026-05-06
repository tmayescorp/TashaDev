<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\SetSettings;

trait SetListingRelationship
{
    protected function setListingRelationship()
    {
        $prefix = 'wilcity_custom_';
        if (!empty($this->aListingRelationships)) {
            foreach ($this->aListingRelationships as $metaKey => $val) {
                SetSettings::deletePostMeta($this->listingID, $prefix.$metaKey);
                if (!empty($val)) {
                    foreach ($val as $id) {
                        SetSettings::addPostMeta($this->listingID, $prefix.$metaKey, $id);
                    }
                }
            }
        }
    }
}
