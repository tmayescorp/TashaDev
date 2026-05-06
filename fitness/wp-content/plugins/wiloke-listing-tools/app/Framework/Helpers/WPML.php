<?php

namespace WilokeListingTools\Framework\Helpers;

/**
 * Class WPML
 * @package WilokeListingTools\Framework\Helpers
 */
class WPML
{
	/**
	 * @var
	 */
    public static $currentLang;

    public static function setCurrentLanguage($lang)
    {
        if (self::isActive()) {
            global $sitepress;
            $sitepress->switch_lang($lang);
        }
    }

	public static function cookieCurrentLanguage()
	{
		if (isset($_GET['lang']) && !empty($_GET['lang'])) {
			$lang = $_GET['lang'];
		} elseif (isset($_POST['lang']) && !empty($_POST['lang'])) {
			$lang = $_POST['lang'];
		} elseif (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
			parse_str(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY), $aQueries);
			if (isset($aQueries['lang'])) {
				$lang = $aQueries['lang'];
			}
		} else if (!is_admin()) {
			if (isset($_COOKIE['wp-wpml_current_language']) && !empty($_COOKIE['wp-wpml_current_language'])) {
				$lang = $_COOKIE['wp-wpml_current_language'];
			}
		}

		if (isset($lang) && self::isActive()) {
			global $sitepress;
			$sitepress->switch_lang($lang);
		}
	}

	/**
	 * @return mixed|void
	 */
    public static function getActiveLanguages()
    {
        return apply_filters('wpml_active_languages', []);
    }

	/**
	 * @return mixed|string|void
	 */
	public static function getCurrentLanguage()
	{
		return apply_filters('wpml_current_language', '');
	}

	/**
	 * @param $aArgs
	 * @return mixed
	 */
	public static function addFilterLanguagePostArgs($aArgs)
	{
		$aArgs['suppress_filters'] = false;

		return $aArgs;
	}

	/**
	 * @return bool
	 */
	public static function isActive()
	{
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

		if (is_plugin_active('sitepress-multilingual-cms/sitepress.php')) {
			return true;
		}

		return false;
	}

	/**
	 * @return mixed|void
	 */
	public static function getDefaultLanguage()
	{
		return apply_filters('wpml_default_language', '');
	}

	public static function switchLanguageApp()
	{
		$lang = self::getCurrentLanguageApp();

		if (!empty($lang) && WPML::isActive()) {
			global $sitepress;
			$sitepress->switch_lang($lang);
		}
	}

	/**
	 * @return mixed|string
	 */
	public static function getCurrentLanguageApp()
	{
		return apply_filters('wiloke-listing-tools/app/Framework/Helpers/WPML/getCurrentLanguageApp', '');
	}

	/**
	 * @param $originPageId
	 * @param string $postType
	 * @param null $languageCode
	 * @return mixed|void
	 */
	public static function getPageIdOfCurrentLanguage($originPageId, $postType = 'page', $languageCode = null)
	{
		return apply_filters('wpml_object_id', $originPageId, $postType, 'false', $languageCode);
	}
}
