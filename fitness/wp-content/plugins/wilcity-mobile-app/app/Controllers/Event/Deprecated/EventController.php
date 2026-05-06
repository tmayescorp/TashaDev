<?php

namespace WILCITY_APP\Controllers\Deprecated;

use WILCITY_APP\Controllers\BuildQuery;
use WILCITY_APP\Controllers\JsonSkeleton;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\WPML;

class EventController
{
	use BuildQuery;
	use JsonSkeleton;

	private $aUnnecessaryItems = ['businessStatus', 'oTerm', 'oIcon'];

	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/v2', '/events/(?P<target>\w+)', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getEvent'],
				'permission_callback' => '__return_true'
			]);
		});

		//        add_filter('wilcity/mobile/render_event_on_mobile', [$this, 'buildListingEventData'], 5, 2);
		add_filter('wilcity/app/single-skeletons/event', [$this, 'addAdditionalEventDataToSkeleton'], 10, 2);
		add_filter('wilcity/app/single-skeletons/event', [$this, 'buildEventSingleContent'], 15, 2);
		//		add_filter('wilcity/app/single-skeletons/event', array($this, 'getEventCommented'), 20, 2);
	}

	public function getEventCommented($aResponse, $post)
	{
		WPML::switchLanguageApp();
		$query = new \WP_Query(
			WPML::addFilterLanguagePostArgs([
				'post_type'      => 'event_comment',
				'post_status'    => 'publish',
				'parent__in'     => [$post->ID],
				'posts_per_page' => 6
			])
		);

		if (!$query->have_posts()) {
			$aResponse['oDiscussions'] = false;
		}

		$aComments = [];

		while ($query->have_posts()) {
			$query->the_post();
			$aComments[] = $this->eventCommentItem($query->post);
		}
		wp_reset_postdata();

		$aResponse['oDiscussions'] = [
			'oContent' => $aComments,
			'next'     => $query->post->max_number_pages > 1 ? 2 : false
		];

		return $aResponse;
	}

	public function buildEventSingleContent($aResponse, $post)
	{
		if (isset($aResponse['version']) && $aResponse['version'] === 'v2') {
			$aSettings = GetSettings::getOptions('event_content_fields');
			if (empty($aSettings)) {
				return $aResponse;
			}
			$aSections = [];
			foreach ($aSettings as $aSetting) {
				$content = $this->getListingData($aSetting['key'], $post);
				if (!empty($content)) {
					$aSections[] = [
						'text'    => $aSetting['name'],
						'type'    => $aSetting['key'],
						'content' => is_array($this->getListingData($aSetting['key'], $post)) ?
							$this->getListingData($aSetting['key'], $post) :
							strip_tags($this->getListingData($aSetting['key'], $post))
					];
				}
			}
			$aResponse['aSections'] = $aSections;
		}

		return $aResponse;
	}

	public function addAdditionalEventDataToSkeleton($aResponse, $post)
	{
		if (isset($aResponse['version']) && $aResponse['version'] === 'v2') {
			$aCalendar = \WilokeListingTools\Controllers\EventController::renderEventCalendar($post, true);
			$aEventMetaData = \WilokeListingTools\Controllers\EventController::getEventMetaData($post);

			$aResponse['oCalendar'] = $aCalendar;
			$aResponse['aMetaData'] = $aEventMetaData;
			$aResponse['hostedBy'] = GetSettings::getEventHostedByName($post);

			foreach ($this->aUnnecessaryItems as $item) {
				unset($aResponse[$item]);
			}
		}

		return $aResponse;
	}

	public function buildListingEventData($aAtts, $post)
	{
		return $this->listingSkeleton($post);
	}

	public function getEvent(\WP_REST_Request $request)
	{
		WPML::switchLanguageApp();
		$aData = $request->get_params();
		$aArgs = $this->buildSingleQuery($aData);
		$query = new \WP_Query(WPML::addFilterLanguagePostArgs($aArgs));

		if ($query->have_posts()) {
			global $post;
			$aPost = [];
			while ($query->have_posts()) {
				$query->the_post();
				$aPost = $this->listingSkeleton($post, [], $aData);
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
				'msg'    => esc_html__('No Post Found', 'wilcity-mobile-app')
			];
		}
	}
}
