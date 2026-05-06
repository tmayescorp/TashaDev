<?php

namespace WILCITY_SC\ParseShortcodeAtts;

use WILCITY_SC\SCHelpers;
use WilokeListingTools\Framework\Helpers\TermSetting;

trait ParseTermsInSc
{
    public function getTermsInSc()
    {
        if (!isset($this->aScAttributes['listing_locations'])) {
            return false;
        }
        $aTaxonomyKeys=[];
        if(isset($this->aScAttributes['post_type'])){
            $aTaxonomyKeys = TermSetting::getListingTaxonomyKeys($this->aScAttributes['post_type']);

        }
        $aTaxonomies = [];
        foreach ($aTaxonomyKeys as $taxonomy) {
            $termIDs = '';
            if (isset($this->aScAttributes[$taxonomy]) && !empty($this->aScAttributes[$taxonomy])) {
                $termIDs = $this->aScAttributes[$taxonomy];
            } else if (isset($this->aScAttributes[$taxonomy.'s']) && !empty($this->aScAttributes[$taxonomy.'s'])) {
                $termIDs = $this->aScAttributes[$taxonomy.'s'];
            }
            
            if (!empty($termIDs)) {
                $aParsedTaxes = SCHelpers::getAutoCompleteVal($termIDs);
                if (!empty($aParsedTaxes)) {
	                $aTaxonomies[$taxonomy] = $aParsedTaxes;
                }
            }
        }
        
        $this->aScAttributes['terms_in_sc'] = $aTaxonomies;
    }
}
