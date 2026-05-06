<?php
global $post, $wilcityArgs;

use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;

if (empty($post->post_content)) {
    return '';
}

?>
<div class="content-box_module__333d9 <?php echo esc_attr(apply_filters('wilcity/filter/class-prefix',
    'wilcity-single-listing-content-box')); ?>">
    <?php get_template_part('single-listing/home-sections/section-heading'); ?>
    <div class="content-box_body__3tSRB">
        <div>
            <?php
            if (GetSettings::isEventExpired($post->ID) && General::isPostTypeInGroup($post->post_type, 'event')) {
                ?>
                <p style="text-align:center; color:red"><strong><?php esc_html_e('Warning, this event was expired!', 'wilcity'); ?></strong></p>
                <?php
            }
            ?>
            <?php the_content(); ?>
        </div>
    </div>
</div>
