<?php

namespace WILCITY_ELEMENTOR\Registers;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

class ContactUs extends Widget_Base
{
	use Helpers;

	public function get_name()
	{
		return WILCITY_WHITE_LABEL . '-contact-us';
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
		return WILCITY_EL_PREFIX . 'Contact Us';
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

	/**
	 * Register the widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since  1.1.0
	 *
	 * @access protected
	 */
	protected function register_controls()
	{
		$this->start_controls_section(
			'grid_general_section',
			[
				'label' => 'General Settings',
			]
		);

		$this->add_control(
			'contact_info_heading',
			[
				'label'   => 'Contact Info Heading',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Contact Info'
			]
		);

		$oRepeater = new Repeater();
		$oRepeater->add_control(
			'icon',
			[
				'label' => 'Icon',
				'type'  => Controls_Manager::ICON
			],
		);
		$oRepeater->add_control(
			'info',
			[
				'label' => 'Info',
				'type'  => Controls_Manager::TEXTAREA
			],
		);
		$oRepeater->add_control(
			'link',
			[
				'label'       => 'Link',
				'description' => 'Enter in # if it is not a real link.',
				'type'        => Controls_Manager::TEXT
			],
		);
		$oRepeater->add_control(
			'type',
			[
				'label'   => 'Type',
				'default' => 'default',
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'default' => 'Default',
					'phone'   => 'Phone',
					'mail'    => 'mail'
				]
			],
		);

		$oRepeater->add_control(
			'target',
			[
				'label'   => 'Open Type',
				'type'    => Controls_Manager::SELECT,
				'default' => '_self',
				'options' => [
					'_self'  => 'Self page',
					'_blank' => 'New Window'
				]
			],
		);


		$this->add_control(
			'contact_info',
			[
				'label'  => 'Contact Info',
				'type'   => Controls_Manager::REPEATER,
				'fields' => $oRepeater->get_controls()
			]
		);

		$this->add_control(
			'contact_form_heading',
			[
				'label'   => 'Contact Form Heading',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Contact Us'
			]
		);

		$this->add_control(
			'contact_form_7',
			[
				'label'   => 'Contact Form 7',
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => $this->getPosts('wpcf7_contact_form')
			]
		);

		$this->add_control(
			'contact_form_shortcode',
			[
				'label'       => 'Contact Form Shortcode',
				'description' => 'If you are using another contact form plugin, please enter its own shortcode here.',
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'options'     => $this->getPosts('wpcf7_contact_form')
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since  1.1.0
	 *
	 * @access protected
	 */
	protected function render()
	{
		/** @var \WilcityShortcodeRepository $wilcityScAttsRepository */
		global $wilcityScAttsRepository;
		$atts = wp_parse_args(
			$this->get_settings(),
			$wilcityScAttsRepository->get($this->findScAttributesFileName(basename(__FILE__)))
		);

		wilcity_sc_render_contact_us($atts);
	}
}
