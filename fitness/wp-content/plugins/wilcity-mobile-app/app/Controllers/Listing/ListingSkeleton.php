<?php

namespace WILCITY_APP\Controllers\Listing;

use Exception;
use WILCITY_APP\Helpers\App;
use WILCITY_SC\SCHelpers;
use WilokeListingTools\Controllers\SearchFormController;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\PostSkeleton;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Frontend\BusinessHours;
use WilokeListingTools\Frontend\SingleListing;
use WilokeListingTools\Frontend\User as WilcityUser;
use WilokeListingTools\MetaBoxes\Listing as ListingMetaBoxes;
use WilokeListingTools\Models\ReviewMetaModel;
use WP_Post;

class ListingSkeleton
{
	/**
	 * This setting is very important. It will define what style custom section should be rendered
	 * @var array
	 */
	protected $aCustomSectionCategories
		                       = [
			'select_field'      => 'boxIcon',
			'checkbox_field'    => 'boxIcon',
			'checkbox2_field'   => 'boxIcon',
			'multiple-checkbox' => 'boxIcon',
			'textarea_field'    => 'text',
			'date_time_field'   => 'text',
			'text_field'        => 'text',
			'input_field'       => 'text',
			'image_field'       => 'image',
			'group_properties'  => 'group_custom_field'
		];
	private   $aCacheScContent = [];
	private   $aCachePostSlug  = [];
	protected $post;
	protected $postID;

	protected function rebuildFavorite($aData)
	{
		return [
			'isMyFavorite'   => $aData['isMyFavorite'],
			'totalFavorites' => $aData['totalFavorites']
		];
	}

	protected function rebuildAuthor($aData)
	{
		return [
			'ID'          => $aData['authorID'],
			'avatar'      => $aData['authorAvatar'],
			'displayName' => $aData['authorName'],
		];
	}

	protected function rebuildGallery($aImages)
	{
		$aGallery = [];
		foreach ($aImages as $aImage) {
			$src = wp_get_attachment_image_url($aImage['id'], 'medium');
			$aGallery['medium'][] = [
				'id'  => $aImage['id'],
				'url' => $src ? $src : $aImage['full']
			];

			$src = wp_get_attachment_image_url($aImage['id'], 'large');
			$aGallery['large'][] = [
				'id'  => $aImage['id'],
				'url' => $src ? $src : $aImage['full']
			];
		}

		return $aGallery;
	}

	/**
	 * @param WP_Post $post
	 *
	 * @return $this
	 */
	protected function setPost(WP_Post $post)
	{
		$this->post = $post;
		$this->setPostID($post->ID);

		return $this;
	}

	protected function setPostID($postID)
	{
		$this->postID = abs($postID);

		return $this;
	}

	protected function getPostIDBySlug($slug)
	{
		global $wpdb;
		if (isset($this->aCachePostSlug[$slug])) {
			return $this->aCachePostSlug[$slug];
		}

		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE name=%s",
				$slug
			)
		);
		$this->aCachePostSlug[$slug] = $id;

		return empty($id) ? false : $id;
	}

	private function setCacheSCContent($key, $val)
	{
		$this->aCacheScContent[$key] = $val;
	}

	private function getCacheSCContent($key)
	{
		return array_key_exists($key, $this->aCacheScContent) ? $this->aCacheScContent[$key] : false;
	}

	protected function isFocusRemoveFromApp($key)
	{
		return in_array($key, ['google_adsense_1', 'google_adsense_2']);
	}

	/**
	 * @param $content
	 *
	 * @return mixed|string
	 */
	protected function getCustomSectionCategory($content)
	{
		foreach ($this->aCustomSectionCategories as $fieldType => $category) {
			if (strpos($content, $fieldType) === false) {
				continue;
			}

			return $category;
		}

		return 'unknown';
	}

	/**
	 * This step is very important. It will add is_mobile to shortcode content, so Wilcity Shortcode will
	 * return json data instead of raw html
	 *
	 * @param        $content
	 * @param string $postID
	 *
	 * @return string
	 */
	protected function parseCustomSectionContent($content)
	{
		if (empty($content)) {
			return '';
		}

		$content = str_replace(['{{', '}}'], ['"', '"'], $content);

		return trim(preg_replace_callback('/\s+/', function ($matched) {
			if (!empty($this->postID)) {
				return ' is_mobile="yes" post_id="' . $this->postID . '" ';
			}

			return ' is_mobile="yes" ';
		}, $content, 1));
	}

	/**
	 * @param $aSection
	 *
	 * @return bool
	 */
	protected function isCustomSection($aSection)
	{
		return (isset($aSection['baseKey']) && $aSection['baseKey'] === 'custom_section') ||
			(isset($aSection['isCustomSection']) && $aSection['isCustomSection'] == 'yes');
	}

	/**
	 * @param $aSection It's section setting under navigation
	 *
	 * @return array|bool|mixed|string|null
	 */
	protected function getSCContent($aSection)
	{
		global $post;
		$post = $this->post;
		$cacheKey = $aSection['key'] . '_' . $this->postID;
		$aRenderMachine = wilokeListingToolsRepository()
			->get('single-sidebar:sidebar_settings', true)
			->sub('renderMachine');

		$val = null;
		if (!isset($aRenderMachine[$aSection['key']])) {
			if (!isset($aSection['content'])) {
				$this->setCacheSCContent($cacheKey, false);

				return false;
			}

			if ($this->isCustomSection($aSection)) {
				$category = $this->getCustomSectionCategory($aSection['content']);

				if ($category !== 'unknown') {
					$parsedContent = $this->parseCustomSectionContent($aSection['content']);

					if (!empty($parsedContent)) {
						$val = do_shortcode($parsedContent);
						if ($category == 'boxIcon') {
							$aSection['category'] = 'tags';
							$val =  json_decode($val, true);
						} else if ($category == 'group_custom_field') {
							$val = json_decode($val, true);
						}
					}
				}
			} else {
				$val = $aSection['content'];
			}
		} else {
			$val = do_shortcode(sprintf(
				"[%s atts='%s' post_id='%s' is_mobile='yes' /]",
				$aRenderMachine[$aSection['key']],
				SCHelpers::encodeAtts($aSection),
				$this->post->ID
			));

			if (!empty($val)) {
				$parseVal = json_decode($val, true);
				$val = is_array($parseVal) ? $parseVal : $val;
			}
		}

		$this->setCacheSCContent($cacheKey, false);

		$val = empty($val) ? false : $val;

		return apply_filters(
			'wilcity/filter/wilcity-mobile-app/app/Controllers/Listing/ListingSkeleton/getSCContent/' .
			$aSection['key'],
			$val,
			$aSection
		);
	}

	/**
	 * @param $aSection
	 *
	 * @return bool
	 * @throws Exception
	 */
	protected function isContentExists($aSection)
	{
		if (!$this->post instanceof WP_Post) {
			throw new Exception('You must set post first');
		}

		switch ($aSection['baseKey']) {
			case 'custom_section':
				$val = $this->getSCContent($aSection);
				break;
			case 'taxonomy':
				$val = App::get('PostSkeleton')->getSkeleton($this->post, ['taxonomy'], $aSection);
				break;
			default:
				$filterHook
					= 'wilcity/filter/wilcity-mobile-app/app/Controllers/Listing/ListingSkeleton/isContentExists/'
					. $aSection['baseKey'];
				if (has_filter($filterHook)) {
					$val = apply_filters(
						$filterHook,
						false,
						$aSection,
						$this->post
					);
				} else {
					$val = App::get('PostSkeleton')->getSkeleton($this->post, [$aSection['key']], $aSection);

					if (empty($val[$aSection['key']])) {
						$val = false;
					}
				}
				break;
		}

		return !empty($val);
	}
}
