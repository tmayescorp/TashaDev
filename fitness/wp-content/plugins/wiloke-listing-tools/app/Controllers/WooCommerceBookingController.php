<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Frontend\User;

class WooCommerceBookingController extends Controller
{
    public function __construct()
    {
        add_action('wp_ajax_wilcity_fetch_my_room', [$this, 'fetchMyRoom']);
        add_action('wp_print_scripts', [$this, 'removeScripts'], 999);
    }

    public function removeScripts()
    {
        if (!function_exists('wilcityIsNoMapTemplate')) {
            return false;
        }
        // Removing jquery smooth on Search Page to resolve conflict Event Calendar style issue
        if (wilcityIsNoMapTemplate() || is_tax() || wilcityIsGoogleMap()) {
            wp_dequeue_script('jquery-ui-style');
        }
    }

    public static function getProductsByUserID($userID, $s = '')
    {
        $aArgs = [
          'post_type'      => 'product',
          's'              => $s,
          'posts_per_page' => 20,
          'post_status'    => ['publish', 'pending'],
          'author'         => $userID,
          'tax_query'      => [
            [
              'taxonomy' => 'product_type',
              'field'    => 'slug',
              'terms'    => ['booking', 'accommodation-booking']
            ]
          ]
        ];
        $oUserData = get_userdata($userID);
        if (in_array('administrator', $oUserData->roles)) {
            unset($aArgs['author']);
        }

        if (empty($s)) {
            unset($aArgs['s']);
        }

	    $query = new \WP_Query(
	    	apply_filters('wilcity/filter/wiloke-listing-tools/app/Controllers/WooCommerceBookingController/getProductsByUserID',
		    $aArgs)
	    );
        $aOptions = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $aOptions[] = General::buildSelect2OptionForm($query->post);
            }
        }

        return $aOptions;
    }

    public function fetchMyRoom()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $s = '';
        if (isset($_GET['search'])) {
            $s = $_GET['search'];
        } else if (isset($_GET['q'])) {
            $s = $_GET['q'];
        }

        $aOptions = self::getProductsByUserID(get_current_user_id(), $s);
        if (empty($aOptions)) {
            $oRetrieve->error(['msg' => esc_html__('We found no rooms', 'wiloke-listing-tools')]);
        }

        if (isset($_GET['mode']) && $_GET['mode'] == 'select') {
            $oRetrieve->success([
              'results' => $aOptions
            ]);
        };

        $oRetrieve->success(
          [
            'msg' => [
              'results' => $aOptions
            ]
          ]
        );
    }
}
