<?php

namespace WilokeListingTools\MetaBoxes;

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Models\Coupon as CouponModel;
use WilokeListingTools\Framework\Helpers\Validation;

class Coupon extends Controller
{
    public function __construct()
    {
        add_action('init', [$this, 'saveCouponWP53'], 1);
        add_action('save_post', [$this, 'saveCouponWP52'], 10, 3);
    }

    public function saveCouponWP52($listingID, $post, $updated)
    {
        if (!current_user_can('administrator') || $this->isWP53()) {
            return false;
        }

        $this->saveCoupon($listingID);
    }

    public function saveCouponWP53()
    {
        if (!$this->isWP53() || !$this->isSavedPostMeta()) {
            return false;
        }

        $listingID = $_POST['post_ID'];
        $this->saveCoupon($listingID);
    }

    protected function saveCoupon($listingID): bool
    {
        if (!isset($_POST['wilcity_coupon']) || !isset($_POST['wilcity_coupon']['expiry_date']) ||
            !isset($_POST['wilcity_coupon']['expiry_date']['date'])) {
            $aCoupon = GetSettings::getPostMeta($listingID, 'coupon');
            if (!empty($aCoupon)) {
                SetSettings::deletePostMeta($listingID, 'coupon');
            }

            return false;
        }

        foreach ($_POST['wilcity_coupon'] as $key => $val) {
            switch ($key) {
                case 'expiry_date':
                    if (is_array($val)) {
                        if (isset($val['date']) && !empty($val['date'])) {
                            if (isset($val['time']) && !empty($val['time'])) {
                                $val = Time::toTimestamp('m/d/Y g:i A', $val['date'].' '.$val['time'], Time::getDefaultTimezoneString());
                            } else {
                                $val = Time::toTimestamp('m/d/Y', $val['date'], Time::getDefaultTimezoneString());
                            }
                            $aCoupon[sanitize_text_field($key)] = $val;
                        } else {
                            $aCoupon[sanitize_text_field($key)] = '';
                        }
                    }
                    break;
                case 'popup_image':
                    if (!empty($val)) {
                        $aCoupon['popup_image']    = wp_get_attachment_image_url($val);
                        $aCoupon['popup_image_id'] = $val;
                    }
                    break;
                default:
                    $aCoupon[sanitize_text_field($key)] = sanitize_text_field($val);
                    break;
            }
        }

        SetSettings::setPostMeta($listingID, 'coupon', $aCoupon);
        SetSettings::setPostMeta($listingID, 'coupon_expiry', $aCoupon['expiry_date']);
        return true;
    }
}
