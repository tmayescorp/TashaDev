<?php

namespace WILCITY_APP\SidebarOnApp;

use WILCITY_APP\Helpers\App;

class Taxonomy
{
    public function __construct()
    {
        add_filter('wilcity/mobile/sidebar/taxonomy', [$this, 'render'], 10, 2);
    }
    
    public function render($post, $atts)
    {
        $aTerms = App::get('PostSkeleton')->setPost($post)->getTaxonomy($atts['taxonomy']);
        return empty($aTerms) ? false : json_encode($aTerms);
    }
}
