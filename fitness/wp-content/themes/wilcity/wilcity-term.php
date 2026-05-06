<?php

use \WilokeListingTools\Framework\Helpers\GetSettings;
use \WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\TermSetting;

global $wilcityTaxonomyPageID, $wiloke;

$taxonomyType = WilokeThemeOptions::getOptionDetail('listing_taxonomy_page_type');

if ($taxonomyType == 'custom') {
    $wilcityTaxonomyPageID = WilokeThemeOptions::getOptionDetail(get_query_var('taxonomy') . '_page');
}

$searchPageID = WilokeThemeOptions::getOptionDetail('search_page');
$searchTemplate = "";
if (!empty($searchPageID)) {
    $searchTemplate = get_post_meta($searchPageID, '_wp_page_template', true);
    $searchTemplate = str_replace('.php', '', $searchTemplate);
}

if (empty($searchTemplate)) {
    $searchTemplate = 'templates/search-v2';
}

if ($taxonomyType == 'default') {
    $oParentTerm = get_queried_object();
    if (GetSettings::isTermParent($oParentTerm->term_id, $oParentTerm->taxonomy)) {
        get_header();
        $headerBg = GetSettings::getTermFeaturedImg($oParentTerm);
        $overColor = WilokeThemeOptions::getColor('listing_overlay_color');
        ?>
        <div class="wil-content">
            <div class="wil-section bg-cover" style="background-image: url(<?php echo esc_url($headerBg); ?>)">
                <div class="container">
                    <?php if (!empty($overColor)) : ?>
                        <div class="wil-overlay"
                             style="background-color: <?php echo esc_attr($overColor); ?>"></div>
                    <?php else: ?>
                        <div class="wil-overlay"></div>
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-12 ">
                            <div class="heading_module__156eJ light wil-text-center mb-0">
                                <h2 class="heading_title__1bzno"><?php echo esc_html($oParentTerm->name); ?></h2>
                                <?php if (!empty($oParentTerm->description)) : ?>
                                    <div class="heading_content__2mtYE">
                                        <?php Wiloke::ksesHTML($oParentTerm->description); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="wil-section pb-0">
                <div class="container">
                    <div class="row" data-col-xs-gap="20">

                        <?php
                        $aTerms = GetSettings::getTerms([
                            'taxonomy' => $oParentTerm->taxonomy,
                            'number'   => $wiloke->aThemeOptions['sub_taxonomies_maximum_can_be_shown'],
                            'orderby'  => $wiloke->aThemeOptions['sub_taxonomies_orderby'],
                            'order'    => $wiloke->aThemeOptions['sub_taxonomies_order'],
                            'parent'   => $oParentTerm->term_id
                        ]);
                        if (!empty($aTerms) && !is_wp_error($aTerms)) {
                            foreach ($aTerms as $oTerm) {
                                $hasTermChild = TermSetting::hasTermChildren(
                                    $oTerm->term_id,
                                    $oTerm->taxonomy
                                );

                                wilcity_render_modern_term_box(
                                    $oTerm,
                                    $oParentTerm->taxonomy,
                                    [
                                        'image_size'     => $wiloke->aThemeOptions['taxonomy_image_size'],
                                        'column_classes' => $wiloke->aThemeOptions['sub_taxonomies_columns'] .
                                            ' col-xs-6 col-sm-6',
                                        'post_type'      => $hasTermChild ? 'any' :
                                            TermSetting::getDefaultPostType($oTerm->term_id, $oTerm->taxonomy),
                                        'term_redirect'  => '_self'
                                    ]
                                );
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php
            if ($wiloke->aThemeOptions['sub_taxonomies_toggle_show_some_listings'] == 'enable') :
                $aBelongsTo = GetSettings::getTermMeta($oParentTerm->term_id, 'belongs_to');

                if (empty($aBelongsTo)) {
                    $aBelongsTo = General::getFirstPostTypeKey(false, false);
                }

                $query = new WP_Query(
                    [
                        'post_type'      => $aBelongsTo,
                        'post_status'    => 'publish',
                        'posts_per_page' => $wiloke->aThemeOptions['sub_taxonomies_maximum_listings_can_be_shown'],
                        'orderby'        => $wiloke->aThemeOptions['sub_taxonomies_maximum_listings_orderby'],
                        'order'          => $wiloke->aThemeOptions['sub_taxonomies_maximum_listings_order'],
                        'tax_query'      => [
                            [
                                'taxonomy' => $oParentTerm->taxonomy,
                                'field'    => 'term_id',
                                'terms'    => $oParentTerm->term_id
                            ]
                        ]
                    ]
                );
                if ($query->have_posts()) :
                    ?>
                    <div class="wil-section pt-0">
                        <div class="container">
                            <div class="row" data-col-xs-gap="20">
                                <?php if (!empty($wiloke->aThemeOptions['sub_taxonomies_listings_title'])) : ?>
                                    <div class="col-md-12 col-lg-12">
                                        <h2><?php Wiloke::ksesHTML($wiloke->aThemeOptions['sub_taxonomies_listings_title']); ?></h2>
                                    </div>
                                <?php endif; ?>
                                <?php
                                while ($query->have_posts()) : $query->the_post();

                                    $featuredImg = GetSettings::getFeaturedImg($query->post->ID, 'thumbnail');
                                    ?>
                                    <div
                                        class="<?php echo esc_attr($wiloke->aThemeOptions['sub_taxonomies_listings_columns']); ?>">
                                        <article
                                            class="listing_module__2EnGq wil-shadow listing_list2__2An8C js-listing-module">
                                            <div class="listing_firstWrap__36UOZ">
                                                <header class="listing_header__2pt4D">
                                                    <a href="<?php echo esc_url(get_permalink($query->post->ID)); ?>">
                                                        <div class="listing_img__3pwlB pos-a-full bg-cover"
                                                             style="background-image: url('<?php echo esc_url($featuredImg); ?>')">
                                                            <img src="<?php echo esc_url($featuredImg); ?>"
                                                                 alt="<?php echo esc_attr($query->post->post_title); ?>">
                                                        </div>
                                                    </a>
                                                </header>
                                                <div class="listing_body__31ndf">
                                                    <h2 class="listing_title__2920A text-ellipsis">
                                                        <a href="<?php echo esc_url(get_permalink($query->post->ID)); ?>"><?php echo get_the_title($query->post->ID); ?></a>
                                                    </h2>
                                                    <?php $tagLine = GetSettings::getTagLine($query->post, true); ?>
                                                    <?php if (!empty($tagLine)) : ?>
                                                        <div class="listing_tagline__1cOB3 text-ellipsis">
                                                            <p><?php Wiloke::ksesHTML($tagLine); ?></p></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </article>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                <?php endif;
                wp_reset_postdata(); ?>
                <div style="margin-bottom: 90px"></div>
            <?php endif; ?>
        </div>
        <?php
        do_action('wilcity/before-close-root');
        get_footer();
    } else {
        get_template_part($searchTemplate);
    }
} else {
    get_template_part('templates/taxonomy-template');
}
