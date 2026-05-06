<?php global $post, $wilcityArgs; ?>
<wil-single-nav-wrapper v-if="currentTab === '<?php echo esc_attr($wilcityArgs['key']); ?>'"
                        :settings="getNavigation('<?php echo esc_attr($wilcityArgs['key']); ?>', true)"
                        id="<?php echo esc_attr($wilcityArgs['key']); ?>">
    <template v-slot:default="{settings}">
        <wil-single-nav-custom-content :post-id="<?php echo abs($post->ID); ?>"
                                       tab-key="<?php echo $wilcityArgs['key'] ?>"></wil-single-nav-custom-content>
    </template>
</wil-single-nav-wrapper>
