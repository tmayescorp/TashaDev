<?php

namespace WILCITY_APP\SidebarOnApp;

class CustomContent
{
    public function __construct()
    {
        add_filter('wilcity/mobile/sidebar/custom_content', [$this, 'render'], 10, 2);
    }
    
    public function render($post, $aAtts)
    {
        return $aAtts['content'];
    }
}
