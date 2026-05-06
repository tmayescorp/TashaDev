<?php

namespace Wilcity\Map;

class FactoryMap
{
    private $mapType;
    
    /**
     * @var InterfaceMap $oInstance
     */
    private $oInstance;
    
    protected $aThemeOptions;
    
    public function __construct()
    {
        $this->mapType = \WilokeThemeOptions::getOptionDetail('map_type');
    }
    
    public function set()
    {
        if ($this->mapType === 'mapbox') {
            $this->oInstance = new Mapbox;
        } else {
            $this->oInstance = new GoogleMap;
        }
        
        return $this->oInstance;
    }
}

