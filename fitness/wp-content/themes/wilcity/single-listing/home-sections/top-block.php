<?php global $post;
if (!is_user_logged_in() || $post->post_author != get_current_user_id()) {
    return '';
}
?>
<wil-lazy-load-component id="wil-listing-top-block" class="mb-20" height="1px;">
    <template v-slot:default="{isInView}">
        <wil-boxes-color-items v-if="isInView" wrapper-classes="row"
                               :boxes="data.highlightBoxes.items"
                               :inner-classes="data.highlightBoxes.itemsPerRow"
                               :post-id="<?php echo abs($post->ID); ?>"></wil-boxes-color-items>
    </template>
</wil-lazy-load-component>
