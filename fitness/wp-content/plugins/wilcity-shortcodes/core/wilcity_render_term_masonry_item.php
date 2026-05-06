<?php

use WilokeListingTools\Framework\Helpers\GetSettings;
use WILCITY_SC\ParseShortcodeAtts\ParseShortcodeAtts;

function wilcity_render_term_masonry_item($oTerm, $aAtts)
{
    $termFeaturedImg = GetSettings::getTermFeaturedImg($oTerm, $aAtts['image_size']);
    $aGradients      = GetSettings::getTermGradients($oTerm);
    
    $oParseAtts    = new ParseShortcodeAtts($aAtts);
    $totalChildren = $oParseAtts->countPostsInTerm($oTerm);
    
    if ($totalChildren < 1) {
        $i18 = esc_html__('0 Listing', 'wilcity-shortcodes');
    } else {
        $i18 = sprintf(
          _n('%s Listing', '%s Listings', $totalChildren, 'wilcity-shortcodes'),
          $totalChildren
        );
    }

    ?>
    <div class="col-sm-4 col-md-4 col-lg-4 grid-item wil-term-masonry-<?php echo esc_attr($oTerm->term_id); ?>">
        <div class="textbox-5_module__2btEX">
            <a href="<?php echo $oParseAtts->parseTermLink($oTerm); ?>">
                <div class="textbox-5_background__1Spwa bg-cover"
                     style="background-image: url(<?php echo esc_url($termFeaturedImg); ?>)">
                    <img src="<?php echo esc_url($termFeaturedImg); ?>" alt="<?php echo esc_attr($oTerm->name); ?>"/>
                </div>
                <div class="textbox-5_content__1o8k9">
                    <h3 class="textbox-5_title__3ClXm"><?php echo esc_attr($oTerm->name); ?></h3>
                    <div class="textbox-5_description__1xY46"><?php echo $i18; ?></div>
                </div>
                <?php if (!empty($aGradients)): ?>
                    <div class="gradient-color"
                         style="background-image: linear-gradient(to top, <?php echo esc_attr($aGradients['left']); ?> 5%, <?php echo esc_attr($aGradients['right']); ?> 100%);"></div>
                <?php endif; ?>
            </a>
        </div>
    </div>
    <?php
}
