<?php

namespace WilokeListingTools\Frontend;

use Carbon\Carbon;
use WilokeListingTools\Framework\Helpers\DebugStatus;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\MetaBoxes\Listing;

class BusinessHours
{
    public static $aTimezones;
    public static $aUTCs;

    /**
     * Get Time Format
     *
     * @param [Int] $listingID
     *
     * @return String
     */
    public static function getTimeFormat($listingID)
    {
        $timeFormat = GetSettings::getPostMeta($listingID, 'timeFormat', '', 'int');
        if (empty($timeFormat) || $timeFormat === 'inherit') {
            $timeFormat = (int)\WilokeThemeOptions::getOptionDetail('timeformat');
        }

        return $timeFormat;
    }

    public static function isEnableBusinessHour($post = null)
    {
        $postID = is_numeric($post) ? $post : $post->ID;

        if (!empty($postID)) {
            $planID = GetSettings::getListingBelongsToPlan($postID);

            if (!empty($planID) && get_post_status($planID) == 'publish' && get_post_type($planID) == 'listing_plan') {
                $aPlanSettings = GetSettings::getPlanSettings($planID);
                if (
                    isset($aPlanSettings['toggle_business_hours']) &&
                    $aPlanSettings['toggle_business_hours'] == 'disable'
                ) {
                    return false;
                }
            }
            $hourMode = GetSettings::getPostMeta($postID, 'hourMode');
            if (!$hourMode || $hourMode == 'no_hours_available') {
                return false;
            }

            return apply_filters('wilcity/is_enable_business_hour', true);
        }

        return false;
    }

    public static function getTimezone($postID)
    {
        $postID = is_numeric($postID) ? $postID : $postID->ID;

        if (isset(self::$aTimezones[$postID])) {
            return self::$aTimezones[$postID];
        }

        $individualTimeFormat = GetSettings::getPostMeta($postID, 'timezone');
        if (!empty($individualTimeFormat)) {
            self::$aTimezones[$postID] = $individualTimeFormat;
        } else {
            self::$aTimezones[$postID] = Time::getDefaultTimezoneString();
        }

        return self::$aTimezones[$postID];
    }

    public static function getListingUTC($post)
    {
        $timezone = self::getTimezone($post);

        return Time::findUTCOffsetByTimezoneID($timezone);
    }

    public static function getTodayIndex($postID = '')
    {
        return Time::convertToNewDateFormat(current_time('timestamp', true), 'w', self::getTimezone($postID));
    }

    public static function getTodayKey($postID = '')
    {
        return Time::getDayKey(self::getTodayIndex($postID));
    }

    public static function getPrevDayKey($postID = '')
    {
        $todayIndex = self::getTodayIndex($postID);

        $prevIndex = $todayIndex <= 0 ? 6 : $todayIndex;
        $prevIndex = $prevIndex - 1;
        return Time::getDayKey($prevIndex);
    }

    public static function getTodayBusinessHours($postID)
    {
        if (!is_numeric($postID)) {
            $postID = $postID->ID;
        }

        $previousKey = self::getPrevDayKey($postID);
        $aBusinessHour = Listing::getBusinessHoursOfDay($postID, $previousKey);

        if (!empty($aBusinessHour) && is_array($aBusinessHour)) {
            if (!empty($aBusinessHour['secondCloseHour'])) {
                $lastClosedHour = $aBusinessHour['secondCloseHour'];
            } else {
                $lastClosedHour = $aBusinessHour['firstCloseHour'];
            }

	        if(!empty($lastClosedHour)){
		        $convertLastHourToNumber = absint(str_replace(':', '', $lastClosedHour));
		        try {
			        $oSevenAM = Carbon::createFromTime('07', '00', '00', Time::getDefaultTimezoneString());

			        if ($convertLastHourToNumber < 70000 &&
				        $oSevenAM->getTimestamp() > Carbon::now(Time::getDefaultTimezoneString())->getTimestamp()) {
				        return $aBusinessHour;
			        }
		        }
		        catch (\Exception $exception) {

		        }
	        }
        }

        return Listing::getBusinessHoursOfDay($postID, self::getTodayKey($postID));
    }

    public static function getPrevBusinessHour($postID)
    {
        if (!is_numeric($postID)) {
            $postID = $postID->ID;
        }

        $prevDayKey = self::getPrevDayKey($postID);

        return Listing::getBusinessHoursOfDay($postID, $prevDayKey);
    }

    public static function isSecondHourExists($aTodayBusinessHour)
    {
        if (
            empty($aTodayBusinessHour['secondOpenHour']) || empty($aTodayBusinessHour['secondCloseHour']) ||
            $aTodayBusinessHour['secondOpenHour'] == $aTodayBusinessHour['secondCloseHour']
        ) {
            return false;
        }

        return true;
    }

    public static function invalidFirstHours($aTodayBusinessHour)
    {
        if (
            empty($aTodayBusinessHour['firstOpenHour']) || empty($aTodayBusinessHour['firstCloseHour']) ||
            ($aTodayBusinessHour['firstOpenHour'] == $aTodayBusinessHour['firstCloseHour'] &&
                $aTodayBusinessHour['firstCloseHour'] !== '24:00:00')
        ) {
            return true;
        }

        return false;
    }

    public static function getAllBusinessHours($postID)
    {
        if (!is_numeric($postID)) {
            $postID = $postID->ID;
        }

        $hourMode = GetSettings::getPostMeta($postID, 'hourMode');

        if (empty($hourMode)) {
            return [
                'mode' => 'no_hours_available'
            ];
        }

        if (in_array($hourMode, ['always_open', 'no_hours_available'])) {
            return [
                'mode' => $hourMode
            ];
        }

        $aDays = array_keys(wilokeListingToolsRepository()->get('general:aDayOfWeek', false));
        $aOperatingTimes = [];
        $timeFormat = get_option('time_format');

        foreach ($aDays as $day) {
            $aBusinessHour = Listing::getBusinessHoursOfDay($postID, $day);

            if (!empty($aBusinessHour['firstOpenHour'])) {
                $aBusinessHour['firstOpenHour']
                    = date('H:i:s', strtotime($aBusinessHour['firstOpenHour']));
                $aBusinessHour['humanReadableFirstOpenHour'] = date($timeFormat,
                    strtotime($aBusinessHour['firstOpenHour']));
            }

            if (!empty($aBusinessHour['firstCloseHour'])) {
                $aBusinessHour['firstCloseHour'] = date('H:i:s', strtotime($aBusinessHour['firstCloseHour']));
                $aBusinessHour['humanReadableFirstCloseHour'] = date($timeFormat,
                    strtotime($aBusinessHour['firstCloseHour']));
            }

            if (!empty($aBusinessHour['secondOpenHour'])) {
                $aBusinessHour['secondOpenHour'] = date('H:i:s', strtotime($aBusinessHour['secondOpenHour']));
                $aBusinessHour['humanReadableSecondOpenHour'] = date($timeFormat,
                    strtotime($aBusinessHour['secondOpenHour']));
            }

            if (!empty($aBusinessHour['secondCloseHour'])) {
                $aBusinessHour['secondCloseHour'] = date('H:i:s', strtotime($aBusinessHour['secondCloseHour']));
                $aBusinessHour['humanReadableSecondCloseHour'] = date($timeFormat,
                    strtotime($aBusinessHour['secondCloseHour']));
            }

            $aOperatingTimes[$day] = $aBusinessHour;
        }

        return [
            'mode'            => $hourMode,
            'operating_times' => $aOperatingTimes,
            'timezone'        => self::getTimezone($postID)
        ];
    }

    public static function getCurrentBusinessHourStatus($postID, $aTodayBusinessHour = [])
    {
        if (!is_numeric($postID)) {
            $postID = $postID->ID;
        }

        $openNow = __('Open now', 'wiloke-listing-tools');
        $closed = __('Closed', 'wiloke-listing-tools');
        $hourMode = GetSettings::getPostMeta($postID, 'hourMode');
        $todayKey = is_array($aTodayBusinessHour) && isset($aTodayBusinessHour['dayOfWeek']) ?
            $aTodayBusinessHour['dayOfWeek'] : self::getTodayKey
            ($postID);
        $timezone = self::getTimezone($postID);

        if (empty($aTodayBusinessHour)) {
            if ($hourMode == 'always_open') {
                return [
                    'status' => 'open',
                    'class'  => 'color-secondary',
                    'text'   => $openNow,
                    'dayKey' => $todayKey
                ];
            }
            $aTodayBusinessHour = self::getTodayBusinessHours($postID);
        }

        if ($hourMode == 'business_closures' || !$aTodayBusinessHour || $aTodayBusinessHour['isOpen'] == 'no') {
            return [
                'status' => 'close',
                'class'  => 'color-quaternary',
                'text'   => esc_html__('Closures', 'wiloke-listing-tools'),
                'dayKey' => $todayKey
            ];
        }

        $today = date('Y-m-d', time());
        $firstStart = Time::convertToNewDateFormat(
            strtotime($today . ' ' . $aTodayBusinessHour['firstOpenHour']), 'H:i:s'
        );

        $firstClosed = Time::convertToNewDateFormat(
            strtotime($today . ' ' . $aTodayBusinessHour['firstCloseHour']),
            'H:i:s'
        );

        $currentHour = Time::convertToNewDateFormat(time(), 'H:i:s', $timezone);

        if (Time::compareDateTime($currentHour, $firstStart, '>=') &&
            Time::compareDateTime($currentHour, $firstClosed, '<=')) {
            return array_merge([
                'status' => 'open',
                'class'  => 'color-secondary',
                'text'   => $openNow,
                'dayKey' => $todayKey
            ], $aTodayBusinessHour);
        }
        $lastOpen = $firstStart;
        $lastEnd = $firstClosed;
        // If the closed is middle night, We should understand that it's 23:59:59
        if ($lastEnd == '00:00:00') {
            $lastEnd = '23:59:59';
        }

        if (self::isSecondHourExists($aTodayBusinessHour)) {
            $secondStart = Time::convertToNewDateFormat(
                strtotime($today . ' ' . $aTodayBusinessHour['secondOpenHour']),
                'H:i:s'
            );

            $secondClosed = Time::convertToNewDateFormat(
                strtotime($today . ' ' . $aTodayBusinessHour['secondCloseHour']),
                'H:i:s'
            );

            if (Time::compareDateTime($currentHour, $secondStart, '>=') &&
                Time::compareDateTime($currentHour, $secondClosed, '<=')) {
                return array_merge([
                    'status' => 'open',
                    'class'  => 'color-secondary',
                    'text'   => $openNow,
                    'dayKey' => $todayKey
                ], $aTodayBusinessHour);
            }
            $lastOpen = $secondStart;
            $lastEnd = $secondClosed;

            if ($lastEnd == '00:00:00') {
                $lastEnd = '23:59:59';
            }
        }

        if ($lastEnd === '23:59:59') {
            if (Time::compareDateTime($currentHour, $lastOpen, '>=') &&
                Time::compareDateTime($currentHour, $lastEnd, '<=')) {
                return array_merge([
                    'status' => 'open',
                    'class'  => 'color-secondary',
                    'text'   => $openNow,
                    'dayKey' => $todayKey
                ], $aTodayBusinessHour);
            }
        }

        if (Time::compareDateTime($lastOpen, $lastEnd, '>') &&
            (
                Time::compareDateTime($currentHour, $lastOpen, '>') ||
                Time::compareDateTime($currentHour, $lastEnd, '<')
            )
        ) {
            return array_merge([
                'status' => 'open',
                'class'  => 'color-secondary',
                'text'   => $openNow,
                'dayKey' => $todayKey
            ], $aTodayBusinessHour);
        }

        $aPrevDayHours = self::getPrevBusinessHour($postID);

        if ($aPrevDayHours['isOpen'] == 'yes') {
            $lastClosedHour = !empty($aPrevDayHours['secondCloseHour']) ? $aPrevDayHours['secondCloseHour'] :
                $aPrevDayHours['secondCloseHour'];
            if (!empty($lastClosedHour)) {
                $secondClosed = Time::convertToNewDateFormat(strtotime($today . ' ' . $lastClosedHour), 'H:i:s');
                $secondClosedToNumber = absint(str_replace(':', '', $lastClosedHour));
                if ($secondClosedToNumber < 70100 && Time::compareDateTime($secondClosed, $currentHour, '>=')) {
                    $prevDayKey = self::getPrevDayKey($postID);

                    return array_merge($aPrevDayHours, [
                        'status'    => 'open',
                        'class'     => 'color-secondary',
                        'text'      => $openNow,
                        'dayKey'    => $prevDayKey,
                        'isPrevDay' => true // this business is overnight and it's opening
                    ]);
                }
            }
        }

        $aTodayBusinessHour = is_array($aTodayBusinessHour) ? $aTodayBusinessHour : [];

        return wp_parse_args([
            'status' => 'close',
            'class'  => 'color-quaternary',
            'text'   => $closed,
            'dayKey' => $todayKey
        ], $aTodayBusinessHour);
    }
}
