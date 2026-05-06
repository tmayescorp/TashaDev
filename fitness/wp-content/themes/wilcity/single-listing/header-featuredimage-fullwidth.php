<?php
global $post, $wiloke;

use WilokeListingTools\Framework\Helpers\GetSettings;

$size          = apply_filters('wilcity/single-listing/image-cover-size', $wiloke->aThemeOptions['listing_featured_img_size']
    ?? 'large');
$coverImg      = GetSettings::getCoverImage($post->ID, $size);
?>
<header class="listing-detail_header__18Cfs">
    <div class="listing-detail_img__3DyYX pos-a-full bg-cover"
         style="background-image: url(<?php echo esc_url($coverImg); ?>);">
        <img src="<?php echo esc_url($coverImg); ?>" alt="<?php the_title(); ?>">
    </div>
    <?php if (isset($wiloke->aThemeOptions['listing_overlay_color']['rgba']) &&
              !empty($wiloke->aThemeOptions['listing_overlay_color']['rgba'])) : ?>
        <div class="wil-overlay"
             style="background-color: <?php echo esc_attr($wiloke->aThemeOptions['listing_overlay_color']['rgba']); ?>"></div>
    <?php endif; ?>
    <?php get_template_part('single-listing/view-gallery-btn'); ?>
</header>
