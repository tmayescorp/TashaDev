<?php

namespace WilokeListingTools\Models;

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Models\Coupon as CouponModel;

class Coupon
{
    private static $aCoupon = [];
    private static $aCouponAllInfo;

    private static function getPostId($post)
    {
        $postID = '';
        if (is_array($post) && isset($post['render_row_cb'])) { // This is cmb2 callback
            $post = '';
        }

        if (!empty($post)) {
            $postID = is_object($post) ? $post->ID : $post;
        } else if (isset($_GET['post'])) {
            $postID = $_GET['post'];
        }

        return $postID;
    }

    public static function getCoupon($postID, $field = '')
    {
        $postID = self::getPostId($postID);

        if (is_array(self::$aCoupon) && isset(self::$aCoupon[$postID])) {
            $aCoupon = self::$aCoupon[$postID];
        } else {
            $aCoupon = GetSettings::getPostMeta($postID, 'coupon');
            $aCoupon = !is_array($aCoupon) ? [] : $aCoupon;
            self::$aCoupon[$postID] = $aCoupon;
        }

        if (empty($aCoupon)) {
            return empty($field) ? [] : "";
        }

        if (empty($field)) {
            return $aCoupon;
        }

        if ($field === 'utcTimestamp') {
            if (!empty($aCoupon['expiry_date'])) {
                return $aCoupon['expiry_date'];
            }

            return 0;
        }

        // The Front-end is using default timezone, but the back-end is using UTC timestamp, so We have to convert it to default timezone to back-end looks like front-end
        if ($field == 'expiry_date' && !empty($aCoupon[$field])) {
            $toCurrentUTC = Time::convertToNewDateFormat(
                $aCoupon[$field],
                'Y-m-d H:i:s',
                Time::getDefaultTimezone()
            );

            return strtotime($toCurrentUTC);
        }

        return $aCoupon[$field] ?? '';
    }

    public static function getAllCouponInfo($postID)
    {
        $postID = self::getPostId($postID);

        if (isset(self::$aCouponAllInfo[$postID])) {
            return self::$aCouponAllInfo[$postID];
        }

        $aFields = [
            'popup_image',
            'title',
            'expiry',
            'description',
            'highlight',
            'code',
            'redirect_to',
            'popup_description',
            'timestamp',
            'utcTimestamp'
        ];

        if (self::isExpired($postID)) {
            return [];
        }

        $aResponse = [];
        foreach ($aFields as $fieldKey) {
            $aResponse[$fieldKey] = self::getCouponInfo($fieldKey, $postID);
        }

        self::$aCouponAllInfo[$postID] = $aResponse;

        return $aResponse;
    }

    public static function getCouponInfo($field, $postID = null)
    {
        $postID = self::getPostId($postID);
        if (!empty($postID)) {
            if ($field === 'timestamp') {
                $val = self::getCoupon($postID, 'expiry_date');
            } else {
                $val = self::getCoupon($postID, $field);
            }

            return $val;
        }

        return "";
    }

    public static function getPopupImage($postID = null)
    {
        $postID = self::getPostId($postID);
        if (!empty($postID)) {
            $val = self::getCoupon($postID, 'popup_image');
            if (is_numeric($val)) {
                return wp_get_attachment_image_url($val,
                    apply_filters("wilcity/Models/Coupon/getPopupImage/Size", "thumbnail"));
            }

            if (!empty($val)) {
                return $val;
            }
        }

        return "";
    }

    public static function getTitle()
    {
        return self::getCouponInfo('title');
    }

    public static function getExpiry()
    {
        $expiryDate = self::getCouponInfo('expiry_date');

        return empty($expiryDate) ? '' : date('m/d/Y g:i A', $expiryDate);
    }

    public static function isExpired($postID)
    {
        $postID = self::getPostId($postID);
        $expiryDate = self::getCouponInfo('timestamp', $postID);

        return empty($expiryDate) || $expiryDate < \time();
    }

    public static function getDescription()
    {
        return self::getCouponInfo('description');
    }

    public static function getHighlight()
    {
        return self::getCouponInfo('highlight');
    }

    public static function getCode()
    {
        return self::getCouponInfo('code');
    }

    public static function getRedirectTo()
    {
        return self::getCouponInfo('redirect_to');
    }

    public static function getPopupDescription()
    {
        return self::getCouponInfo('popup_description');
    }
}
