<?php

namespace WILCITY_SC\ParseShortcodeAtts;

use WilokeListingTools\Framework\Helpers\TermSetting;

trait ParseTermArgs
{
    private function parseTermArgs()
    {
        if (!isset($this->aScAttributes['parseCategories']) ||
            !in_array('term', $this->aScAttributes['parseCategories'])) {
            return $this->aScAttributes;
        }
        
        if (is_tax()) {
            $currentTaxonomy = get_queried_object()->taxonomy;
            $currentTermId   = abs(get_queried_object()->term_id);
            
            // If the taxonomy is matched current taxonomy page, the children terms will be shown. So, each term box is
            // is company of term child and the specify term setting. EG: We are in Listing Location page. The shortcode
            // is set to taxonomy listing_location. The Listing Category is not empty => The box is a company of
            // Child of Listing Location + Listing Category
            if (isset($this->aScAttributes['group']) &&
                $this->aScAttributes['group'] === 'term' &&
                isset($this->aScAttributes['taxonomy']) &&
                $this->aScAttributes['taxonomy'] === $currentTaxonomy) {
                $aTermChildren = TermSetting::getTerms(
                  [
                    'number'     => $this->aScAttributes['number'],
                    'taxonomy'   => $currentTaxonomy,
                    'parent'     => $currentTermId,
                    'orderby'    => $this->aScAttributes['orderby'],
                    'order'      => $this->aScAttributes['order'],
                    'hide_empty' => isset($this->aScAttributes['is_hide_empty']) &&
                                    $this->aScAttributes['is_hide_empty'] == 'yes'
                  ]
                );
                
                if (!empty($aTermChildren)) {
                    $aTermChildrenIds = array_map(function ($oTerm) {
                        return $oTerm->term_id;
                    }, $aTermChildren);
                    
                    $this->aScAttributes[$currentTaxonomy.'s'] = $aTermChildrenIds;
                }
            } else {
                $this->aScAttributes[$currentTaxonomy] = $currentTermId;
            }
        } else {
            if ((isset($this->aScAttributes['group']) && $this->aScAttributes['group'] === 'term')) {
                if (empty($this->aScAttributes[$this->aScAttributes['taxonomy'].'s'])) {
                    $aArgs = [
                      'taxonomy'   => $this->aScAttributes['taxonomy'],
                      'orderby'    => $this->aScAttributes['orderby'],
                      'order'      => $this->aScAttributes['order'],
                      'number'     => $this->aScAttributes['number'],
                      'hide_empty' => isset($this->aScAttributes['is_hide_empty']) &&
                                      $this->aScAttributes['is_hide_empty'] == 'yes'
                    ];
                    
                    if ($this->aScAttributes['is_show_parent_only'] === 'yes') {
                        $aArgs['parent'] = 0;
                    }
                    
                    $aRawTerms = get_terms($aArgs);
                    if (!empty($aRawTerms) && !is_wp_error($aRawTerms)) {
                        $this->aScAttributes[$this->aScAttributes['taxonomy'].'s'] = [];
                        foreach ($aRawTerms as $oTerm) {
                            $this->aScAttributes[$this->aScAttributes['taxonomy'].'s'][] = $oTerm->term_id;
                        }
                    }
                }
            }
        }
        
        return $this->aScAttributes;
    }
}
