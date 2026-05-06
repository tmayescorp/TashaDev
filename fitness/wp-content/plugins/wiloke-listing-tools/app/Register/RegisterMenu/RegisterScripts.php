<?php

namespace WilokeListingTools\Register\RegisterMenu;

use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Register\ListingToolsGeneralConfig;

class RegisterScripts
{
    use ListingToolsGeneralConfig;
    
    public function __construct()
    {
        $this->registerScripts();
    }
    
    public function registerScripts()
    {
        new RegisterListingScripts();
        new RegisterEventScripts();
    }
}
