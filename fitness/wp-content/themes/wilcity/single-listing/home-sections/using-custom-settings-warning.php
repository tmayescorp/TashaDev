<?php
global $post;
if ($post->post_author != get_current_user_id()) {
    return '';
}
?>
<wil-alert v-if="usingCustomListingSettingsWarning.length"
           :msg="''|filterTranslation(usingCustomListingSettingsWarning)"
           type="warning"
           wrapper-classes="content-box_module__333d9">
    <template v-slot:after-msg>
        <wil-switch-tab-btn page-url="<?php echo esc_url(get_permalink($post->ID)); ?>"
                            tab-key="listing-settings"
                            wrapper-classes="color-primary"
                            btn-name="<?php esc_html_e('Listing Settings', 'wilcity'); ?>"
        ></wil-switch-tab-btn>
    </template>
</wil-alert>
