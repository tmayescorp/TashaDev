<?php

use WILCITY_SC\SCHelpers;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WILCITY_SC\ParseShortcodeAtts\ParseShortcodeAtts;
use WILCITY_APP\Helpers\AppHelpers;

function wilcity_sc_render_grid($atts)
{
    $oParseSC       = new ParseShortcodeAtts($atts);
    $atts           = $oParseSC->parse();
    $atts['border'] = $atts['border'] ?? '';
    $aArgs          = SCHelpers::parseArgs($atts);

    if ($atts['orderby'] !== 'nearbyme') {
        $query = new WP_Query($aArgs);
        if (!$query->have_posts()) {
            wp_reset_postdata();

            return '';
        }
    }

    $atts = SCHelpers::mergeIsAppRenderingAttr($atts);
    if (SCHelpers::isApp($atts)) {
        $aResponse = [];
        $oSkeleton = new AppHelpers();

        if ($atts['orderby'] !== 'nearbyme') {
            while ($query->have_posts()) {
                $query->the_post();
                $aResponse[] = $oSkeleton->listingSkeleton($query->post, ['oGallery', 'oSocialNetworks', 'oVideos']);
            }
            wp_reset_postdata();
        }

        echo '%SC%'.json_encode(
            [
              'oSettings' => $atts,
              'oResults'  => $aResponse,
              'TYPE'      => $atts['TYPE']
            ]
          ).'%SC%';

        return '';
    }

    $wrap_class = apply_filters('wilcity-el-class', $atts);

    $wrap_class = implode(' ', $wrap_class).'  '.$atts['extra_class'];
    $wrap_class .= apply_filters('wilcity/filter/class-prefix', ' wilcity-grid');

    if (wp_is_mobile() && isset($atts['mobile_img_size']) && !empty($atts['mobile_img_size'])) {
        $atts['img_size'] = $atts['mobile_img_size'];
    }

    if ($atts['orderby'] == 'nearbyme') {
        if (is_tax()) {
            $oQueriedObject = get_queried_object();
            $taxonomy       = $oQueriedObject->taxonomy;
            $termID         = $oQueriedObject->term_id;

            if ($atts['post_type'] == 'depends_on_belongs_to') {
                $aDirectoryTypes = GetSettings::getTermMeta($termID, 'belongs_to');
                if (empty($aDirectoryTypes)) {
                    $atts['post_type'] = GetSettings::getDefaultPostType(true);
                } else {
                    $atts['post_type'] = json_encode($aDirectoryTypes);
                }
            }

            if (!isset($atts[$taxonomy.'s']) || empty($atts[$taxonomy.'s'])) {
                $atts[$taxonomy.'s'] = $termID.':'.$oQueriedObject->name;
            }
        }
        wilcity_sc_render_new_grid($atts);
    } else {
        ?>
        <div id="<?php echo esc_attr($atts['wrapper_id']); ?>" class="<?php echo esc_attr($wrap_class); ?>">
            <?php
            if (!empty($atts['heading']) || !empty($atts['desc'])) {
                wilcity_render_heading([
                  'TYPE'              => 'HEADING',
                  'blur_mark'         => '',
                  'blur_mark_color'   => '',
                  'heading'           => $atts['heading'],
                  'heading_color'     => $atts['heading_color'],
                  'desc'              => $atts['desc'],
                  'description_color' => $atts['desc_color'],
                  'alignment'         => $atts['header_desc_text_align'],
                  'extra_class'       => ''
                ]);
            }
            ?>
            <?php if ($atts['toggle_viewmore'] == 'enable') : ?>
                <div class="<?php echo esc_attr(apply_filters('wilcity/filter/class-prefix',
                  'btn-view-all-wrap clearfix')); ?>">
                    <a class="<?php echo esc_attr(apply_filters('wilcity/filter/class-prefix',
                      'wil-view-all mb-15 btn-view-all wil-float-right')); ?>"
                       href="<?php echo SCHelpers::getViewAllUrl($atts); ?>"><?php echo esc_html($atts['viewmore_btn_name']); ?></a>
                </div>
            <?php endif; ?>

            <div class="row row-clearfix wil-flex-wrap">
                <?php
                do_action('wilcity/listing-grid/before-loop', $query, $atts);
                if ($query->have_posts()) {
                    $atts['item_class'] = 'mb-30';
                    while ($query->have_posts()) {
                        $query->the_post();
                        wilcity_render_grid_item($query->post, $atts);
                    }
                    wp_reset_postdata();
                }
                do_action('wilcity/listing-grid/after-loop', $query, $atts);
                ?>
            </div>
        </div>
        <?php
    }
}
