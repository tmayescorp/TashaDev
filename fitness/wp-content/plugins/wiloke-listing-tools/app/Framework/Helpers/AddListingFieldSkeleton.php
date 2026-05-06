<?php

namespace WilokeListingTools\Framework\Helpers;

use WilokeListingTools\Framework\Helpers\Collection\ArrayCollection;

final class AddListingFieldSkeleton
{
    private $postType;
    private $searchFieldKey;
    private $aSearchUsedFields;
    private $aCacheFields;

    public function __construct($postType)
    {
        $this->postType = $postType;
        $this->setSearchFieldKey();
        $this->setFields();
    }

    public function setSearchFieldKey()
    {
        $this->searchFieldKey = General::getUsedSectionKey($this->postType);
    }

    private function rebuildSearchFields($aFields)
    {
        $oCollection = new ArrayCollection($aFields);

        return $oCollection->magicKeyGroup('key')->output();
    }

    private function setFields(): void
    {
        if (!isset($this->aCacheFields[$this->postType])) {
            $this->aCacheFields[$this->postType] = GetSettings::getOptions($this->searchFieldKey, false, true);
        }
        $this->aSearchUsedFields = $this->rebuildSearchFields($this->aCacheFields[$this->postType]);
    }

    public function getFields(): ?array
    {
        return is_array($this->aSearchUsedFields) ? $this->aSearchUsedFields : [];
    }

    /**
     * @param $fieldKey
     *
     * @return array|null
     */
    public function getField($fieldKey): ?array
    {
        return isset($this->aSearchUsedFields[$fieldKey]) ? $this->aSearchUsedFields[$fieldKey] : null;
    }

    public function getFieldParam($fieldKey, $param, $std = '')
    {
        $aField = $this->getField($fieldKey);
        if (empty($aField)) {
            return null;
        }

        $oCollection = new ArrayCollection($aField);

        return $oCollection->deepPluck($param)->output($std);
    }
}
