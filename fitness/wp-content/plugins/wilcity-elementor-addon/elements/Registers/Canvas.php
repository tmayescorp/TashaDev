<?php

namespace WILCITY_ELEMENTOR\Registers;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;
use WILCITY_ELEMENTOR\Registers\Helpers;
use WilokeListingTools\Framework\Helpers\General;

class Canvas extends Widget_Base
{
	use Helpers;

	public function get_name()
	{
		return WILCITY_WHITE_LABEL . '-canvas';
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
		return WILCITY_EL_PREFIX . 'Wiloke Waves';
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
			'heading',
			[
				'label' => 'Heading',
				'type'  => Controls_Manager::TEXT
			]
		);

		$this->add_control(
			'description',
			[
				'label' => 'Description',
				'type'  => Controls_Manager::TEXTAREA
			]
		);

		$this->add_control(
			'left_gradient_color',
			[
				'label'   => 'Left Gradient',
				'type'    => Controls_Manager::COLOR,
				'default' => '#f06292'
			]
		);

		$this->add_control(
			'right_gradient_color',
			[
				'label'   => 'Right Gradient',
				'type'    => Controls_Manager::COLOR,
				'default' => '#f97f5f'
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
			'name',
			[
				'label' => 'Button Name',
				'type'  => Controls_Manager::TEXT
			],
		);
		$oRepeater->add_control(
			'url',
			[
				'label' => 'Button URL',
				'type'  => Controls_Manager::TEXT
			],
		);
		$oRepeater->add_control(
			'open_type',
			[
				'label'   => 'Open Type',
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'_self'  => 'In the same window',
					'_blank' => 'In a New Window'
				]
			],
		);


		$this->add_control(
			'btn_group',
			[
				'label'  => 'Button Group',
				'type'   => Controls_Manager::REPEATER,
				'fields' => $oRepeater->get_controls()
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
		$atts = wp_parse_args(
			$this->get_settings(),
			[
				'TYPE'                 => 'WAVE',
				'_id'                  => '',
				'heading'              => '',
				'description'          => '',
				'desc'                 => '',
				'btn_group'            => [],
				'left_gradient_color'  => '#f06292',
				'right_gradient_color' => '#f97f5f',
				'extra_class'          => ''
			]
		);
		wilcity_render_wiloke_wave($atts);
	}
}
