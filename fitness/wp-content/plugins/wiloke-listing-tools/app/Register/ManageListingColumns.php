<?php

namespace WilokeListingTools\Register;

use \WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\Time;

class ManageListingColumns
{
    public function __construct()
    {
        add_filter('manage_posts_columns', [$this, 'registerListingColumns'], 10, 2);
        
        $aPostTypes = General::getPostTypeKeys(false, true);
        
        foreach ($aPostTypes as $postType) {
            add_action('manage_'.$postType.'_posts_custom_column', [$this, 'addCustomInfoToTable'], 10, 2);
        }
    }
    
    public function addCustomInfoToTable($columnName, $postID)
    {
        $val = '';
        
        switch ($columnName) {
            case 'listing_expired':
                $val = GetSettings::getPostMeta($postID, 'post_expiry');
                if (empty($val)) {
                    $val = 'Forever';
                } else {
                    $val = Time::renderPostDate($val).' '.Time::renderPostTime($val);
                }
                break;
            case 'listing_claim_status':
                $val = GetSettings::getPostMeta($postID, 'claim_status');
                if (empty($val)) {
                    $val = 'not_claim';
                }
                break;
            case 'listing_plan':
                $val = GetSettings::getListingBelongsToPlan($postID);
                if (empty($val) || get_post_type($val) !== 'listing_plan') {
                    $val = 'Empty';
                } else {
                    $val = get_the_title($val);
                }
                
                break;
        }
        
        echo $val;
    }
    
    public function registerListingColumns($aColumns, $postType)
    {
        $aPostTypes = General::getPostTypeKeys(false, true);
        
        if (!in_array($postType, $aPostTypes)) {
            return $aColumns;
        }
        
        $aColumns['listing_expired']      = 'Listing Expired';
        $aColumns['listing_claim_status'] = 'Claim Status';
        $aColumns['listing_plan']         = 'Listing Plan';
        
//        unset($aColumns['date']);
        
        return $aColumns;
    }
}
