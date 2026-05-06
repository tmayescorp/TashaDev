<?php

namespace WilokeListingTools\Register;

use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\SetSettings;

class RegisterSingleSidebarSettings extends RegisterController
{
	protected $aSidebarAllSections;
	protected $aSidebarAvailableSections;
	protected $aSidebarUsedSections;
	protected $aFields;

	public function __construct()
	{
		add_filter(
			'wilcity/filter/wiloke-listing-tools/listing-settings',
			[$this, 'registerSingleSidebarSettings'],
			10,
			2
		);

		add_action(
			'wilcity/wiloke-listing-tools/import-demo/setup-sidebar-search-form',
			[$this, 'setupDefaultSidebarWhileImportingDemo']
		);

		add_action('wp_ajax_wilcity_design_single_sidebar', [$this, 'saveDesignSidebar']);
		add_action('wp_ajax_wilcity_reset_to_default_sidebar', [$this, 'resetDesignSidebar']);
	}

	public function setupDefaultSidebarWhileImportingDemo($postType)
	{
		$this->setDefaultSidebarItems($postType);
	}

	protected function setDefaultSidebarItems($postType = '')
	{
		$aSidebarItems = wilokeListingToolsRepository()->get('listing-settings:sidebar_settings', true)->sub('items');
		$postType = empty($postType) ? General::detectPostTypeSubmission() : $postType;
		$postType = $this->getPostType($postType);

		SetSettings::setOptions(General::getSingleListingSettingKey('sidebar', $postType), $aSidebarItems,true);

		return $aSidebarItems;
	}

	protected function getAllSidebarSections()
	{
		if (!empty($this->aSidebarAllSections)) {
			return $this->aSidebarAllSections;
		}

		$this->aSidebarAllSections = apply_filters(
			'wiloke-listing-tools/single/sidebar-items',
			wilokeListingToolsRepository()
				->get('single-sidebar:sidebar_settings', true)
				->sub('items')
		);
	}

	protected function getUsedSidebarSections()
	{
		$postType = General::detectPostTypeSubmission();
		$postType = $this->getPostType($postType);

		$this->aSidebarUsedSections = GetSettings::getOptions(
			General::getSingleListingSettingKey('sidebar', $postType), false, true
		);

		if (!is_array($this->aSidebarUsedSections)) {
			$this->aSidebarUsedSections = $this->setDefaultSidebarItems();
		} else {
			foreach ($this->aSidebarUsedSections as $order => $aSection) {
				if (isset($aSection['isCustomSection'])) {
					$aSection['baseKey'] = 'custom_section';
				} else {
					if (!isset($aSection['baseKey'])) {
						$aSection['baseKey'] = $aSection['key'];
					}
				}

				$this->aSidebarUsedSections[$order] = $aSection;
			}
		}
	}

	protected function getAvailableSidebarSections()
	{
		$this->getAllSidebarSections();
		$this->getUsedSidebarSections();

		if (!empty($this->aSidebarAvailableSections)) {
			return $this->aSidebarAvailableSections;
		}

		//        $aAllSectionsKey = array_keys($this->aSidebarAllSections);
		$this->aSidebarAvailableSections = $this->aSidebarAllSections;

		if (is_array($this->aSidebarUsedSections)) {
			foreach ($this->aSidebarUsedSections as $order => $aSection) {
				$baseKey = isset($aSection['baseKey']) ? $aSection['baseKey'] : $aSection['key'];
				if (!isset($this->aSidebarAllSections[$baseKey])) {
					unset($this->aSidebarUsedSections[$order]);
					continue;
				}

				if (!isset($this->aSidebarAllSections[$baseKey]['isClone'])) {
					unset($this->aSidebarAvailableSections[$baseKey]);
				}
			}
		}

		return $this->aSidebarAvailableSections;
	}

	public function saveDesignSidebar()
	{
		$this->validateAjaxPermission();
		$aSidebarItems = [];
		foreach ($_POST['data'] as $aItem) {
			if ($aItem['key'] == 'promotion') {
				$key = isset($aItem['promotionID']) && !empty($aItem['promotionID']) ?
					$aItem['key'] . '_' . $aItem['promotionID'] : $aItem['key'] . '_' . uniqid();
			} elseif (isset($aItem['isMultipleSections']) && $aItem['isMultipleSections']) {
				$key = $aItem['key'] . '_' . uniqid();
			} elseif ($aItem['key'] == 'taxonomy') {
				$key = isset($aItem['taxonomy']) && !empty($aItem['taxonomy']) ? $aItem['taxonomy'] : $aItem['key'];
			} else {
				$key = $aItem['key'];
			}
			$aSidebarItems[$key] = $aItem;
		}

		SetSettings::setOptions(
			General::getSingleListingSettingKey('sidebar', $_POST['postType']),
			self::unSlashDeep($aSidebarItems), true
		);
		$msg = $this->congratulationMsg();
		wp_send_json_success(['msg' => $msg]);
	}

	public function resetDesignSidebar()
	{
		$this->validateAjaxPermission();
		$this->setDefaultSidebarItems($_POST['postType']);
		wp_send_json_success([
			'msg' => esc_html__('The settings have been reset successfully', 'wiloke-listing-tools')
		]);
	}

	protected function getSectionFields()
	{
		$aCommonFields = wilokeListingToolsRepository()
			->get('single-sidebar:sidebar_settings', true)
			->sub('fields', true)
			->sub('common');

		$aSections = wilokeListingToolsRepository()
			->get('single-sidebar:sidebar_settings', true)
			->sub('fields', true)
			->sub('sections');

		foreach ($aSections as $sectionKey => $aSectionSettings) {
			$aParsedFields = [];
			foreach ($aSectionSettings['fields'] as $field) {
				if ($field === 'common') {
					$aField = $aCommonFields;
				} else {
					$aField = $field;
				}

				$aParsedFields = array_merge($aParsedFields, $aField);
			}

			$aSections[$sectionKey]['fields'] = $aParsedFields;
			$aSections[$sectionKey]['baseKey'] = $sectionKey;
		}

		$this->aFields = $aSections;

		return $aSections;
	}

	public function registerSingleSidebarSettings($aConfiguration, $that)
	{
		$this->getSectionFields();
		$this->getAvailableSidebarSections();

		if (is_array($this->aSidebarUsedSections)) {
			$this->aSidebarUsedSections = self::unSlashDeep($this->aSidebarUsedSections);
		} else {
			$this->aSidebarAllSections = [];
		}

		$aConfiguration['aSidebar'] = [
			'fields'             => $this->aFields,
			'aUsedSections'      => !is_array($this->aSidebarUsedSections) ? [] : $this->aSidebarUsedSections,
			'aAllSections'       => $this->aSidebarAllSections,
			'aAvailableSections' => $this->aSidebarAvailableSections,
			'aStyles'            => wilokeListingToolsRepository()
				->get('single-sidebar:sidebar_settings', true)
				->sub('aStyles'),
			'aRelatedBy'         => wilokeListingToolsRepository()
				->get('single-sidebar:sidebar_settings', true)
				->sub('aRelatedBy'),
			'aOrderBy'           => wilokeListingToolsRepository()->get('general:aOrderBy'),
			'aOrderFallbackBy'   => wilokeListingToolsRepository()->get('general:aOrderByFallback'),
			'ajaxAction'         => 'wilcity_design_single_sidebar'
		];

		return $aConfiguration;
	}
}
