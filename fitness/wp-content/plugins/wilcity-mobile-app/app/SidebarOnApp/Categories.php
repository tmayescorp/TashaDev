<?php
namespace WILCITY_APP\SidebarOnApp;

use WILCITY_APP\Helpers\App;

class Categories
{
    public function __construct()
    {
        add_filter('wilcity/mobile/sidebar/categories', [$this, 'render']);
    }
    
    public function render($post)
    {
        $aTerms = App::get('PostSkeleton')->setPost($post)->getTaxonomy('listing_cat');
        return empty($aTerms) ? false : json_encode($aTerms);
    }
}
