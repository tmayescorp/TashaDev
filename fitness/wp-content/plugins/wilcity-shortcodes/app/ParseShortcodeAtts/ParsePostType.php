<?php

namespace WILCITY_SC\ParseShortcodeAtts;

use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\TermSetting;

trait ParsePostType
{
    private function parsePostType()
    {
        if (!isset($this->aScAttributes['post_type'])) {
            return false;
        }
        
        if (
          (isset($this->aScAttributes['isRequiredPostType']) && $this->aScAttributes['isRequiredPostType'] === 'yes') ||
          ($this->aScAttributes['post_type'] === 'flexible')
        ) {
            if (is_tax()) {
                if ($this->aScAttributes['post_type'] !== 'flexible') {
                    // Customer is using Term Group shortcode but the term box is not using current taxonomy.
                    // EG: Customer is using Listing Category on Listing Location page
                    if (isset($this->aScAttributes['taxonomy']) && $this->aScAttributes['taxonomy'] !==
                                                                   get_queried_object()->taxonomy) {
                        return true;
                    }
                }
                
                $postType = TermSetting::getDefaultPostType(get_queried_object_id(), get_queried_object()->taxonomy);
                
                if (empty($postType)) {
                    $postType = General::getFirstPostTypeKey(false, true);
                }
                
                $this->aScAttributes['post_type'] = $postType;
            }
        }
        
        return true;
    }
}
