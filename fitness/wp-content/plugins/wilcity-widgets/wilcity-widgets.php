<?php
/*
 * Plugin Name: Wilcity Widgets
 * Plugin URI: https://wiloke.com
 * Author: Wiloke
 * Author URI: https://wiloke.com
 * Version: 1.2.4
 * Description: This tool allows customizing your Add Listing page
 * Text Domain: wilcity-widgets
 * Domain Path: /languages/
 */

define('WILCITY_WIDGET', '(Wilcity)');

add_action('wiloke-listing-tools/run-extension', function () {
    require_once plugin_dir_path(__FILE__)."vendor/autoload.php";
    require_once plugin_dir_path(__FILE__).'wilcity-register-widgets.php';
    require_once plugin_dir_path(__FILE__).'mailchimp/func.mailchimp.php';
    require_once plugin_dir_path(__FILE__).'instagram/func.instagram-settings.php';
    add_action('init', function () {
        require_once plugin_dir_path(__FILE__) . 'instagram/func.instagram-authorize.php';
    });
});
