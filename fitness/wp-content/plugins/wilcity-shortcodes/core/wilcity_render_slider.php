<?php

use WILCITY_SC\SCHelpers;
use WILCITY_SC\ParseShortcodeAtts\ParseShortcodeAtts;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\QueryHelper;

function wilcity_render_slider($atts)
{
	$oParseSC = new ParseShortcodeAtts($atts);
	$atts = $oParseSC->parse();

	$aArgs = SCHelpers::parseArgs($atts);
	$aArgs = wp_parse_args(QueryHelper::buildQueryArgs($aArgs), $aArgs);

	if (
		General::getPostTypeGroup($aArgs['post_type'], "event") &&
		in_array($aArgs['orderby'], [
			'upcoming_event',
			'happening_event',
			'ongoing_event',
			'starts_from_ongoing_event'
		])
	) {
		unset($aArgs['orderby']);
	}

	$query = new WP_Query($aArgs);

	if (!$query->have_posts()) {
		echo '';
		wp_reset_postdata();

		return false;
	}

	$atts = SCHelpers::mergeIsAppRenderingAttr($atts);

	if (SCHelpers::isApp($atts)) {
		$aResponse = apply_filters('wilcity/mobile/render_slider_sc', $atts, $query);

		echo '%SC%' . json_encode(
				[
					'oSettings' => $atts,
					'oResults'  => $aResponse,
					'TYPE'      => $atts['TYPE']
				]
			) . '%SC%';

		return '';
	}

	$wrap_class = apply_filters('wilcity-el-class', $atts);
	$wrap_class = implode(' ', $wrap_class) . '  ' . $atts['extra_class'] . ' ' .
		apply_filters('wilcity/filter/class-prefix', 'wilcity-events-slider-wrapper');

	if (wp_is_mobile() && isset($atts['mobile_img_size']) && !empty($atts['mobile_img_size'])) {
		$imgSize = $atts['mobile_img_size'];
	} else {
		if (!empty($atts['desktop_image_size'])) {
			$imgSize = $atts['desktop_image_size'];
		} else if (!empty($atts['image_size'])) {
			$imgSize = $atts['image_size'];
		} else {
			if ($atts['maximum_posts_on_lg_screen'] <= 2) {
				$imgSize = 'large';
			} else if ($atts['maximum_posts_on_lg_screen'] == 3) {
				$imgSize = 'medium';
			} else {
				$imgSize = 'wilcity_380x215';
			}
		}
	}

	$atts['lazyLoad'] = WilokeThemeOptions::isEnable('general_toggle_lazyload');
	if ((isset($atts['is_auto_play']) && $atts['is_auto_play'] === 'enable') || !isset($atts['autoplay'])) {
		$atts['autoplay'] = 3000;
	}
	?>
    <div id="<?php echo esc_attr($atts['wrapper_id']); ?>" class="<?php echo esc_attr($wrap_class); ?>">
		<?php
		if (!empty($atts['heading']) || !empty($atts['desc'])) {
			wilcity_render_heading([
				'TYPE'            => 'HEADING',
				'blur_mark'       => '',
				'blur_mark_color' => '',
				'heading'         => $atts['heading'],
				'heading_color'   => $atts['heading_color'],
				'desc'            => $atts['desc'],
				'desc_color'      => $atts['desc_color'],
				'alignment'       => $atts['header_desc_text_align'],
				'extra_class'     => ''
			]);
		}

		?>
		<?php if (class_exists('\Elementor\Plugin') && isset($_GET['action']) && $_GET['action'] == 'elementor') : ?>
            <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../images/listing-slider-placeholder.png');
			?>"/>
		<?php else : ?>
            <div id="<?php echo esc_attr(uniqid(apply_filters('wilcity/filter/id-prefix', 'wilcity-slider-'))); ?>"
                 class="swiper__module swiper-container swiper--button-pill swiper--button-abs-outer swiper--button-mobile-disable"
                 data-options='{"autoplay": {"delay":<?php echo abs($atts['autoplay']); ?>}, "slidesPerView":<?php echo esc_attr($atts['maximum_posts_on_extra_lg_screen']); ?>,"spaceBetween":30,"breakpoints":{"0":{"slidesPerView":1,"spaceBetween":10},"640":{"slidesPerView":<?php echo esc_attr($atts['maximum_posts_on_extra_sm_screen']); ?>,"spaceBetween":10},"992":{"slidesPerView":<?php echo esc_attr($atts['maximum_posts_on_sm_screen']); ?>,"spaceBetween":10},"1200":{"slidesPerView":<?php echo esc_attr($atts['maximum_posts_on_md_screen']); ?>,"spaceBetween":10},"1400":{"slidesPerView":<?php echo esc_attr($atts['maximum_posts_on_lg_screen']); ?>, "spaceBetween":20}, "1600":{"slidesPerView":<?php echo esc_attr($atts['maximum_posts_on_extra_lg_screen']); ?>, "spaceBetween":20}}}'
                 style="min-height: 285px;" data-lazy-load="<?php echo esc_attr($atts['lazyLoad']); ?>">

				<?php if ($atts['toggle_viewmore'] == 'enable') : ?>
                    <div class="<?php echo esc_attr(apply_filters('wilcity/filter/class-prefix',
						'btn-view-all-wrap clearfix')); ?>">
                        <a class="<?php echo esc_attr(apply_filters('wilcity/filter/class-prefix',
							'wil-view-all mb-15 btn-view-all wil-float-right')); ?>"
                           href="<?php echo SCHelpers::getViewAllUrl($atts); ?>"><?php echo esc_html($atts['viewmore_btn_name']); ?></a>
                    </div>
				<?php endif; ?>

                <div class="swiper-wrapper" data-lazy-load="<?php echo esc_attr($atts['lazyLoad']); ?>">
					<?php
					while ($query->have_posts()) {
						$query->the_post();
						if (General::isPostTypeInGroup($query->post->post_type, 'event')) {
							wilcity_event_slider_item($query->post, $imgSize, $atts);
						} else {
							wilcity_feature_slider_item($query->post, $imgSize, $atts);
						}
					}
					wp_reset_postdata();
					?>
                </div>

                <div class="swiper-button-custom">
                    <div class="swiper-button-prev-custom"><i class='la la-angle-left'></i></div>
                    <div class="swiper-button-next-custom"><i class='la la-angle-right'></i></div>
                </div>
            </div>
		<?php endif; ?>
    </div>
	<?php
}
