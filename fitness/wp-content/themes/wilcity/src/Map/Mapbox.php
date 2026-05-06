<?php
namespace Wilcity\Map;

class Mapbox extends AbstractMap implements InterfaceMap
{
    private $aKeys = [
        'mapbox_api'                 => 'accessToken',
        'general_locale_code'        => 'language',
        'general_search_restriction' => 'restriction',
        'mapbox_style'               => 'style',
        'mapbox_iconsize'            => 'iconSize'
    ];
    
    public function getAllConfig()
    {
        $aValues = [
            'vueComponent' => 'wil-mapbox'
        ];
        
        foreach ($this->aKeys as $optionKey => $commonOption) {
            $aValues[$commonOption] = \WilokeThemeOptions::getOptionDetail($optionKey);
        }
    
        return $aValues + $this->getCommonSettings();
    }
    
    public function getKey($key)
    {
        return \WilokeThemeOptions::getOptionDetail($key);
    }
}
