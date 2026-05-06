<?php
namespace WILCITY_APP\Controllers\WooCommerce;

use WILCITY_APP\Controllers\VerifyToken;

class WooCommerceCouponController
{
    use VerifyToken;
    
    public function __construct()
    {
        add_action('rest_api_init', function () {
            register_rest_route(WILOKE_PREFIX.'/'.WILOKE_MOBILE_REST_VERSION, '/wc/apply-coupon', [
              'methods'  => 'POST',
              'callback' => [$this, 'applyCoupon'],
              'permission_callback' => '__return_true',
            ]);
            
            register_rest_route(WILOKE_PREFIX.'/v2', '/wc/apply-coupon', [
              'methods'  => 'POST',
              'callback' => [$this, 'applyCoupon'],
              'permission_callback' => '__return_true',
            ]);

            register_rest_route(WILOKE_PREFIX.'/'.WILOKE_MOBILE_REST_VERSION, '/wc/remove-coupon', [
                'methods'  => 'POST',
                'callback' => [$this, 'removeCoupon'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route(WILOKE_PREFIX.'/v2', '/wc/apply-coupon', [
                'methods'  => 'POST',
                'callback' => [$this, 'removeCoupon'],
                'permission_callback' => '__return_true',
            ]);
            
        });
    }
    
    /**
     * @param \WP_REST_Request $oRequest
     *
     * @return array
     */
    public function applyCoupon(\WP_REST_Request $oRequest)
    {
        $oToken = $this->verifyPermanentToken();
        if (!$oToken) {
            return $this->tokenExpiration();
        }

        $aAppliedCoupon = WC()->cart->get_applied_coupons();
        $applyCoupon    = $oRequest->get_param('coupon');
        if (empty($applyCoupon)) {
            wc_add_notice(\WC_Coupon::get_generic_coupon_error(\WC_Coupon::E_WC_COUPON_PLEASE_ENTER), 'error');
            $result = false;
        } else {
            $result = WC()->cart->add_discount(wc_format_coupon_code(wp_unslash($applyCoupon)));
        }
        
        $aNotices = WC()->session->get('wc_notices', []);
        $status   = '';
        $msg      = '';
        if (!empty($aNotices) && is_array($aNotices)) {
            if ($result === true) {
                if (!empty($aAppliedCoupon)) {
                    foreach ($aAppliedCoupon as $coupon) {
                        $this->handleRemoveCoupon($coupon);
                    }
                }
                $status = 'success';
                $msg    = end($aNotices['success'])['notice'];
            } else {
                $status = 'error';
                $msg    = end($aNotices['error'])['notice'];
            }
        }
        
        $aResponse = [
          'status' => $status,
          'msg'    => html_entity_decode(strip_tags($msg))
        ];
        
        if ($result === true) {
            $aResponse['totalPrice']     = floatval(WC()->cart->cart_contents_total);
            $aResponse['totalPriceHTML'] = wc_price(WC()->cart->cart_contents_total);
        }
        
        return $aResponse;
    }

    /**
     * @return array
     * @param \WP_REST_Request $oRequest
     */
    public function removeCoupon(\WP_REST_Request $oRequest)
    {
        $oToken = $this->verifyPermanentToken();
        if (!$oToken) {
            return $this->tokenExpiration();
        }

        $couponCode = $oRequest->get_param('coupon');
        $this->handleRemoveCoupon($couponCode);

        if (empty($couponCode)) {
            wc_add_notice(__('Sorry there was a problem removing this coupon.'), 'error');
        } else {
            wc_add_notice(__('Coupon has been removed.'));
        }

        $aNotices = WC()->session->get('wc_notices', []);
        $status   = '';
        $msg      = '';
        if (!empty($aNotices) && is_array($aNotices)) {
            if (empty($couponCode)) {
                $status = 'success';
                $msg    = end($aNotices['success'])['notice'];
            } else {
                $status = 'error';
                $msg    = end($aNotices['error'])['notice'];
            }
        }

        $aResponse = [
            'status' => $status,
            'msg'    => html_entity_decode(strip_tags($msg))
        ];

        return $aResponse;
    }

    /**
     * @param string $couponCode
     */
    public function handleRemoveCoupon($couponCode = '')
    {
        WC()->cart->remove_coupon($couponCode);
        //Check coupon again
        do_action('woocommerce_applied_coupon', $couponCode);
    }
}
