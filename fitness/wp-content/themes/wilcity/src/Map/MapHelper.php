<?php

namespace Wilcity\Map;

class MapHelper
{
    
    public function getConfiguration()
    {
    
    }
    
    function wilcityGetMapType() {
        $mapType   = \WilokeThemeOptions::getOptionDetail('map_type');
        if ($mapType === 'mapbox') {
            return 'wil-mapbox';
        }
        
        return 'wil-google-map';
    }
    
}
