<?php

namespace WILCITY_APP\Controllers\Listing;

use WILCITY_APP\Helpers\App;

class ListingGeneralData extends ListingSkeleton
{
	/**
	 * @var array
	 */
	protected array $aListingDataPluck
		= [
			'ID',
			'isAds',
			'postTitle',
			'postLink',
			'tagLine',
			'phone',
			'logo',
			'oVideos',
			'timezone',
			'header',
			'coverImg',
			'oAddress',
			'oFeaturedImg',
			'businessHours',
			'oPriceRange',
			'claimStatus',
			'oSocialNetworks',
			'oGallery',
			'authorID',
			'authorName',
			'authorAvatar',
			'isReport',
			'isChat',
			'averageReview',
			'reviewQuality',
			'reviewMode',
			'isEnableReview',
			'isMyFavorite',
			'totalFavorites',
			'myProducts',
			'myRoom',
			'myPosts',
			'myEvents',
			'instafeedhub',
			'footerCard'
		];

	private function rebuildReview($aData)
	{
		if (!isset($aData['reviewQuality'])) {
			unset($aData['reviewQuality']);
			unset($aData['reviewMode']);
			unset($aData['averageReview']);
			unset($aData['isEnableReview']);

			return $aData;
		}

		$aReview = [
			'quality'        => $aData['reviewQuality'],
			'mode'           => $aData['reviewMode'],
			'averageReview'  => $aData['averageReview'],
			'isEnableReview' => $aData['isEnableReview']
		];

		unset($aData['reviewQuality']);
		unset($aData['reviewMode']);
		unset($aData['averageReview']);
		unset($aData['isEnableReview']);

		$aData['oReview'] = $aReview;

		return $aData;
	}

	private function rebuildBusinessHour($aBusinessHour)
	{
		if (isset($aBusinessHour['operating_times']) &&
			is_array($aBusinessHour['operating_times'])) {
			$aBusinessHour['operating_times'] = array_values($aBusinessHour['operating_times']);
		}

		return $aBusinessHour;
	}

	private function fixOdlVersion($aData)
	{
		return isset($aData['operating_times']) ? array_values($aData['operating_times']) : false;
	}

	public function getData($post, $aPluck = [], $aPluckExcludes = [], $aAtts = [])
	{
		if (empty($aPluck)) {
			$aPluck = $this->aListingDataPluck;
		} else {
			$aPluck = is_array($aPluck) ? $aPluck : [$aPluck];
		}

		if (!empty($aPluckExcludes)) {
			$aPluck = array_diff($aPluck, $aPluckExcludes);
		}

		$aAtts['cover_img_size'] = 'medium';
		$aAtts['logo_size'] = 'medium';
		$aData = App::get('PostSkeleton')->getSkeleton($post, $aPluck, $aAtts);

		if (isset($aData['oGallery']) && !empty($aData['oGallery'])) {
			$aData['oGallery'] = $this->rebuildGallery($aData['oGallery']);
		}

		if (isset($aData['isMyFavorite'])) {
			$aData['oFavorite'] = $this->rebuildFavorite($aData);
			unset($aData['isMyFavorite']);
			unset($aData['totalFavorites']);
		}
		$aData = $this->rebuildReview($aData);

		if (isset($aData['authorName'])) {
			$aData['oAuthor'] = $this->rebuildAuthor($aData);

			unset($aData['authorID']);
			unset($aData['authorAvatar']);
			unset($aData['authorName']);
		}

		if (isset($aData['businessHours'])) {
			if (empty($aData['businessHours']) || $aData['businessHours']['mode'] == 'no_hours_available') {
				unset($aData['businessHours']);
			} else {
				$aData['newBusinessHours'] = $this->rebuildBusinessHour($aData['businessHours']);
				$aData['hourMode'] = $aData['businessHours']['mode'];
				$aData['businessHours'] = $this->fixOdlVersion($aData['businessHours']);
			}
		}

		// new
		$aData['group'] = 'listing';

		return apply_filters(
			'wilcity/wilcity-mobile-app/filter/app/Controllers/Listing/ListingGeneralData/getData',
			$aData,
			$post
		);
	}
}
