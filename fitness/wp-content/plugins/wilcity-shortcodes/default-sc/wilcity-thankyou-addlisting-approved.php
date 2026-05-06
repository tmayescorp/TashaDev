<?php

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Models\PaymentMetaModel;

function wilcityThankyouAddListingApproved($aArgs, $content)
{
    if (!isset($_REQUEST['category']) || !in_array($_REQUEST['category'], ['addlisting'])) {
        return '';
    }
    
    if (!isset($_REQUEST['postID'])) {
        return '';
    }
    
    $aParsePostIDs = explode(',', $_REQUEST['postID']);
    
    if (get_post_status($aParsePostIDs[0]) !== 'publish') {
        return '';
    }
    
    if ($_REQUEST['category'] === 'paidClaim') {
        if (isset($_REQUEST['paymentID'])) {
            $claimID     = PaymentMetaModel::get($_REQUEST['paymentID'], 'claimID');
            $claimStatus = GetSettings::getPostMeta($claimID, 'claim_status');
            if ($claimStatus !== 'approved') {
                return false;
            }
        }
    }
    
    return apply_filters('wilcity/thankyou-content', $content, [
      'postID'      => $_REQUEST['postID'],
      'promotionID' => isset($_REQUEST['promotionID']) ? $_REQUEST['promotionID'] : '',
      'category'    => $_REQUEST['category']
    ]);
}

add_shortcode('wilcity_thankyou_addlisting_approved', 'wilcityThankyouAddListingApproved');
