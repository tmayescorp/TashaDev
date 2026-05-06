<?php

namespace WILCITY_ELEMENTOR\Registers;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

class Testimonials extends Widget_Base
{
    use Helpers;

    public function get_name()
    {
        return WILCITY_WHITE_LABEL.'-testimonials1';
    }

    /**
     * Retrieve the widget title.
     *
     * @return string Widget title.
     * @since  1.1.0
     *
     * @access public
     *
     */
    public function get_title()
    {
        return WILCITY_EL_PREFIX.'Testimonials';
    }

    /**
     * Retrieve the widget icon.
     *
     * @return string Widget icon.
     * @since  1.1.0
     *
     * @access public
     *
     */
    public function get_icon()
    {
        return 'fa fa-picture-o';
    }

    /**
     * Retrieve the list of categories the widget belongs to.
     *
     * Used to determine where to display the widget in the editor.
     *
     * Note that currently Elementor supports only one category.
     * When multiple categories passed, Elementor uses the first one.
     *
     * @return array Widget categories.
     * @since  1.1.0
     *
     * @access public
     *
     */
    public function get_categories()
    {
        return ['theme-elements'];
    }

    protected function register_controls()
    {
        /** @var \WilcityShortcodeRepository $wilcityScRepository */
        global $wilcityScRepository;

        $this->convertKCToEl(
          $wilcityScRepository->get('wilcity_kc_testimonials:wilcity_kc_testimonials', true)
                              ->sub('params')
        )->registerShortcode()
        ;
    }

    protected function render()
    {
        $aSettings = $this->get_settings();

        if (!empty($aSettings)) {
            $aTestimonials = [];
            foreach ($aSettings['testimonials'] as $aTestimonial) {
                $aTestimonials[] = [
                  'name'        => $aTestimonial['name'],
                  'testimonial' => $aTestimonial['testimonial'],
                  'profesional' => $aTestimonial['profesional'],
                  'avatar'      => isset($aTestimonial['avatar']['url']) ? $aTestimonial['avatar']['url'] : ''
                ];
            }

            unset($aSettings['testimonials']);
            $aSettings['testimonials'] = (object)$aTestimonials;
        }

        /** @var \WilcityShortcodeRepository $wilcityScAttsRepository */
        global $wilcityScAttsRepository;
        $aSettings = wp_parse_args(
          $aSettings,
          $wilcityScAttsRepository->get($this->findScAttributesFileName(basename(__FILE__)))
        );

        wilcity_sc_render_testimonials($aSettings);
    }
}
