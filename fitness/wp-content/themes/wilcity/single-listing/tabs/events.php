<?php global $post; ?>
<wil-single-nav-wrapper v-if="currentTab === 'events'" :settings="getNavigation('events')" id="single-nav-events">
    <template v-slot:default="{settings}">
        <wil-single-nav-posts :post-id="<?php echo abs($post->ID); ?>"
                              tab-key="events"
                              :settings="settings"></wil-single-nav-posts>
    </template>
</wil-single-nav-wrapper>
