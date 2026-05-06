<?php

namespace WilokeListingTools\Register;

trait GetAvailableSections
{
    protected function getAvailableSections()
    {
        $this->aAvailableSections = $this->aAllSections;
        if (empty($this->aUsedSections)) {
            $this->aAvailableSections = $this->aAllSections;
            return true;
        }
        
        if (empty($this->aAvailableSections)) {
            return true;
        }
        
        foreach ($this->aUsedSections as $aUsedSection) {
            if (!isset($aUsedSection['isClone']) || !$aUsedSection['isClone']) {
                unset($this->aAvailableSections[$aUsedSection['type']]);
            }
        }
        
        if (empty($this->aAvailableSections)) {
            return true;
        }
        
//        foreach ($this->aAvailableSections as $sectionKey => $aSection) {
//            $this->aAvailableSections[$sectionKey] = $this->parseSection($aSection);
//        }
        
        return true;
    }
}
