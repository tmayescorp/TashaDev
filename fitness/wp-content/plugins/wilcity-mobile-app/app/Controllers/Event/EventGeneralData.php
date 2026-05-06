<?php

namespace WILCITY_APP\Controllers\Event;

use WILCITY_APP\Controllers\Listing\ListingSkeleton;
use WILCITY_APP\Helpers\App;

class EventGeneralData extends ListingSkeleton
{
	/**
	 * @var array
	 */
	protected $aListingDataPluck
		= [
			'ID',
			'postTitle',
			'isAds',
			'postLink',
			'tagLine',
			'timezone',
			'oCalendar',
			'oFeaturedImg',
			'isMyFavorite',
			'totalFavorites',
			'hostedBy',
			'headerBlock',
			'bodyBlock'
		];

	protected function rebuildBodyBlock($aBodyBlock)
	{
		if (empty($aBodyBlock)) {
			return $aBodyBlock;
		}

		foreach ($aBodyBlock as $key => $aField) {
			if (isset($this->aCustomSectionCategories[$aField['type']])) {
				$aBodyBlock[$key]['type'] = $this->aCustomSectionCategories[$aField['type']];
			}
		}

		return $aBodyBlock;
	}

	public function getData($post, $aPluck = [], $aPluckExcludes = [])
	{
		if (empty($aPluck)) {
			$aPluck = $this->aListingDataPluck;
		}

		if (!empty($aPluckExcludes)) {
			$aPluck = array_diff($aPluck, $aPluckExcludes);
		}

		$aData = App::get('EventSkeleton')->getSkeleton($post, $aPluck);
		if (empty($aData['oCalendar'])) {
			unset($aData['oCalendar']);
		}
		if (!empty($aData['oGallery'])) {
			$aData = $this->rebuildGallery($aData);
		}

		$aData['oFavorite'] = $this->rebuildFavorite($aData);

		if (isset($aData['bodyBlock'])) {
			$aData['bodyBlock'] = $this->rebuildBodyBlock($aData['bodyBlock']);
		}

		// new
		$aData['group'] = 'event';

		return $aData;
	}
}
