<?php

use WilokeListingTools\Models\PostModel;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\General;
use \Wilcity\Ultils\ListItems\Lists;

General::$isBookingFormOnSidebar = true;
get_header();
while (have_posts()):
    the_post();
    do_action('wilcity/single-listing/before/wrapper', $post);
    if (!isset($_GET['hide_body']) || $_GET['hide_body'] !== 'listing_details') {
        global $post, $wiloke, $wilcitySingleSidebarPos;
        $logo = GetSettings::getLogo($post->ID, 'thumbnail');
        $url = get_permalink($post->ID);

        $aGeneralListingSettings = GetSettings::getPostMeta($post->ID,
            wilokeListingToolsRepository()->get('listing-settings:keys', true)->sub('general'));

        if (!isset($aGeneralListingSettings['sidebarPosition'])) {
            $wilcitySingleSidebarPos = 'wil-sidebar' . ucfirst($wiloke->aThemeOptions['single_listing_sidebar_layout']);
        } else {
            $wilcitySingleSidebarPos = 'wil-sidebar' . ucfirst($aGeneralListingSettings['sidebarPosition']);
        }

        $aListingConfiguration = wilcityGetConfig('listing');


        ?>
        <div id="<?php echo esc_attr(apply_filters('wilcity/filter/id-prefix',
            'wilcity-single-listing-content')); ?>">
            <div class="wil-content">
                <div class="listing-detail_module__2-bfH">
                    <?php get_template_part('single-listing/header'); ?>
                    <div class="listing-detail_first__1PClf">
                        <div class="container">
                            <div class="listing-detail_left__22FMI">
                                <div class="listing-detail_goo__1A8J-">
                                    <div class="listing-detail_logo__3fI4O bg-cover"
                                         style="background-image: url(<?php echo esc_url($logo); ?>);">
                                        <a href="<?php the_permalink(); ?>">
                                            <img class="hidden"
                                                 src="<?php echo esc_url($logo); ?>"
                                                 alt="<?php echo esc_attr($post->post_title); ?>"></a>
                                    </div>
                                </div>
                                <div class="listing-detail_titleWrap__2A2Mm js-titleWrap-detail">
                                    <h1 class="listing-detail_title__2cR-R">
                                    <span class="listing-detail_text__31u2P"><?php the_title(); ?>
                                        <?php if (PostModel::isClaimed($post->ID)) : ?>
                                            <span class="listing-detail_claim__10fsw color-primary"><i
                                                    class="la la-check"></i><span>
                                                <?php esc_html_e('Claimed', 'wilcity'); ?></span>
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                    </h1>
                                    <?php
                                    $tagLine = WilokeHelpers::getPostMeta($post->ID, 'tagline');
                                    if (!empty($tagLine)) :
                                        ?>
                                        <span
                                            class="listing-detail_tagline__3u_9y"><?php Wiloke::ksesHTML($tagLine); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="listing-detail_right__2KHL5">

                                <div class="listing-detail_rightButton__30xaS clearfix">
                                    <?php do_action('wilcity/action/single-listing/right-top-tools/before'); ?>
                                    <?php
                                    $oLists = new Lists();
                                    try {
                                        if (!WilokeThemeOptions::isEnable('toggle_report')) {
                                            unset($aListingConfiguration['singleListingRightTopToolsBtn']['wilReportBtn']);
                                        }

                                        if (!WilokeThemeOptions::isEnable('listing_toggle_favorite')) {
                                            unset($aListingConfiguration['singleListingRightTopToolsBtn']['wilFavoriteBtn']);
                                        }

                                        echo $oLists->setConfiguration(array_values($aListingConfiguration['singleListingRightTopToolsBtn']))
                                            ->beforeRenderElements()
                                            ->setWrapperEl('div')
                                            ->render();
                                    }
                                    catch (Exception $e) {
                                        WilokeMessage::message([
                                            'type' => 'danger',
                                            'msg'  => $e->getMessage()
                                        ]);
                                    }
                                    do_action('wilcity/action/single-listing/right-top-tools/after');
                                    ?>
                                </div>

                                <?php
                                $oLists = new Lists();
                                try {
                                    if (GetSettings::getOptions('toggle_report', true, true) !== 'enable') {
                                        unset($aListingConfiguration['singleListingDropdownBtn']['wilReportBtn']);
                                    }
                                    $oLists->setConfiguration($aListingConfiguration['singleListingDropdownBtn'])
                                        ->setWrapperClasses('list_module__1eis9 list-none list_small__3fRoS list_abs__OP7Og arrow--top-right')
                                        ->beforeRenderElements();
                                    if ($oLists->hasElements()) {
                                        ?>
                                        <wil-buttons-dropdown wrapper-classes="listing-detail_rightDropdown__3J1qK">
                                            <?php echo $oLists->render(); ?>
                                        </wil-buttons-dropdown>
                                        <?php
                                    }
                                }
                                catch (Exception $e) {
                                    WilokeMessage::message([
                                        'type' => 'danger',
                                        'msg'  => $e->getMessage()
                                    ]);
                                }
                                ?>

                            </div>
                        </div>
                    </div>
                    <?php
                    /*
                     * @hooked SingleListing:printNavigation
                     */
                    get_template_part('single-listing/navigation');
                    do_action('wilcity/single-listing/before-listing-template');
                    ?>
                    <?php get_template_part('wiloke-submission/listing-settings'); ?>
                    <div id="wil-home-section-wrapper" class="listing-detail_body__287ZB">
                        <div class="container">
                            <?php get_template_part('single-listing/content'); ?>
                        </div>
                    </div>
                </div>
                <?php do_action('wilcity/single-listing/wil-content', $post, true); ?>
            </div>
            <?php do_action('wilcity/single-listing/footer-wil-content'); ?>
        </div>
        <?php

        do_action('wilcity/before-close-root');
    }

    do_action('wilcity/single-listing/after/wrapper', $post);
endwhile;
wp_reset_postdata();

get_footer();
