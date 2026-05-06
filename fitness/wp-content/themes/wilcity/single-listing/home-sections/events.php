<?php
global $post, $wilcityArgs, $wilcityTabKey, $event, $wilParentPost;

use WilokeListingTools\Framework\Helpers\General;

$oEventQuery = new WP_Query(
    [
        'post_type'                  => General::getPostTypeKeysGroup('event'),
        'posts_per_page'             => $wilcityArgs['maximumItemsOnHome'],
        'post_status'                => 'publish',
        'post_parent'                => $post->ID,
        'order'                      => 'ASC',
        'orderby'                    => 'wilcity_event_starts_on',
        'isFocusExcludeEventExpired' => true,
        'isEventOnListingPage'       => true
    ]
);
$wilParentPost = $post;

if ($oEventQuery->have_posts()) :
    $wilcityTabKey = 'events';
    ?>
    <div class="content-box_module__333d9 wilcity-single-listing-events-box">
        <?php get_template_part('single-listing/home-sections/section-heading'); ?>
        <div class="content-box_body__3tSRB">
            <div class="row" data-col-xs-gap="10">
                <?php
                while ($oEventQuery->have_posts()) {
                    $oEventQuery->the_post();
                    $event = $oEventQuery->post;
                    get_template_part('single-listing/partials/event');
                }
                ?>
            </div>
        </div>
        <?php get_template_part('single-listing/home-sections/footer-seeall'); ?>
    </div>
<?php
endif;
wp_reset_postdata();
