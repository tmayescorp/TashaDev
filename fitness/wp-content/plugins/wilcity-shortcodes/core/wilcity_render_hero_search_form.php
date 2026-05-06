<?php

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\General;

function wilcity_sc_render_hero_search_form($aAtts)
{
	global $wilcityEnqueueHero;

	if (!$wilcityEnqueueHero) {
		wp_enqueue_script('HeroSearchForm');
		$wilcityEnqueueHero = true;
	}

	if (!is_array($aAtts['items'])) {
		return '';
	}
	$aTabs = [];

	$aPostTypeKeys = General::getPostTypeKeys(false, false);

	foreach ($aAtts['items'] as $index => $item) {
		$item = is_array($item) ? (object)$item : $item;
		if (in_array($item->post_type, $aPostTypeKeys)) {
			$item->icon = GetSettings::getPostTypeField('icon', $item->post_type);
			$aTabs[] = $item;
		}
	}
	$oFirstItem = reset($aAtts['items']);
	$oFirstItem = is_array($oFirstItem) ? (object)$oFirstItem : $oFirstItem;
	$wrapper_class = isset($aAtts['extra_class']) ? $aAtts['extra_class'] : '';
	?>
    <div id="<?php echo esc_attr(apply_filters('wilcity/filter/id-prefix', 'wilcity-hero-search-form')); ?>"
         class="tab_module__3fEXT wil-tab"
         data-layout="<?php echo esc_attr($aAtts['style'] ?? 'hero_formDark__3fCkB'); ?>"
         data-extra-classes="<?php echo esc_attr($wrapper_class); ?>"
         data-default-selected="hero-tab-<?php echo esc_attr($oFirstItem->post_type); ?>"
         data-search-url="<?php echo esc_url(get_permalink(WilokeThemeOptions::getOptionDetail('search_page'))) ?>"
         data-raw-tabs="<?php echo esc_attr(base64_encode(json_encode($aTabs))); ?>"
    >
		<?php
		if (class_exists('\Elementor\Plugin') && isset($_GET['action']) && $_GET['action'] == 'elementor') {
			?>
            <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../images/search-placeholder.png'); ?>"/>
			<?php
		}
		?></div>
	<?php
}
