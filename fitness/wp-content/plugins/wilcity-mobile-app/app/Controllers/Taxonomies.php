<?php

namespace WILCITY_APP\Controllers;

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\WPML;

class Taxonomies
{
	private $aTaxonomyKeys
		= [
			'listing_locations'  => 'listing_location',
			'listing-locations'  => 'listing_location',
			'listing_categories' => 'listing_cat',
			'listing-categories' => 'listing_cat',
			'listing_tags'       => 'listing_tag'
		];

	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'taxonomies/(?P<taxonomy>[^/]+)', [
				'methods'             => 'GET',
				'callback'            => [$this, 'fetchTerms'],
				'permission_callback' => '__return_true',
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'taxonomies/(?P<taxonomy>[^/]+)', [
				'methods'             => 'GET',
				'callback'            => [$this, 'fetchTerms'],
				'permission_callback' => '__return_true',
			]);
		});

		add_filter('wilcity/filter/wilcity-mobile-app/Controllers/ListingControllers/term-children',
			[$this, 'getTermChildren'], 10, 3);
	}

	public function fetchTerms(\WP_REST_Request $request)
	{
		WPML::switchLanguageApp();
		$taxonomy = $request->get_param('taxonomy');
		$taxonomy = isset($this->aTaxonomyKeys[$taxonomy]) ? $this->aTaxonomyKeys[$taxonomy] :
			$this->aTaxonomyKeys[$taxonomy];

		$aParams = apply_filters(
			'wilcity/filter/wilcity-mobile-app/app/Controllers/Taxonomies/fetchTerms/param',
			$request->get_params()
		);
		$aTerms = $this->parseTerms($taxonomy, $aParams);

		if (!$aTerms) {
			return [
				'status' => 'error',
				'msg'    => esc_html__('There are no terms', WILCITY_MOBILE_APP)
			];
		}

		return $this->responseTerms($aTerms, $aParams);
	}

	public function getTermChildren($oTerm, $postType, $outputType = 'default')
	{
		return $this->parseTerms($oTerm->taxonomy, ['postType' => $postType, 'parentId' => $oTerm->term_id],
			$outputType);
	}

	private function parseTerms($taxonomy, $aData, $outputType = 'default')
	{
		$isHideEmpty = isset($aData['hideEmpty']) && $aData['hideEmpty'] == 'yes';
		$aArgs = [];

		if (isset($aData['orderBy'])) {
			$aArgs['orderBy'] = $aData['orderBy'];
		} else {
			$aArgs['orderBy'] = 'count';
		}

		if (isset($aData['postType']) && !empty($aData['postType'])) {
			$aRawTerms = GetSettings::getTaxonomyHierarchy([
				'taxonomy' => $taxonomy,
				'orderby'  => isset($aData['orderBy']) ? $aData['orderBy'] : 'count',
				'parent'   => isset($aData['parentId']) ? $aData['parentId'] : 0
			], $aData['postType'], false, false);

			if (empty($aRawTerms) || is_wp_error($aRawTerms)) {
				return false;
			}

			$aTermIDs = [];
			foreach ($aRawTerms as $oTerm) {
				$aTermIDs[] = $oTerm->term_id;
			}

			$aArgs['include'] = $aTermIDs;
		}

		$aArgs = $aArgs + [
				'taxonomy'   => $taxonomy,
				'hide_empty' => $isHideEmpty
			];
		$aTerms = GetSettings::getTerms($aArgs);

		if (!$aTerms) {
			return false;
		}

		$aResponse = [];
		foreach ($aTerms as $key => $oTerm) {
			switch ($outputType) {
				case 'select':
					$aTerm = [
						'name'     => $oTerm->name,
						'id'       => $oTerm->term_id,
						'selected' => false
					];
					break;
				default:
					$aTerm = get_object_vars($oTerm);
					$aTerm['featuredImg'] = GetSettings::getTermMeta($oTerm->term_id, 'featured_image');
					$aTerm['oIcon'] = \WilokeHelpers::getTermOriginalIcon($oTerm);
					if (isset($aData['postType']) && !empty($aData['postType'])) {
						$aPostTypes = explode(',', $aData['postType']);
						$aTerm['count'] = GetSettings::getTermCountInPostType($aPostTypes, $oTerm->term_id);
					}
					break;
			}

			$aResponse[$key] = $aTerm;
		}

		return $aResponse;
	}

	protected function responseTerms($aTerms, $aData)
	{
		return apply_filters('wilcity/wilcity-mobile-app/term', [
			'status' => 'success',
			'aTerms' => $aTerms
		], $aData);
	}

	public function getLocationTerms($aData)
	{
		$aTerms = $this->parseTerms('listing_location', $aData);
		if (!$aTerms) {
			return [
				'status' => 'error',
				'msg'    => esc_html__('There are no terms', WILCITY_MOBILE_APP)
			];
		}

		return $this->responseTerms($aTerms, $aData);
	}

	public function getCategoryTerms($aData)
	{
		$aTerms = $this->parseTerms('listing_cat', $aData);

		if (!$aTerms) {
			return [
				'status' => 'error',
				'msg'    => esc_html__('There are no terms', WILCITY_MOBILE_APP)
			];
		}

		return $this->responseTerms($aTerms, $aData);
	}

	public function getTagTerms($aData)
	{
		$aTerms = $this->parseTerms('listing_tag', $aData);

		if (!$aTerms) {
			return [
				'status' => 'error',
				'msg'    => esc_html__('There are no terms', WILCITY_MOBILE_APP)
			];
		}

		return $this->responseTerms($aTerms, $aData);
	}
}
