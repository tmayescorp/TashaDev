<?php
namespace Wilcity\Map;

class GoogleMap extends AbstractMap implements InterfaceMap
{
    private $aKeys = [
        'general_google_api'         => 'accessToken',
        'general_locale_code'        => 'language',
        'general_search_restriction' => 'restriction',
        'map_theme'                  => 'style'
    ];
    
    public function getAllConfig()
    {
        $aValues = [
            'vueComponent' => 'wil-google-map'
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
