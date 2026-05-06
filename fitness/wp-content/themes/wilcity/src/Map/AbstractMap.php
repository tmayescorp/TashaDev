<?php

namespace Wilcity\Map;

class AbstractMap
{
    protected function getCommonSettings()
    {
        $aKeys = [
            'map_default_zoom'                  => 'defaultZoom',
            'map_minimum_zoom'                  => 'minZoom',
            'map_max_zoom'                      => 'maxZoom',
            'single_map_default_zoom'           => 'singleDefaultZoom',
            'single_map_minimum_zoom'           => 'singleMinimumZoom',
            'single_map_max_zoom'               => 'singleMaxZoom',
            'single_event_map_default_zoom'     => 'singleEventDefaultZoom',
            'single_event_map_minimum_zoom'     => 'singleEventMinimumZoom',
            'single_event_map_max_zoom'         => 'singleEventMaxZoom',
        ];
        
        $aValues = [];
        foreach ($aKeys as $optionKey => $commonKey) {
            $aValues[$commonKey] = \WilokeThemeOptions::getOptionDetail($optionKey);
        }
        
        return $aValues;
    }
}
