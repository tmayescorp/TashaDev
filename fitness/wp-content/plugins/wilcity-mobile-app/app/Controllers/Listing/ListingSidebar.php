<?php

namespace WILCITY_APP\Controllers\Listing;

use WilokeListingTools\Frontend\SingleListing;

class ListingSidebar extends ListingSkeleton
{
	/**
	 * @param $post
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function getData(\WP_Post $post)
	{
		$aSidebarSettings = SingleListing::getSidebarOrder($post);
		$this->setPost($post);

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

			$aSidebarSetting['is_mobile'] = true;
			$aSidebarSetting['isMobile'] = true;
			$content = $this->getSCContent($aSidebarSetting);

			if ((isset($aSidebarSetting['isCustomSection']) && $aSidebarSetting['isCustomSection'] == 'yes') || (isset
					($aSidebarSetting['baseKey']) && $aSidebarSetting['baseKey'] == 'custom_section')) {
				$category = $this->getCustomSectionCategory($aSidebarSetting['content']);
				if ($category == 'boxIcon') {
					$aSidebarSetting['key'] = 'tags';
				} else {
					$aSidebarSetting['style'] = $category;
				}
			}

			if (empty($content) || $content == 'null') {
				continue;
			}

			$aSidebarSetting['name'] = $aSidebarSetting['name'] ?? '';
			$aSidebarItems[] = [
				'aSettings' => $aSidebarSetting,
				'oContent'  => $content
			];
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
}
