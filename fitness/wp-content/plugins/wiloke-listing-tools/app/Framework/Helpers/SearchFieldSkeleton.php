<?php

namespace WilokeListingTools\Framework\Helpers;

class SearchFieldSkeleton
{
	protected $aFields;
	protected $oAddListingFields;
	protected $postType;
	protected $aCache;

	/**
	 * @return $this
	 */
	public function setHeroSearchFields()
	{
		$cacheKey = $this->postType . '_hero_form';
		if (isset($this->aCache[$cacheKey])) {
			return $this->setFields($this->aCache[$cacheKey]);
		}

		$aFields = GetSettings::getOptions(General::getHeroSearchFieldsKey($this->postType));
		if (empty($aFields)) {
			$aFields = [];
		} else {
			$this->aCache[$cacheKey] = $aFields;
		}

		return $this->setFields($aFields);
	}

	/**
	 * @return $this
	 */
	public function setSearchFields()
	{
		$cacheKey = $this->postType . '_main_form';

		if (isset($this->aCache[$cacheKey])) {
			return $this->setFields($this->aCache[$cacheKey]);
		}

		$aFields = GetSettings::getOptions(General::getSearchFieldsKey($this->postType),false,true);
		if (empty($aFields)) {
			$aFields = [];
		} else {
			$this->aCache[$cacheKey] = $aFields;
		}

		return $this->setFields($aFields);
	}

	/**
	 * @param array $aFields
	 *
	 * @return $this
	 */
	public function setFields(array $aFields)
	{
		$this->aFields = $aFields;

		return $this;
	}

	/**
	 * @param $postType
	 *
	 * @return $this
	 */
	public function setPostType($postType)
	{
		$this->postType = $postType;

		return $this;
	}

	private function generateFunction($key)
	{
		$aParse = explode('_', $key);

		$aParse = array_map(function ($item) {
			return ucfirst($item);
		}, $aParse);

		return 'get' . implode('', $aParse);
	}

	protected function getPostType($aField)
	{
		$aTypes = General::getPostTypes(false);
		foreach ($aTypes as $directoryType => $aType) {
			$aOption['label'] = $aType['name'];
			$aOption['id'] = $directoryType;
			$aField['options'][] = $aOption;
		}
		$aField['loadOptionMode'] = 'default';

		return $aField;
	}

	protected function getEventFilter($aField)
	{
		$aAllEventFilters = wilokeListingToolsRepository()
			->get('listing-settings:searchFields', true)
			->sub('event_filter', true)
			->sub('options', false);

		$aOptions = [];
		foreach ($aField['options'] as $option) {
			$aOptions[] = [
				'label' => !empty($aAllEventFilters[$option]) ? $aAllEventFilters[$option] : '',
				'id'    => $option
			];
		}

		$aField['options'] = $aOptions;
		$aField['loadOptionMode'] = 'default';

		return $aField;
	}

	private function getCustomSearchFieldOptions($aSearchField, $postType)
	{
		if (empty($this->oAddListingFields)) {
			$this->oAddListingFields = new AddListingFieldSkeleton($postType);
		}

		$options = $this->oAddListingFields->getFieldParam($aSearchField['key'], 'fieldGroups->settings->options');
		if (empty($options)) {
			return null;
		}

		return General::parseSelectFieldOptions($options, 'wil-select-tree');
	}

	protected function getCustomDropdown($aField)
	{
		$aField['options'] = $this->getCustomSearchFieldOptions($aField, $this->postType);
		$aField['childType'] = 'wil-checkbox';

		return $aField;
	}

	/**
	 * @param $aField
	 *
	 * @return mixed
	 */
	protected function getOrderby($aField)
	{
		if (!empty($aField['options'])) {
			$aOrderBy = wilokeListingToolsRepository()->get('listing-settings:orderby', false);
			$aOptions = [];
			foreach ($aField['options'] as $option) {
				$aOptions[] = [
					'label' => $aOrderBy[$option],
					'id'    => $option
				];
			}

			$aField['options'] = $aOptions;
			$aField['loadOptionMode'] = 'default';
		}

		return $aField;
	}

	protected function getOrder($aField)
	{
		if (!isset($aField['options'])) {
			$aField['options'] = [
				[
					'label' => esc_html__('DESC', 'wiloke-listing-tools'),
					'id'    => 'DESC'
				],
				[
					'label' => esc_html__('ASC', 'wiloke-listing-tools'),
					'id'    => 'ASC'
				]
			];
			$aField['loadOptionMode'] = 'default';
		}

		return $aField;
	}

	protected function getPriceRange($aField)
	{
		foreach (wilokeListingToolsRepository()->get('general:priceRange') as $rangeKey => $rangeName) {
			$aOptions['label'] = $rangeName;
			$aOptions['id'] = $rangeKey;
			$aField['options'][] = $aOptions;
		}

		return $aField;
	}

	public function getField($aField)
	{
		if (is_array($aField)) {
			if (isset($aField['originalKey'])) {
				$originalKey = $aField['originalKey'];
			} else {
				$originalKey = $aField['key'];
			}
		} else {
			$originalKey = $aField;
		}

		$method = $this->generateFunction($originalKey);

		if (method_exists(__CLASS__, $method)) {
			return $this->{$method}($aField);
		}

		return false;
	}
}
