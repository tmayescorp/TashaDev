<?php

use WILCITY_SC\ParseShortcodeAtts\ParseShortcodeAtts;
use WILCITY_SC\SCHelpers;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\QueryHelper;

function wilcity_sc_render_events_grid($atts)
{
	$oParseSC = new ParseShortcodeAtts($atts);
	$atts = $oParseSC->parse();
	$aArgs = SCHelpers::parseArgs($atts);

	$aArgs = wp_parse_args(QueryHelper::buildQueryArgs($aArgs), $aArgs);
	$aArgs['post_type'] = !isset($aArgs['post_type']) || empty($aArgs['post_type']) ? 'event' : $aArgs['post_type'];
	if (General::getPostTypeGroup($aArgs['post_type'], "event") &&
		in_array($aArgs['orderby'], [
				'upcoming_event',
				'happening_event',
				'ongoing_event',
				'starts_from_ongoing_event'
			]
		)) {
		unset($aArgs['orderby']);
	}

	$query = new WP_Query($aArgs);

	if (!$query->have_posts()) {
		wp_reset_postdata();

		return '';
	}

	$wrap_class = apply_filters('wilcity-el-class', $atts);
	$wrap_class = implode(' ', $wrap_class) . '  ' . $atts['extra_class'];
	$wrap_class .= apply_filters('wilcity/filter/class-prefix', ' wilcity-event-grid wilcity-grid');

	if (wp_is_mobile() && isset($atts['mobile_img_size']) && !empty($atts['mobile_img_size'])) {
		$atts['img_size'] = $atts['mobile_img_size'];
	}
	?>
    <div id="<?php echo esc_attr($atts['wrapper_id']); ?>" class="<?php echo esc_attr($wrap_class); ?>"
         data-col-xs-gap="15" data-col-sm-gap="15" data-col-md-gap="15">
		<?php
		if (!empty($atts['heading']) || !empty($atts['desc'])) {
			wilcity_render_heading([
				'TYPE'              => 'HEADING',
				'blur_mark'         => '',
				'blur_mark_color'   => '',
				'heading'           => $atts['heading'],
				'heading_color'     => $atts['heading_color'],
				'description'       => $atts['desc'],
				'description_color' => $atts['desc_color'],
				'alignment'         => $atts['header_desc_text_align'],
				'extra_class'       => ''
			]);
		}
		?>
		<?php if ($atts['toggle_viewmore'] == 'enable') : ?>
            <div class="<?php echo esc_attr(apply_filters('wilcity/filter/class-prefix',
				'btn-view-all-wrap clearfix')); ?>">
                <a class="<?php echo esc_attr(apply_filters('wilcity/filter/class-prefix',
					'wil-view-all btn-view-all wil-float-right mb-15')); ?>"
                   href="<?php echo SCHelpers::getViewAllUrl($atts); ?>"><?php echo esc_html($atts['viewmore_btn_name']); ?></a>
            </div>
		<?php endif; ?>
        <div class="row">

			<?php
			while ($query->have_posts()) {
				$query->the_post();
				wilcity_render_event_item($query->post, $atts);
			}
			wp_reset_postdata();
			?>
        </div>
    </div>
	<?php
}
