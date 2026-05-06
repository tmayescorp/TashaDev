<?php
global $post;
?>
<wil-lazy-load-component id="wil-review-listing-statistic" height="50px">
    <template v-slot:default="{isInView}">
        <wil-review-listing-statistic v-if="isInView"
                                      :reviews="reviewConfiguration.reviews"
                                      :post-id="<?php echo abs($post->ID); ?>"
                                      heading="<?php echo esc_attr__('Average Reviews',
                                          'wilcity'); ?>"></wil-review-listing-statistic>
    </template>
</wil-lazy-load-component>
