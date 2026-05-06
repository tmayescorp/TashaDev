<?php

namespace WilokeListingTools\Framework\Helpers;

class Submission
{
    protected static $aSupportedPostTypes;
    protected static $aAddListingPostTypes;
    protected static $aPlanSettings;

    public static function detectPostStatus()
    {
        if (GetWilokeSubmission::getField('approved_method') == 'manual_review') {
            $newStatus = 'pending';
        } else {
            $newStatus = 'publish';
        }

        return $newStatus;
    }

    public static function convertGalleryToBackendFormat($aRawGallery)
    {
        if (empty($aRawGallery)) {
            return [];
        }

        $aGallery = [];
        if (isset($aRawGallery['id'])) {
            $aGallery[$aRawGallery['id']] = $aRawGallery['src'];
        } else {
            foreach ($aRawGallery as $aImg) {
                $aGallery[$aImg['id']] = $aImg['src'];
            }
        }

        return $aGallery;
    }

    public static function isPlanSupported($planID, $key): bool
    {
        if (!isset(self::$aPlanSettings[$planID])) {
            self::$aPlanSettings[$planID] = GetSettings::getPlanSettings($planID);
        }

        if (isset(self::$aPlanSettings[$planID][$key])) {
            return self::$aPlanSettings[$planID][$key] !== 'disable';
        }

        if (isset(self::$aPlanSettings[$planID]['toggle_'.$key])) {
            return self::$aPlanSettings[$planID]['toggle_'.$key] !== 'disable';
        }

        return true;
    }

    public static function listingStatusWillPublishImmediately($postStatus)
    {
        return !empty($postStatus) && in_array($postStatus, ['expired', 'publish']);
    }

    public static function getAddListingPostTypeKeys()
    {
        self::getSupportedPostTypes();
        $aPlans = GetSettings::getFrontendPostTypes(true);

        foreach ($aPlans as $key => $postType) {
            if (!in_array($postType, self::$aSupportedPostTypes)) {
                unset($aPlans[$key]);
            }
        }

        return $aPlans;
    }

    public static function getSupportedPostTypes()
    {
        if (!empty(self::$aSupportedPostTypes)) {
            return self::$aSupportedPostTypes;
        }

        self::$aSupportedPostTypes = General::getPostTypeKeys(false);

        return self::$aSupportedPostTypes;
    }

    public static function getListingPostTypes()
    {
       return self::getSupportedPostTypes();
    }
}
