<?php
/*
 * Plugin Name: Wilcity Elementor Addon
 * Plugin URI: https://wilcity.com
 * Author: Wiloke
 * Author URI: https://wiloke.com
 * Version: 1.4.3
 */


use WILCITY_ELEMENTOR\Registers\Init;

add_action('wiloke-listing-tools/run-extension', function () {
    if (!function_exists('wilcityShortcodesRepository')) {
        return false;
    }

    define('WILCITY_EL_PREFIX', 'WILCITY ');
    define('WILCITY_EL_VERSION', '1.4.3');
    define('WILCITY_EL_SOURCE_URL', plugin_dir_url(__FILE__) . 'source/');
    require_once plugin_dir_path(__FILE__).'vendor/autoload.php';

    /** @var WilcityShortcodeRepository $wilcityScRepository */
    /** @var WilcityShortcodeRepository $wilcityScAttsRepository */
    global $wilcityScRepository, $wilcityScAttsRepository;

    $wilcityScRepository     = wilcityShortcodesRepository(WILCITY_SC_DIR.'configs/sc/');
    $wilcityScAttsRepository = wilcityShortcodesRepository(WILCITY_SC_DIR.'configs/sc-attributes/');

//	add_action('wp_enqueue_scripts', function () {
//		wp_enqueue_style('elementor-fe', plugin_dir_url(__FILE__) . 'source/css/style.css', false, WILCITY_EL_VERSION);
//	});

    new Init();
}, 15);
