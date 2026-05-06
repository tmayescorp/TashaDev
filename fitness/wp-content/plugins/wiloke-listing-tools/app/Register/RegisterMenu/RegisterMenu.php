<?php
namespace WilokeListingTools\Register\RegisterMenu;

use WilokeListingTools\Framework\Helpers\Inc;
use WilokeListingTools\Register\ListingToolsGeneralConfig;
use WilokeListingTools\Register\RegisterMenu\RegisterEventSettings;
use WilokeListingTools\Register\RegisterMenu\RegisterListingSettings;

class RegisterMenu
{
    private $aGroup = ['listing', 'event'];
    
    public function __construct()
    {
        add_action('admin_menu', [$this, 'registerMenu']);
    }
    
    public function registerMenu()
    {
        foreach ($this->aGroup as $group) {
            $oRegisterMenu = null;
            switch ($group) {
                case 'event':
                    $oRegisterMenu = new RegisterEventSettings();
                    break;
                default:
                    $oRegisterMenu = new RegisterListingSettings();
                    break;
            }
            
            $oRegisterMenu->registerMenu();
        }
    }
}
