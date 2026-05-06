<?php

namespace WILCITY_APP\Controllers;

use Stripe\Util\Set;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Validation;
use WilokeListingTools\Framework\Helpers\WPML;
use WilokeThemeOptions;

class HomeController
{
	use JsonSkeleton;

	public  $mobileAppId;
	private $isBuildingApp = false;

	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'homepage-sections', [
				'methods'             => 'GET',
				'callback'            => [$this, 'homepageSections'],
				'permission_callback' => '__return_true',
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'homepage-sections', [
				'methods'             => 'GET',
				'callback'            => [$this, 'homepageSections'],
				'permission_callback' => '__return_true',
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION,
				'homepage-section-detail/(?P<id>\w+)', [
					'methods'             => 'GET',
					'callback'            => [$this, 'homepageSectionDetails'],
					'permission_callback' => '__return_true',
				]);
			register_rest_route(WILOKE_PREFIX . '/v2', 'homepage-section-detail/(?P<id>\w+)', [
				'methods'             => 'GET',
				'callback'            => [$this, 'homepageSectionDetails'],
				'permission_callback' => '__return_true',
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'homepage-sections/(?P<id>\w+)', [
				'methods'             => 'GET',
				'callback'            => [$this, 'homepageSectionDetails'],
				'permission_callback' => '__return_true',
			]);
			register_rest_route(WILOKE_PREFIX . '/v2', 'homepage-sections/(?P<id>\w+)', [
				'methods'             => 'GET',
				'callback'            => [$this, 'homepageSectionDetails'],
				'permission_callback' => '__return_true',
			]);
		});

		add_action('admin_enqueue_scripts', [$this, 'pageEnqueueScripts']);
//		add_action('trashed_post', [$this, 'updateHomePageCache'], 10, 1);
//		add_action('after_delete_post', [$this, 'updateHomePageCache'], 10, 1);
		add_filter('wilcity/mobile/render_slider_sc', [$this, 'getSliderSC'], 10, 2);
		//        add_filter('wilcity/mobile/render_listings_on_mobile', [$this, 'getListingsOnMobile'], 10, 2);
		add_action('wilcity/mobile/update-cache', [$this, 'reUpdateAppCache']);
		add_action('update_option', [$this, 'updateHomePageAfterSavingThemeOptions']);
		add_action('updated_postmeta', [$this, 'updateHomePageAfterUpdatingMetaData'], 10, 3);
//		add_action('edited_term', [$this, 'flushCacheAfterUpdatingSite']);
		add_action('post_updated', [$this, 'rebuildHomePageAfterPostUpdated'], 10);
//		add_action('wp_insert_post', [$this, 'rebuildHomePageAfterPostAdded'], 10, 3);
		add_action('wilcity/wiloke-listing-tools/updated-business-hours',
			[$this, 'rebuildHomePageAfterUpdatedBusinessHour']);
//		add_action('wilcity-mobile-app/focus/rebuild/homepage', [$this, 'focusRebuildHomepage']);
		add_action('wilcity_mobile_app_schedule_everyday', [$this, 'rebuildHomePageAppEveryday']);
		add_filter('display_post_states', [$this, 'addMobileAppHomePageSign'], 10, 2);
	}

	public function getMobileAppId()
	{
		if ($this->mobileAppId) {
			return $this->mobileAppId;
		}
		$this->mobileAppId = $this->getOptionField('mobile_app_page', true);
		return $this->mobileAppId;
	}

	public function addMobileAppHomePageSign($aStates, $post)
	{
		if ($this->getMobileAppId() == $post->ID) {
			$aStates[] = 'Wilcity App';
		}

		return $aStates;
	}

	public function rebuildHomePageAppEveryday()
	{
		$this->focusRebuildHomepage();
	}

	private function verifyRebuildHomePage($oPost): bool
	{
		$aListingPostTypes = General::getPostTypeKeys(true, false);
		$aListingPostTypes[] = 'review';
		$aListingPostTypes[] = 'discussion';

		$aListingPostTypes = apply_filters(
			'wilcity/wilcity-mobile-app/filter/rebuildHomePageAfterPostUpdated/allowedPostTypes',
			$aListingPostTypes
		);
		if ($oPost->post_status != 'publish' || !in_array($oPost->post_type, $aListingPostTypes)) {
			return false;
		}

		return true;
	}

	public function focusRebuildHomepage()
	{
		$this->proceedSaveAppCache($this->getMobileAppId(), true);
	}

	private function rebuildHomePageApp($mobilePageID)
	{
		$this->proceedSaveAppCache($mobilePageID, true);
	}

	public function rebuildHomePageAfterPostAdded($postID)
	{
		if ($postID != $this->getMobileAppId()) {
			return false;
		}

		$this->rebuildHomePageApp($this->getMobileAppId());
	}

	public function rebuildHomePageAfterPostUpdated($postID)
	{
		$this->rebuildHomePageAfterPostAdded($postID);
	}

	public function rebuildHomePageAfterUpdatedBusinessHour($postID)
	{
		$this->isBuildingApp = false;
		$this->updateHomePageAfterUpdatingMetaData('', '', 'wilcity_business');
	}

	public function updateHomePageAfterUpdatingMetaData($metaID, $objectID, $metaKey)
	{
		if (!current_user_can('administrator')) {
			return false;
		}
		if (strpos($metaKey, 'wilcity_') === false) {
			return false;
		}
		$mobilePageID = $this->getOptionField('mobile_app_page', true);
		if (empty($mobilePageID)) {
			return false;
		}
		$this->proceedSaveAppCache($mobilePageID, true);
	}

	public function getListingsOnMobile($atts, $post)
	{
		$aListing = $this->listingSkeleton($post, ['oGallery', 'oSocialNetworks', 'oVideos', 'oNavigation'], $atts);

		return $aListing;
	}

	public function getSliderSC($atts, \WP_Query $query)
	{
		$aResponse = [];
		while ($query->have_posts()) {
			$query->the_post();
			$aPost = $this->listingSkeleton($query->post);
			$aNavAndHome = $this->getNavigationAndHome($query->post);
			$aResponse[] = $aPost + $aNavAndHome;
		}

		return $aResponse;
	}

	private function proceedSaveAppCache($postID, $isFocus = false, $content = null)
	{
		if ($this->isBuildingApp && !$isFocus) {
			return false;
		}

		$rawContent = empty($content) ? get_post_field('post_content_filtered', $postID) : $content;

		if (empty($rawContent)) {
			$rawContent = get_post_field('post_content', $postID);
			if (empty($rawContent)) {
				return false;
			}
		}

		$this->isBuildingApp = true;
		$compliedSC = do_shortcode($rawContent);
		$aParseContent = explode('%SC%', $compliedSC);
		$aSectionsSettings = [];
		$aSectionIDs = [];

		foreach ($aParseContent as $sc) {
			$id = uniqid('section_');
			$sc = trim($sc);
			$sc = wp_kses($sc, []);
			if (!empty($sc)) {
				if (Validation::isValidJson($sc)) {
					$aParseSC = Validation::getJsonDecoded();
				} else {
					$sc = base64_decode($sc);
					if (Validation::isValidJson($sc)) {
						$aParseSC = Validation::getJsonDecoded();
					}
				}
				if (isset($aParseSC) && is_array($aParseSC)) {
					$aSectionIDs[$id] = $aParseSC['TYPE'];
					$aSectionsSettings[$id] = base64_encode($sc);
				}
			}
		}

		$aSettings = apply_filters('wilcity/wilcity-mobile-app/before-save-homepage-sections', [
			'aSectionKeys'      => $aSectionIDs,
			'aSectionsSettings' => $aSectionsSettings
		]);

		SetSettings::setOptions('app_homepage', json_encode($aSettings['aSectionsSettings']), 'HomeMobileApp');
		SetSettings::setOptions('app_homepage_section', $aSettings['aSectionKeys'], 'HomeMobileApp');
		SetSettings::setOptions('app_homepage_id', $postID, 'HomeMobileApp');
		SetSettings::setOptions('app_homepage_last_cache', current_time('timestamp', 1), 'HomeMobileApp');
	}

	public function flushCacheAfterUpdatingSite()
	{
		$mobilePageID = $this->getOptionField('mobile_app_page', true);
		if (empty($mobilePageID)) {
			return false;
		}
		$this->proceedSaveAppCache($mobilePageID);
	}

	public function updateHomePageCache($postID)
	{
		$mobilePageID = $this->getOptionField('mobile_app_page', true);

		if (WPML::isActive()) {
			$postID = WPML::getPageIdOfCurrentLanguage($postID);
		}

		if (empty($mobilePageID) || $mobilePageID == $postID) {
			return false;
		}
		$this->proceedSaveAppCache($mobilePageID);
	}

	public function updateHomePageAfterSavingThemeOptions($option)
	{
		if ($option != 'wiloke_themeoptions' && $option != 'wiloke_themeoptions-transients') {
			return false;
		}

		if (!WilokeThemeOptions::isEnable('app_google_admob_homepage', false)) {
			return false;
		}
		$mobilePageID = $this->getOptionField('mobile_app_page', true);
		if (empty($mobilePageID)) {
			return false;
		}

		$this->proceedSaveAppCache($mobilePageID, true);
	}

	public function saveHomepageSections($postID, $oPost)
	{
		if (!in_array($oPost->post_status, ['publish', 'inherit'])) {
			return false;
		}

		$mobilePageID = $this->getOptionField('mobile_app_page', true);
		if (WPML::isActive()) {
			$postID = WPML::getPageIdOfCurrentLanguage($postID);
		}

		if (empty($mobilePageID)) {
			if (get_page_template_slug($postID) != 'templates/mobile-app-homepage.php') {
				return false;
			}
		} else {
			$postID = $mobilePageID;
		}

		$this->proceedSaveAppCache($postID);
	}

	public function reUpdateAppCache()
	{
		$lastCache = GetSettings::getOptions('app_homepage_last_cache', false, 'HomeMobileApp');
		$now = current_time('timestamp', 1);
		if (empty($lastCache) || ((($now - $lastCache) / 60) > 10)) {
			$postID = GetSettings::getOptions('app_homepage_id', false, 'HomeMobileApp');
			$this->proceedSaveAppCache($postID);
		}
	}

	public function pageEnqueueScripts()
	{
		if (!isset($_GET['post']) || !is_numeric($_GET['post'])) {
			return false;
		}

		if (get_page_template_slug($_GET['post']) != 'templates/mobile-app-homepage.php') {
			return false;
		}

		wp_enqueue_script('wilcity-mobile-app', plugin_dir_url(__FILE__) . '../../assets/js/script.js', ['jquery'],
			null,
			true);
	}

	public function compilerBox()
	{
		if (!isset($_GET['post'])) {
			return false;
		}

		$pageID = abs($_GET['post']);

		$status = $this->isMobileAppTemplate($pageID);
		if (!$status) {
			return false;
		}
		?>
        <button id="wilcity-compiler-code" class="button button-primary">Compiler code</button>
		<?php
	}

	public function homePageOptions()
	{
		$rawHomeData = GetSettings::getOptions('app_homepage', false, 'HomeMobileApp');
		if (empty($rawHomeData)) {
			return ['error' => 'Error'];
		}

		return json_decode($rawHomeData, true);
	}

	function homepageAllSections(): array
	{
		$aParseHomeData = $this->homePageOptions();
		$aResponse = [];

		foreach ($aParseHomeData as $key => $rawSection) {
			$aSection = json_decode(base64_decode($rawSection), true);
			$aResponse[$key] = $aSection;
		}

		return [
			'status' => 'success',
			'oData'  => apply_filters('wilcity/wilcity-mobile-app/homepage-sections', $aResponse)
		];
	}

	public function homepageSections(): array
	{
		$aSections = GetSettings::getOptions('app_homepage_section', false, 'HomeMobileApp');

		if (empty($aSections)) {
			return [
				'status' => 'error',
				'msg'    => esc_html__('There are sections', WILCITY_MOBILE_APP)
			];
		}

		return [
			'status' => 'success',
			'oData'  => $aSections
		];
	}

	public function homepageSectionDetails($aData): array
	{
		WPML::switchLanguageApp();
		$msg = esc_html__('This section does not exists', WILCITY_MOBILE_APP);
		if (empty($aData['id'])) {
			return [
				'status' => 'error',
				'msg'    => $msg
			];
		}
		$aSections = $this->homePageOptions();

		if (empty($aSections[$aData['id']])) {
			return [
				'status' => 'error',
				'msg'    => $msg
			];
		}

		$data = str_replace('&amp;', '&', base64_decode($aSections[$aData['id']]));
		return [
			'status' => 'success',
			'oData'  => json_decode($data, true)
		];
	}

	/**
	 * @param $pageID
	 *
	 * @return bool
	 */
	public function isMobileAppTemplate($pageID)
	{
		$pageTemplate = get_page_template_slug($pageID);
		if ($pageTemplate !== 'templates/mobile-app-homepage.php') {
			return false;
		}

		return true;
	}
}
