<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\Submission;

trait TraitAddListingSettings
{
    protected $aAllSections         = [];
    protected $aAddListingSettings  = [];
    protected $aUsedSections        = [];
    protected $aAvailableSections   = [];
    protected $aDefaultUsedSections = [];

    protected function getDefaultUsedSections()
    {
        foreach ($this->aUsedSections as $aSection) {
            $this->aDefaultUsedSections[$aSection['key']] = $this->aAllSections[$aSection['key']];
        }
    }

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

        return true;
    }

    protected function getAllSections()
    {
        if (empty($this->postType)) {
            wp_die('The post type is required ' . __CLASS__);
        }

        $this->aAllSections = wilokeListingToolsRepository()->get('settings:allSections');

        $this->aAllSections = array_filter($this->aAllSections, function ($aField) {
            $group = General::getPostTypeGroup($this->postType);
            return !isset($aField['listingTypes']) || in_array($group, $aField['listingTypes']);
        });
    }

    protected function getUsedSections(): array
    {
        if (is_array($this->aAddListingSettings)) {
            $aSectionKeys = array_reduce($this->aAddListingSettings, function ($aCarry, $aSection) {
                if (isset($aSection['key'])) {
                    $aCarry[$aSection['key']] = $aSection['type'];
                }

                return $aCarry;
            }, []);

            foreach ($aSectionKeys as $key => $type) {
                if (isset($this->aAllSections[$type])) {
                    $this->aUsedSections[] = array_merge($this->aAllSections[$type], ['key' => $key]);
                }
            }
        }

        return $this->aUsedSections;
    }

    public function cleanAddListingFields($field)
    {
        if (!is_array($field)) {
            return stripslashes($field);
        }

        return array_map('stripslashes', $field);
    }

    protected function getAddListingData()
    {
        if (empty($this->postType)) {
            wp_die('The post type is required ' . __CLASS__);
        }

        $this->getAllSections();
        $this->aAddListingSettings = GetSettings::getOptions(General::getUsedSectionKey($this->postType), false, true);
        $this->aAddListingSettings = General::unSlashDeep($this->aAddListingSettings);
        $this->getUsedSections();
        $this->getAvailableSections();
    }
}
