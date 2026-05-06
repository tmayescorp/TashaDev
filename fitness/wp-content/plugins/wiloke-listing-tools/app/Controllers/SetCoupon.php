<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Upload\Upload;

trait SetCoupon
{
    private function setCoupon()
    {
        if (empty($this->aCoupon)) {
            SetSettings::deletePostMeta($this->listingID, 'coupon');
            SetSettings::deletePostMeta($this->listingID, 'coupon_expiry');
        } else {
            if (isset($this->aCoupon['expiry_date'])) {
                SetSettings::setPostMeta($this->listingID, 'coupon_expiry', $this->aCoupon['expiry_date']);
            } else {
                SetSettings::deletePostMeta($this->listingID, 'coupon_expiry');
            }

            SetSettings::setPostMeta($this->listingID, 'coupon', $this->aCoupon);
        }
    }
}
