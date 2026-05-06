<?php

namespace WilokeListingTools\Controllers;


use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Upload\Upload;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Framework\Helpers\Validation;

trait InsertGallery
{
    protected function insertGallery()
    {
        if (empty($this->aGallery)) {
            SetSettings::deletePostMeta($this->listingID, 'gallery');
            return false;
        }

        if (!empty($this->aGallery)) {
            SetSettings::setPostMeta($this->listingID, 'gallery', $this->aGallery);
        }
    }
}
