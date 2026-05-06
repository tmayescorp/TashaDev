<?php global $post, $wilcityTabKey, $wilcityArgs; ?>
<wil-single-nav-wrapper v-if="currentTab === '<?php echo esc_attr($wilcityTabKey); ?>'"
                        :settings="getNavigation('<?php echo esc_attr($wilcityTabKey); ?>')"
                        id="single-nav-posts">
    <template v-slot:default="{settings}">
        <wil-single-nav-posts :post-id="<?php echo abs($post->ID); ?>"
                              ajax-action="<?php echo esc_attr($wilcityArgs['ajaxAction']); ?>"
                              tab-key="<?php echo esc_attr($wilcityTabKey); ?>"
                              :settings="settings"></wil-single-nav-posts>
    </template>
</wil-single-nav-wrapper>
