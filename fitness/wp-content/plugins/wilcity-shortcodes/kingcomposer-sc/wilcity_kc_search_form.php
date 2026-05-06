<?php
/** @var WilcityShortcodeRepository $wilcityKcTemplateRepository */
global $wilcityKcTemplateRepository;
$scFile = str_replace(['wilcity_kc_', '_', '.php'], ['', '-', ''], basename(__FILE__));

$atts = shortcode_atts(
  $wilcityKcTemplateRepository->get($scFile),
  $atts
);

wilcity_sc_render_hero_search_form($atts);
