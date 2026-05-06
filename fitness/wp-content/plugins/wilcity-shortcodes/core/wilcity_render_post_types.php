<?php

use WilokeListingTools\Framework\Helpers\TermSetting;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\QueryHelper;
use WILCITY_SC\ParseShortcodeAtts\ParseShortcodeAtts;

function wilcity_render_post_types($atts)
{
    $oParseAtts = new ParseShortcodeAtts($atts);
    if (empty($atts['post_types'])) {
        if (is_tax()) {
            $aPostTypes = TermSetting::getTermPostTypeKeys(
              get_queried_object()->term_id
            );
        } else {
            $aPostTypes = General::getPostTypeKeys(false, false);
        }
    } else {
        $aPostTypes = $oParseAtts->toArray('post_types');
    }
    $atts = $oParseAtts->parse();

    $wrapperClasses = implode(' ', apply_filters('wilcity-el-class', $atts));

    $columnClasses = $atts['maximum_posts_on_lg_screen'].' '.$atts['maximum_posts_on_md_screen'].' '.
                     $atts['maximum_posts_on_sm_screen'].' col-xs-6';

    ?>
    <div id="<?php echo esc_attr($atts['wrapper_id']); ?>" class="<?php echo esc_attr($wrapperClasses); ?>">
        <?php
        if (!empty($atts['heading']) || !empty($atts['desc'])) {
            wilcity_render_heading([
              'TYPE'            => 'HEADING',
              'blur_mark'       => '',
              'blur_mark_color' => '',
              'heading'         => $atts['heading'],
              'heading_color'   => $atts['heading_color'],
              'desc'            => $atts['desc'],
              'desc_color'      => $atts['desc_color'],
              'alignment'       => $atts['header_desc_text_align'],
              'extra_class'     => ''
            ]);
        }
        ?>
        <div class="row wil-flex-wrap" data-col-xs-gap="20" data-col-sm-gap="20" data-col-md-gap="20">
            <?php foreach ($aPostTypes as $postType) :
                $aPostTypeSettings = General::getPostTypeSettings($postType);
                if (empty($aPostTypeSettings)) {
                    continue;
                }
                ?>
                <div class="<?php echo esc_attr($columnClasses); ?>">
                    <?php
                    $aQueryArgs = [
                      'postType' => $postType
                    ];

                    if (is_tax()) {
                        $aQueryArgs[get_queried_object()->taxonomy] = get_queried_object()->term_id;
                    }

                    $bgUrl = '';
                    if (isset($aPostTypeSettings['bgImg'])) {
                        if (isset($aPostTypeSettings['bgImg']['id'])) {
                            $bgUrl = wp_get_attachment_image_url($aPostTypeSettings['bgImg']['id'], $atts['image_size']);
                        }

                        if (empty($bgUrl) && isset($aPostTypeSettings['bgImg']['url'])) {
                            $bgUrl = $aPostTypeSettings['bgImg']['url'];
                        }


                    }

                    wilcityRenderImageBoxSC([
                      'link'    => QueryHelper::buildSearchPageURL($aQueryArgs),
                      'bg_img'  => $bgUrl,
                      'heading' => $aPostTypeSettings['name'],
                      'desc'    => $aPostTypeSettings['desc']
                    ]); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
