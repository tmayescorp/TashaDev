<?php
/**
 * kc_tab shortcode
 **/
global $wilcityHasTabContentActive, $wilTabId;

$tab_id = $title = '';

extract($atts);
preg_match('/(href=[\"\']#)([^"].+)([\"|\'])/', $adv_title, $aMatched);

if (isset($aMatched[2])) {
    $tab_id = $aMatched[2];
}

if (empty($tab_id)) {
    $tab_id = strtolower(trim($title));
    $tab_id = str_replace(array('&', 'amp;'), array('', ''), $tab_id);

    $tab_id = preg_replace_callback('/\s+/', function ($aMatched) {
        return '-';
    }, $tab_id);
}

$css_class = apply_filters('wilcity-el-class', $atts);

$css_class = array_merge($css_class, array('wilTab_panel__wznsS', 'wil-tab__panel'));

if (empty($wilcityHasTabContentActive)) {
    $wilcityHasTabContentActive = 'yes';
    $css_class[] = 'active';
}

if (isset($class)) {
    array_push($css_class, $class);
}
$output = '<div id="' . $tab_id . '" class="' . esc_attr(implode(' ', $css_class)) . '">' .
    (('' === trim($content))
        ? __('Empty tab. Edit page to add content here.', 'wiloke-listing-tools')
        : do_shortcode(str_replace('kc_tab#', 'kc_tab', $content))) .
    '</div>';

$wilTabId = '';
echo $output;