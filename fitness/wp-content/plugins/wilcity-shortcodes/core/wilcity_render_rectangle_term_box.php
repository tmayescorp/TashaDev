<?php

use WilokeListingTools\Framework\Helpers\GetSettings;
use WILCITY_SC\SCHelpers;
use Wilcity\Term\TermCount;
use WilokeListingTools\Framework\Helpers\TermSetting;
use WILCITY_SC\ParseShortcodeAtts\ParseShortcodeAtts;

function wilcity_render_rectangle_term_box($oTerm, $aAtts = [])
{
	$oParseAtts = new ParseShortcodeAtts($aAtts);

	if (!isset($aAtts['is_external_term'])) {
		$totalChildren = $oParseAtts->countPostsInTerm($oTerm);
		$link = $oParseAtts->parseTermLink($oTerm);
		$featuredImg = GetSettings::getTermFeaturedImg($oTerm, $aAtts['image_size']);
	} else {
		$link = $oTerm->link;
		$totalChildren = $oTerm->count;
		$featuredImg = $oTerm->featuredImg['large'];
	}

	if ($totalChildren === 0) {
		$i18 = isset($aAtts['singular_text']) ? $aAtts['singular_text'] : esc_html__('0 Listing', 'wilcity-shortcodes');
	} else {
		if (isset($aAtts['singular_text'])) {
			$i18 = sprintf(
				_n('%s ' . $aAtts['singular_text'], '%s ' . $aAtts['plural_text'], $totalChildren,
					'wilcity-shortcodes'),
				number_format_i18n($totalChildren)
			);
		} else {
			$i18 = sprintf(
				_n('%s Listing', '%s Listings', $totalChildren, 'wilcity-shortcodes'),
				number_format_i18n($totalChildren)
			);
		}
	}

	$target = isset($aAtts['target']) ? $aAtts['target'] : '_self';

	?>
    <div class="<?php echo esc_attr($aAtts['column_classes']); ?>">
        <div class="textbox-4_module__2gJjK">
            <a href="<?php echo esc_url($link); ?>"
               target="<?php echo esc_attr($target); ?>">
                <div class="textbox-4_background__3bSqa">
                    <div class="wil-overlay"></div>
					<?php SCHelpers::renderLazyLoad($featuredImg, [
							'divClass' => 'textbox-4_img__2_DKb bg-cover'
						]
					); ?>
                </div>
                <div class="textbox-4_top__1919H">
                    <i class="la la-edit color-primary"></i> <?php echo esc_html($i18); ?>
                </div>
                <div class="textbox-4_content__1B-wJ">
                    <h3 class="textbox-4_title__pVQr7"><?php echo esc_html($oTerm->name); ?></h3>
                    <span class="wil-btn wil-btn--primary wil-btn--block wil-btn--lg wil-btn--round">
                        <?php esc_html_e('Discover', 'wilcity-shortcodes'); ?>
                    </span>
                </div>
            </a>
        </div>
    </div>
	<?php
}
