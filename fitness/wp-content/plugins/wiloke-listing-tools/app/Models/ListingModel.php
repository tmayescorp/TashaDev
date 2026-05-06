<?php

namespace WilokeListingTools\Models;


use WilokeListingTools\Framework\Helpers\GetSettings;

class ListingModel {
	private static $aCache = array();

	public static function listingBelongsToPromotion($listingID){
		global $wpdb;
		$key = 'promotion_'.$listingID;
  
		if ( isset(self::$aCache[$key]) ){
			return self::$aCache[$key];
		}
		
		$promotionID = GetSettings::getPostMeta($listingID, 'wilcity_belongs_to_promotion');
		
		if (empty($promotionID) || get_post_status($promotionID) !== 'publish') {
            self::$aCache[$key] = false;
            return false;
        }
		
		$postTbl = $wpdb->posts;
		$postmetaTbl = $wpdb->postmeta;

		$aRawResults = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $postmetaTbl LEFT JOIN $postTbl ON ($postTbl.ID = $postmetaTbl.post_id) WHERE $postmetaTbl.post_id=%d",
                $promotionID
			),
			ARRAY_A
		);

		if ( empty($aRawResults) ){
			self::$aCache[$key] = false;
			return false;
		}

		$aResults = array();
		foreach ($aRawResults as $aValue){
			$aResults[] = array(
				'title' => get_the_title($aValue['meta_value']),
				'id'    => $aValue['meta_value']
			);
		}

		self::$aCache[$key] = $aResults;
		return $aResults;
	}
}
