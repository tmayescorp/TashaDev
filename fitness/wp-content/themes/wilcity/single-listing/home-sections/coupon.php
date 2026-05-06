<?php if (\WilokeListingTools\Frontend\SingleListing::hasCoupon($post)) : ?>
<wil-lazy-load-component id="wil-listing-coupon-info" height="1px;">
    <template v-slot:default="{isInView}">
        <?php get_template_part('single-listing/home-sections/section-heading'); ?>
        <wil-coupon-listing v-if="isInView" :settings="data.coupon" :post-url="data.postUrl"></wil-coupon-listing>
    </template>
</wil-lazy-load-component>
<?php endif; ?>

