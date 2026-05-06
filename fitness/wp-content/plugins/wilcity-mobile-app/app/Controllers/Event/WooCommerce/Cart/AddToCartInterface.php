<?php
namespace WILCITY_APP\Controllers\WooCommerce\Cart;

interface AddToCartInterface
{
    public function setProduct(\WC_Product $product);
    
    public function setUserId($userId);
    
    public function setQuantity($quantity);
    
    public function setVariationId($variationId);
    
    public function setVariations($aVariations);
    
    public function setCartKey($cartKey);
    
    /**
     * @param string $mode There are 3 modes: addOne, deduceOne, specifyQuantity. specifyQuantity is default
     *
     * @return mixed
     */
    public function setMode($mode);
}
