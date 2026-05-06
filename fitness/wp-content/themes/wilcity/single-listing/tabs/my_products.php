<?php global $post; ?>
<wil-single-nav-wrapper v-if="currentTab === 'my_products'" :settings="getNavigation('my_products')" id="single-products">
    <template v-slot:default="{settings}">
        <wil-single-nav-posts :post-id="<?php echo abs($post->ID); ?>"
                              tab-key="my_products"
                              ajax-action="wilcity_get_my_products"
                              :settings="settings"></wil-single-nav-posts>
    </template>
</wil-single-nav-wrapper>
