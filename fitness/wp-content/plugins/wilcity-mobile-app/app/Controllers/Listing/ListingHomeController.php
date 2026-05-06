<?php

namespace WILCITY_APP\Controllers\Listing;

use WILCITY_APP\Helpers\App;
use WilokeListingTools\Framework\Helpers\PostSkeleton;
use WilokeListingTools\Frontend\SingleListing;

class ListingHomeController extends ListingSkeleton
{
	private $isGettingForHome;

	private function isAlwaysShowUpOnHome($key): bool
	{
		return in_array($key, ['review', 'reviews']);
	}

	private function isDirectlyPrint($aSection): bool
	{
		return isset($aSection['isAppDirectPrint']) && $aSection['isAppDirectPrint'];
	}

	public function isGettingForHome($status): ListingHomeController
	{
		$this->isGettingForHome = $status;
		return $this;
	}

	/**
	 * @param $post
	 * @param array $aRequest
	 * @return mixed|void
	 * @throws \Exception
	 */
	public function getData($post, $aRequest = [])
	{
		$aSettings = SingleListing::getNavOrder($post);
		$aHomeSections = [];
		//        $oPostSkeleton = new PostSkeleton();
		$this->setPost($post);

		$aExcludeFromCache = isset($aRequest['cache_excludes']) && !empty($aRequest['cache_excludes']) ? explode(',',
			$aRequest['cache_excludes']) : [];

		if (!empty($aExcludeFromCache)) {
			App::get('PostSkeleton')->setExcludeCache($aExcludeFromCache);
		}

		foreach ($aSettings as $key => $aSection) {
			$aSection = apply_filters(
				'wilcity/filter/wilcity-mobile-app/app/controller/json-skeleton/get-navigation-and-home',
				$aSection,
				$post,
				$aSettings
			);

			if ($aSection['isShowOnHome'] !== 'yes' || $this->isFocusRemoveFromApp($aSection['key'])) {
				unset($aSettings[$key]);
				continue;
			}

			if (!$this->isAlwaysShowUpOnHome($aSection['key'])) {
				$isContentExists = $this->isContentExists($aSection);
				if ($isContentExists) {
					if ($this->isCustomSection($aSection)) {
						$aSection['style'] = $this->getCustomSectionCategory($aSection['content']);
						$aSection['content'] = $this->getSCContent($aSection);
					} else if ($this->isDirectlyPrint($aSection)) {
						$aSection['content'] = $this->getSCContent($aSection);
					}
				} else {
					unset($aSettings[$key]);
					continue;
				}

				$aSection['key'] = trim($aSection['key']);
				$aHomeSections[trim($key)] = $aSection;
			} else {
				$aSection['key'] = trim($aSection['key']);
				$aHomeSections[trim($key)] = $aSection;
			}
		}

		if (!empty($aExcludeFromCache)) {
			App::get('PostSkeleton')->removeExcludeCache($aExcludeFromCache);
		}
		$aHomeSections['postType'] = get_post_type(get_the_id());
		return apply_filters('wilcity/wilcity-mobile-app/listing/home', $aHomeSections, $post);
	}
}
