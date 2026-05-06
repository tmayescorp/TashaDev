<?php global $post; ?>
<wil-single-nav-wrapper v-if="currentTab === 'photos'" :settings="getNavigation('photos')" id="single-photos">
    <template v-slot:default="{settings}">
        <wil-single-nav-photos :post-id="<?php echo abs($post->ID); ?>"
                              :settings="settings"></wil-single-nav-photos>
    </template>
</wil-single-nav-wrapper>
