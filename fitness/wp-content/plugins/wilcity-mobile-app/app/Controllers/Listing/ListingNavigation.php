<?php

namespace WILCITY_APP\Controllers\Listing;

use WILCITY_APP\Helpers\App;
use WilokeListingTools\Frontend\SingleListing;

class ListingNavigation extends ListingSkeleton
{
	private function isAlwaysShowUpOnNavigationEvenEmpty($key): bool
	{
		return in_array($key, ['review', 'reviews']);
	}

	private function isFocusExcludes($aItem): bool
	{
		$aExcludes = ['google_adsense_1', 'google_adsense_2'];
		if (isset($aItem['baseKey'])) {
			return in_array($aItem['baseKey'], $aExcludes);
		}

		if (isset($aItem['key'])) {
			return in_array($aItem['key'], $aExcludes);
		}

		return false;
	}

	/**
	 * @param $post
	 * @param array $aRequest
	 * @return bool|mixed|void
	 * @throws \Exception
	 */
	public function getData($post, array $aRequest = []): array
	{
		$aNavigation = SingleListing::getNavOrder($post);
		$this->setPost($post);
		$aExcludeFromCache = isset($aRequest['cache_excludes']) && !empty($aRequest['cache_excludes']) ? explode(',',
			$aRequest['cache_excludes']) : [];

		if (!empty($aExcludeFromCache)) {
			App::get('PostSkeleton')->setExcludeCache($aExcludeFromCache);
		}

		foreach ($aNavigation as $order => $aItem) {
			if ($this->isFocusExcludes($aItem)) {
				unset($aNavigation[$order]);
			}

			if ($aItem['status'] !== 'yes') {
				unset($aNavigation[$order]);
				continue;
			}

			if ($this->isAlwaysShowUpOnNavigationEvenEmpty($aItem['key'])) {
				continue;
			}

			if (!$this->isContentExists($aItem)) {
				unset($aNavigation[$order]);
				continue;
			}

			if ($this->isCustomSection($aItem)) {
				$aNavigation[$order]['style'] = $this->getCustomSectionCategory($aItem['content']);
			}
		}

		if (!empty($aExcludeFromCache)) {
			App::get('PostSkeleton')->removeExcludeCache($aExcludeFromCache);
		}

		return apply_filters('wilcity/wilcity-mobile-app/listing/navigation', (array)$aNavigation, $post);
	}
}
