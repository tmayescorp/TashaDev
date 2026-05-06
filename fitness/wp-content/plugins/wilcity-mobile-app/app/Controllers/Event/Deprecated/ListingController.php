<?php

namespace WILCITY_APP\Controllers\Deprecated;

use WILCITY_APP\Controllers\BuildQuery;
use WILCITY_APP\Controllers\JsonSkeleton;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Frontend\BusinessHours;
use WilokeListingTools\Frontend\SingleListing;
use WilokeListingTools\Framework\Helpers\WPML;

class ListingController
{
	use BuildQuery;
	use JsonSkeleton;

	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/v2', '/listings/(?P<target>\w+)', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getListing'],
				'permission_callback' => '__return_true'
			]);
		});

		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/v2', '/listing-detail/(?P<target>\w+)', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getListing'],
				'permission_callback' => '__return_true'
			]);
		});

		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/v2', '/listing-detail/(?P<target>\d+)', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getListing'],
				'permission_callback' => '__return_true'
			]);
		});

		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/v2',
				'/listing-meta/(?P<target>\d+)/(?P<metaKey>\w+)', [
					'methods'             => 'GET',
					'callback'            => [$this, 'getListingMeta'],
					'permission_callback' => '__return_true'
				]);
		});

		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/v2',
				'/listing-meta/(?P<target>\w+)/(?P<metaKey>\w+)', [
					'methods'             => 'GET',
					'callback'            => [$this, 'getListingMeta'],
					'permission_callback' => '__return_true'
				]);
		});

		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/v2', '/listing/sidebar/(?P<id>\d+)', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getSidebar'],
				'permission_callback' => '__return_true'
			]);
		});

		//        add_filter('wilcity/nav-order', [$this, 'addTypeToSections']);
		add_filter(
			'wilcity/wilcity-mobile-app/filter/wilcity_app_listing_blocks',
			[$this, 'getListingSkeletonOnHomepage'],
			10,
			2
		);
		//        add_filter('wilcity/mobile/render_listings_on_mobile', [$this, 'getListingSkeletonOnHomepage'], 10, 2);
		//        add_action('save_post', [$this, 'deleteCacheFile']);
		//        add_action('wilcity/wiloke-listing-tools/updated-business-hours', [$this, 'deleteCacheFile']);
		//        add_action('before_delete_post', [$this, 'deleteCacheFile']);
	}

	protected function getCaching($fileName)
	{
		$fileName = $this->buildCachingFile($fileName);
		if (FileSystem::isFileExists($fileName, 'wilcity-mobile-app')) {
			$content = FileSystem::fileGetContents($fileName, 'wilcity-mobile-app');
			if (!empty($content)) {
				return json_decode($content, true);
			}
		}

		return '';
	}

	protected function writeCaching($content, $fileName)
	{
		$fileName = $this->buildCachingFile($fileName);
		$content = is_array($content) ? json_encode($content) : $content;
		FileSystem::filePutContents($fileName, $content, 'wilcity-mobile-app');
	}

	public function deleteCacheFile($postID)
	{
		$aPostTypes = General::getPostTypeKeys(false, false);
		if (!in_array(get_post_type($postID), $aPostTypes)) {
			return false;
		}
		$postSlug = get_post_field('post_name', $postID);
		$this->deleteCaching('listing-detail-' . $postSlug);
		$this->deleteCaching('json-skeleton-' . $postSlug);
	}

	public function getListingSkeletonOnHomepage($atts, $post)
	{
		$cache = $this->getCaching('json-skeleton-' . $post->post_name);
		if (!empty($cache)) {
			return $cache;
		}

		$aListing = $this->listingSkeleton($post, ['oGallery', 'oSocialNetworks', 'oVideos', 'oNavigation'], $atts);
		$this->writeCaching($aListing, 'json-skeleton-' . $post->post_name);

		return $aListing;
	}

	public function getListingCustomSection($aData)
	{
		$this->getCustomSection($aData['target'], $aData['metaKey']);
	}

	public function addTypeToSidebar($aSections)
	{
		foreach ($aSections as $key => $aVal) {
			if (isset($aVal['isCustomSection']) && $aVal['isCustomSection'] == 'yes') {
				$category = $this->detectShortcodeType($aVal['content']);

				if (!empty($category)) {
					$sc = $this->parseCustomShortcode($aVal['content']);
					if (!empty($sc)) {
						$aSections[$key]['oContent'] = do_shortcode($sc);
					}
				}
			}
		}

		return $aSections;
	}

	public function addTypeToSections($aSections)
	{
		if (empty($aSections)) {
			return [];
		}

		foreach ($aSections as $key => $aVal) {
			if (isset($aVal['isCustomSection']) && $aVal['isCustomSection'] == 'yes') {
				$aSections[$key]['category'] = $this->detectShortcodeType($aVal['content']);
			} else {
				$aSections[$key]['category'] = $aVal['key'];
			}
		}

		return $aSections;
	}

	public function getListingMeta($aData)
	{
		WPML::switchLanguageApp();
		$aResult = $this->getPostMeta($aData);

		if (empty($aResult)) {
			return [
				'status' => 'error',
				'msg'    => 'noDataFound'
			];
		} else {
			return [
				'status'   => 'success',
				'oResults' => $aResult
			];
		}
	}

	private function handleBusinessHour($post)
	{
		$hourMode = GetSettings::getPostMeta($post->ID, 'hourMode');

		if ($hourMode == 'no_hours_available') {
			return [
				'mode' => 'no_hours_available'
			];
		} else if ($hourMode == 'always_open') {
			return [
				'mode' => 'always_open'
			];
		} else {
			$aBusinessHours = GetSettings::getBusinessHours($post->ID);
			if (empty($aBusinessHours)) {
				return false;
			}

			$aResponse['mode'] = 'rest';

			$timeFormat = GetSettings::getPostMeta($post->ID, 'timeFormat');
			$aDefineDaysOfWeek = wilcityShortcodesRepository()->get('config:aDaysOfWeek');
			$aTodayBusinessHours = BusinessHours::getTodayBusinessHours($post);
			$isInvalidFirstHour = BusinessHours::invalidFirstHours($aTodayBusinessHours);

			if ($aTodayBusinessHours['isOpen'] == 'no' || $isInvalidFirstHour) {
				$aResponse['oCurrent'] = [
					'status' => 'day_off',
					'is'     => $aDefineDaysOfWeek[BusinessHours::getTodayKey($post)],
					'text'   => esc_html__('Day Off', WILCITY_MOBILE_APP)
				];
			} else {
				$aBusinessStatus = BusinessHours::getCurrentBusinessHourStatus($post, $aTodayBusinessHours);
				$aResponse['oCurrent'] = $aBusinessStatus;
				$aResponse['oCurrent']['is'] = $aDefineDaysOfWeek[BusinessHours::getTodayKey($post)];

				$aResponse['oCurrent']['firstOpenHour']
					= Time::renderTime($aTodayBusinessHours['firstOpenHour'], $timeFormat);
				$aResponse['oCurrent']['firstCloseHour']
					= Time::renderTime($aTodayBusinessHours['firstCloseHour'], $timeFormat);

				if (BusinessHours::isSecondHourExists($aTodayBusinessHours)) {
					$aResponse['oCurrent']['secondOpenHour']
						= Time::renderTime($aTodayBusinessHours['secondOpenHour'], $timeFormat);
					$aResponse['oCurrent']['secondCloseHour']
						= Time::renderTime($aTodayBusinessHours['secondCloseHour'], $timeFormat);
				}
			}

			foreach ($aBusinessHours as $aDayInfo) {
				$aDay = [];
				if ($aDayInfo['isOpen'] == 'no') {
					$aDay['status'] = 'day_off';
					$aDay['text'] = esc_html__('Day Off', 'wilcity-mobile-app');
				} else {
					$aDay = [
						'firstOpenHour'  => Time::renderTime($aDayInfo['firstOpenHour'], $timeFormat),
						'firstCloseHour' => Time::renderTime($aDayInfo['firstCloseHour'], $timeFormat)
					];
					if (BusinessHours::isSecondHourExists($aDayInfo)) {
						$aDay['secondOpenHour'] = Time::renderTime($aDayInfo['secondOpenHour'], $timeFormat);
						$aDay['secondCloseHour'] = Time::renderTime($aDayInfo['secondCloseHour'], $timeFormat);
					}
					$aDay['status'] = 'working_day';
				}
				$aDay['is'] = $aDefineDaysOfWeek[$aDayInfo['dayOfWeek']];
				$aResponse['oAllBusinessHours'][] = $aDay;
			}
		}

		return [
			'mode'     => 'rest',
			'oDetails' => $aResponse
		];
	}

	public function getSidebar(\WP_REST_Request $request)
	{
		WPML::switchLanguageApp();
		global $post;
		$post = get_post($request->get_param('id'));
		$thePost = $post;
		$aSidebarSettings = SingleListing::getSidebarOrder($post);
		$this->listingID = $post->ID;

		if (empty($aSidebarSettings)) {
			return [
				'status' => 'error',
				'msg'    => esc_html__('There are no sidebar item', WILCITY_MOBILE_APP)
			];
		}

		$aSidebarItems = [];

		foreach ($aSidebarSettings as $aSidebarSetting) {
			if (!isset($aSidebarSetting['key']) ||
				(isset($aSidebarSetting['status']) && $aSidebarSetting['status'] == 'no')) {
				continue;
			}
			$aSidebarSetting['isMobile'] = true;
			$val = $this->getSCContent($aSidebarSetting);
			if (isset($aSidebarSetting['isCustomSection']) && $aSidebarSetting['isCustomSection'] == 'yes') {
				$category = $this->detectShortcodeType($aSidebarSetting['content']);
				if ($category == 'boxIcon') {
					$aSidebarSetting['key'] = 'tags';
				}
			} elseif ($aSidebarSetting['key'] == 'businessHours') {
				$val = $this->handleBusinessHour($thePost);
			}

			if (!empty($val)) {
				$aSidebarItems[] = [
					'aSettings' => $aSidebarSetting,
					'oContent'  => $val
				];
			}
		}
		if (empty($aSidebarItems)) {
			return [
				'status' => 'error'
			];
		} else {
			return [
				'status'   => 'success',
				'oResults' => $aSidebarItems
			];
		}
	}

	public function getListing($aData)
	{
		WPML::switchLanguageApp();
		$aArgs = $this->buildSingleQuery($aData);
		$query = new \WP_Query(WPML::addFilterLanguagePostArgs($aArgs));

		if ($query->have_posts()) {
			$aPost = [];
			while ($query->have_posts()) {
				$query->the_post();
				$aPost = $this->listingSkeleton($query->post);
				$aNavAndHome = $this->getNavigationAndHome($query->post);
				$aButton = $this->getListingDetailExternalButton($query->post->ID);
				$aPost = $aPost + $aNavAndHome + $aButton;
				$postID = $query->post->ID;
			}

			wp_reset_postdata();

			return apply_filters('wilcity/wilcity-mobile-app/filter/listing-detail', [
				'status'   => 'success',
				'oResults' => $aPost
			], $aData, $postID);
		} else {
			return [
				'status' => 'error',
				'msg'    => 'noPostFound'
			];
		}
	}
}
