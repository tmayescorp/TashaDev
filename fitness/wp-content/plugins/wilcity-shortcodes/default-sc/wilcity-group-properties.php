<?php

use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\Collection\ArrayCollection;

function wilcityGroupPropertiesSCParseOption($options)
{
	$aRawOptions = explode(',', $options);

	$aOptions = [];
	foreach ($aRawOptions as $rawOption) {
		$aParsed = explode(':', trim($rawOption));
		$val = isset($aParsed[1]) ? $aParsed[1] : $aParsed[0];

		$aOptions[$aParsed[0]] = $val;
	}

	return $aOptions;
}

function wilcityGroupPropertiesSCValueEmpty($aValues)
{
	foreach ($aValues as $val) {
		if (!empty($val)) {
			return false;
		}
	}

	return true;
}

function wilcityGroupFindMatchedValue($aOptions, $rawVal)
{
	foreach ($aOptions as $aItem) {
		if ($aItem['key'] && $aItem['key'] == $rawVal) {
			return $aItem;
		}
	}

	return false;
}

function wilcityGroupPropertiesSC($aAtts)
{
	$aAtts = shortcode_atts(
		[
			'group_key'       => '',
			'heading'         => '',
			'description'     => '',
			'post_id'         => '',
			'is_mobile'       => 'no',
			'gallery_size'    => 'thumbnail',
			'custom_sc_name'  => '',
			'gallery_columns' => 3,
			'item_wrapper'    => 'col-xs-12 col-md-4 col-lg-4'
		],
		$aAtts
	);

	if (empty($aAtts['group_key'])) {
		return '';
	}

	if (!empty($aAtts['post_id'])) {
		$postID = $aAtts['post_id'];
		$postType = get_post_type($postID);
	} else {
		global $post;
		$postID = $post->ID;
		$postType = $post->post_type;
	}

	if (strpos($aAtts['group_key'], 'wilcity_group') === false) {
		$groupKey = 'wilcity_group_' . $aAtts['group_key'];
	} else {
		$groupKey = $aAtts['group_key'];
	}

	$aSettings = GetSettings::getPostMeta($postID, $groupKey);
	if (empty($aSettings) || !isset($aSettings['items']) || empty($aSettings['items'])) {
		return '';
	}

	$aGroupSettings = General::findField($postType, $aAtts['group_key']);
	if (empty($aGroupSettings)) {
		return '';
	}

	$focusFilter
		= "wilcity_shortcode/focus-filter/wilcity_render_group_field/" . get_post_type($postID) . "/" .
		$aAtts['group_key'];
	if (has_filter($focusFilter)) {
		return apply_filters(
			$focusFilter,
			'',
			$aSettings,
			$aGroupSettings,
			$aAtts
		);
	}

	$focusFilter = "wilcity_shortcode/focus-filter/wilcity_render_group_field/" . $aAtts['group_key'];
	if (has_filter($focusFilter)) {
		return apply_filters(
			$focusFilter,
			'',
			$aSettings,
			$aGroupSettings,
			$aAtts
		);
	}

	if (!empty($aAtts['custom_sc_name'])) {
		$focusFilter = "wilcity_shortcode/focus-filter/wilcity_render_group_field/" . $aAtts['custom_sc_name'];
		if (has_filter($focusFilter)) {
			return apply_filters(
				$focusFilter,
				'',
				$aSettings,
				$aGroupSettings,
				$aAtts
			);
		}
	}

	$oCollection = new ArrayCollection($aGroupSettings);
	$aFieldSkeletons = $oCollection->deepPluck('fieldGroups->settings->fieldsSkeleton')
		->magicKeyGroup('key')
		->output();

	if (isset($aAtts['is_mobile']) && $aAtts['is_mobile'] == 'yes') {
		foreach ($aSettings['items'] as $order => $aItems) {
			if (empty($aItems)) {
				unset($aSettings['items'][$order]);
				continue;
			}

			foreach ($aItems as $itemKey => $rawVal) {
				if (empty($rawVal) || !isset($aFieldSkeletons[$itemKey])) {
					unset($aItems[$itemKey]);
					continue;
				}

				$aData = [
					'title'   => $aFieldSkeletons[$itemKey]['label'],
					'content' => $rawVal
				];

				$aItems[$itemKey] = $aData;
			}

			$aSettings['items'][$order] = $aItems;

			if (empty($aItems)) {
				unset($aSettings['items'][$order]);
				continue;
			}
		}

		return apply_filters(
			'wilcity/filter/wilcity-shortcodes/default-sc/wilcity-group-properties',
			json_encode($aSettings),
			$aAtts
		);
	}

	ob_start();
	?>
	<?php if (!empty($aSettings['title'])) : ?>
    <div class="col-md-12"><h4 class="<?php echo esc_attr(apply_filters('wilcity/filter/class-prefix',
			'wilcity-group-properties-heading')); ?>"><?php echo esc_html($aSettings['title']); ?></h4></div>
<?php endif; ?>

	<?php if (!empty($aSettings['description'])) : ?>
    <div class="col-md-12"><p class="<?php echo esc_attr(apply_filters('wilcity/filter/class-prefix',
			'wilcity-group-properties-desc')); ?>"><?php Wiloke::ksesHTML($aSettings['description']); ?></p></div>
<?php endif; ?>
	<?php
	if (empty($aFieldSkeletons)) {
		$content = \WilokeMessage::message([
			'status' => 'danger',
			'msg'    => esc_html__('Invalid group fields format, please re-check your settings and correct it',
				'wilcity-shortcodes')
		]);
	} else {
		foreach ($aSettings['items'] as $aItems) :
			if (empty($aItems)) {
				continue;
			}
			?>
            <div class="<?php echo esc_attr($aAtts['item_wrapper']); ?>">
				<?php
				foreach ($aItems as $itemKey => $rawVal) :
					$itemColor = '';
					$itemIcon = '';
					$isGallery = false;
					if (empty($rawVal) || !isset($aFieldSkeletons[$itemKey])) {
						continue;
					}

					$itemVal = [];
					if (strpos($aFieldSkeletons[$itemKey]['type'], 'select') !== false) {
						$aOptions = General::parseSelectFieldOptions($aFieldSkeletons[$itemKey]['options'], 'full');
						if (is_array($rawVal)) {
							foreach ($aOptions as $aOption) {
								if (in_array($aOption['key'], $rawVal)) {
									$itemVal[] = $aOption['name'];
								}
							}
							$itemVal = implode(',', $itemVal);
						} else {
							$aSelectedItem = wilcityGroupFindMatchedValue($aOptions, $rawVal);
							if (!empty($aSelectedItem)) {
								$itemVal = $aSelectedItem['name'];
								$itemColor = $aSelectedItem['color'];
								$itemIcon = $aSelectedItem['icon'];
							}
						}
					} else {
						$isGallery = strpos($aFieldSkeletons[$itemKey]['type'], 'uploader') !== false;
						$itemVal = $rawVal;
					}
					?>
                    <div class="utility-meta-02_module__1VqhJ">
                        <div class="utility-meta-02_left__3P9WL"><?php echo esc_html($aFieldSkeletons[$itemKey]['label']); ?>
                            :
                        </div>
                        <div class="utility-meta-02_right__OiUF-">
							<?php if (!empty($itemIcon)) : ?>
                                <i class="<?php echo esc_attr($itemIcon); ?>"
                                   style="color: <?php echo esc_attr($itemColor); ?>"></i>
							<?php endif; ?>
							<?php
							if ($isGallery) {
								if (is_array($itemVal)) {
									$galleryIds = array_keys($itemVal);
									echo gallery_shortcode([
										'ids'     => $galleryIds,
										'size'    => $aAtts['gallery_size'],
										'columns' => $aAtts['gallery_columns'],
										'link'    => 'file'
									]);
								} else {
									$galleryId = isset($aItems[$itemKey . '_id']) ? $aItems[$itemKey . '_id'] : '';
									if (!empty($galleryId)) {
										echo gallery_shortcode([
											'ids'     => $galleryId,
											'size'    =>
												$aAtts['gallery_size'],
											'columns' => $aAtts['gallery_columns'],
											'link'    => 'file'
										]);
									}
								}
							} else {
								Wiloke::ksesHTML(nl2br($itemVal));
							}
							?>
                        </div>
                    </div>
				<?php endforeach; ?>
            </div>
		<?php
		endforeach;

		$content = ob_get_clean();

		$content = apply_filters(
			"wilcity_shortcode/wilcity_render_group_field/" . get_post_type($postID) . "/" . $aAtts['group_key'],
			$content,
			$aSettings,
			$aGroupSettings,
			$aAtts
		);
	}

	return $content;

}

add_shortcode('wilcity_group_properties', 'wilcityGroupPropertiesSC');
