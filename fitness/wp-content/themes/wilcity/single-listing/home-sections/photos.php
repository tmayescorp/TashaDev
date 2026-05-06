<?php
global $post, $wilcityArgs, $wilcityTabKey;

use WilokeListingTools\Framework\Helpers\GetSettings;

$aRawGallery = GetSettings::getPostMeta($post->ID, 'gallery');
if (empty($aRawGallery)) {
    return '';
}
$wilcityTabKey  = 'photos';
$numberOfPhotos = isset($wilcityArgs['maximumItemsOnHome']) && !empty($wilcityArgs['maximumItemsOnHome']) ?
    $wilcityArgs['maximumItemsOnHome'] : 4;

?>
<!-- content-box_module__333d9 -->
<div class="content-box_module__333d9">
    <?php get_template_part('single-listing/home-sections/section-heading'); ?>
    <wil-lazy-load-component v-if="currentTab==='home'" id="wil-listing-gallery" height="100px;">
        <template v-slot:default="{isInView}">
            <wil-gallery wrapper-classes="content-box_body__3tSRB"
                         row-classes="row"
                         column-classes="<?php echo apply_filters('wilcity/filter/gallery-item-classes', 'col-xs-6 col-sm-3'); ?>"
                         :preview-size="data.gallery.previewSize"
                         :maximum-preview-items="<?php echo absint($numberOfPhotos); ?>"
                         :items="data.gallery.items"
                         :is-show-total="true"></wil-gallery>
        </template>
    </wil-lazy-load-component>
    <?php get_template_part('single-listing/home-sections/footer-seeall'); ?>
</div><!-- End / content-box_module__333d9 -->
