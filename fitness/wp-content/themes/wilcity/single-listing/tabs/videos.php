<wil-single-nav-wrapper v-if="currentTab === 'videos'" :settings="getNavigation('videos')" id="single-nav-videos">
    <template v-slot:default="{settings}">
        <wil-single-nav-videos  :items="data.videos.items"
                                :settings="settings"></wil-single-nav-videos>
    </template>
</wil-single-nav-wrapper>
