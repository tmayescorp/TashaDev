<?php

namespace WILCITY_SC\RegisterSC;

use WilokeListingTools\Framework\Helpers\General;

abstract class AbstractRegisterShortcodes
{
    private $aSc = [];
    private $aCommonSC = [];
    private $isTest = false;
    
    protected function getPricingOptions()
    {
        $aPricingOptions = ['flexible' => 'Depends on Listing Type Request'];
        $aPostTypes      = General::getPostTypeKeys(false, false);
        if (!empty($aPostTypes)) {
            $aPricingOptions = array_merge($aPricingOptions, array_combine($aPostTypes, $aPostTypes));
        }
        
        return $aPricingOptions;
    }
    
    protected function prepareShortcodeItem($aParams, $isTest = false)
    {
        $this->isTest = $isTest;
        foreach ($aParams as $groupKey => $aItems) {
            if (is_array($aItems)) {
                foreach ($aItems as $order => $itemVal) {
                    if (is_string($itemVal) || (is_array($itemVal) && isset($itemVal['common']))) {
                        $itemKey = !is_array($itemVal) ? $itemVal : $itemVal['common'];
                        $aItem   = $this->getCommonConfiguration($itemKey, 'item');
                        if ($aItem) {
                            if (isset($itemVal['additional'])) {
                                $aItem = array_merge($aItem, $itemVal['additional']);
                            }
                            $aParams[$groupKey][$order] = $aItem;
                        }
                    }
                    
                    if (isset($aParams[$groupKey][$order]['options']) &&
                        is_string($aParams[$groupKey][$order]['options'])) {
                        try {
                            if (
                            $aOption = $this->getCommonConfiguration(
                              $aParams[$groupKey][$order]['options'],
                              'option'
                            )) {
                                $aParams[$groupKey][$order]['options'] = $aOption;
                            }
                        } catch (\Exception $oE) {
                            if (WP_DEBUG) {
                                echo $oE->getMessage();
                                die;
                            }
                        }
                    }
                }
            } else {
                try {
                    if ($aGroup = $this->getCommonConfiguration($aItems, 'group')) {
                        $aParams[$groupKey] = $aGroup;
                    }
                } catch (\Exception $oE) {
                    if (WP_DEBUG) {
                        echo $oE->getMessage();
                    }
                }
            }
        }
        
        return $aParams;
    }
    
    protected function getCommonConfigurations()
    {
        if (!empty($this->aCommonSC)) {
            return $this->aCommonSC;
        }
        
        $this->aCommonSC                   = include WILCITY_SC_DIR.'configs/common_shortcodes.php';
        $this->aCommonSC['pricingOptions'] = $this->getPricingOptions();
        
        return $this->aCommonSC;
    }
    
    /**
     * Finding common item / common option / common group of shortcode items
     *
     * @param $key
     *
     * @return array|bool
     */
    protected function getCommonConfiguration($key, $type = 'item')
    {
        $this->getCommonConfigurations();
        if (!isset($this->aCommonSC[$type])) {
            return false;
        }
        
        if (isset($this->aCommonSC[$type][$key])) {
            return $this->aCommonSC[$type][$key];
        }
        
        return false;
    }
    
    protected function getConfigurations()
    {
        foreach (glob(WILCITY_SC_DIR.'configs/sc/*.php') as $filename) {
            $aConfig   = include $filename;
            $this->aSc = array_merge($this->aSc, $aConfig);
        }
        
        return apply_filters('wilcity/filter/wilcity-shortcodes/app/RegisterSC/configurations', $this->aSc);
    }
}
