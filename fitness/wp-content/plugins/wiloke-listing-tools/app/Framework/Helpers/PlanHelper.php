<?php

namespace WilokeListingTools\Framework\Helpers;

final class PlanHelper
{
    protected static $aPlanSettings;

    public static function sureItemsDoNotExceededPlan($aPlanSettings, $planKey, $aItems)
    {
        if (!isset($aPlanSettings[$planKey]) || empty($aPlanSettings[$planKey])) {
            return $aItems;
        }

        return array_slice($aItems, 0, $aPlanSettings[$planKey], true);
    }

    public static function isEnable($planIdOraPlanSettings, $sectionKey)
    {
        if (is_numeric($planIdOraPlanSettings)) {
            $aPlanSettings = GetSettings::getPlanSettings($planIdOraPlanSettings);
        } else {
            $aPlanSettings = $planIdOraPlanSettings;
        }

        if ($sectionKey == 'video') {
            $sectionKey = 'videos';
        }

        return !isset($aPlanSettings['toggle_' . $sectionKey]) || $aPlanSettings['toggle_' . $sectionKey] === 'enable';
    }

    public static function getPlanSettings($planID)
    {
        self::$aPlanSettings = GetSettings::getPlanSettings($planID);

        return self::$aPlanSettings;
    }

    /**
     * @param $field
     * @param $planID
     *
     * @return string
     */
    public static function getField($field, $planID)
    {
        self::getPlanSettings($planID);

        return is_array(self::$aPlanSettings) && isset(self::$aPlanSettings[$field]) ? self::$aPlanSettings[$field] :
            '';
    }

    /**
     * @param $planID
     *
     * @return float|int
     */
    public static function getRegularPrice($planID)
    {
        $price = self::getField('regular_price', $planID);

        return empty($price) ? 0 : floatval($price);
    }

    /**
     * @param $planID
     *
     * @return bool
     */
    public static function isFreePlan($planID)
    {
        $price = self::getRegularPrice($planID);

        return empty($price);
    }
    
    public static function getPlanCategory($planId)
    {
        $category = GetSettings::getPostMeta($planId, 'listing_plan_category');
        
        return empty($category) ? 'addlisting' : $category;
    }
}
