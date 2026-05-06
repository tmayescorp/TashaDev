<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Helpers\GetSettings;

class TranslationController
{
    public function __construct()
    {
        if (defined('WILCITY_CORE_HOOK_PREFIX')) {
            add_filter(
                WILCITY_CORE_HOOK_PREFIX . 'Language/Controllers/LanguageController/lang',
                [$this, 'loadLang'],
                10,
                2
            );
        }
    }

    public function loadLang(array $aArgs, ?string $lang)
    {
        $aWilcityLang = GetSettings::getTranslation($lang);
        return array_merge($aArgs, $aWilcityLang);
    }
}
