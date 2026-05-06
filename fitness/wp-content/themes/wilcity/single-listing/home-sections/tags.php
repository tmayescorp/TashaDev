<?php

use \WilokeListingTools\Framework\Helpers\GetSettings;

global $post, $wilcityArgs, $wilcityTabKey;

if (isset($wilcityArgs['maximumItemsOnHome']) && !empty($wilcityArgs['maximumItemsOnHome'])) {
    $aTags = GetSettings::getPostTerms($post->ID, 'listing_tag', [
        'number' => $wilcityArgs['maximumItemsOnHome']
    ]);
} else {
    $aTags = GetSettings::getPostTerms($post->ID, 'listing_tag');
}

$url = get_permalink($post->ID);

if ($aTags) :
    $wilcityTabKey = 'tags';
    ?>
    <div class="<?php echo esc_attr(apply_filters('wilcity/filter/class-prefix',
        'content-box_module__333d9 wilcity-single-listing-tag-box')); ?>">
        <?php get_template_part('single-listing/home-sections/section-heading'); ?>
        <div class="content-box_body__3tSRB">
            <div class="row">
                <?php
                $index = 0;
                foreach ($aTags as $oTag) :
                    if (!empty($oTag) && !is_wp_error($oTag)) : ?>
                        <div class="col-sm-4">
                            <div class="icon-box-1_module__uyg5F three-text-ellipsis mt-20 mt-sm-15">
                                <div class="icon-box-1_block1__bJ25J">
                                    <?php echo WilokeHelpers::getTermIcon($oTag,
                                        'icon-box-1_icon__3V5c0 rounded-circle', true, ['type' => $post->post_type]); ?>
                                </div>
                            </div>
                        </div>
                        <?php
                        $index++;
                    endif;
                endforeach;
                ?>
            </div>
        </div>
        <?php get_template_part('single-listing/home-sections/footer-seeall'); ?>
    </div>
<?php endif; ?>
