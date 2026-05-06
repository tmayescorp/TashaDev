<?php

namespace WilokeListingTools\Register\RegisterMenu;

use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\Inc;
use WilokeListingTools\Register\ListingToolsGeneralConfig;

class RegisterEventSettings implements InterfaceRegisterMenu
{
    use ListingToolsGeneralConfig;

    public function registerMenu()
    {
        if (!empty($aListingTypes = General::getPostTypesGroup('event'))) {
            foreach ($aListingTypes as $menuSlug => $aListingType) {
                add_submenu_page(
                    $this->parentSlug,
                    $aListingType['menu_name'],
                    $aListingType['menu_name'],
                    'edit_theme_options',
                    $aListingType['menu_slug'],
                    [$this, 'registerSettingArea']
                );
            }
        }
    }

    public function registerSettingArea()
    {
        Inc::file('event-settings:index');
    }
}
