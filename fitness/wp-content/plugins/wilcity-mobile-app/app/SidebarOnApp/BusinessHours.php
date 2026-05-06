<?php

namespace WILCITY_APP\SidebarOnApp;

use WILCITY_APP\Helpers\App;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Frontend\BusinessHours as BH;
use WilokeListingTools\Framework\Helpers\Time;

class BusinessHours
{
	public function __construct()
	{
		add_filter('wilcity/mobile/sidebar/business_hours', [$this, 'render'], 10);
	}

	public function render($thepost)
	{
		$aBusinessHour = [];
		$aData = App::get('ListingGeneralData')->getData($thepost, ['businessHours']);
		if (isset($aData['newBusinessHours'])) {
			$aBusinessHour = is_array($aData['newBusinessHours']) ? $aData['newBusinessHours'] : [];
		} else if (isset($aData['businessHours'])) {
			$aBusinessHour = is_array($aData['businessHours']) ? $aData['businessHours'] : [];
		}

		return json_encode($aBusinessHour);
	}
}
