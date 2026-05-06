<?php

namespace WILCITY_APP\Controllers\Event;

use WILCITY_APP\Controllers\Listing\ListingSkeleton;
use WILCITY_APP\Helpers\App;
use WilokeListingTools\Frontend\User;

class EventShortcodeController extends ListingSkeleton
{
    public function __construct()
    {
        add_filter('wilcity/mobile/render_event_on_mobile', [$this, 'buildListingEventData'], 5, 2);
        //        add_filter('wilcity/app/single-skeletons/event', [$this, 'addAdditionalEventDataToSkeleton'], 10, 2);
        //        add_filter('wilcity/app/single-skeletons/event', [$this, 'buildEventSingleContent'], 15, 2);
    }
    
    public function buildListingEventData($aAtts, $post)
    {
        $aData = App::get('EventGeneralData')->getData($post);
        return empty($aData) ? [] : $aData;
    }
}
