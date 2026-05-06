<?php

namespace WILCITY_APP\SidebarOnApp;

class Claim
{
    public function __construct()
    {
        add_filter('wilcity/mobile/sidebar/claim', [$this, 'render'], 10, 2);
    }
    
    public function render($post, $aAtts)
    {
        return '';
    }
}
