<?php

namespace WilokeListingTools\Framework\Helpers\ListingProduct;

use WilokeListingTools\Framework\Helpers\App;

class AbstractListingProduct
{
    protected $mode;
    protected $postID;
    protected $aProductTypes;
    protected $aCatIds;
    protected $author;
    
    /**
     * @param $method
     * @param $arguments
     *
     * @return mixed
     * @throws \Exception
     */
    public function __call($method, $arguments)
    {
        if (method_exists($this, $method)) {
            if (strpos($method, 'get') !== false) {
                $this->isPostIDExists();
            }
        }
    }
    
    public function setAuthor($author)
    {
        $this->author = is_array($author) ? $author : [$author];
        
        return $this;
    }
    
    /**
     * @return $this
     * @throws \Exception
     */
    protected function isPostIDExists()
    {
        if (empty($this->postID)) {
            throw new \Exception('The id is required');
        }
        
        return $this;
    }
    
    public function setPostID(int $postID)
    {
        $this->postID = $postID;
        
        return $this;
    }
    
    /**
     * @param array $aProductTypes
     *
     * @return $this
     */
    public function setProductTypes(array $aProductTypes)
    {
        $this->aProductTypes = $aProductTypes;
        
        return $this;
    }
    
    protected function buildGeneralQuery(): array
    {
        $this->aCatIds = [];
        $mode          = $this->getProductMode();
        $postAuthor    = get_post_field('post_author', $this->postID);
        $aOriginalArgs = [];
        
        switch ($mode) {
            case 'author_products':
                $aOriginalArgs = [
                    'author' => $postAuthor
                ];
                break;
            case 'specify_product_cats':
                $this->aCatIds = $this->getMyProductCats();
                if (empty($this->aCatIds)) {
                    return [];
                }
                break;
            case 'specify_products':
                $productIDs = $this->getSpecifyProducts();
                if (empty($productIDs)) {
                    return [];
                }
                
                $aOriginalArgs = [
                    'post__in' => $productIDs
                ];
                break;
        }
        
        $aOriginalArgs = array_merge([
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'orderby'        => 'post_title',
            'order'          => 'DESC',
            'posts_per_page' => 50
        ], $aOriginalArgs);
        
        if (!empty($this->aProductTypes)) {
            $aOriginalArgs['tax_query'][] = [
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => $this->aProductTypes
            ];
        }
        
        if (!isset($aOriginalArgs['author']) && !empty($this->author)) {
            if (!user_can($this->author, 'administrator')) {
                $aOriginalArgs['author__in'] = $this->author;
            }
        }
        
        return apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Framework/Helpers/ListingProduct/AbstractListingProduct/buildGeneralQuery',
            $aOriginalArgs
        );
    }
}
