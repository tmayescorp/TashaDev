<?php
/**
 * WilokeThemeOptions Class
 *
 * @category Theme Options
 * @package  Wiloke Framework
 * @author   Wiloke Team
 * @version  1.0
 */

use WilokeListingTools\Framework\Helpers\WPML;

if (!defined('ABSPATH')) {
    exit;
}

class WilokeThemeOptions
{
    /**
     * @var string The key of theme options
     */
    protected static $_key      = 'wiloke_themeoptions';
    protected static $_wpmlKey  = '';
    public           $aArgs     = [];
    public           $aSections = [];

    public static function getMaybeWPMLKey($default = ""): string
    {
        if (class_exists('\WilokeListingTools\Framework\Helpers\WPML')) {
            if (WPML::isActive()) {
                self::$_wpmlKey = self::$_key . '_' . WPML::getCurrentLanguage();
            }
        }

        return !empty(self::$_wpmlKey) ? self::$_wpmlKey : $default;
    }

    public static function getOption()
    {
        $aOption = get_option(self::getMaybeWPMLKey(self::$_key));
        if (self::isOptionEmpty($aOption)) {
            $aOption = get_option(self::$_key);
        }

        return empty($aOption) ? [] : $aOption;
    }

    private static function isOptionEmpty($aOptions): bool
    {
        if (empty($aOptions)) {
            return true;
        }

        return  is_array($aOptions) && empty($aOptions['general_logo']) && (empty($aOptions['search_page']) ||
            get_permalink
            ($aOptions['search_page']) == false) && empty( $aOptions['user_avatar']) &&
            $aOptions['privacy_policy_desc'] == 'I agree to the <a href="#" target="_blank">Privacy Policy</a>';
    }

    public function update_theme_options()
    {
        $aOptions = [];

        if (class_exists('WilokeListingTools\Framework\Helpers\WPML') && WPML::isActive()) {
            $wpmlKey = self::getMaybeWPMLKey();
            if (!empty($wpmlKey)) {
                $aOptions = get_option($wpmlKey);
                if (self::isOptionEmpty($aOptions)) {
                    $mainLanguage = WPML::getDefaultLanguage();

                    $aOptions = get_option(self::$_key . '_' . $mainLanguage);
                    if (!self::isOptionEmpty($aOptions)) {
                        update_option($wpmlKey, $aOptions);
                    } else {
                        $aOptions = get_option(self::$_key);
                        if (!empty($aOptions)) {
                            update_option($wpmlKey, $aOptions);
                        }
                    }
                }
            }
        } else {
            if (!get_option(Wiloke::$firsTimeInstallation) && !self::getOption()) {
                global $wiloke;
                foreach ($wiloke->aConfigs['themeoptions']['redux']['sections'] as $aSections) {
                    foreach ($aSections['fields'] as $aFields) {
                        $aOptions[$aFields['id']] = $aFields['default'] ?? '';
                    }
                }

                update_option(self::$_key, $aOptions);
            }
        }
    }

    public static function getColor($key, $default = '')
    {
        global $wiloke;
        if (!isset($wiloke->aThemeOptions[$key])) {
            return $default;
        }

        if (!isset($wiloke->aThemeOptions[$key]['rgba'])) {
            return $default;
        }

        return $wiloke->aThemeOptions[$key]['rgba'];
    }

    public static function getOptionDetail($key, $default = '')
    {
        global $wiloke;

        if (empty($wiloke->aThemeOptions)) {
            $wiloke->aThemeOptions = self::getOption();
        }

        if (isset($wiloke->aThemeOptions[$key])) {
            return $wiloke->aThemeOptions[$key];
        }

        return $default;
    }

    /**
     * @param        $key
     * @param string $size
     * @param string $thumbnailUrl default url
     *
     * @return false|mixed|string
     */
    public static function getThumbnailUrl($key, $size = 'thumbnail', $thumbnailUrl = '')
    {
        $aGeneralThumbnail = self::getOptionDetail($key);

        if (is_array($aGeneralThumbnail)) {
            if (isset($aGeneralThumbnail['id'])) {
                $thumbnailUrl = get_the_post_thumbnail_url($aGeneralThumbnail['id'], $size);
            }

            if (empty($thumbnailUrl)) {
                if (isset($aGeneralThumbnail['thumbnail']) && !empty($aGeneralThumbnail['thumbnail'])) {
                    $thumbnailUrl = $aGeneralThumbnail['thumbnail'];
                }
            }
        }

        return $thumbnailUrl;
    }

    public static function keyOption()
    {
        return 'wiloke_themeoptions';
    }

    /**
     * Get Options. Ensures this function only run if it's front-page.
     */
    public function get_option()
    {
        global $wiloke;
        if (!$wiloke->kindofrequest() || $wiloke->kindofrequest('widgets.php') || $wiloke->kindofrequest('post.php')) {
            $wiloke->aThemeOptions = self::getOption();

            if (!$wiloke->aThemeOptions) {
                return false;
            }

            return $wiloke->aThemeOptions;
        }
    }

    /**
     * Rendering Theme Options
     */
    public function render()
    {
        global $wiloke;

        try {
            if ($wiloke->isClassExists('ReduxFramework', false)) {
                $this->setArguments();
                $this->setSections();

                new ReduxFramework($this->aSections, $this->aArgs);
            }
        }
        catch (Exception $e) {
            $message = $e->getMessage();

            if ($message) {
                Wiloke::$aErrors['error'][] = esc_html__('Redux Framework plugin is required.', 'wilcity');
            }
        }
    }

    public function setSections()
    {
        global $wiloke;
        $aSections = $this->addColorPalettes($wiloke->aConfigs['themeoptions']['redux']['sections']);

        $this->aSections = $aSections;
    }

    public function addColorPalettes($aArgs)
    {
        global $wiloke;

        if (!isset($wiloke->aConfigs['general']['color_picker']['palette']) &&
            empty($wiloke->aConfigs['general']['color_picker']['palette'])) {
            return $aArgs;
        }

        if (is_array($aArgs)) {
            if (isset($aArgs['type'])) {
                if ($aArgs['type'] == 'color_rgba') {
                    $aArgs['options']['palette'] = $wiloke->aConfigs['general']['color_picker']['palette'];
                    $aArgs['options']['show_palette'] = true;

                    return $aArgs;
                }
            } else {
                return array_map([$this, 'addColorPalettes'], $aArgs);
            }
        }

        return $aArgs;
    }

    /**
     * All the possible arguments for Redux.
     * For full documentation on arguments, please refer to:
     * https://github.com/ReduxFramework/ReduxFramework/wiki/Arguments
     * */
    public function setArguments()
    {
        global $wiloke;
        $wiloke->aConfigs['themeoptions']['redux']['args']['opt_name'] = self::getMaybeWPMLKey(self::$_key);
        $this->aArgs
            = $wiloke->aConfigs['themeoptions']['redux']['args'];
    }

    public static function isEnable($featureKey, $lowerCheckLevel = false): bool
    {
        global $wiloke;

        if (empty($wiloke) || empty($wiloke->aThemeOptions)) {
            $aThemeOptions = Wiloke::getThemeOptions(true);
        } else {
            $aThemeOptions = $wiloke->aThemeOptions;
        }

        if ($lowerCheckLevel) {
            return !isset($aThemeOptions[$featureKey]) || $aThemeOptions[$featureKey] !== 'disable';
        }

        return isset($aThemeOptions[$featureKey]) && $aThemeOptions[$featureKey] == 'enable';
    }
}
