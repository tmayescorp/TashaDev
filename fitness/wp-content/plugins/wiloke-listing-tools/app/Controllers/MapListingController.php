<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Helpers\GetSettings;

class MapListingController
{
    private $post;
    private $aPluck;
    private $aArgs = [
        'img_size' => 'large'
    ];
    
    public function __construct($post, $aArgs = [])
    {
        if (is_numeric($post)) {
            $this->post = get_post($post);
        } else {
            $this->post = $post;
        }
        
        if (!empty($aArgs)) {
            $this->aArgs = $aArgs;
        }
    }
    
    public function setPluck($pluck)
    {
        $this->aPluck = is_array($pluck) ? $pluck : explode(',', $pluck);
        
        return $this;
    }
    
    public function getTitle()
    {
        return $this->post->post_title;
    }
    
    public function getListingTitle()
    {
        return $this->getTitle();
    }
    
    public function getContent()
    {
        return get_the_content('', '', $this->post);
    }
    
    public function getListingContent()
    {
        return $this->getContent();
    }
    
    public function getLogo()
    {
        return GetSettings::getPostMeta($this->post->ID, 'logo');
    }
    
    public function getFeaturedImage()
    {
        return get_the_post_thumbnail_url($this->post->ID, $this->aArgs['img_size']);
    }
    
    public function getPostTerms($taxonomy)
    {
        $aTerms = wp_get_post_terms($this->post->ID, $taxonomy);
        if (empty($aTerms) || is_wp_error($aTerms)) {
            return [];
        }
        
        $aCategories = [];
        foreach ($aTerms as $oTerm) {
            $aCategories[] = [
                'name'  => $oTerm->name,
                'slug'  => $oTerm->slug,
                'id'    => $oTerm->term_id,
                'link'  => get_term_link($oTerm),
                'oIcon' => \WilokeHelpers::getTermOriginalIcon($oTerm)
            ];
        }
        
        return $aCategories;
    }
    
    public function getListingCat()
    {
        return $this->getPostTerms('listing_cat');
    }
    
    public function getListingLocation()
    {
        return $this->getPostTerms('listing_location');
    }
    
    public function getListingTag()
    {
        return $this->getPostTerms('listing_tag');
    }
    
    public function getPostMeta($metaKey)
    {
        return GetSettings::getPostMeta($this->post->ID, $metaKey);
    }
    
    public function get()
    {
        $aResults = [];
        foreach ($this->aPluck as $pluck) {
            if (method_exists(__CLASS__, $pluck)) {
                $aResults[$pluck] = $this->$pluck($pluck);
            } else {
                $aResults[$pluck] = $this->getPostMeta($pluck);
            }
        }
        
        return $aResults;
    }
}
