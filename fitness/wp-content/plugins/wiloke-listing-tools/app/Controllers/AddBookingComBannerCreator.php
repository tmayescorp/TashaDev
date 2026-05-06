<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Upload\Upload;
use WilokeListingTools\MetaBoxes\BookingComBannerCreator;
use WilokeListingTools\Models\BookingCom;

trait AddBookingComBannerCreator
{
    protected function addBookingComBannerCreator()
    {
        $bookingID = BookingCom::getCreatorIDByParentID($this->listingID);
        if (!empty($this->aBookingComBannerCreator)) {
            if (
                !isset($this->aBookingComBannerCreator['bannerLink']) ||
                empty($this->aBookingComBannerCreator['bannerLink'])
            ) {
                if (!empty($bookingID)) {
                    wp_delete_post($bookingID, true);
                }

                return false;
            }

            if (!empty($bookingID)) {
                BookingCom::updateBannerCreator($this->listingID, $bookingID, $this->aBookingComBannerCreator);
            } else {
                BookingCom::insertBannerCreator($this->listingID, $this->aBookingComBannerCreator);
            }
        } else {
            if (!empty($bookingID)) {
                wp_delete_post($bookingID, true);
            }
        }
    }
}
