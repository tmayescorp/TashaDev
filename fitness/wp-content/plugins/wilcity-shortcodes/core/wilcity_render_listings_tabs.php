<?php

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\TermSetting;
use WILCITY_SC\SCHelpers;

/**
 * https://wilcityservice.com/support/ticket/44291
 * @param $aTermIds
 * @param $aTermsInfo : it's a pair key value of term: 1 => Shopping
 * @param $taxonomy
 */


function wilcityFallbackFindTermBySlug($aTermIds, $aTermsInfo, $taxonomy)
{
	$aTerms = [];
	foreach ($aTermIds as $termID) {
		$oTerm = get_term($termID, $taxonomy);
		if (empty($oTerm) || is_wp_error($oTerm)) {
			if (isset($aTermsInfo[$termID])) {
				$slug = sanitize_title($aTermsInfo[$termID]);
				$oTerm = get_term_by('slug', $slug, $taxonomy);
				if (empty($oTerm) || is_wp_error($oTerm)) {
					continue;
				}

				$aTerms[] = $oTerm->term_id;
			}
		} else {
			$aTerms[] = $termID;
		}
	}

	return $aTerms;
}

function wilcityRenderListingsTabsSC($aAtts)
{
	$aParentTermIDs = SCHelpers::getAutoCompleteVal($aAtts[$aAtts['taxonomy'] . 's']);
	$aParentTerms = SCHelpers::getAutoCompleteVal($aAtts[$aAtts['taxonomy'] . 's'], 'both');
	$aTabs = [];
	if (empty($aParentTermIDs)) {
		return '';
	}
	$aTermChildren = [];
	$prefix = 'term_tab';
	$aQueryArgs = [
		'postsPerPage' => $aAtts['posts_per_page'],
		'post_status'  => 'publish',
		'orderby'      => $aAtts['orderby'],
		'order'        => $aAtts['order'],
		'page'         => 1
	];

	$selected = '';

	if ($aAtts['taxonomy'] == 'custom') {
		if (empty($aAtts['custom_taxonomies_id']) || empty($aAtts['custom_taxonomy_key'])) {
			return '';
		}

		$aAtts['taxonomy'] = $aAtts['custom_taxonomy_key'];
		$aParentTermIDs = explode(',', $aAtts['custom_taxonomies_id']);
	}
	if ($aAtts['get_term_type'] == 'term_children') {
		$aRawTermIDs = get_terms(
			[
				'hide_empty' => false,
				'parent'     => $aParentTermIDs[0],
				'taxonomy'   => $aAtts['taxonomy'],
				'number'     => $aAtts['number_of_term_children'],
				'orderby'    => $aAtts['navigation_orderby'],
				'order'      => $aAtts['navigation_order'],
			]
		);

		if (empty($aRawTermIDs) || is_wp_error($aRawTermIDs)) {
			return '';
		}
		$oParentTerm = get_term($aParentTermIDs[0], $aAtts['taxonomy']);
		$aQueryArgs[$aAtts['taxonomy']] = $oParentTerm->term_id;

		$aTabs[$prefix . $oParentTerm->slug] = [
			'slug'     => $oParentTerm->slug,
			'query'    => $aQueryArgs,
			'name'     => esc_html__('All', 'wilcity-mobile-app'),
			'endpoint' => 'terms/' . $oParentTerm->slug
		];
		$selected = $prefix . $oParentTerm->slug;
		$parentLink = get_term_link($oParentTerm->term_id);
		foreach ($aRawTermIDs as $oTerm) {
			$aTermChildren[] = $oTerm->term_id;
		}
	} else {
		if ($aAtts['navigation_orderby'] === 'include') {
			$aTermChildren = $aParentTermIDs;
		} else {
			$aRawTermIDs = get_terms(
				[
					'hide_empty' => false,
					'taxonomy'   => $aAtts['taxonomy'],
					'orderby'    => $aAtts['navigation_orderby'],
					'order'      => $aAtts['navigation_order'],
					'include'    => $aParentTermIDs
				]
			);

			if (!empty($aRawTermIDs) && !is_wp_error($aRawTermIDs)) {
				foreach ($aRawTermIDs as $oNavigationItem) {
					if (empty($selected)) {
						$selected = $prefix . $oNavigationItem->slug;
					}
					$aTermChildren[] = $oNavigationItem->term_id;
				}
			}
		}
	}

	$aParsedOrderBy
		= is_array($aAtts['orderby_options']) ? $aAtts['orderby_options'] : explode(',', $aAtts['orderby_options']);

	$aPostKeys = [];

	if (!empty($aAtts['post_types_filter'])) {
		if (is_array($aAtts['post_types_filter'])) {
			$aPostKeys = $aAtts['post_types_filter'];
		} else {
			$aPostKeys = explode(',', $aAtts['post_types_filter']);
		}
	}

	$defaultPostType = '';
	if (!empty($aPostKeys)) {
		$defaultPostType = $aPostKeys[0];
	}

	foreach ($aTermChildren as $termID) {
		$oTerm = get_term($termID, $aAtts['taxonomy']);

		if (empty($oTerm) || is_wp_error($oTerm)) {
			if (isset($aParentTerms[$termID])) {
				$slug = sanitize_title($aParentTerms[$termID]);
				$oTerm = get_term_by('slug', $slug, $aAtts['taxonomy']);
				if (empty($oTerm) || is_wp_error($oTerm)) {
					continue;
				}
			}
		}

		if (empty($defaultPostType)) {
			$defaultPostType = TermSetting::getDefaultPostType($oTerm->term_id, $oTerm->taxonomy);
		}

		if (empty($selected)) {
			$selected = $prefix . $oTerm->slug;
		}

		$aQueryArgs[$aAtts['taxonomy']] = $oTerm->term_id;

		$aTabs[$prefix . $oTerm->slug] = [
			'slug'     => $oTerm->slug,
			'query'    => $aQueryArgs,
			'name'     => $oTerm->name,
			'endpoint' => 'terms/' . $oTerm->slug
		];
	}

	$aSCSettings = array_diff_assoc([
		'maximum_posts_on_lg_screen' => $aAtts['maximum_posts_on_lg_screen'],
		'maximum_posts_on_md_screen' => $aAtts['maximum_posts_on_md_screen'],
		'maximum_posts_on_sm_screen' => $aAtts['maximum_posts_on_sm_screen'],
		'maximum_posts_on_xs_screen' => $aAtts['maximum_posts_on_xs_screen'],
		'img_size'                   => $aAtts['img_size']
	], $aQueryArgs);

	unset($aSCSettings['heading_color']);
	unset($aSCSettings['description_color']);
	unset($aSCSettings['taxonomy']);
	unset($aSCSettings['get_term_type']);
	unset($aSCSettings['listing_cats']);
	unset($aSCSettings['terms_tab_id']);
	unset($aSCSettings['maximum_posts_on_lg_screen']);
	unset($aSCSettings['maximum_posts_on_md_screen']);
	unset($aSCSettings['maximum_posts_on_sm_screen']);
	unset($aSCSettings['maximum_posts_on_xs_screen']);
	$itemWrapperClass = $aAtts['maximum_posts_on_lg_screen'] . ' ' . $aAtts['maximum_posts_on_md_screen'] . ' ' .
		$aAtts['maximum_posts_on_sm_screen'] . ' ' . $aAtts['maximum_posts_on_xs_screen'] . ' mb-30';

	$aAtts['radius']
		= empty($aAtts['radius']) ? WilokeThemeOptions::getOptionDetail('default_radius') : $aAtts['radius'];
	$aAtts['unit'] = WilokeThemeOptions::getOptionDetail('unit_of_distance');

	$searchURL = add_query_arg(
		[
			'orderby' => $aAtts['orderby'],
			'order'   => $aAtts['order']
		],
		GetSettings::getSearchPage()
	);

	if ($aAtts['taxonomy'] === 'listing_location') {
		if (!empty($aAtts['listing_cat'])) {
			$aListingCatIds = SCHelpers::getAutoCompleteVal($aAtts['listing_cat']);
			$aListingCats = SCHelpers::getAutoCompleteVal($aAtts['listing_cat'], 'both');
			$aQueryArgs['listing_cat'] = wilcityFallbackFindTermBySlug(
				$aListingCatIds,
				$aListingCats,
				'listing_cat'
			);
		}
	} elseif ($aAtts['taxonomy'] === 'listing_cat') {
		if (!empty($aAtts['listing_location'])) {
			$aListingLocationIds = SCHelpers::getAutoCompleteVal($aAtts['listing_location']);
			$aListingLocations = SCHelpers::getAutoCompleteVal($aAtts['listing_location'], 'both');
			$aQueryArgs['listing_location'] = wilcityFallbackFindTermBySlug(
				$aListingLocationIds,
				$aListingLocations,
				'listing_location'
			);

		}
	}

	$aQueryArgs['postType'] = $defaultPostType;
	$isAutoPostsPerPage = !isset($aAtts['posts_per_page']) || empty($aAtts['posts_per_page']);

	$aQueryArgs = apply_filters(
		'wilcity/filter/wiloke-shortcodes/listings-tabs/query-args',
		$aQueryArgs,
		$aAtts
	);

	$wrap_class = implode(' ', apply_filters('wilcity-el-class', $aAtts));
	$wrap_class .= apply_filters('wilcity/filter/class-prefix', ' wilcity-terms-tabs wilcity-listings-tabs');

	?>

    <div id="<?php echo uniqid('wil-listing-tabs'); ?>"
         class="<?php echo esc_attr($wrap_class); ?>"
         data-orderby="<?php echo esc_attr($aAtts['orderby']); ?>"
         data-order="<?php echo esc_attr($aAtts['order']); ?>"
         data-queryargs='<?php echo json_encode($aQueryArgs); ?>'
         data-searchurl="<?php echo esc_url($searchURL); ?>"
         data-radius="<?php echo esc_attr($aAtts['radius']); ?>"
         data-unit="<?php echo esc_attr($aAtts['unit']); ?>"
         style="min-height: 400px;"
         data-taxonomy="<?php echo esc_attr($aAtts['taxonomy']); ?>">
		<?php if (class_exists('\Elementor\Plugin') && isset($_GET['action']) && $_GET['action'] == 'elementor') : ?>
            <img style="margin-top: 30px"
                 src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../images/listing-tabs-placeholder.png'); ?>"/>
		<?php else : ?>
            <wil-lazy-load-component id="<?php echo esc_attr(uniqid('lazyload-listings-tabs')); ?>"
                                     :intersection-args="{rootMargin: '100px'}">
                <template v-slot:default="{isInView}">
                    <wil-tabs v-if="isInView"
                              tab-key="<?php echo esc_attr(uniqid('listings_tabs_')); ?>"
                              selected="<?php echo esc_attr($selected); ?>"
                              tab-alignment="<?php echo esc_attr($aAtts['tab_alignment']); ?>"
                              v-on="{change: handleQueryChange('<?php echo esc_attr($aAtts['taxonomy']); ?>')}">
						<?php if (!empty($aAtts['heading']) || !empty($aAtts['post_types_filter'])) : ?>
                            <template v-slot:first-nav-item>
								<?php if (!empty($aAtts['heading'])) : ?>
                                    <li class="term-grid-title float-left">
										<?php if (isset($parentLink)) : ?>
                                            <a style="padding-left: 0; color: <?php echo esc_attr($aAtts['heading_color']); ?>"
                                               class="ignore-lava"
                                               href="<?php echo esc_url($parentLink); ?>"><?php echo esc_html($aAtts['heading']); ?></a>
										<?php else : ?>
                                            <a style="padding-left: 0; color: <?php echo esc_attr($aAtts['heading_color']); ?>"
                                               class="ignore-lava"
                                               href="#"><?php echo esc_html($aAtts['heading']); ?></a>
										<?php endif; ?>
                                    </li>
								<?php endif; ?>
								<?php
								if (!empty($aAtts['post_types_filter']) && count($aPostKeys) > 1) :
									$aOptions = [];
									foreach ($aPostKeys as $postType) {
										$oPostType = get_post_type_object($postType);
										if (empty($oPostType) || is_wp_error($oPostType)) {
											continue;
										}
										$aOptions[] = [
											'label' => $oPostType->labels->singular_name,
											'id'    => $postType
										];
									}
									?>
                                    <li class="term-tab-post-type-filter" style="margin-right: 20px;">
                                        <wil-simple-select v-on="{change: handleQueryChange('postType')}"
                                                           :value="queryArgs.postType"
                                                           :options='<?php echo json_encode($aOptions); ?>'>
                                        </wil-simple-select>
                                    </li>
								<?php endif; ?>
								<?php
								if (count($aParsedOrderBy) > 1) :
									$aAllOrderByType
										= wilcityShortcodesRepository()->get('configs/orderby', true)->sub('listing');
									$aOrderByOptions = [];
									foreach ($aParsedOrderBy as $orderby) {
										$aOrderByOptions[] = [
											'id'    => $orderby,
											'label' => $aAllOrderByType[$orderby]
										];
									}
									?>
                                    <li class="term-tab-post-type-filter">
                                        <wil-simple-select v-on="{change: handleOrderByChange}"
                                                           :value="fakeOrderBy"
                                                           :options='<?php echo json_encode($aOrderByOptions); ?>'>
                                        </wil-simple-select>
                                    </li>
								<?php endif; ?>
                            </template>
						<?php endif; ?>

						<?php if ($aAtts['toggle_viewmore'] == 'enable') : ?>
                            <template v-slot:last-nav-item>
                                <li class="term-view-more-wrapper">
                                    <a class="ignore-lava wilcity-view-all" :href="viewMoreURL">
										<?php esc_html_e('View more', 'wilcity-shortcodes'); ?>
                                    </a>
                                </li>
                            </template>
						<?php endif; ?>

                        <template slot-scope="{selected}">
							<?php foreach ($aTabs as $tabID => $aTermInfo) : ?>
                                <wil-tab :selected="selected"
                                         tab-id="<?php echo esc_attr($tabID); ?>"
                                         name="<?php echo esc_attr($aTermInfo['name']); ?>">
                                    <template v-slot:default="{tabId, selected}">
                                        <wil-async-grid
                                                endpoint="<?php echo esc_url(rest_url(WILOKE_PREFIX .
													'/v2/listings')); ?>"
                                                :query-args="queryArgs"
                                                :radius="<?php echo abs($aAtts['radius']); ?>"
                                                column-classes="<?php echo esc_attr($itemWrapperClass); ?>"
                                                unit="<?php echo esc_attr($aAtts['unit']); ?>"
                                                is-navigation="<?php echo esc_attr($aAtts['is_navigation']); ?>"
                                                :focus-error-msg="focusErrorMsg"
                                                v-on:page-paged="handlePageChanged"
                                                is-auto-posts-per-page="<?php echo $isAutoPostsPerPage ? 'yes' :
													'no'; ?>"
                                                wrapper-classes="w-100 pos-r float-left"
                                        >
                                        </wil-async-grid>
                                    </template>
                                </wil-tab>
							<?php endforeach; ?>
                        </template>
                    </wil-tabs>

					<?php do_action('wilcity/wilcity-shortcodes/core/wilcity_render_listings_tabs/after/tab',
						$aAtts); ?>
                </template>
            </wil-lazy-load-component>
		<?php endif; ?>
    </div>
	<?php
}
