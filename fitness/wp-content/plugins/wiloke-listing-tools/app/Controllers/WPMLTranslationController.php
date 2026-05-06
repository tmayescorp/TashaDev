<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\MetaBoxes\Listing;
use WilokeListingTools\Frontend\BusinessHours;
use WilokeListingTools\Framework\Helpers\SetSettings;

/**
 * Class WPMLTranslationController
 * @package WilokeListingTools\Controllers
 */
class WPMLTranslationController
{
	public function __construct()
	{
//		add_action('init',function(){
//			if (isset($_GET['aaa'])) {
////				$aBusinessHours = BusinessHours::getAllBusinessHours(13397);
////				SetSettings::setPostMeta(13549, 'hourMode', $aBusinessHours['mode']);
////				$timeFormat = BusinessHours::getTimeFormat(13397);
////				SetSettings::setPostMeta(13549, 'timeFormat', $timeFormat);
////				if($aBusinessHours['mode']=='open_for_selected_hours'){
////					foreach ($aBusinessHours['operating_times'] as $dayOfWeek => $aValue) {
////						$aBusinessHourInDay = [
////							'firstOpenHour'   => $aValue['firstOpenHour'],
////							'firstCloseHour'  => $aValue['firstCloseHour'],
////							'secondOpenHour'  => $aValue['secondOpenHour'],
////							'secondCloseHour' => $aValue['secondCloseHour'],
////							'isOpen'          => $aValue['isOpen']
////						];
////
////						Listing::updateBusinessHourTbl(13549, $dayOfWeek, $aBusinessHourInDay, $aBusinessHours['timezone']);
////					}
////				}
//
//				$aAddress = Listing::getListingAddress(13391);
//
//				if (!empty($aAddress)) {
//					$aAddress = array_intersect_key($aAddress, [
//						'address' => '',
//						'lat'     => '',
//						'lng'     => ''
//					]);
//					Listing::saveData(13481, $aAddress);
//				}
//			}
//		});
		add_action('wpml_pro_translation_completed', [$this, 'updateFieldsAfterTranslation'], 10, 3);
	}

	/**
	 * @param $newPostId
	 * @param $data
	 * @param $job
	 * @return bool
	 */
	public function updateFieldsAfterTranslation($newPostId, $data, $job)
	{
		if (!isset($job->original_doc_id) || empty($job->original_doc_id)) {
			return false;
		} else {
			$originalPostId = $job->original_doc_id;
		}

		if (!in_array(General::getPostTypeGroup(get_post_type($originalPostId)), ['listing', 'event'])) {
			return false;
		}

		$aBusinessHours = BusinessHours::getAllBusinessHours($originalPostId);
		if(!empty($aBusinessHours)){
			SetSettings::setPostMeta($newPostId, 'hourMode', $aBusinessHours['mode']);
			$timeFormat = BusinessHours::getTimeFormat($originalPostId);
			SetSettings::setPostMeta($newPostId, 'timeFormat', $timeFormat);
			if($aBusinessHours['mode']=='open_for_selected_hours'){
				foreach ($aBusinessHours['operating_times'] as $dayOfWeek => $aValue) {
					$aBusinessHourInDay = array_intersect_key($aValue, [
						'firstOpenHour'   => '',
						'firstCloseHour'  => '',
						'secondOpenHour'  => '',
						'secondCloseHour' => '',
						'isOpen'          => 'no'
					]);

					Listing::updateBusinessHourTbl($newPostId, $dayOfWeek, $aBusinessHourInDay, $aBusinessHours['timezone']);
				}
			}
		}

		$aAddress = Listing::getListingAddress($originalPostId);
		if (!empty($aAddress)) {
			$aAddress = array_intersect_key($aAddress, [
				'address' => '',
				'lat'     => '',
				'lng'     => ''
			]);
			Listing::saveData($newPostId, $aAddress);
		}

	}
}
