<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Controllers\Retrieve\RestRetrieve;
use WilokeListingTools\Framework\Helpers\TermSetting;
use WilokeListingTools\Framework\Routing\Controller;

class IconController extends Controller
{
    public function __construct()
    {
        add_action('rest_api_init', function () {
            register_rest_route(WILOKE_PREFIX . '/v2', '/icons', [
                'methods'             => 'GET',
                'callback'            => [$this, 'getIcons'],
                'permission_callback' => '__return_true'
            ]);
        });

        add_filter('wiloke-listing-tools/map-icon-url-default', [$this, 'setDefaultTermIcon'], 10, 2);
    }

    public function setDefaultTermIcon($iconImg, $oTerm)
    {
        $aIcon = \WilokeThemeOptions::getOptionDetail($oTerm->taxonomy . '_icon_image');

        if (is_array($aIcon) && isset($aIcon['url']) && !empty($aIcon['url'])) {
            return $aIcon['url'];
        }

        return $iconImg;
    }

    public function getIcons(\WP_REST_Request $oRequest)
    {
        $oRetreive = new RetrieveController(new RestRetrieve());

        $aIcons = [
            [
                'icon'  => 'la la-minus-square',
                'id'    => 'la la-minus-square',
                'name'  => 'Square',
                'label' => 'Square'
            ],
            [
                'icon'  => 'la la-calendar',
                'id'    => 'la la-calendar',
                'name'  => 'Calendar',
                'label' => 'Calendar'
            ],
            [
                'icon'  => 'la la-envelope-o',
                'id'    => 'la la-envelope-o',
                'name'  => 'Envelope',
                'label' => 'Envelope'
            ],
            [
                'icon'  => 'la la-phone',
                'id'    => 'la la-phone',
                'name'  => 'Phone',
                'label' => 'Phone'
            ],
            [
                'icon'  => 'la la-home',
                'id'    => 'la la-home',
                'name'  => 'Home',
                'label' => 'Home'
            ],
            [
                'icon'  => 'la la-hotel',
                'id'    => 'la la-hotel',
                'name'  => 'Hotel',
                'label' => 'Hotel'
            ],
            [
                'icon'  => 'la la-link',
                'id'    => 'la la-link',
                'name'  => 'Link',
                'label' => 'Link'
            ],
            [
                'icon'  => 'la la-facebook',
                'id'    => 'la la-facebook',
                'name'  => 'Facebook',
                'label' => 'Facebook'
            ],
            [
                'icon'  => 'la la-twitter',
                'id'    => 'la la-twitter',
                'name'  => 'Twitter',
                'label' => 'Twitter'
            ],
            [
                'icon'  => 'la la-map-marker',
                'id'    => 'la la-map-marker',
                'name'  => 'Marker',
                'label' => 'Marker'
            ],
            [
                'icon'  => 'la la-map-pin',
                'id'    => 'la la-map-pin',
                'name'  => 'Map Pin',
                'label' => 'Map Pin'
            ],
            [
                'icon'  => 'fa fa-cutlery',
                'id'    => 'fa fa-cutlery',
                'name'  => 'Cutlery',
                'label' => 'Cutlery'
            ],
            [
                'icon'  => 'la la-shopping-cart',
                'id'    => 'la la-shopping-cart',
                'name'  => 'Cart',
                'label' => 'Cart'
            ],
            [
                'icon'  => 'la la-whatsapp',
                'id'    => 'la la-whatsapp',
                'name'  => 'Whatsapp',
                'label' => 'Whatsapp'
            ],
            [
                'icon'  => 'fa fa-check-circle-o',
                'id'    => 'fa fa-check-circle-o',
                'name'  => 'Check Circle',
                'label' => 'Check Circle'
            ]
        ];

        $aIcons = apply_filters('wilcity/wiloke-listing-tools/external-button-icon', $aIcons);

        if ($oRequest->get_param('mode') === 'select') {
            return $oRetreive->success(['results' => $aIcons]);
        }

        return [
            'data' => $aIcons
        ];
    }
}
