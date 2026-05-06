<?php

namespace WilokeListingTools\Framework\Helpers;

use WilokeListingTools\Framework\Helpers\Collection\ArrayCollection;

class SearchFormSkeleton
{
    private $postType;
    private $searchKey;
    private $aCacheFields = [];
    private $aSearchFields;
    private $aCustomSearchFields;
    private $aCustomTaxonomies;
    
    /**
     * @var self $self
     */
    private static $self;
    
    public static function load($postType)
    {
        if (!empty(self::$self)) {
            return self::$self->setConfigs($postType);
        }
        
        self::$self = new SearchFormSkeleton($postType);
        
        return self::$self;
    }
    
    public function __construct($postType)
    {
        $this->setConfigs($postType);
    }
    
    private function setConfigs($postType)
    {
        $this->postType = $postType;
        $this->setSearchKey();
        $this->setFields();
        
        return $this;
    }
    
    private function rebuildSearchFields($aFields)
    {
        $oCollection = new ArrayCollection($aFields);
        
        return $oCollection->magicKeyGroup('key')->output();
    }
    
    private function setSearchKey()
    {
        $this->searchKey = General::getSearchFieldsKey($this->postType,false,true);
    }
    
    private function setFields(): void
    {
        if (!isset($this->aCacheFields[$this->postType])) {
            $aSearchForm = GetSettings::getOptions($this->searchKey);
            if (empty($aSearchForm)) {
                $this->aCacheFields[$this->postType] = [];
            } else {
                $this->aCacheFields[$this->postType] = $aSearchForm;
            }
        }
        $this->aSearchFields = $this->rebuildSearchFields($this->aCacheFields[$this->postType]);
    }
    
    public function getFields(): ?array
    {
        return $this->aSearchFields;
    }
    
    public function getCustomDropdowns()
    {
        if (!isset($this->aCustomSearchFields[$this->postType])) {
            $this->aCustomSearchFields[$this->postType] = array_filter($this->aSearchFields, function ($aItem) {
                return isset($aItem['originalKey']) && $aItem['originalKey'] === 'custom_dropdown';
            });
        }
        
        return $this->aCustomSearchFields[$this->postType];
    }
    
    public function getCustomTaxonomies()
    {
        if (!isset($this->aCustomTaxonomies[$this->postType])) {
            $this->aCustomTaxonomies[$this->postType] = array_filter($this->aSearchFields, function ($aItem) {
                return isset($aItem['group']) && $aItem['group'] === 'term' &&
                       !in_array($aItem['group'], ['listing_cat', 'listing_location', 'listing_tag']);
            });
        }
        
        return $this->aCustomTaxonomies[$this->postType];
    }
    
    public function getCustomDropdownKeys()
    {
        $aCustomFields = $this->getCustomDropdowns();
        if (empty($aCustomFields)) {
            return [];
        }
        
        return array_reduce($aCustomFields, function ($aCarry, $aField) {
            array_push($aCarry, $aField['key']);
            
            return $aCarry;
        }, []);
        
    }
    
    public function getCustomTaxonomiesKeys()
    {
        $aCustomFields = $this->getCustomTaxonomies();
        if (empty($aCustomFields)) {
            return [];
        }
        
        return array_reduce($aCustomFields, function ($aCarry, $aField) {
            return $aCarry + [$aField['key']];
        }, []);
    }
    
    /**
     * @param $fieldKey
     *
     * @return array|null
     */
    public function getField($fieldKey): ?array
    {
        return isset($this->aSearchFields[$fieldKey]) ? $this->aSearchFields[$fieldKey] : null;
    }
    
    public function getFieldParam($fieldKey, $param)
    {
        $aField = $this->getField($fieldKey);
        if (empty($aField)) {
            return null;
        }
        
        $oCollection = new ArrayCollection($aField);
        
        return $oCollection->deepPluck($param)->output();
    }
}
