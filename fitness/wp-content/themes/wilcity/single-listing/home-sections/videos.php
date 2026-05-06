<?php
global $post, $wilcityArgs, $wilcityTabKey;

use WilokeListingTools\Framework\Helpers\GetSettings;

$aRawVideos = GetSettings::getPostMeta($post->ID, 'video_srcs');

if (empty($aRawVideos)) {
    return '';
}

$wilcityTabKey = 'videos';

?>
<div class="content-box_module__333d9">
    <?php get_template_part('single-listing/home-sections/section-heading'); ?>
    <div class="content-box_body__3tSRB">
        <wil-video-gallery v-if="currentTab==='home'" :is-on-home="true"
                           :items="data.videos.items"
                           :maximum-preview-items="getNavigationInfo('videos','maximumItemsOnHome', <?php echo esc_attr($wilcityArgs['maximumItemsOnHome']) ?>)"></wil-video-gallery>
    </div>
    <?php get_template_part('single-listing/home-sections/footer-seeall'); ?>
</div>
