<?php

namespace WilokeListingTools\Models;

use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;

class PromotionModel
{
    protected static $aCache = [];
    
    protected function generateKey($listingID, $position = '')
    {
        return $listingID.'_'.$position;
    }
    
    public static function getListingPromotionDetail($listingID, $position)
    {
        global $wpdb;
        $key = $listingID.'_'.$position;
        if (isset(self::$aCache[$key])) {
            return self::$aCache[$key];
        }
        
        $aResult = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $wpdb->postmeta LEFT JOIN $wpdb->post ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) WHERE $wpdb->postmeta.post_id=%d AND $wpdb->posts.post_status='publish' AND $wpdb->postmeta.meta_key %s",
                $listingID, $key
            )
        );
        
        if (empty($aResult)) {
            self::$aCache[$key] = false;
            
            return false;
        }
        
        self::$aCache[$key] = $aResult;
        
        return $aResult;
    }
    
    public static function getListingPromotions($promotionIDOrListingId)
    {
        global $wpdb;
        if (isset(self::$aCache[$promotionIDOrListingId])) {
            return self::$aCache[$promotionIDOrListingId];
        }
        
        if (get_post_type($promotionIDOrListingId) === 'promotion') {
            $promotionID = $promotionIDOrListingId;
        } else {
            $promotionID = GetSettings::getPostMeta($promotionIDOrListingId, 'belongs_to_promotion');
        }
        
        if (empty($promotionID)) {
            return [];
        }
        
        $aPromotionPlans = GetSettings::getPromotionPlans();
        
        if (empty($aPromotionPlans)) {
            self::$aCache[$promotionIDOrListingId] = false;
            
            return false;
        }
        $aPromotionPositions = [];
        
        foreach ($aPromotionPlans as $position => $aSettings) {
            $aPromotionPositions[] = $wpdb->_real_escape(General::addPrefixToPromotionPosition($position));
        }
        
        $aResults = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT $wpdb->postmeta.* FROM $wpdb->postmeta LEFT JOIN $wpdb->posts ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) WHERE $wpdb->postmeta.post_id=%d AND $wpdb->posts.post_status='publish' AND $wpdb->postmeta.meta_key IN ('".
                implode("','", $aPromotionPositions)."')",
                $promotionID
            ),
            ARRAY_A
        );
        
        if (empty($aResults)) {
            self::$aCache[$promotionIDOrListingId] = false;
            
            return false;
        }
        
        self::$aCache[$promotionIDOrListingId] = $aResults;
        
        return $aResults;
    }
}
