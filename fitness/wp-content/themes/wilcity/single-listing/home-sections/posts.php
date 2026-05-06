<?php
global $post, $wilcityArgs, $wilcityTabKey;

if (!function_exists('wilcity_render_grid_post')) {
    return '';
}

$aPostIDs = WilokeListingTools\Framework\Helpers\GetSettings::getPostsBelongToListing($post->ID);

if (empty($aPostIDs)) {
    return '';
}

$aArgs = [
    'post_type'      => 'post',
    'posts_per_page' => $wilcityArgs['maximumItemsOnHome'],
    'post_status'    => 'publish',
    'post__in'       => is_array($aPostIDs) ? array_map('intval', $aPostIDs) :
        array_map('intval', explode(',', $aPostIDs))
];

$oPosts = new WP_Query($aArgs);

if ($oPosts->have_posts()) :
    $wilcityTabKey = 'posts';
    ?>
    <div class="content-box_module__333d9 wilcity-single-listing-post-box">
        <?php get_template_part('single-listing/home-sections/section-heading'); ?>
        <div class="content-box_body__3tSRB">
            <div class="row" data-col-xs-gap="10">
                <?php
                while ($oPosts->have_posts()) {
                    $oPosts->the_post();
                    ?>
                    <div class="col-sm-6">
                        <?php wilcity_render_grid_post($oPosts->post); ?>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
        <?php get_template_part('single-listing/home-sections/footer-seeall'); ?>
    </div>
<?php
endif;
wp_reset_postdata();
