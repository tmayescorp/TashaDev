<?php

namespace WILCITY_APP\SidebarOnApp;

use WILCITY_APP\Controllers\Listing\ListingSkeleton;
use WILCITY_APP\Helpers\App;

class Tags extends ListingSkeleton
{
    public function __construct()
    {
        add_filter('wilcity/mobile/sidebar/tags', [$this, 'render']);
    }
    
    public function render($post)
    {
        $aTerms = App::get('PostSkeleton')->setPost($post)->getTaxonomy('listing_tag');
        return empty($aTerms) ? false : json_encode($aTerms);
    }
}
