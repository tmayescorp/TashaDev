<?php

namespace WilokeListingTools\Framework\Helpers;

use WilokeListingTools\Frontend\User;

class Validation
{
    private static $cb;
    private static $jsonDecoded;

    public static function isUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    public static function isEmail($url)
    {
        return filter_var($url, FILTER_VALIDATE_EMAIL);
    }

    public static function isPostAuthor($postID, $isCheckEventAdmin = false)
    {
        if (!$isCheckEventAdmin) {
            if (User::can('administrator')) {
                return true;
            }
        }

        return $postID == User::getCurrentUserID();
    }

    public static function deepValidation($input, $cb = 'sanitize_text_field')
    {
        if (!is_array($input)) {
            if (!empty(self::$cb)) {
                $cb = self::$cb;
                self::$cb = '';
            }

            if ($cb === 'sanitize_text_field') {
                return implode("\n", array_map('sanitize_textarea_field', explode("\n", $input)));
            }
            return $cb($input);
        }

        self::$cb = $cb;

        return array_map([__CLASS__, 'deepValidation'], $input);
    }

    public static function isValidJson($data, $decodeToArray = true): bool
    {
        self::$jsonDecoded = json_decode($data, $decodeToArray);
        if (json_last_error() !== JSON_ERROR_NONE) {
            self::$jsonDecoded = json_decode(stripslashes($data), $decodeToArray);

            return json_last_error() === JSON_ERROR_NONE;
        }

        return true;
    }

    public static function getJsonDecoded()
    {
        return self::$jsonDecoded;
    }
}

