<?php

namespace WILCITY_ELEMENTOR\Registers;

use Elementor\Plugin;

class Init
{
	public function __construct()
	{
		add_action('elementor/editor/before_enqueue_scripts', [$this, 'adminScripts']);
		add_action('elementor/widgets/register', [$this, 'registerWidgets']);
		add_action('elementor/controls/register', [$this, 'registerSelectTwoAjaxControl']);
	}

	public function registerSelectTwoAjaxControl()
	{
		$controls_manager = Plugin::$instance->controls_manager;
		$controls_manager->register(new SelectTwoAjaxControl(), 'wil_select2_ajax');
	}

	public function adminScripts()
	{
		if (isset($_GET['action']) && $_GET['action'] === 'elementor') {
			wp_enqueue_style('wilcity-elementor-style', WILCITY_EL_SOURCE_URL . 'css/elementor-style.css', [],
				WILCITY_EL_VERSION);
		}
	}

	/**
	 * Register Widget
	 *
	 * @since  1.0.0
	 *
	 * @access private
	 */
	public function registerWidgets()
	{
		Plugin::instance()->widgets_manager->register(new PostTypes());
		Plugin::instance()->widgets_manager->register(new Heading());
		Plugin::instance()->widgets_manager->register(new SearchForm());
		Plugin::instance()->widgets_manager->register(new Hero());
		Plugin::instance()->widgets_manager->register(new Grid());
		Plugin::instance()->widgets_manager->register(new NewGrid());
		Plugin::instance()->widgets_manager->register(new EventsGrid());
		Plugin::instance()->widgets_manager->register(new RestaurantListings());
		Plugin::instance()->widgets_manager->register(new TermBoxes());
		Plugin::instance()->widgets_manager->register(new RectangleTermBoxes());
		Plugin::instance()->widgets_manager->register(new ModernTermBoxes());
		Plugin::instance()->widgets_manager->register(new MasonryTermBoxes());
		Plugin::instance()->widgets_manager->register(new ListingsSlider());
		Plugin::instance()->widgets_manager->register(new EventsSlider());
		Plugin::instance()->widgets_manager->register(new Pricing());
		Plugin::instance()->widgets_manager->register(new BoxIcon());
		Plugin::instance()->widgets_manager->register(new Testimonials());
		Plugin::instance()->widgets_manager->register(new Canvas());
		Plugin::instance()->widgets_manager->register(new ContactUs());
		Plugin::instance()->widgets_manager->register(new IntroBox());
		Plugin::instance()->widgets_manager->register(new TeamIntroSlider());
		Plugin::instance()->widgets_manager->register(new ListingTabs());
		Plugin::instance()->widgets_manager->register(new ListingsTabs());
		Plugin::instance()->widgets_manager->register(new CustomLogin());
		Plugin::instance()->widgets_manager->register(new AuthorSlider());
	}
}
