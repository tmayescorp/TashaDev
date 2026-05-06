<?php

namespace WILCITY_APP\Controllers\Listing;

use WILCITY_APP\Helpers\App;
use WilokeListingTools\Framework\Helpers\GetSettings;

class ListingExternalButton extends ListingSkeleton
{
    public function getData($post)
    {
        $this->setPost($post);
        
        return App::get('PostSkeleton')->getSkeleton($this->post, ['oButton'], ['ignoreMenuOrder' => true]);
    }
}
