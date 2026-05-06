<?php

namespace WilokeListingTools\Framework\Payment\Receipt;

use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Controllers\RetrieveController;

final class ReceiptStaticFactory
{
    /**
     * @param string $category addlisting|promotion
     * @param        $aInfo
     *
     * @return AddListingReceiptStructure
     */
    public static function get($category, $aInfo)
    {
        $oRetrieve = new RetrieveController(new NormalRetrieve());
        
        switch ($category) {
            case 'addlisting':
                $oReceipt = new AddListingReceiptStructure($aInfo);
                break;
            case 'promotion':
                $oReceipt = new WilokePromotionReceipt($aInfo);
                break;
            default:
                $oReceipt = apply_filters(
                    'wilcity/filter/wiloke-listing-tools/app/Framework/Payment/Receipt/ReceiptStaticFactory/get',
                    null,
                    $category,
                    $aInfo
                );
                break;
        }
        
        if (isset($oReceipt) && $oReceipt instanceof ReceiptStructureInterface) {
            return $oReceipt;
        }
        
        return $oRetrieve->error([
            'msg' => 'Unknown gateway receipt'
        ]);
    }
}
