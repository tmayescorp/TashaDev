<?php

namespace WILCITY_ELEMENTOR\Registers;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;
use WILCITY_SC\SCHelpers;

class CustomLogin extends Widget_Base
{
    use Helpers;

    public function get_name()
    {
        return WILCITY_WHITE_LABEL.'-custom-login';
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
        return WILCITY_EL_PREFIX.'Custom Login';
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
          'custom_login_general_section',
          [
            'label' => 'General Settings',
          ]
        );

        $this->add_control(
          'login_section_title',
          [
            'label'   => 'Login Title',
            'type'    => Controls_Manager::TEXT,
            'default' => 'Welcome back, please login to your account'
          ]
        );

        $this->add_control(
          'register_section_title',
          [
            'label'   => 'Register Title',
            'type'    => Controls_Manager::TEXT,
            'default' => 'Create an account! It\'s free and always will be.'
          ]
        );

        $this->add_control(
          'rp_section_title',
          [
            'label'   => 'Reset Password Title',
            'type'    => Controls_Manager::TEXT,
            'default' => 'Find Your Account'
          ]
        );

        $this->add_control(
          'social_login_type',
          [
            'label'   => 'Social Login',
            'type'    => Controls_Manager::SELECT,
            'options' => [
              'fb_default'       => 'Using Facebook Login as Default',
              'custom_shortcode' => 'Inserting External Shortcode',
              'off'              => 'I do not want to use this feature'
            ],
            'default' => 'fb_default'
          ]
        );

        $this->add_control(
          'social_login_shortcode',
          [
            'label'     => 'Social Login Shortcode',
            'type'      => Controls_Manager::TEXT,
            'default'   => '',
            'condition' => [
              'social_login_type' => 'custom_shortcode'
            ]
          ]
        );

        $this->add_control(
          'extra_class',
          [
            'label' => 'Extra Class',
            'type'  => Controls_Manager::TEXT
          ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
          'intro_section',
          [
            'label' => 'Intro Section'
          ]
        );

        $this->add_control(
          'login_bg_img',
          [
            'label' => 'Background Image',
            'type'  => Controls_Manager::MEDIA
          ]
        );

        $this->add_control(
          'login_bg_color',
          [
            'label'   => 'Background Color',
            'type'    => Controls_Manager::COLOR,
            'default' => ''
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
		    'description',
		    [
			    'label' => 'Description',
			    'type'  => Controls_Manager::TEXTAREA
		    ],
	    );
	    $oRepeater->add_control(
		    'icon_color',
		    [
			    'label' => 'Icon Color',
			    'type'  => Controls_Manager::COLOR
		    ],
	    );
	    $oRepeater->add_control(
		    'text_color',
		    [
			    'label' => 'Text Color',
			    'type'  => Controls_Manager::COLOR
		    ],
	    );

        $this->add_control(
          'login_boxes',
          [
            'label'  => 'Intro Box',
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
        $aSettings = $this->get_settings();
        $aSettings = wp_parse_args(
          $aSettings,
          [
            'TYPE'                   => 'EXTERNAL_LOGIN',
            '_id'                    => '',
            'social_login_type'      => 'fb_default',
            'custom_shortcode'       => '',
            'login_section_title'    => '',
            'register_section_title' => '',
            'rp_section_title'       => '',
            'social_login_shortcode' => '',
            'login_bg_img'           => '',
            'login_bg_color'         => '',
            'login_boxes'            => '',
          ]
        );

        $imgURL = '';
        if (isset($aSettings['login_bg_img']['id'])) {
            $imgURL = wp_get_attachment_image_url($aSettings['login_bg_img']['id'], 'large');
        }
        if (empty($imgURL) && isset($aSettings['login_bg_img']['url'])) {
            $imgURL = $aSettings['login_bg_img']['url'];
        }
        $aSettings['login_bg_img'] = $imgURL;

        wilcity_render_custom_login_sc($aSettings);
    }
}
