<?php
/** @var WilcityShortcodeRepository $wilcityKcTemplateRepository */
global $wilcityKcTemplateRepository;
$scFile = str_replace(['wilcity_kc_', '_', '.php'], ['', '-', ''], basename(__FILE__));

$atts = shortcode_atts(
  $wilcityKcTemplateRepository->get($scFile),
  $atts
);

wilcity_render_slider($atts);
