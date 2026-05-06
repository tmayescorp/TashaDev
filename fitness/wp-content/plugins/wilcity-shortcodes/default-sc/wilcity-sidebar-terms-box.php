<?php

use WILCITY_SC\SCHelpers;
use \WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\TermSetting;

add_shortcode('wilcity_sidebar_terms_box', 'wilcitySidebarTermsBox');

function wilcitySidebarTermsBox($aArgs)
{
    $aArgs['atts'] = SCHelpers::decodeAtts($aArgs['atts']);

    $aArgs = shortcode_atts(
      [
        'name'            => isset($aArgs['name']) ? $aArgs['name'] : $aArgs['atts']['name'],
        'atts'            => [],
        'wrapper_classes' => 'col-sm-6 col-sm-6-clear'
      ],
      $aArgs
    );

    $aAtts = wp_parse_args(
      $aArgs['atts'],
      [
        'name'               => '',
        'icon'               => 'la la-sitemap',
        'taxonomy'           => 'listing_cat',
        'postID'             => '',
        'taxonomy_post_type' => 'flexible'
      ]
    );

    $aTerms = wp_get_post_terms($aAtts['postID'], $aAtts['taxonomy']);
    if (empty($aTerms) || is_wp_error($aTerms)) {
        return '';
    }

    if (isset($aAtts['isMobile'])) {
        return apply_filters('wilcity/mobile/sidebar/terms_box', $aTerms, $aAtts);
    }

    $wrapperClass = 'content-box_module__333d9 wilcity-sidebar-item-term-box wilcity-sidebar-item-'.$aAtts['taxonomy'];
    ob_start();
    ?>
    <div class="<?php echo esc_attr(apply_filters('wilcity/filter/class-prefix', $wrapperClass)); ?>">
        <?php wilcityRenderSidebarHeader($aArgs['name'], $aAtts['icon']); ?>
        <div class="content-box_body__3tSRB">
            <div class="row">
                <?php
                $aQueryArgs = [];
                if (TermSetting::isTermRedirectToSearch()) {
                    $aQueryArgs = [
                      'postType' => get_post_type(get_the_ID())
                    ];
                }
                foreach ($aTerms as $oTerm) :
                    if (empty($oTerm) || is_wp_error($oTerm)) {
                        continue;
                    }
                    ?>
                    <div class="<?php echo esc_attr($aArgs['wrapper_classes']); ?>">
                        <div class="icon-box-1_module__uyg5F two-text-ellipsis mt-20 mt-sm-15">
                            <div class="icon-box-1_block1__bJ25J">
                                <?php echo WilokeHelpers::getTermIcon(
                                  $oTerm,
                                  'icon-box-1_icon__3V5c0 rounded-circle',
                                  true,
                                  $aQueryArgs
                                ); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
}
