<?php

namespace WilokeListingTools\Framework\Helpers;

class Event
{
    public static function getEventSidebarKey($postType)
    {
        return 'wilcity-single-'.$postType.'-sidebar';
    }
    
    public static function getEventsSidebarKey($postType)
    {
        return 'wilcity-sidebar-'.$postType.'s';
    }
    
    /**
     * Get Event Post Type in the Event Template
     *
     * @param $pageId
     *
     * @return mixed|string
     */
    public static function getEventPostType($pageId)
    {
        $event = GetSettings::getPostMeta($pageId, 'event_post_type');
        return empty($event) ? 'event' : $event;
    }
}
