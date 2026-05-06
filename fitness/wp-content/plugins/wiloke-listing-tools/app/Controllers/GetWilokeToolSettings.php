<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\PlanHelper;

trait GetWilokeToolSettings
{
    public function getAvailableFields($listingType = '')
    {
        if (empty($listingType)) {
            $listingType = isset($_REQUEST['listing_type']) && !empty($_REQUEST['listing_type']) ? $_REQUEST['listing_type'] :
                General::getDefaultPostTypeKey(false, true);
        }

        $availableKey = General::getUsedSectionKey($listingType);
        $aAvailablePlans = GetSettings::getOptions($availableKey, false, true);
        if (self::$disableFieldType === 'toggle') {
            return array_filter($aAvailablePlans, function ($aSection) {
                return PlanHelper::isEnable($this->aPlanSettings, $aSection['key']);
            });
        } else {
            return array_map(function ($aSection) {
                $aSection['fieldStatus'] = PlanHelper::isEnable(
                    $this->aPlanSettings, $aSection['key']
                ) ? 'enable' : 'disable';

                return $aSection;
            }, $aAvailablePlans);
        }
    }
}
