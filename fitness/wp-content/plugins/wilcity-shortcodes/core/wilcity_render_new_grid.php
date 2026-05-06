<?php

use WILCITY_SC\SCHelpers;
use WilokeListingTools\Framework\Helpers\General;
use \WilokeListingTools\Framework\Helpers\GetSettings;
use WILCITY_SC\ParseShortcodeAtts\ParseShortcodeAtts;
use WilokeListingTools\Framework\Helpers\QueryHelper;

function wilcity_sc_render_new_grid($aAtts)
{
	$oParseSC = new ParseShortcodeAtts($aAtts);
	$aAtts = $oParseSC->parse();
	$aArgs = [
		'TYPE'           => 'GRID',
		'posts_per_page' => $aAtts['posts_per_page'],
		'post_type'      => $aAtts['post_type'],
		'order'          => $aAtts['order'],
		'orderby'        => $aAtts['orderby']
	];

	if (!empty($aAtts['terms_in_sc'])) {
		foreach ($aAtts['terms_in_sc'] as $taxonomy => $aTerms) {
			$aArgs['tax_query'][] = [
				'taxonomy' => $taxonomy,
				'terms'    => $aTerms,
				'field'    => 'term_id'
			];
		}
	}

	$aArgs = apply_filters(
		'wilcity/filter/wiloke-shortcodes/new-grid/query-args',
		$aArgs,
		$aAtts
	);

    if (isset($_GET['action']) && $_GET['action'] == 'elementor' && $aArgs['post_type'] == 'flexible') {
        $aArgs['post_type'] = General::getDefaultPostType(true);
    }

	$query = new WP_Query($aArgs);
	if (!$query->have_posts()) {
		return '';
	}

	$wrap_class = apply_filters('wilcity-el-class', $aAtts);
	$wrap_class = implode(' ', $wrap_class) . '  ' . $aAtts['extra_class'] . ' ';
	$wrap_class .= apply_filters('wilcity/filter/class-prefix', 'wil-new-grid-wrapper');
	$columnClasses = $aAtts['maximum_posts_on_lg_screen'] . ' ' . $aAtts['maximum_posts_on_md_screen'] . ' ' .
		$aAtts['maximum_posts_on_sm_screen'] . ' ' . $aAtts['maximum_posts_on_xs_screen'];
	$aArgs['postsPerPage'] = abs($aAtts['posts_per_page']);
	$headingJSON = SCHelpers::parseHeading($aAtts);
	unset($aArgs['posts_per_page']);
	?>
    <div id="<?php echo esc_attr(uniqid('wil-new-grid-')); ?>"
         class="<?php echo esc_attr($wrap_class); ?>"
         data-orderby="<?php echo esc_attr($aAtts['orderby']); ?>"
         data-raw-query-args="<?php echo base64_encode(json_encode($aArgs)); ?>">

		<?php if (class_exists('\Elementor\Plugin') && isset($_GET['action']) && $_GET['action'] == 'elementor') : ?>
            <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../images/listing-grid-placeholder.png'); ?>"/>
		<?php else : ?>
            <wil-lazy-load-component id="<?php echo esc_attr(uniqid('lazyload-grid')); ?>"
                                     :intersection-args="{rootMargin: '100px'}">
                <template v-slot:default="{isInView}">
                    <wil-async-grid v-if="isInView"
                                    :query-args="queryArgs"
                                    :focus-error-msg="focusErrorMsg"
                                    endpoint="<?php echo esc_url(rest_url(WILOKE_PREFIX . '/v2/listings')); ?>"
                                    column-classes="<?php echo esc_attr($columnClasses); ?>">
                        <template v-slot:before-grid="{isLoading, isLoaded, maxPages}">
                            <div v-if="maxPages > 0">
                                <wil-section-heading settings="<?php echo base64_encode($headingJSON); ?>">
									<?php if ($aAtts['toggle_viewmore'] == 'enable') : ?>
                                        <template v-slot:after-heading>
											<?php
											$aViewMoreArgs = [
												'postType' => $aAtts['post_type'],
												'order'    => $aAtts['order'],
												'orderby'  => $aAtts['orderby']
											];

											if (isset($aAtts['terms_in_sc']) && !empty($aAtts['terms_in_sc'])) {
												$aViewMoreArgs = $aViewMoreArgs + $aAtts['terms_in_sc'];
											}
											$aAtts['viewmore_btn_name'] = empty($aAtts['viewmore_btn_name']) ?
												esc_html__
												('View more', 'wilcity-shortcodes') : $aAtts['viewmore_btn_name'];
											?>
                                            <div class="clearfix wil-grid-viewmore-btn-wrapper">
                                                <a class="ignore-lava wilcity-view-all mt-10 d-inline-block"
                                                   href="<?php echo esc_url(QueryHelper::buildSearchPageURL($aViewMoreArgs)); ?>">
													<?php echo esc_html($aAtts['viewmore_btn_name']); ?>
                                                </a>
                                            </div>
                                        </template>
									<?php endif; ?>
                                </wil-section-heading>
                            </div>
                        </template>
                    </wil-async-grid>

					<?php do_action('wilcity/wilcity-shortcodes/core/wilcity_render_new_grid/after/grid', $aAtts); ?>
                </template>
            </wil-lazy-load-component>
		<?php endif; ?>
    </div>
	<?php
}
