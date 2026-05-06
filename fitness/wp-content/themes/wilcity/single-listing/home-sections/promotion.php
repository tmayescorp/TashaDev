<?php

use WilokeListingTools\Frontend\User;
use WilokeListingTools\Framework\Helpers\GetSettings;

global $post, $wilcityArgs;

if (!User::isPostAuthor($post) || $post->post_status !== 'publish') {
    return '';
}

$togglePromotion = GetSettings::getOptions('toggle_promotion');
if ($togglePromotion != 'enable') {
    return '';
}

?>
<wil-lazy-load-component v-if="currentTab==='home'" id="wil-listing-promotion-statistic" height="1px;">
    <template v-slot:default="{isInView}">
        <wil-promotion-listing-statistic v-if="isInView" icon="la la-bar-chart"
                                         heading="<?php echo esc_attr__('Boost your listing', 'wilcity'); ?>"
                                         btn-name="<?php echo esc_attr__('Promote Listing', 'wilcity'); ?>"
                                         boost-listing-title="<?php echo esc_attr__('Boost Your Listing today',
                                             'wilcity'); ?>"
                                         boost-listing-desc="<?php echo esc_attr__('Reach more people visit your listing',
                                             'wilcity'); ?>"
                                         :post-id="<?php echo abs($post->ID); ?>"
                                         :compares="data.compareStatistic"
                                         :reviews="reviewConfiguration.reviews.statistic"></wil-promotion-listing-statistic>
    </template>
</wil-lazy-load-component>
