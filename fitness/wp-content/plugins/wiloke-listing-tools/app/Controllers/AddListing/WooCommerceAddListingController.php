<?php
namespace WilokeListingTools\Controllers\AddListing;

use WilokeListingTools\Framework\Helpers\App;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\Select;

class WooCommerceAddListingController
{
    public function __construct()
    {
        add_filter(
            'wilcity/filter/wiloke-listing-tools/app/Controllers/AddListingController/section/my_products',
            [$this, 'addProductModeOptions']
        );
        
        add_filter('wilcity/filter/wiloke-listing-tools/app/AddListingController/getResults/callback/my_products',
            [$this, 'setGetResultCallBackFuncCallback']);
        
        add_filter('wilcity/filter/wiloke-listing-tools/app/AddListingController/getDefaultValue/callback/my_products',
            [$this, 'setGetDefaultResultFuncCallback']);
    }
    
    public function setGetResultCallBackFuncCallback()
    {
        return [App::get('WooCommerceAddListingController'), 'getMyProduct'];
    }
    
    public function setGetDefaultResultFuncCallback()
    {
        return [App::get('WooCommerceAddListingController'), 'getDefaultMyProduct'];
    }
    
    public function getDefaultMyProduct()
    {
        return [
            'my_product_mode' => 'author_products',
            'my_products'     => [],
            'my_product_cats' => []
        ];
    }
    
    public function getMyProduct($aSection, $listingID)
    {
        $aPostIDs = GetSettings::getMyProducts($listingID);
        $aTermIds = GetSettings::getMyProductCats($listingID);
        
        $maximumProducts = empty($aSection['fieldGroups']['my_products']['queryArgs']['maximum']) ? 1000 :
            $aSection['fieldGroups']['my_products']['queryArgs']['maximum'];
        
        return [
            'my_product_mode' => GetSettings::getProductMode($listingID),
            'my_products'     => empty($aPostIDs) ? [] : Select::buildPostsSelectTree(
                $aPostIDs,
                $aSection['fieldGroups']['my_products']['selectValueFormat'],
                $maximumProducts
            ),
            'my_product_cats' => empty($aTermIds) ? [] : Select::buildTermSelectTree(
                $aTermIds,
                'product_cat',
                $aSection['fieldGroups']['my_products']['selectValueFormat'],
                1000
            )
        ];
    }
    
    public function addProductModeOptions($aSection)
    {
        $aRawOptions = wilokeListingToolsRepository()->get('settings:productModeOptions');
        
        unset($aRawOptions['inherit']);
        $aOptions = [];
        
        foreach ($aRawOptions as $key => $val) {
            $aOptions[] = [
                'id'    => $key,
                'label' => $val
            ];
        }
        
        $aSection['fieldGroups']['my_product_mode']['options'] = $aOptions;
        
        return $aSection;
    }
}
