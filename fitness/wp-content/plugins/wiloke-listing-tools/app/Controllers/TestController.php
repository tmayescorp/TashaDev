<?php


namespace WilokeListingTools\Controllers;


class TestController
{
    public function __construct() {
        add_action('wp_ajax_test_cai', [$this, 'test']);
    }

    public function test() {
        if ($_REQUEST['userid'] == 1) {
            wp_send_json_success();
        }

        wp_send_json_error();
    }
}
