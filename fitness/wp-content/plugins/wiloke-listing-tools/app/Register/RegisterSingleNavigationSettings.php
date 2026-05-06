<?php

namespace WilokeListingTools\Register;

use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\SetSettings;

class RegisterSingleNavigationSettings extends RegisterController
{
    protected $aSingleNav;

    public function __construct()
    {
        add_filter(
            'wilcity/filter/wiloke-listing-tools/listing-settings',
            [$this, 'registerSingleNavSettings'],
            10,
            2
        );
    }

    protected function getSectionFields()
    {
        $aCommonFields = wilokeListingToolsRepository()->get('single-nav:fields', true)->sub('common');
        $aSections     = wilokeListingToolsRepository()->get('single-nav:fields', true)->sub('sections');

        foreach ($aSections as $sectionKey => $aSectionSettings) {
            $aParsedFields = [];
            foreach ($aSectionSettings['fields'] as $field) {
                if ($field === 'common') {
                    $aField = $aCommonFields;
                } else {
                    $aField = $field;
                }

                if (isset($aField[0]) && is_array($aField)) {
                    $aParsedFields = array_merge($aParsedFields, $aField);
                } else {
                    $aParsedFields = array_push($aParsedFields, $aField);
                }
            }
            $aSections[$sectionKey]['fields']  = $aParsedFields;
            $aSections[$sectionKey]['baseKey'] = $sectionKey;
        }

        return $aSections;
    }

    protected function getCustomSections()
    {
        return wilokeListingToolsRepository()->get('single-nav:defaultSections');
    }

    protected function getShortcodes()
    {
        return wilokeListingToolsRepository()->get('single-nav:shortcodes');
    }

    protected function getSingleNavigation()
    {
        $postType     = General::detectPostTypeSubmission();
        $postType     = $this->getPostType($postType);
        $aDefault     = wilokeListingToolsRepository()->get('single-nav:draggable');
        $aNavSettings = GetSettings::getOptions(General::getSingleListingSettingKey('navigation', $postType),false,true);

        if (!empty($aNavSettings) && is_array($aNavSettings)) {
            $aNavSettings = array_values($aNavSettings);

            foreach ($aNavSettings as $order => $aSection) {
                if (isset($aSection['key'])) {
                    $sectionKey = $aSection['key'];
                } elseif ($aSection['baseKey']) {
                    $sectionKey = $aSection['baseKey'];
                } else {
                    continue;
                }

                $this->aSingleNav[$sectionKey] = $aSection;

                if (!isset($aSection['baseKey'])) {
                    if (isset($aSection['isCustomSection']) && $aSection['isCustomSection'] === 'yes') {
                        $this->aSingleNav[$sectionKey]['baseKey'] = 'custom_section';
                    } else if (isset($aSection['taxonomy'])) {
                        $this->aSingleNav[$sectionKey]['baseKey'] = 'taxonomy';
                    } else {
                        $this->aSingleNav[$sectionKey]['baseKey'] = $aSection['key'];
                    }
                }

                $this->aSingleNav[$sectionKey]['vueKey'] = uniqid($aSection['key']);
            }

            $this->aSingleNav = $this->aSingleNav + $aDefault;
        } else {
            $this->aSingleNav = $aDefault;
        }

        return $this->aSingleNav;
    }

    public function registerSingleNavSettings($aConfiguration, $that)
    {
        $this->getSingleNavigation();
        $this->aSingleNav = !empty($this->aSingleNav) ? $this->unSlashDeep($this->aSingleNav) : [];

        $aConfiguration['aSingleNavigation'] = [
            'aSections'       => $this->aSingleNav,
            'sectionFields'   => $this->getSectionFields(),
            'ajaxAction'      => 'wilcity_design_single_nav',
            'defaultSections' => $this->getCustomSections(),
            'shortcodes'      => $this->getShortcodes()
        ];

        return $aConfiguration;
    }
}
