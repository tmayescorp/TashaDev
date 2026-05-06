<?php

namespace WILCITY_ELEMENTOR\Registers;

use Elementor\Controls_Manager;
use WILCITY_SC\RegisterSC\AbstractRegisterShortcodes;
use WilokeListingTools\Framework\Helpers\TermSetting;

class ElementorAdapterConfiguration extends AbstractRegisterShortcodes
{
	private $aConfiguration;
	private $aCache;
	private $taxonomy;

	public function __construct($aConfiguration)
	{
		$this->aConfiguration = $aConfiguration;
	}

	private function parseGroupTitle($group): string
	{
		$group = str_replace('_', ' ', $group);

		return ucfirst($group);
	}

	private function convertType($type): string
	{
		switch ($type) {
			case 'select':
				$newType = Controls_Manager::SELECT;
				break;
			case 'group':
				$newType = Controls_Manager::REPEATER;
				break;
			case 'textarea':
				$newType = Controls_Manager::TEXTAREA;
				break;
			case 'color_picker':
				$newType = Controls_Manager::COLOR;
				break;
			case 'icon_picker':
				$newType = Controls_Manager::ICON;
				break;
			case 'multiple':
				$newType = Controls_Manager::SELECT2;
				break;
			case 'editor':
				$newType = Controls_Manager::WYSIWYG;
				break;
			case 'attach_image_url':
				$newType = Controls_Manager::MEDIA;
				break;
			case 'select2_ajax':
				$newType = SelectTwoAjaxControl::TYPE;
				break;
			default:
				$newType = Controls_Manager::TEXT;
				break;
		}

		return $newType;
	}

	private function getTerms()
	{
		if (isset($this->aCache[$this->taxonomy])) {
			return $this->aCache[$this->taxonomy];
		}

		$totals = wp_count_terms($this->taxonomy);
		if ($totals > 100) {
			$this->aCache[$this->taxonomy] = 'toomany';

			return $this->aCache[$this->taxonomy];
		}

		$aRawTerms = get_terms(['taxonomy' => $this->taxonomy, 'hide_empty' => false]);

		$options = ['' => '-------------'];
		if (!empty($aRawTerms) && !is_wp_error($aRawTerms)) {
			foreach ($aRawTerms as $oTerm) {
				$options[$oTerm->term_id] = $oTerm->name;
			}
		}
		$this->aCache[$this->taxonomy] = $options;

		return $options;
	}

	private function isTaxonomy($id)
	{
		$aTaxonomies = TermSetting::getListingTaxonomyKeys();

		if (in_array($id, $aTaxonomies)) {
			$this->taxonomy = $id;

			return true;
		}

		if (in_array(rtrim($id, 's'), $aTaxonomies)) {
			$this->taxonomy = rtrim($id, 's');

			return true;
		}

		return false;
	}

	private function convertKcConfigToEl($info)
	{
		$type = $info['el_type'] ?? $info['type'];
		if ($type === 'multiple' || $type == 'autocomplete') {
			if (!isset($info['multiple']) || $info['multiple']) {
				$info['multiple'] = true;
			}
		}

		$info['type'] = $this->convertType($type);
		$info['id'] = $info['name'];

		if (isset($info['value']) && !empty($info['value'])) {
			$info['default'] = $info['value'];
		}

		if (isset($info['relation'])) {
			if (isset($info['relation']['show_when'])) {
				$info['condition'] = [
					$info['relation']['parent'] => is_string($info['relation']['show_when']) ?
						$info['relation']['show_when'] :
						$info['relation']['show_when'][2]
				];
			}
			unset($info['relation']);
		}

		if (isset($info['params'])) {
			$aParseParams = [];
			foreach ($info['params'] as $paramOrder => $paramItem) {
				$aParseParams[] = $this->convertKcConfigToEl($paramItem);
			}
			$info['fields'] = $aParseParams;
			unset($info['params']);
		}

		if ($this->isTaxonomy($info['id'])) {
			if ($this->getTerms() !== 'toomany') {
				$info['type'] = Controls_Manager::SELECT2;
				$info['options'] = $this->getTerms();
			} else {
				$info['type'] = Controls_Manager::TEXT;
				if (isset($info['multiple']) && $info['multiple']) {
					$desc = 'Each location is separated by a comma. For example: 1,2,3';
					if (isset($info['description'])) {
						$info['description'] = $info['description'] . ' ' . $desc;
					} else {
						$info['description'] = $desc;
					}
				}
			}
		}

		return $info;
	}

	private function setItemStructure($info, $type)
	{
		switch ($type) {
			case 'start_section':
				$aItem = [
					'cb'     => 'start_controls_section',
					'id'     => $info,
					'config' => [
						'label' => $this->parseGroupTitle($info)
					]
				];
				break;
			case 'end_section':
				$aItem = [
					'cb' => 'end_controls_section'
				];
				break;
			default:
				$aItem = [
					'cb'     => 'add_control',
					'id'     => $info['name'],
					'config' => $this->convertKcConfigToEl($info)
				];
				break;
		}

		return $aItem;
	}

	/**
	 * The configuration will contain the params value of kc configuration now
	 */
	public function convert()
	{
		$this->aConfiguration = $this->prepareShortcodeItem($this->aConfiguration, true);
		$aGroups = array_keys($this->aConfiguration);
		$aResponse = [];
		foreach ($aGroups as $group) {
			if ($group === 'styling') {
				continue;
			}

			$aResponse[] = $this->setItemStructure($group, 'start_section');
			foreach ($this->aConfiguration[$group] as $aItem) {
				$aResponse[] = $this->setItemStructure($aItem, 'item');
			}
			$aResponse[] = $this->setItemStructure($group, 'end_section');
		}

		if (!isset($aGroups['extra_class'])) {
			$aResponse[] = $this->setItemStructure('extra_class_section', 'start_section');
			$aResponse[] = [
				'cb'     => 'add_control',
				'id'     => 'extra_class',
				'config' => [
					'label' => 'Extra Class',
					'type'  => Controls_Manager::TEXT
				]
			];
			$aResponse[] = $this->setItemStructure('', 'end_section');
		}

		return $aResponse;
	}
}
