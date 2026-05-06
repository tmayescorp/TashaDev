<?php global $post; ?>

<wil-single-nav-wrapper v-if="currentTab === 'content'" :settings="getNavigation('content')" id="single-nav-content">
    <template v-slot:default="{settings}">
        <wil-single-nav-content
            :post-id="<?php echo abs($post->ID) ?>"
            :settings="settings"></wil-single-nav-content>
    </template>
</wil-single-nav-wrapper>
