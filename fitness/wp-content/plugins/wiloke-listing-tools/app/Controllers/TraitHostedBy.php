<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\General;

trait TraitHostedBy
{
    private function setHostedBy()
    {
	    if (General::getPostTypeGroup($this->listingType) !== 'event') {
            return false;
        }
        
        if (empty($this->aHostedBy)) {
            SetSettings::deletePostMeta($this->listingID, 'hosted_by_profile_url');
            SetSettings::deletePostMeta($this->listingID, 'hosted_by');
            
            return true;
        }
        
        SetSettings::setPostMeta(
            $this->listingID,
            'hosted_by_profile_url',
            $this->aHostedBy['hosted_by_profile_url']
        );
        
        SetSettings::setPostMeta($this->listingID, 'hosted_by', $this->aHostedBy['hosted_by']);
    }
}
