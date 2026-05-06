<?php

namespace WilokeListingTools\Framework\Helpers\ListingProduct;

interface InterfaceListingProduct
{
    /**
     * @param int $postID
     */
    public function setPostID(int $postID);
    
    /**
     * @param $author array|int
     *
     * @return mixed
     */
    public function setAuthor($author);
    
    /**
     * @return mixed
     */
    public function getProductMode(): string;
    
    /**
     * @return array
     */
    public function getProducts(): array;
    
    /**
     * @return array
     */
    public function getMyProductCats(): array;
    
    public function getSpecifyProducts(): array;
}
