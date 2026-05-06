<?php

use WilokeListingTools\Framework\Helpers\GetSettings;
use WILCITY_SC\ParseShortcodeAtts\ParseShortcodeAtts;

function wilcity_sc_render_modern_term_boxes($atts)
{
    $oParseSC = new ParseShortcodeAtts($atts);
    $atts     = $oParseSC->parse();
    $aTerms   = get_terms($oParseSC->buildTermQueryArgs());
    
    if (empty($aTerms) || is_wp_error($aTerms)) {
        return '';
    }
    
    if ($atts['isApp']) {
        $aResponse = [];
        foreach ($aTerms as $oTerm) {
            $aPostFeaturedImgs = GetSettings::getPostFeaturedImgsByTerm($oTerm->term_id, $atts['taxonomy']);
            
            $aResponse[] = [
              'oTerm'            => $oTerm,
              'aPostFeaturedImg' => $aPostFeaturedImgs,
              'oCount'           => [
                'number' => $oTerm->count,
                'text'   => $oTerm->count > 1 ? esc_html__('Listings', 'wilcity-shortcodes') :
                  esc_html__('Listing', 'wilcity-shortcodes')
              ],
              'oIcon'            => WilokeHelpers::getTermOriginalIcon($oTerm)
            ];
        }
        
        echo '%SC%'.json_encode([
            'oSettings' => $atts,
            'oResults'  => $aResponse,
            'TYPE'      => $atts['TYPE']
          ]).'%SC%';
        
        return '';
    }
    
    $wrapper_class = apply_filters('wilcity-el-class', $atts);
    $wrapper_class = implode(' ', $wrapper_class).'  '.$atts['extra_class'].' '.
                     apply_filters('wilcity/filter/class-prefix', 'wilcity-modern-term-boxes');
    ?>
    <div class="<?php echo esc_attr($wrapper_class); ?>">
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
        <div class="row" data-col-xs-gap="<?php echo esc_attr($atts['col_gap']); ?>"
             data-totals="<?php echo esc_attr(count($aTerms)); ?>">
            <?php
            foreach ($aTerms as $oTerm) {
                wilcity_render_modern_term_box($oTerm, $atts['taxonomy'], $atts);
            } ?>

        </div>
    </div>
    <?php
}
