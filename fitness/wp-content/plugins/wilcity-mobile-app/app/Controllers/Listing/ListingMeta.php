<?php

namespace WILCITY_APP\Controllers\Listing;

use WILCITY_APP\Helpers\App;

class ListingMeta extends ListingSkeleton
{
	protected $aGetValuesOnly
		= [
			'my_events',
			'my_posts',
			'events',
			'posts'
		];

	/**
	 * @param $key
	 * @param $aData
	 *
	 * @return array
	 */
	protected function getArrayValues($key, $aData)
	{
		return in_array($key, $this->aGetValuesOnly) ? array_values($aData) : $aData;
	}

	protected function isGalleryType($baseKey): bool
	{
		return in_array(
			$baseKey,
			apply_filters(
				'wilcity/filter/wilcity-mobile-app/gallery-type',
				['photos', 'oGallery', 'gallery']
			)
		);
	}

	private function getTerms($postID, $taxonomy, $aArgs = [])
	{
		$aRawTerms = wp_get_post_terms($postID, $taxonomy, $aArgs);
		$aTerms = [];

		if (!empty($aRawTerms) && !is_wp_error($aRawTerms)) {
			foreach ($aRawTerms as $oTerm) {
				$aTerm = get_object_vars($oTerm);
				$aTerm['oIcon'] = \WilokeHelpers::getTermOriginalIcon($oTerm);
				$aTerms[] = $aTerm;
			}
		}

		return empty($aTerms) ? false : $aTerms;
	}

	private function rebuildAdvancedProducts($aData)
	{
		if (isset($aData['items'])) {
			return $aData['items'];
		}

		return false;
	}

	public function getData($post, \WP_REST_Request $request)
	{
		$response = null;
		$baseKey = $request->get_param('baseKey');
		$key = $request->get_param('metaKey');
		$key = str_replace('__splash__', '/', $key);
		$maximumItems = $request->get_param('maximumItemsOnHome');

		switch ($baseKey) {
			case 'taxonomy':
			case 'tags':
				$aArgs = [];
				if (!empty($maximumItems)) {
					$aArgs = ['number' => $maximumItems];
				}
				if ($baseKey === 'tags') {
					$taxonomy = 'listing_tag';
				} else {
					$taxonomy = $request->get_param('taxonomy');
				}

				$response = $this->getTerms($post->ID, $taxonomy, $aArgs);
				break;
			case 'reviews':
				$aAtts = [];
				if (!empty($maximumItems)) {
					$aAtts['posts_per_page'] = absint($maximumItems);
				} else if (!empty($request->get_param('postsPerPage'))) {
					$aAtts['posts_per_page'] = $request->get_param('postsPerPage');
				} else {
					$aAtts['posts_per_page'] = 10;
				}
				$aAtts['page'] = $request->get_param('page') ? abs($request->get_param('page')) : 1;

				$response = App::get('ListingReview')->getData($post, $aAtts);
				break;
			case 'listing_content':
			case 'content':
				$response = do_shortcode(get_post_field('post_content', $post->ID));
				break;
			case 'my_advanced_products':
				$response = App::get('PostSkeleton')
					->getSkeleton(
						$post,
						[$key],
						[
							'ignoreMenuOrder' => true,
							'myProductAtts'   => [
								'isApp' => true
							]
						]
					);
				$response = $this->rebuildAdvancedProducts($response[$key]);
				break;
			default:
				$filter = 'wilcity/filter/wilcity-mobile-app/Controllers/Listing/ListingMeta/' . $key;

				if (has_filter($filter)) {
					$response = apply_filters(
						$filter,
						false,
						$request->get_params(),
						$post
					);
				} else {
					$aListingMeta = App::get('PostSkeleton')
						->getSkeleton(
							$post,
							[$key],
							['ignoreMenuOrder' => true]
						);
					$response = $aListingMeta[$key];
				}

				if (!empty($response)) {
					if ($this->isGalleryType($baseKey)) {
						$response = $this->rebuildGallery($response);
					} else {
						$response = $this->getArrayValues($baseKey, $response);
					}
				}
				break;
		}

		return $response;
	}
}
