<?php

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Models\PaymentMetaModel;

function wilcityThankyouClaim($aArgs, $content = '')
{
	$aArgs = shortcode_atts([
		'status' => 'approved' // cancelled, approved, pending
	], $aArgs);

	if (!isset($_REQUEST['category']) || $_REQUEST['category'] !== 'paidClaim' || !isset($_REQUEST['postID'])) {
		return '';
	}

	if (isset($_REQUEST['paymentID'])) {
		$claimID = PaymentMetaModel::get($_REQUEST['paymentID'], 'claimID');
		$claimStatus = GetSettings::getPostMeta($claimID, 'claim_status');
		if ($claimStatus !== $aArgs['status']) {
			return '';
		}
	}

	return apply_filters('wilcity/thankyou-content', $content, [
		'postID'   => $_REQUEST['postID'],
		'category' => $_REQUEST['category']
	]);
}

add_shortcode('wilcity_thankyou_claim', 'wilcityThankyouClaim');
