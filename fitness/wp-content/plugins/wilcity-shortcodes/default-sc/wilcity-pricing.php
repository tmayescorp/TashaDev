<?php

use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Frontend\SingleListing;
use WilokeListingTools\Models\PlanRelationshipModel;
use WilokeListingTools\Models\UserModel;
use WILCITY_SC\SCHelpers;

function wilcityPricing($aAtts)
{
	$aArgs = [
		'post_type'      => 'listing_plan',
		'post_status'    => 'publish',
		'orderby'        => 'post__in',
		'posts_per_page' => -1
	];

	$wrapperClass = 'container ' . $aAtts['extra_class'];

	if (empty($aAtts['listing_type'])) {
		$aAtts['listing_type'] = 'flexible';
	}

	if (empty($aAtts['listing_type']) || $aAtts['listing_type'] == 'flexible') {
		if (!isset($_REQUEST['listing_type'])) {
			if (SingleListing::isElementorEditing()) {
				$aAtts['listing_type'] = General::getFirstPostTypeKey(false, false);
			} else {
				return '';
			}
		} else {
			$aAtts['listing_type'] = $_REQUEST['listing_type'];
		}

		$aIncludes = GetWilokeSubmission::getAddListingPlans($aAtts['listing_type'] . '_plans');
		$aArgs['post__in'] = $aIncludes;
		$aArgs['order'] = 'ASC';
	} else {
		$planKey = $aAtts['listing_type'] . '_plans';
		$aPlans = GetWilokeSubmission::getField($planKey);
		$aArgs['post__in'] = explode(',', $aPlans);
		$aArgs['order'] = 'ASC';
	}

	if (empty($aArgs['post__in'])) {
		WilokeMessage::message(
			[
				'status'       => 'warning',
				'hasRemoveBtn' => false,
				'hasMsgIcon'   => false,
				'msgIcon'      => 'la la-envelope-o',
				'msg'          => sprintf(__('It is almost done. Now, please read the following tutorials to setup Listing Pricing and Add Listing page <a href="https://documentation.wilcity.com/knowledgebase/setting-up-listing-pricing/" target="_blank">Setting up Listing Pricing</strong>',
					'wiloke-listing-tools'), ucfirst($aAtts['listing_type']) . ' Plans')
			]
		);

		return '';
	}

	$postID = $_REQUEST['postID'] ?? '';
	if (empty($postID)) {
		$postID = $_REQUEST['postId'] ?? '';
	}

	if (isset($_REQUEST['parentID']) && !empty($_REQUEST['parentID'])) {
		$aAtts['parentID'] = $_REQUEST['parentID'];
	} else if (isset($_REQUEST['parentId']) && !empty($_REQUEST['parentId'])) {
		$aAtts['parentID'] = $_REQUEST['parentId'];
	}

	$query = new WP_Query($aArgs);

	// Pricing card centered align
	$pricingTotalPosts = $query->found_posts;

	$pricingCol = '';

	if ($pricingTotalPosts === 1) {
		$pricingCol = 'one-col';
	} elseif ($pricingTotalPosts === 2) {
		$pricingCol = 'two-col';
	}

	?>

    <div class="<?php echo esc_attr($wrapperClass); ?>">
        <div class="row <?php echo esc_attr($pricingCol); ?>">
			<?php
			if ($query->have_posts()) :
				while ($query->have_posts()) : $query->the_post();
					$planId = GetWilokeSubmission::getOriginalPlanId($query->post->ID);
					$aPlanSettings = GetSettings::getPlanSettings($planId);
					$productID = GetSettings::getPostMeta($planId, 'woocommerce_association');

					$itemWrapperClass = GetSettings::getPostMeta($planId, 'is_recommended') ?
						'pricing_module__2WIXR pricing_features__3Ki80' : 'pricing_module__2WIXR';
					$btnClasses = 'wil-btn wil-btn--primary wil-btn--md wil-btn--round wil-btn--block';

					if (!empty($aPlanSettings['availability_items']) && empty($aPlanSettings['regular_price'])) {
						$totalListingsSubmitted = PlanRelationshipModel::countListingsUserSubmittedInPlan(
							$planId,
							get_current_user_id()
						);
						if ($totalListingsSubmitted >= abs($aPlanSettings['availability_items'])) {
							$btnClasses .= ' disabled';
						}
					}

					?>
                    <div class="<?php echo esc_attr($aAtts['items_per_row']); ?>">
                        <div class="<?php echo esc_attr($itemWrapperClass); ?>">
                            <header class="pricing_header__1hEFl">
								<?php if (GetSettings::getPostMeta($query->post->ID, 'is_recommended')) : ?>
                                    <div class="pricing_featuresText__1zmFJ">
										<?php echo esc_html(GetSettings::getPostMeta($query->post->ID,
											'recommend_text')); ?></div>
								<?php endif; ?>
                                <h2 class="pricing_title__1vXhE"><?php echo get_the_title($query->post->ID); ?></h2>
								<?php
								if (has_action('wilcity/wilcity-shortcodes/wilcity-pricing/render-price')) {
									do_action('wilcity/wilcity-shortcodes/wilcity-pricing/render-price', '',
										$aPlanSettings, $productID);
								} else {
									if (!empty($aPlanSettings['compare_at_price'])) {
										echo SCHelpers::renderPlanPrice(
											$aPlanSettings['compare_at_price'],
											$aPlanSettings,
											$productID,
											true
										);
									}
									echo SCHelpers::renderPlanPrice(
										$aPlanSettings['regular_price'],
										$aPlanSettings,
										$productID
									);
								}

								if (empty($productID) &&
									!empty($aPlanSettings['regular_price']) &&
									GetWilokeSubmission::isTaxOnPricing()) {
									?>
                                    <i class="wilcity-pricing-tax">
										<?php echo sprintf(
											__('%s: %s', 'wilcity-shortcodes'),
											GetWilokeSubmission::getField('tax_title'),
											GetWilokeSubmission::renderPrice(GetWilokeSubmission::calculateTax($aPlanSettings['regular_price']))
										);
										?></i>
									<?php
								}

								$remainingItems = UserModel::getRemainingItemsOfPlans($planId);
								if (!empty($remainingItems)) :
									?>
                                    <i class="wilcity-remaining-item-info"
                                       style="color: red;"><?php esc_html_e('Remaining Items: ',
											'wilcity-shortcodes'); ?><?php echo $remainingItems >= 1000 ?
											esc_html__('Unlimited', 'wilcity-shortcodes') :
											esc_html($remainingItems); ?></i>
								<?php endif; ?>
                            </header>
                            <div class="pricing_body__2-Vq5">
                                <div class="pricing_list__KtU8u">
									<?php the_content(); ?>
                                </div>
                            </div>
                            <footer class="pricing_footer__qz3lM">
								<?php if (isset($aAtts['toggle_nofollow']) &&
									$aAtts['toggle_nofollow'] == 'enable') : ?>
                                    <a class="<?php echo esc_attr($btnClasses); ?>"
                                       rel="nofollow"
                                       href="<?php echo esc_url(apply_filters('wilcity/submission/pricingUrl',
										   $query->post->ID, $postID, $aAtts)); ?>">
                                        <i class="la la-check"></i> <?php esc_html_e('Get Now',
											'wilcity-shortcodes'); ?>
                                    </a>
								<?php else: ?>
                                    <a class="<?php echo esc_attr($btnClasses); ?>"
                                       href="<?php echo esc_url(apply_filters('wilcity/submission/pricingUrl',
										   $query->post->ID, $postID, $aAtts)); ?>">
                                        <i class="la la-check"></i>
										<?php echo $remainingItems > 1 ? esc_html__('Continue', 'wilcity-shortcodes')
											: esc_html__('Get Now', 'wilcity-shortcodes'); ?>
                                    </a>
								<?php endif; ?>

                            </footer>
                        </div>
                    </div>
				<?php endwhile;
				wp_reset_postdata(); ?>
			<?php else: ?>
				<?php
				WilokeMessage::message(
					[
						'status' => 'danger',
						'msg'    => esc_html__('You do not have any Add Listing Plan. From the admin sidebar, click on Listing Plans to create one',
							'wilcity-shortcodes')
					]
				);
				?>
			<?php endif; ?>
        </div>
    </div>
	<?php
}
