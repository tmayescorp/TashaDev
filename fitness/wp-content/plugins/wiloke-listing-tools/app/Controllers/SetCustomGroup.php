<?php

namespace WilokeListingTools\Controllers;


use WilokeListingTools\Framework\Helpers\SetSettings;

trait SetCustomGroup
{
    public function setCustomGroup()
    {
        if (empty($this->aCustomGroupCollection)) {
            return true;
        }

        foreach ($this->aCustomGroupCollection as $key => $aData) {
            SetSettings::setPostMeta($this->listingID, $key, $aData, 'wilcity_group_');
        }
    }
}
