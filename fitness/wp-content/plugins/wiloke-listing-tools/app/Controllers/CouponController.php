<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Payment\Coupon;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Framework\Store\Session;
use \WilokeListingTools\Models\Coupon as CouponModel;

class CouponController extends Controller
{
    protected $planID;
    
    public function __construct()
    {
        add_action('wp_ajax_nopriv_wiloke_submission_verify_coupon', [$this, 'verifyCoupon']);
        add_action('wp_ajax_wiloke_submission_verify_coupon', [$this, 'verifyCoupon']);
    }
    
    public function verifyCoupon()
    {
        $this->planID = Session::getSession(wilokeListingToolsRepository()->get('payment:storePlanID'));
        $planPostType = get_post_field('post_type', $this->planID);
        $aPackageInfo = GetSettings::getPlanSettings($this->planID);
        $subTotal     = $aPackageInfo['regular_price'];
        
        $aErrMsg = [
            'msg' => \WilokeMessage::message([
                'msg'    => esc_html__('The coupon is invalid or has expired', 'wiloke-listing-tools'),
                'status' => 'danger'
            ], true)
        ];
        
        if (!isset($_POST['code']) || empty($_POST['code'])) {
            wp_send_json_error($aErrMsg);
        }
        
        $instCoupon = new Coupon();
        if (!$instCoupon->getCouponID($_POST['code'])) {
            wp_send_json_error($aErrMsg);
        }
        
        $instCoupon->getCouponInfo();
        if ($instCoupon->isCouponExpired()) {
            wp_send_json_error($aErrMsg);
        }
        
        if (!$instCoupon->isPostTypeSupported($planPostType)) {
            wp_send_json_error($aErrMsg);
        }
        
        if ($instCoupon->aSettings['type'] == 'percentage') {
            $discountPrice = round(($subTotal * $instCoupon->aSettings['amount']) / 100, 2);
        } else {
            $discountPrice = $instCoupon->aSettings['amount'];
        }
        
        $taxRate = GetWilokeSubmission::getTaxRate();
        if ($subTotal < $discountPrice) {
            $subTotal = 0;
            $aMessage = \WilokeMessage::message([
                'msg' => sprintf(__('You save %s', 'wiloke-listing-tools'), GetWilokeSubmission::renderPrice($price))
            ], true);
            $tax      = 0;
            $newTotal = 0;
        } else {
            $aMessage = \WilokeMessage::message([
                'msg' => sprintf(__('You save %s', 'wiloke-listing-tools'),
                    GetWilokeSubmission::renderPrice($discountPrice))
            ], true);
            
            $tax      = round(($subTotal - $discountPrice) * $taxRate / 100, 2);
            $newTotal = round(($tax + $subTotal - $discountPrice), 2);
        }
        
        wp_send_json_success(
            [
                'msg'      => $aMessage,
                'subTotal' => $subTotal,
                'tax'      => $tax,
                'discount' => GetWilokeSubmission::renderPrice($discountPrice),
                'total'    => GetWilokeSubmission::renderPrice($newTotal)
            ]
        );
    }
}
