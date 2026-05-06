<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Validation;

trait SetProductsToListing
{
    private function setProductsToListing()
    {
        if (empty($this->aMyProducts)) {
            SetSettings::deletePostMeta($this->listingID, 'my_products');
        } else {
            if ($this->aMyProducts['my_product_mode'] === 'specify_products') {
                SetSettings::setPostMeta(
                    $this->listingID,
                    'my_products',
                    Validation::deepValidation($this->aMyProducts['my_products'])
                );
            } else if ($this->aMyProducts['my_product_mode'] == 'specify_product_cats') {
                SetSettings::setPostMeta(
                    $this->listingID,
                    'wilcity_my_product_cats',
                    Validation::deepValidation($this->aMyProducts['my_product_cats'])
                );
            }

            SetSettings::setPostMeta(
                $this->listingID,
                'my_product_mode',
                Validation::deepValidation($this->aMyProducts['my_product_mode'])
            );
        }
    }
}
