<?php

namespace WilokeListingTools\Controllers\SingleListing;

use WilokeListingTools\Framework\Helpers\General;

class WooCommerce
{
    public function __construct()
    {
        add_filter('woocommerce_shortcode_products_query', [$this, 'addAuthorToProductShortcode'], 10, 3);
    }
    
    /**
     * @param $aQueryArgs
     * @param $aAtts
     * @param $type
     *
     * @return mixed
     */
    public function addAuthorToProductShortcode($aQueryArgs, $aAtts, $type)
    {
        global $post;
        if ($type === 'products') {
            if (isset($post->post_type) && General::isPostTypeSubmission($post->post_type)) {
                $aQueryArgs['author'] = $post->post_author;
            }
            
        }
        
        return $aQueryArgs;
    }
}
