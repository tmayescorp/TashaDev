<?php

namespace WilokeListingTools\Framework\Helpers;

use WilokeListingTools\Controllers\Map\GoogleMap;
use WilokeListingTools\Controllers\Map\Mapbox;
use WilokeThemeOptions;

final class MapFactory
{
    /**
     * @return GoogleMap|Mapbox
     */
    public static function get()
    {
        if (WilokeThemeOptions::getOptionDetail('map_type') == 'mapbox') {
            $oInstance = new Mapbox();
        } else {
            $oInstance = new GoogleMap();
        }

        return $oInstance;
    }
}
