<?php

namespace WilokeListingTools\Framework\Helpers;

use DateTime;
use DateTimeZone;
use Exception;
use WilokeThemeOptions;

class Time
{
    public static function convertOffsetToString($timezone)
    {
        if (strpos($timezone, 'UTC') === false) {
            return $timezone;
        }

        $aTimezones = wilokeListingToolsRepository()->get('utcToGmt', false);

        if (isset($aTimezones[$timezone])) {
            return $aTimezones[$timezone];
        }

        $time = str_replace(['UTC', '+'], ['', ''], $timezone);
        $aParseTime = explode(':', $time);
        $time = intval($aParseTime[0]);

        if (isset($aParseTime[1])) {
            $aParseTime[1] = floatval($aParseTime[1]);
            switch ($aParseTime[1]) {
                case 30:
                    $time = $time + 0.5;
                    break;
                case 45:
                    $time = $time + 0.75;
                    break;
                case 15:
                    $time = $time + 0.25;
                    break;
            }
        }

        if (empty($time)) {
            return 'UTC';
        }

        $utcOffset = intval($time * 3600);

        if ($gmtTimezone = timezone_name_from_abbr('', $utcOffset)) {
            return $gmtTimezone;
        }

        $isGetFirst = false;
        foreach (timezone_abbreviations_list() as $abbr) {
            foreach ($abbr as $city) {
                if ($city['timezone_id'] && intval($city['offset']) == $utcOffset) {
                    if ((bool)date('I') === (bool)$city['dst']) {
                        return $city['timezone_id'];
                    }
                    if (!$isGetFirst) {
                        $isGetFirst = true;
                        $timezone = $city['timezone_id'];
                    }
                }
            }
        }

        // fallback to UTC
        return $timezone;
    }

    public static function convertUTCToGMT($timezone)
    {
        return self::convertOffsetToString($timezone);
    }

    /*
     * Compare 2 Times
     *
     * @var $biggerThan (int) Bigger than X day
     * @since 1.2.0
     */
    public static function compareTwoTimes($timestampA, $timestampB = 0, $biggerThan = 1)
    {
        $timestampB = empty($timestampB) ? current_time('timestamp') : $timestampB;
        $timeDiff = $timestampA - $timestampB;

        if ($timeDiff < 0) {
            return false;
        }

        return $timeDiff > 86400 * absint($biggerThan);
    }

    public static function compareDateTime($f, $s, $compare)
    {
        $f = trim($f);
        $s = trim($s);
        if ($compare === '=') {
            return $f === $s;
        }
        if (!is_numeric($f)) {
            $f = strtotime($f);
        }
        if (!is_numeric($s)) {
            $s = strtotime($s);
        }
        $fLocalDate = date('Y-m-d H:i:s', $f);
        $sLocalDate = date('Y-m-d H:i:s', $s);

        try {
            $fD = new DateTime($fLocalDate);
            $sD = new DateTime($sLocalDate);

            $result = false;
            switch ($compare) {
                case '>':
                    $result = $fD > $sD;
                    break;
                case '>=':
                    $result = $fD >= $sD;
                    break;
                case '<':
                    $result = $fD < $sD;
                    break;
                case '<=':
                    $result = $fD <= $sD;
                    break;
            }
        }
        catch (Exception $e) {

        }

        return $result;
    }

    public static function convertJSDateFormatToPHPDateFormat($format)
    {
        $format = str_replace(['yy', 'mm', 'dd'], ['Y', 'm', 'd'], $format);

        return apply_filters('wilcity/filter/convert-js-date-format-to-php-date-format', $format);
    }

    public static function convertBackendEventDateFormat()
    {
        $dateFormat = get_option('date_format');

        return str_replace(
            [
                'F j, Y',
                'F j Y',
                'j F, Y',
                'j F Y'
            ],
            [
                'm/d/Y',
                'm/d/Y',
                'd/m/Y',
                'd/m/Y'
            ],
            $dateFormat
        );
    }

    public static function toTimestamp($format, $date, $timezone = 'UTC')
    {
        if (is_numeric($date)) {
            return $date;
        }

        if (!empty($timezone)) {
            $timezone = self::convertOffsetToString($timezone);
            $timezone = new DateTimeZone($timezone);
        }

        $format = self::convertJSDateFormatToPHPDateFormat($format);
        $oDT = DateTime::createFromFormat($format, $date, $timezone);

        return $oDT ? $oDT->getTimestamp() : '';
    }

    public static function isDateInThisWeek($day)
    {
        $monday = date('Y-m-d', strtotime('monday this week'));
        $sunday = date('Y-m-d', strtotime('sunday this week'));
        $day = date('Y-m-d', strtotime($day));

        return $day >= $monday && $day <= $sunday;
    }

    public static function getPostDate($postDate)
    {
        return date_i18n(get_option('date_format'), strtotime($postDate));
    }

    public static function renderPostDate($timestamp)
    {
        return date_i18n(get_option('date_format'), $timestamp);
    }

    public static function renderPostTime($timestamp)
    {
        return date_i18n(get_option('time_format'), $timestamp);
    }

    public static function getDateTimeFormat(DateTime $oDateTime, $timeFormat)
    {
        switch ($timeFormat) {
            case 'mysql':
                $response = $oDateTime->format('Y-m-d H:i:s');
                break;
            case 'timestamp':
                $response = $oDateTime->getTimestamp();
                break;
            case 'timezone':
                $response = $oDateTime->getTimezone();
                break;
            default:
                $response = $oDateTime->format($timeFormat);
                break;
        }

        return $response;
    }

    /**
     * @param           $type
     * @param DateTime $oDateTime
     *
     * @return DateTime
     */
    public static function setHourType($type, DateTime $oDateTime)
    {
        switch ($type) {
            case 'earliest':
                $oDateTime->setTime(0, 0, 0);
                break;
            case 'latest':
                $oDateTime->setTime(23, 59, 59);
                break;
        }

        return $oDateTime;
    }

    /**
     * @param        $format
     * @param        $timeFormat
     * @param string $specialType
     *
     * @throws Exception
     */
    public static function getDateTime($format, $timeFormat, $specialType = '')
    {
        $oDateTime = new DateTime($format);
        $oDateTime = self::setHourType($specialType, $oDateTime);

        return self::getDateTimeFormat($oDateTime, $timeFormat);
    }

    /**
     * @return int
     * @throws Exception
     */
    public static function getTomorrow($type = 'latest', $timeFormat = 'timestamp')
    {
        return self::getDateTime('tomorrow', $timeFormat, $type);
    }

    /**
     * @param string $type
     * @param string $timeFormat
     *
     * @return DateTimeZone|int|string
     * @throws Exception
     */
    public static function getToday($type = 'latest', $timeFormat = 'timestamp')
    {
        return self::getDateTime('today', $timeFormat, $type);
    }

    /**
     * @param      $xDays
     * @param bool $isStartOfDay which means We will get next timestamp from 00:00:00 of next x days
     *
     * @return int
     * @throws Exception
     */
    public static function getTimestampNextDays($xDays, $type = 'latest', $timeFormat = 'timestamp')
    {
        return self::getDateTime('+' . $xDays . ' day', $timeFormat, $type);
    }

    /**
     * @param string $type
     * @param string $timeFormat
     *
     * @return DateTimeZone|int|string
     * @throws Exception
     */
    public static function getFirstDayOfNextWeek($type = 'latest', $timeFormat = 'timestamp')
    {
        return self::getDateTime('Monday next week', $timeFormat, $type);
    }

    /**
     * @param string $type
     * @param string $timeFormat
     *
     * @return DateTimeZone|int|string
     * @throws Exception
     */
    public static function getLastDayOfNextWeek($type = 'latest', $timeFormat = 'timestamp')
    {
        return self::getDateTime('Sunday next week', $timeFormat, $type);
    }

    /**
     * @param string $type
     * @param string $timeFormat
     *
     * @return DateTimeZone|int|string
     * @throws Exception
     */
    public static function getFirstDayOfThisMonth($type = 'latest', $timeFormat = 'timestamp')
    {
        return self::getDateTime('first day of this month', $timeFormat, $type);
    }

    /**
     * @param string $type
     * @param string $timeFormat
     *
     * @return DateTimeZone|int|string
     * @throws Exception
     */
    public static function getLastDayOfThisMonth($type = 'latest', $timeFormat = 'timestamp')
    {
        return self::getDateTime('last day of this month', $timeFormat, $type);
    }

    /**
     * @param string $type
     * @param string $timeFormat
     *
     * @return DateTimeZone|int|string
     * @throws Exception
     */
    public static function getLastDayOfThisWeek($type = 'latest', $timeFormat = 'timestamp')
    {
        return self::getDateTime('Sunday this week', $timeFormat, $type);
    }

    /**
     * @param string $type
     * @param string $timeFormat
     *
     * @return DateTimeZone|int|string
     * @throws Exception
     */
    public static function getFirstDayOfThisWeek($type = 'latest', $timeFormat = 'timestamp')
    {
        return self::getDateTime('Monday this week', $timeFormat, $type);
    }

    public static function renderTimeFormat($timestamp, $postID)
    {

        $timeFormat = GetSettings::getPostMeta($postID, 'event_time_format');

        if (empty($timeFormat) || $timeFormat == 'inherit') {
            $timeFormat = WilokeThemeOptions::getOptionDetail('timeformat');
        }

        if (empty($timeFormat)) {
            return date_i18n(get_option('time_format'), $timestamp);
        }

        if ($timeFormat == 12) {
            return date('h:i A', $timestamp);
        }

        return date('H:i', $timestamp);
    }

    public static function getAllDaysInThis($timeFormat = 'Y-m-d')
    {
        return [
            'sunday'    => date($timeFormat, strtotime('sunday this week')),
            'monday'    => date($timeFormat, strtotime('monday this week')),
            'tuesday'   => date($timeFormat, strtotime('tuesday this week')),
            'wednesday' => date($timeFormat, strtotime('wednesday this week')),
            'thursday'  => date($timeFormat, strtotime('thursday this week')),
            'friday'    => date($timeFormat, strtotime('friday this week')),
            'saturday'  => date($timeFormat, strtotime('saturday this week'))
        ];
    }

    public static function getDayKeyOfWeek($today)
    {
        $aDayOfWeek = wilokeListingToolsRepository()->get('general:aDayOfWeek');
        $aDayOfWeekKey = array_keys($aDayOfWeek);

        return $aDayOfWeekKey[$today - 1];
    }

    public static function dateDiff($timestamp1, $timestamp2, $diffIn = 'hour')
    {
        $diff = $timestamp2 - $timestamp1;
        switch ($diffIn) {
            case 'day':
                return floor($diff / (60 * 60 * 24));
                break;
            case 'minute':
                return floor($diff / 60);
                break;
            case 'hour':
                return floor($diff / (60 * 60));
                break;
            default:
                return $diff;
                break;
        }
    }

    public static function timeFromNow($timestamp, $isUTC = false)
    {
        $now = $isUTC ? current_time('timestamp', true) : current_time('timestamp');
        $minutes = self::dateDiff($timestamp, $now, 'minute');

        if ($minutes < 60) {
            return sprintf(_n('%s minute ago', '%s minutes ago', $minutes, 'wiloke-listing-tools'), $minutes);
        }

        $hours = self::dateDiff($timestamp, $now, 'minute');
        if ($hours < 24) {
            return sprintf(_n('%s hour ago', '%s hours ago', $hours, 'wiloke-listing-tools'), $hours);
        }

        return date_i18n(get_option('date_format'), $timestamp);
    }

    public static function getTimeFormat($format = '')
    {
        switch ($format) {
            case '24':
                $format = 'H:i';
                break;
            case '12':
                $format = 'h:i A';
                break;
            default:
                $aThemeOptions = class_exists('Wiloke') ? \Wiloke::getThemeOptions() : [];
                $format = isset($aThemeOptions['timeformat']) ? $aThemeOptions['timeformat'] : 12;
                if ($format == 12) {
                    $format = 'h:i A';
                } else {
                    $format = 'H:i';
                }
                break;
        }

        return $format;
    }

    public static function getJSTimeFormat($format = '')
    {
        switch ($format) {
            case '24':
                $format = 'HH:mm';
                break;
            case '12':
                $format = 'hh:mm A';
                break;
            default:
                $aThemeOptions = class_exists('Wiloke') ? \Wiloke::getThemeOptions() : [];
                $format = isset($aThemeOptions['timeformat']) ? $aThemeOptions['timeformat'] : 12;

                if ($format == 12) {
                    $format = 'hh:mm A';
                } else {
                    $format = 'HH:mm';
                }
                break;
        }

        return $format;
    }

    public static function toMysqlDateFormat($time = '', $isUTC = false)
    {
        if (empty($time)) {
            $time = current_time('timestamp', $isUTC);
        }

        return date('Y-m-d', $time);
    }

    public static function toTwelveFormat($timestamp)
    {
        return date('h:i A', $timestamp);
    }

    public static function renderTime($hour, $format)
    {
        $format = self::getTimeFormat($format);

        return date($format, strtotime($hour));
    }

    public static function toDateFormat($date, $format = '')
    {
        $dateFormat = empty($format) ? get_option('date_format') : $format;
        $date = is_numeric($date) ? $date : strtotime($date);

        return date_i18n($dateFormat, $date);
    }

    public static function toTimeFormat($time, $format = '')
    {
        $time = is_numeric($time) ? $time : strtotime($time);
        $format = self::getTimeFormat($format);

        return date($format, $time);
    }

    public static function findUTCOffsetByTimezoneID($timezone)
    {
        if (strpos($timezone, 'UTC') === 0) {
            return $timezone;
        }

        if (empty($timezone)) {
            return 'UTC';
        }

        $dtz = new DateTimeZone($timezone);
        try {
            $timeZoneIn = new DateTime('now', $dtz);
        }
        catch (Exception $e) {

        }

        $offset = $dtz->getOffset($timeZoneIn);
        $utcOffset = $offset / 3600;

        if ($utcOffset > 0) {
            return 'UTC+' . $utcOffset;
        }

        return 'UTC' . $utcOffset;
    }

    public static function timeStampNow()
    {
        return current_time('timestamp');
    }

    /*
     * Return object $oNow
     */
    public static function getAtomString()
    {
        return date(DATE_ATOM, current_time('timestamp'));
    }

    public static function getAtomUTCString()
    {
        return date(DATE_ATOM, current_time('timestamp', 1));
    }

    /*
     * String To UTC Time
     *
     * @param number $timestamp
     * @return string $date
     */
    public static function toAtomUTC($timeStamp)
    {
        $timeStamp = preg_match('/[^0-9]/', $timeStamp) ? strtotime($timeStamp) : $timeStamp;

        //        date_default_timezone_set("UTC");

        return self::convertToNewDateFormat($timeStamp, DATE_ATOM, 'UTC');
    }

    public static function toAtom($timeStamp)
    {
        return date(DATE_ATOM, $timeStamp);
    }

    public static function iso8601StartDate()
    {
        $startDate = date('c', current_time('timestamp'));
        $startDate = date('c', strtotime($startDate . ' +1 day'));
        $startDate = str_replace('+00:00', 'Z', $startDate);

        return $startDate;
    }

    public static function utcToLocal($utc, $timezone)
    {
        //        date_default_timezone_set($timezone);

        return strtotime($utc);
    }

    /**
     * Get timestamp UTC now
     */
    public static function timestampUTCNow($plus = '')
    {
        //        date_default_timezone_set('UTC');
        return empty($plus) ? time() : strtotime($plus);
    }

    /**
     * Get timestamp UTC now
     */
    public static function timestampUTC($timestamp, $plus = null)
    {
        return strtotime(self::toAtomUTC($timestamp) . ' ' . $plus);
    }

    public static function convertDayToSeconds($day)
    {
        return $day * 24 * 60 * 60;
    }

    public static function convertSecondsToDay($seconds, $type = 'floor')
    {
        if ($type == 'floor') {
            return floor($seconds / (24 * 60 * 60));
        } else {
            return ceil($seconds / (24 * 60 * 60));
        }
    }

    public static function convertToTimezoneUTC($timestamp, $fromTimezone, $format = 'Y-m-d')
    {
        if (empty($fromTimezone)) {
            $msg = sprintf(__(
                'Please set Timezone to this post or go to <a href="%s">General</a> -> Set General Timezone for your site. Note that you have to use GMT format instead of UTC format',
                'wiloke-listing-tools'
            ), admin_url('options-general.php'));
            if (wp_doing_ajax()) {
                wp_send_json_error(
                    [
                        'msg' => $msg
                    ]
                );
            }
            wp_die($msg);
        }

        if (!is_numeric($timestamp)) {
            $timestamp = strtotime($timestamp);
        }
        $localDate = date('Y-m-d H:i:s', $timestamp);

        $fromTimezone = self::convertGMTToUtc($fromTimezone);

        try {
            $dt = new DateTime($localDate, new DateTimeZone($fromTimezone));
            $dt->setTimeZone(new DateTimeZone('UTC'));

            return $dt->format($format);
        }
        catch (Exception $e) {
            return null;
        }
    }

    public static function dayOfWeekKeys()
    {
        $aDayOfWeek = wilokeListingToolsRepository()->get('general:aDayOfWeek');

        return array_keys($aDayOfWeek);
    }

    public static function getDayKey($index)
    {
        $aKeys = self::dayOfWeekKeys();

        return $aKeys[$index];
    }

    public static function getCurrentDayKey($timezone)
    {

    }

    public static function mysqlDate($timestamp = null)
    {
        $timestamp = empty($timestamp) ? current_time('timestamp') : $timestamp;

        return date('Y-m-d', $timestamp);
    }

    public static function getDefaultTimezoneString()
    {
        $timezoneString = GetSettings::getOptions('timezone_string');
        if (empty($timezoneString)) {
            $timezoneString = GetSettings::getOptions('gmt_offset');
            if (preg_match('/[0-9]/', $timezoneString)) {
                if (strpos($timezoneString, 'UTC') === false) {
                    $timezoneString = strpos($timezoneString, '-') === false ? 'UTC+' . $timezoneString : 'UTC' .
                        $timezoneString;
                }
            }
            return self::convertOffsetToString($timezoneString);
        }

        return $timezoneString;
    }

    public static function getDefaultTimezone()
    {
        if (!function_exists('current_datetime')) {
            return self::getDefaultTimezoneString();
        }

        return current_datetime()->getTimezone()->getName();
    }

    /**
     * @param        $timestamp
     * @param        $newFormat
     * @param string $newUTC
     *
     * @return false|string
     */
    public static function convertToNewDateFormat($timestamp, $newFormat, string $newUTC = '')
    {
        if (!is_numeric($timestamp)) {
            $timestamp = strtotime($timestamp);
        }
        $localDate = date('Y-m-d H:i:s', $timestamp);

        try {
            $dateTime = new DateTime($localDate);
            if (!empty($newUTC)) {
                $newUTC = self::convertOffsetToString($newUTC);
                $timezone = new DateTimeZone($newUTC);
                $dateTime->setTimezone($timezone);
            }

            return $dateTime->format($newFormat);
        }
        catch (Exception $err) {
            return date($newFormat, $timestamp);
        }
    }

    /**
     *
     * @param $dateTime String format is Y-m-d H:i:s
     * @param string $dateFormat
     * @return string
     */
    public static function convertFromCurrentSiteTimezoneToUTC(string $dateTime,
                                                               string $dateFormat = "Y-m-d H:i:s"): string
    {
        try {
            $localDate = new DateTime($dateTime, new DateTimeZone(Time::getDefaultTimezoneString()));
            $localDate->setTimezone(new DateTimeZone('UTC'));
            return $localDate->format($dateFormat);
        }
        catch (Exception $err) {
            return "";
        }
    }

    public static function resolveJSAndPHPTimestamp($timestamp)
    {
//        $now = time();
//        if ($timestamp / $now > 500) {
            return $timestamp / 1000;
//        }

//        return $timestamp;
    }

    public static function mysqlDateTimeWithWebsiteTimezone($timestamp = '')
    {
        $timestamp = empty($timestamp) ? current_time('timestamp') : $timestamp;
        return self::convertToNewDateFormat($timestamp, 'Y-m-d H:i:s');
    }

    public static function mysqlDateTime($timestamp = '', $timezone = '')
    {
        $timestamp = empty($timestamp) ? current_time('timestamp') : $timestamp;
        $timezone = empty($timezone) ? self::getDefaultTimezone() : self::convertOffsetToString($timezone);

        return self::convertToNewDateFormat($timestamp, 'Y-m-d H:i:s', $timezone);
    }

    public static function toMysqlTimeFormat($timestamp)
    {
        $localDate = date('Y-m-d H:i:s', $timestamp);

        try {
            $dateTime = new DateTime($localDate);
        }
        catch (Exception $e) {
            return false;
        }

        return $dateTime->format('Y-m-d H:i:s');
    }

    /**
     * @param $gmtTimezone
     *
     * @return bool|string
     */
    public static function convertGMTToUtc($gmtTimezone)
    {
        $offset = self::timestampOffset($gmtTimezone);
        if ($offset === false) {
            return false;
        }

        return 'UTC' . $offset / 3600;
    }

    /**
     * @param string $gmtTimezone
     *
     * @return bool|float|int|mixed
     */
    public static function timestampOffset($gmtTimezone = '')
    {
        if (empty($gmtTimezone)) {
            $gmtTimezone = get_option('timezone_string');
        }

        if ($gmtTimezone === 'UTC' || $gmtTimezone === 'UTC+0') {
            return 0;
        }

        if (strpos($gmtTimezone, 'UTC') !== false) {
            $offset = str_replace('UTC', '', $gmtTimezone);
            if (strpos($gmtTimezone, '-') === false) {
                return 3600 * absint($offset);
            } else {
                return -3600 * absint($offset);
            }
        }

        $userTimezone = new DateTimeZone($gmtTimezone);
        $gmtTimezone = new DateTimeZone('GMT');
        try {
            $myDateTime = new DateTime(date('Y-m-d H:i', time()), $gmtTimezone);

            $offset = $userTimezone->getOffset($myDateTime);

            return $offset;
        }
        catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                echo $e->getMessage();
            }

            return false;
        }
    }

    /**
     * @param $timestamp
     *
     * @return bool|float|int|mixed
     */
    public static function convertJSTimestampToPHPTimestamp($timestamp)
    {
        $timestamp = floor(absint($timestamp) / 1000);
        $offset = Time::timestampOffset();
        if ($offset !== false) {
            $timestamp = $offset + $timestamp;
        }

        return $timestamp;
    }

    /**
     * @param $timestamp
     *
     * @return bool|float|int|mixed
     */
    public static function convertPHPTimestampToJSTimestamp($timestamp)
    {
        $offset = Time::timestampOffset();
        if ($offset !== false) {
            $timestamp = absint($offset - $timestamp);
        }
        $timestamp = floor(absint($timestamp) * 1000);

        return $timestamp;
    }

    public static function diffTimestamp($firstTimezone, $secondTimezone = 'UTC')
    {
        $firstTimezoneOffset = self::timestampOffset(self::convertUTCToGMT($firstTimezone));
        $secondTimezoneOffset = self::timestampOffset(self::convertUTCToGMT($secondTimezone));

        return $firstTimezoneOffset - $secondTimezoneOffset;
    }

    public static function convertLocalTimestampToUTCTimestamp($timestamp, $localTimestamp = '')
    {
        $localTimestamp = empty($localTimestamp) ? self::getDefaultTimezoneString() : self::convertUTCToGMT
        ($localTimestamp);
        $offset = self::timestampOffset($localTimestamp);

        return $timestamp - $offset;
    }

    public static function convertUTCTimestampToLocalTimestamp($timestamp, $localTimestamp = '')
    {
        $localTimestamp = empty($localTimestamp) ? self::getDefaultTimezoneString() : self::convertUTCToGMT
        ($localTimestamp);
        $offset = self::timestampOffset($localTimestamp);

        return $timestamp + $offset;
    }

    public static function getJsHourFormat()
    {
        return WilokeThemeOptions::getOptionDetail('timeformat') == 12 ? 'hh:mm A' : 'HH:mm';
    }

    public static function getPHPHourFormat()
    {
        return WilokeThemeOptions::getOptionDetail('timeformat') == 12 ? 'h:i a' : 'H:i';
    }
}
