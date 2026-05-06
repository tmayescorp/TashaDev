<?php

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Models\PaymentMetaModel;

function wilcityThankyouAddListingPending($aArgs, $content = '')
{
    if (!isset($_REQUEST['category']) || !in_array($_REQUEST['category'], ['addlisting'])) {
        return '';
    }
    
    if (!isset($_REQUEST['postID'])) {
        return '';
    }
    
    $aParsePostIDs = explode(',', $_REQUEST['postID']);
    $isPending     = false;
    if ($_REQUEST['category'] === 'paidClaim') {
        if (isset($_REQUEST['paymentID'])) {
            $claimID     = PaymentMetaModel::get($_REQUEST['paymentID'], 'claimID');
            $claimStatus = GetSettings::getPostMeta($claimID, 'claim_status');
            if ($claimStatus !== 'approved') {
                $isPending = true;
            }
        }
    }
    
    if (!$isPending && get_post_status($aParsePostIDs[0]) !== 'pending') {
        return '';
    }
    
    return apply_filters('wilcity/thankyou-content', $content, [
      'postID'      => $_REQUEST['postID'],
      'promotionID' => isset($_REQUEST['promotionID']) ? $_REQUEST['promotionID'] : '',
      'category'    => $_REQUEST['category']
    ]);
}

add_shortcode('wilcity_thankyou_addlisting_pending', 'wilcityThankyouAddListingPending');
