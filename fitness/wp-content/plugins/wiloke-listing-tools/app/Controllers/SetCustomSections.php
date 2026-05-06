<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Helpers\SetSettings;

trait SetCustomSections
{
    protected function setCustomSections()
    {
        if (empty($this->aCustomSections)) {
            return false;
        }
        
        $prefix = wilokeListingToolsRepository()->get('addlisting:customMetaBoxPrefix');
        
        foreach ($this->aCustomSections as $sectionKey => $val) {
            if (empty($val)) {
                SetSettings::deletePostMeta($this->listingID, $sectionKey, $prefix);
            } else {
                SetSettings::setPostMeta($this->listingID, $sectionKey, $val, $prefix);
            }
        }
    }
}
