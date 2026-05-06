<?php

namespace WILCITY_APP\Controllers;

use WILCITY_APP\Helpers\App;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\WPML;

class SearchField
{
	protected $aTaxonomyAndParamsRelationship
		                                 = [
			'listing_location' => 'listingLocation',
			'listing_cat'      => 'listingCat',
			'listing_tag'      => 'listingTag'
		];
	private   $aExcludeFieldKeys         = ['nearbyme'];
	private   $aExcludeFieldOriginalKeys = ['new_price_range'];

	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, '/search-fields/listing', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getFields'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, '/get-tags/(?P<categoryID>\d+)', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getTagsByCatID'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/search-fields/listing', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getFields'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/get-tags/(?P<categoryID>\d+)', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getTagsByCatID'],
				'permission_callback' => '__return_true'
			]);
		});
	}

	public function getTagsByCatID($aData)
	{
		WPML::switchLanguageApp();
		$oTerm = get_term($aData['categoryID'], 'listing_cat');

		$aTagSlugs = GetSettings::getTermMeta($oTerm->term_id, 'tags_belong_to');
		if (empty($aTagSlugs)) {
			return [
				'status' => 'error'
			];
		}

		$aTags = [];
		foreach ($aTagSlugs as $slug) {
			$oTerm = get_term_by('slug', $slug, 'listing_tag');
			$aTags[] = [
				'name'     => $oTerm->name,
				'id'       => $oTerm->term_id,
				'slug'     => $oTerm->slug,
				'selected' => false
			];
		}

		return [
			'status'   => 'success',
			'aOptions' => $aTags
		];
	}

	public function getFields(\WP_REST_Request $oRequest)
	{
		WPML::switchLanguageApp();
		$aParams = $oRequest->get_params();
		$postType = !isset($aParams['postType']) ? 'listing' : sanitize_text_field($aParams['postType']);

		if (empty($postType)) {
			$postType = General::getDefaultPostTypeKey(false);
		}

		$aRawSearchFields = GetSettings::getOptions(General::getSearchFieldsKey($postType), false, true);

		if (empty($aRawSearchFields)) {
			return [
				'status' => 'error'
			];
		}

		$aSearchFields = [];
		foreach ($aRawSearchFields as $key => $aField) {
			if (in_array($aField['key'], $this->aExcludeFieldKeys) || in_array($aField['originalKey'],
					$this->aExcludeFieldOriginalKeys)) {
				continue;
			}

			$aSearchField = $aField;
			$aSearchField['key'] = $aField['key'];
			$aSearchField['name'] = $aField['label'];
			$aSearchField['value'] = '';

			if (isset($aField['isDefault'])) {
				if ($aField['isDefault'] == 'true') {
					$aSearchField['isDefault'] = true;
				} else if ($aField['isDefault'] == 'false') {
					$aSearchField['isDefault'] = false;
				}
			}

			if (in_array($aField['oldType'], ['wil-select-tree', 'wil-switch'])) {
				if ($aField['oldType'] === 'wil-select-tree') {
					$aSearchField['type'] = 'select2';
				} else {
					$aSearchField['type'] = 'checkbox';
				}
			} else {
				$aSearchField['type'] = str_replace('wil-', '', $aField['oldType']);
			}

			switch ($aSearchField['type']) {
				case 'select2':
				case 'select':
				case 'checkbox2':
					$aSearchField['type'] = 'select';
					if ($aSearchField['type'] == 'checkbox2') {
						$aSearchField['isMultiple'] = 'yes';
					} else {
						if (isset($aField['isMultiple']) && ($aField['isMultiple'] == 'yes')) {
							$aSearchField['isMultiple'] = 'yes';
						} else {
							$aSearchField['isMultiple'] = 'no';
						}
					}

					if (isset($aField['group']) && $aField['group'] === 'term') {
						//                        if (!isset($aField['isAjax']) || ($aField['isAjax'] == 'no')) {
						$isParentOnly = isset($aField['isShowParentOnly']) && $aField['isShowParentOnly'] == 'yes';
						$isHideEmpty = isset($aField['isHideEmpty']) ? $aField['isHideEmpty'] : false;
						$aRawTerms = GetSettings::getTaxonomyHierarchy([
							'taxonomy'   => $aField['key'],
							'orderby'    => isset($aField['orderBy']) ? $aField['orderBy'] : 'count',
							'order'      => isset($aField['order']) ? $aField['order'] : 'DESC',
							'parent'     => 0,
							'hide_empty' => $isHideEmpty,
							'number'     => 100
						], $postType, $isParentOnly, false);

						if (empty($aRawTerms) || is_wp_error($aRawTerms)) {
							$aSearchField['options'] = [
								[
									'name' => esc_html__('No categories', 'wiloke-mobile-app'),
									'id'   => -1
								]
							];
						} else {
							$aTerms = [];
							$paramKey = $this->aTaxonomyAndParamsRelationship[$key];
							foreach ($aRawTerms as $oTerm) {
								$aTerms[] = [
									'name'     => $oTerm->name,
									'id'       => $oTerm->term_id,
									'slug'     => $oTerm->slug,
									'selected' => isset($aData[$paramKey]) && ($aParams[$paramKey] == $oTerm->slug
											|| $aParams[$paramKey] ==
											$oTerm->term_id),
									'count'    => GetSettings::getTermCountInPostType($postType, $aField['key'])
								];
							}
							$aSearchField['options'] = $aTerms;
						}
						//                        }
					} else {
						switch ($aField['key']) {
							case 'orderby':
								$aOrderBy
									= wilcityShortcodesRepository(WILCITY_SC_DIR . 'configs/')->get('orderby:listing');
								if (empty($aField['options'])) {
									$aField['options'] = array_keys($aOrderBy);
								}
								unset($aSearchField['options']);

								foreach ($aField['options'] as $option) {
									$aSearchField['options'][] = [
										'name'     => $aOrderBy[$option],
										'id'       => $option,
										'selected' => $option === $aField['std']
									];
								}
								break;
							case 'order':
								$aSearchField['options'] = [
									[
										'name'     => 'DESC',
										'id'       => 'DESC',
										'selected' => 'DESC' == $aField['std']
									],
									[
										'name'     => 'ASC',
										'id'       => 'ASC',
										'selected' => 'ASC' == $aField['std']
									]
								];
								break;
							case 'price_range':
								$aRawPriceRange = wilokeListingToolsRepository()->get('general:priceRange');
								$aPriceRange = [];
								foreach ($aRawPriceRange as $priceKey => $priceDesc) {
									$aPriceRange[] = [
										'name'     => $priceDesc,
										'id'       => $priceKey,
										'selected' => $priceKey == 'nottosay' ? true : false
									];
								}

								$aSearchField['options'] = $aPriceRange;
								break;
							case 'post_type':
							case 'postType':
								$aSearchField['key'] = 'postType';
								$aRawPostTypes = General::getPostTypes(false, false);
								$aPostTypes = [];
								$order = 1;
								foreach ($aRawPostTypes as $type => $aSettings) {
									$aPostTypes[] = [
										'name'     => $aSettings['name'],
										'id'       => $type,
										'selected' => $order === 1 ? true : false
									];
									$order++;
								}
								$aSearchField['options'] = $aPostTypes;
								break;
						}
					}
					break;
				case 'autocomplete':
				case 'auto-complete':
					$aSearchField['type'] = 'google_auto_complete';
					$aSearchField['maxRadius'] = abs($aField['maxRadius']);
					$aSearchField['defaultRadius'] = abs($aField['defaultRadius']);
					break;
				case 'checkbox':
					$aSearchField['type'] = 'checkbox';
					break;
				case 'wp_search':
					$aSearchField['type'] = 'input';
					$aSearchField['key'] = 's';
					break;
				case 'date-range':
					$aSearchField['type'] = 'date_range';
					break;
				default:
					switch ($aField['originalKey']) {
						case 'wp_search':
							$aSearchField['type'] = 'input';
							$aSearchField['key'] = 's';
							break;
					}
					break;
			}

			$aSearchFields[] = $aSearchField;
		}

		return [
			'status'   => 'success',
			'oResults' => $aSearchFields
		];
	}
}
