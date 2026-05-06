<?php

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Store\Session;

function wilcityThankyouPromotion($aArgs, $content = '')
{
    if (!isset($_REQUEST['category']) || $_REQUEST['category'] != 'promotion') {
        return '';
    }
    
    if (!isset($_REQUEST['postID']) || !isset($_REQUEST['promotionID'])) {
        $promotionID = Session::getSession('promotionID', false);
        if (empty($promotionID)) {
            return '';
        }
    } else {
        $promotionID = $_REQUEST['promotionID'];
    }
    
    return apply_filters('wilcity/thankyou-content', $content, [
      'postID'      => $_REQUEST['postID'],
      'promotionID' => $promotionID,
      'category'    => $_REQUEST['category']
    ]);
}

add_shortcode('wilcity_thankyou_promotion', 'wilcityThankyouPromotion');
