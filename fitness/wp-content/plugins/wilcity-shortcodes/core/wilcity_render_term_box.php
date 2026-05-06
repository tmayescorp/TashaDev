<?php

use WilokeListingTools\Framework\Helpers\GetSettings;
use WILCITY_SC\SCHelpers;
use WILCITY_SC\ParseShortcodeAtts\ParseShortcodeAtts;

function wilcity_render_term_box($oTerm, $aAtts = [])
{
    $aTermIcon     = WilokeHelpers::getTermOriginalIcon($oTerm);
    $innerClass    = $aAtts['toggle_box_gradient'] == 'enable' ? 'textbox-1_style2__cPkly textbox-1_module__bn5-O' :
      'textbox-1_module__bn5-O bg-color-primary--hover';
    $featuredImg   = GetSettings::getTermFeaturedImg($oTerm, $aAtts['image_size']);
    $oParseAtts    = new ParseShortcodeAtts($aAtts);
    $totalChildren = $oParseAtts->countPostsInTerm($oTerm);
    if ($totalChildren < 1) {
        $i18 = esc_html__('0 Listing', 'wilcity-shortcodes');
    } else {
        $i18 = sprintf(
          _n('%d Listing', '%d Listings', $totalChildren, 'wilcity-shortcodes'),
          $totalChildren
        );
    }

    ?>
    <div class="<?php echo esc_attr($aAtts['column_classes']); ?>">
        <div class="<?php echo esc_attr($innerClass); ?>">
            <?php
            if ($aAtts['toggle_box_gradient'] == 'enable'):
                $leftBg = GetSettings::getTermMeta($oTerm->term_id, 'left_gradient_bg');
                $rightBg = GetSettings::getTermMeta($oTerm->term_id, 'right_gradient_bg');
                $tiltedDegrees = GetSettings::getTermMeta($oTerm->term_id, 'gradient_tilted_degrees');

                $leftBg        = empty($leftBg) ? '#006bf7' : $leftBg;
                $rightBg       = empty($rightBg) ? '#f06292' : $rightBg;
                $tiltedDegrees = empty($tiltedDegrees) ? -10 : $tiltedDegrees;
                ?>
                <div class="wil-overlay"
                     style="background-image: linear-gradient(<?php echo esc_attr($tiltedDegrees); ?>deg, <?php echo esc_attr($leftBg) ?> 0%, <?php echo esc_attr($rightBg) ?> 100%)"></div>
                <a href="<?php echo esc_url($oParseAtts->parseTermLink($oTerm)); ?>"
                   class="bg-cover"
                   style="background-image: url(<?php echo esc_url($featuredImg); ?>)">
                    <?php if (empty($aTermIcon)) : ?>
                        <div class="textbox-1_icon__3wBDQ" style="color: #e45b5b"><i class="la la-heart-o"></i></div>
                    <?php else: ?>
                        <?php if ($aTermIcon['type'] == 'image') : ?>
                            <div class="textbox-1_icon__3wBDQ wil-termbox-icon">
                                <img src="<?php echo esc_url($aTermIcon['url']); ?>"
                                     alt="<?php echo esc_attr($oTerm->name); ?>"></div>
                        <?php else: ?>
                            <div class="textbox-1_icon__3wBDQ wil-termbox-icon"
                                 style="color: <?php echo esc_attr($aTermIcon['color']); ?>;">
                                <i class="<?php echo esc_attr($aTermIcon['icon']); ?>"></i></div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div class="textbox-1_content__3IRq1 wil-termbox-info">
                        <span class="textbox-1_text__5g4er wil-termbox-name"><?php echo esc_html($oTerm->name);
                        ?></span>
                        <h3 class="textbox-1_title__Tf1Gy wil-termbox-counter"><?php echo esc_html($i18);
                        ?></h3>
                        <span class="textbox-1_arrow__38itC wil-termbox-counter-icon"><i class="la
                        la-long-arrow-right"></i></span>
                    </div>
                </a>
            <?php else: ?>
                <a href="<?php echo esc_url(SCHelpers::getTermLink($aAtts, $oTerm)); ?>">
                    <div class="textbox-1_icon__3wBDQ bg-cover"
                         style="background-image: url('<?php echo esc_url($featuredImg); ?>')">
                        <div class="wil-overlay"></div>
                        <?php if ($aTermIcon['type'] == 'image') : ?>
                            <img src="<?php echo esc_url($aTermIcon['url']); ?>"
                                 alt="<?php echo esc_attr($oTerm->name); ?>">
                        <?php else: ?>
                            <i class="<?php echo esc_attr($aTermIcon['icon']); ?>"></i>
                        <?php endif; ?>
                    </div>
                    <div class="textbox-1_content__3IRq1 wil-termbox-info">
                        <span class="textbox-1_text__5g4er wil-termbox-name"><?php echo esc_html($oTerm->name);
                        ?></span>
                        <h3 class="textbox-1_title__Tf1Gy wil-termbox-counter"><?php echo esc_html($i18); ?></h3>
                        <span class="textbox-1_arrow__38itC wil-termbox-counter-icon"><i class="la la-long-arrow-right"></i></span>
                    </div>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
