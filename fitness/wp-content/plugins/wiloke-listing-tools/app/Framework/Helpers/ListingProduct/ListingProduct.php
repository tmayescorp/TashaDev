<?php

namespace WilokeListingTools\Framework\Helpers\ListingProduct;

use WilokeListingTools\Framework\Helpers\App;
use WilokeListingTools\Framework\Helpers\GetSettings;

class ListingProduct extends AbstractListingProduct implements InterfaceListingProduct
{
    public function getProductMode(): string
    {
        $this->mode = GetSettings::getPostMeta($this->postID, 'my_product_mode');

        if ($this->mode === 'inherit') {
            $this->mode = \WilokeThemeOptions::getOptionDetail('advanced_woo_get_product_mode');
        }

        return empty($this->mode) ? 'specify_products' : $this->mode;
    }

    /**
     * @return array
     */
    public function getMyProductCats(): array
    {
        $aCats = GetSettings::getPostMeta($this->postID, 'my_product_cats');
        if (empty($aCats)) {
            return [];
        }

        return array_filter($aCats, function ($catID) {
            return term_exists(absint($catID), 'product_cat');
        });
    }

    public function getSpecifyProducts(): array
    {
	    $aProducts = GetSettings::getPostMeta($this->postID, 'my_products');

	    if (empty($aProducts)) {
		    return [];
	    }

	    if (!is_array($aProducts)) {
		    $aProducts = [$aProducts];
	    }

	    return array_filter($aProducts, function ($productID) {
		    return get_post_status($productID) === 'publish';
	    });
    }

    /**
     * @param array $aAtts
     *
     * @return array
     */
    public function getProducts($aAtts = []): array
    {
        $aOriginalArgs = $this->buildGeneralQuery();

        if (!empty($aProductTypes)) {
            $aOriginalArgs['tax_query'][] = [
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => $aProductTypes
            ];
        }

        if (!empty($this->aCatIds)) {
            $aOriginalArgs['tax_query'][] = [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $this->aCatIds
            ];
        }

        $query = new \WP_Query($aOriginalArgs);
        $aProducts = [];

        $aPluck = isset($aAtts['pluck']) ? $aAtts['pluck'] : [];
        unset($aAtts['pluck']);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $aProducts[] = App::get('ProductSkeleton')->getSkeleton(
                    $query->post->ID,
                    $aPluck,
                    $aAtts
                );
            }
        }

        return $aProducts;
    }
}
