<?php

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Controllers\ReviewController;
use WilokeListingTools\Models\ReviewMetaModel;
use WilokeListingTools\Models\ReviewModel;
use WilokeListingTools\Frontend\SingleListing;
use WilokeListingTools\Framework\Helpers\General;

global $post, $wiloke, $wilcityArgs, $wilcitySingleSidebarPos;
$aContentsOrder = SingleListing::getNavOrder();

?>
<div id="single-home" class="single-tab-content <?php echo esc_attr(apply_filters('wilcity/filter/class-prefix',
    'wilcity-js-toggle-group')); ?>" data-tab-key="home" v-show="currentTab === 'home'">
    <div class="listing-detail_row__2UU6R clearfix">
        <div class="wil-colLarge <?php echo esc_attr($wilcitySingleSidebarPos); ?>">
            <?php
            do_action('wilcity/single-listing/home/before/content', $post, $wilcityArgs);

            get_template_part('single-listing/home-sections/using-custom-settings-warning');
            get_template_part('single-listing/home-sections/top-block');
            get_template_part('single-listing/home-sections/average-rating');
            get_template_part('single-listing/home-sections/promotion');
            get_template_part('single-listing/home-sections/woocommerce-booking');

            foreach ($aContentsOrder as $key => $aContentSetting) {
                if ($aContentSetting['isShowOnHome'] === 'no') {
                    continue;
                }

                $wilcityArgs = $aContentSetting;

                if (isset($aContentSetting['isCustomSection']) && $aContentSetting['isCustomSection'] === 'yes') {
                    $fileName = 'custom-section';
                } elseif (strpos($aContentSetting['key'], 'google_adsense') !== false) {
                    $fileName = 'google-adsense';
                } else if ($key === 'tags') {
                    $fileName                = 'taxonomy';
                    $wilcityArgs['taxonomy'] = 'listing_tag';
                } else if (isset($wilcityArgs['taxonomy']) && taxonomy_exists($wilcityArgs['taxonomy'])) {
                    $fileName = 'taxonomy';
                } else {
                    $fileName = isset($wilcityArgs['baseKey']) ? $wilcityArgs['baseKey'] : $wilcityArgs['key'];
                }

                $fileDir = 'wilcity/single-listing/home-sections/'.$fileName;

                if (has_action($fileDir)) {
                    do_action($fileDir, $wilcityArgs);
                } else {
                    $fileDir = apply_filters(
                        'wilcity/filter/single-listing/home/navigation-dir',
                        str_replace('wilcity', '', $fileDir),
                        $aContentSetting
                    );

                    if (strpos($fileDir, 'single-listing/home-sections') !== false) {
                        get_template_part($fileDir);
                    } else {
                        include $fileDir;
                    }
                }
            }
            ?>
        </div>
        <div class="wil-colSmall <?php echo esc_attr(apply_filters('wilcity/filter/class-prefix',
            'wilcity-js-toggle-group')); ?>" data-tab-key="home">
            <?php
            /*
             * @hooked SingleListing:printContent
             */
            get_template_part('single-listing/sidebar');
            ?>
        </div>
    </div>
</div>
