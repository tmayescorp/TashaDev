<?php

namespace WILCITY_SC\ParseShortcodeAtts;

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\TermSetting;

trait GetPostsInTerm
{
    private $aPostFeaturedImgsByTerm = [];
    
    public function getPostsInTerm($oTerm)
    {
        if ($this->aScAttributes['post_type'] === 'flexible') {
            $this->aScAttributes['post_type'] = TermSetting::getDefaultPostType($oTerm->term_id, $oTerm->taxonomy);
        }
        
        if (isset($this->aScAttributes['terms_in_sc'])) {
            $aTaxQuery = $this->aScAttributes['terms_in_sc'];
        } else {
            $aTaxQuery = [];
        }
        $aTaxQuery[$oTerm->taxonomy] = [$oTerm->term_id];
        
        $aArgs = [
          'post_type'      => $this->aScAttributes['post_type'],
          'posts_per_page' => 4,
          'post_status'    => 'publish'
        ];
        
        foreach ($aTaxQuery as $taxonomy => $aTerms) {
            $firstTermID = is_array($aTerms) ? $aTerms[0] : $aTerms;
            $aArgs['tax_query'][] = [
              'taxonomy' => $taxonomy,
              'terms'    => $aTerms,
              'field'    => is_numeric($firstTermID) ? 'term_id' : 'slug'
            ];
        }
        
        $cacheKey = md5(serialize($aArgs));
        
        if (isset($this->aPostFeaturedImgsByTerm[$cacheKey])) {
            return $this->aPostFeaturedImgsByTerm[$cacheKey];
        }
     
        $query = new \WP_Query($aArgs);
       
        if (!$query->have_posts()) {
            wp_reset_postdata();
            
            return false;
        }
       
        $this->aPostFeaturedImgsByTerm[$cacheKey] = [];
        
        while ($query->have_posts()) {
            $query->the_post();
            $logo = GetSettings::getLogo($query->post->ID, 'wilcity_40x40');
            if (empty($logo)) {
                $logo = GetSettings::getFeaturedImg($query->post->ID, 'wilcity_40x40');
            }
            
            if (!empty($logo)) {
                $this->aPostFeaturedImgsByTerm[$cacheKey][$query->post->ID] = $logo;
            } else {
                $this->aPostFeaturedImgsByTerm[$cacheKey][$query->post->ID] = '';
            }
        }
        wp_reset_postdata();
        
        return $this->aPostFeaturedImgsByTerm[$cacheKey];
    }
}
