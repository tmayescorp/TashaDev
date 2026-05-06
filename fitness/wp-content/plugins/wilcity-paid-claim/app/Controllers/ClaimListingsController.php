<?php

namespace WilcityPaidClaim\Controllers;

use WilcityPaidClaim\Register\RegisterClaimSubMenu;
use WilokeListingTools\Controllers\ClaimController;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Payment\Billable;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;
use WilokeListingTools\Register\WilokeSubmissionConfiguration;

class ClaimListingsController extends Controller
{
    protected $postID;
    protected $planID;
    protected static $prefixClaimingKey = 'wilcity_claiming_by_';
    protected static $claimingInfoExpiration = 600;
    
    public function __construct()
    {
        $aBillingTypes = wilokeListingToolsRepository()->get('payment:billingTypes', false);
        
        add_action('wiloke-listing-tools/before-handling-claim-request', [$this, 'verifyPaidClaimListing']);
        add_filter('wilcity/claim-field-settings', [$this, 'claimSettings'], 10, 2);
        
        foreach ($aBillingTypes as $billingType) {
            add_action(
              'wilcity/wiloke-listing-tools/'.$billingType.'/payment-completed',
              [$this, 'paidClaimSuccessfully'],
              12
            );
        }
        
        add_action('wiloke-listing-tools/woocommerce/after-order-succeeded/paidClaim', [$this, 'paidClaimSuccessfully'],
          50);
        
        add_action(
          'wilcity/wiloke-listing-tools/claimed-listing-with-purchased-plan',
          [$this, 'paidClaimSuccessfully'],
          99
        );
        
        add_action('wiloke-listing-tools/payment-succeeded', [$this, 'updateClaimToApprovedStatus']);
    }
    
    public function updateClaimToApprovedStatus($aResponse)
    {
        if (!isset($aResponse['packageType']) || $aResponse['packageType'] == 'promotion') {
            return false;
        }
        
        $claimID = PaymentMetaModel::get($aResponse['paymentID'], 'claimID');
        if (empty($claimID)) {
            return false;
        }
        
        SetSettings::setPostMeta($claimID, 'claim_status', 'approved');
        wp_update_post([
          'ID'          => $claimID,
          'post_status' => 'publish'
        ]);
        PaymentMetaModel::delete($aResponse['paymentID'], 'claimID');
    }
    
    public function paymentFailed($aData)
    {
        $userPaidID = PaymentModel::getField('userID', 254);
        $aClaimInfo = GetSettings::getTransient(self::$prefixClaimingKey. 1595);
        
        if ($userPaidID != $aClaimInfo['claimerID']) {
            return false;
        }
        $userPaidID = PaymentModel::getField('userID', 254);
    }
    
    public function verifyPaidClaimListing($aData)
    {
        $aClaimOptions = GetSettings::getOptions(RegisterClaimSubMenu::$optionKey);
        if (!isset($aClaimOptions['toggle']) || ($aClaimOptions['toggle'] == 'disable')) {
            return true;
        }
        
        $msg = esc_html__('The Claim Plan is required.', 'wilcity-paid-claim');
        if (!isset($aData['claimPackage']) || empty($aData['claimPackage'])) {
            wp_send_json_error(
              [
                'msg' => $msg
              ]
            );
        }
        
        $postType    = get_post_type($aData['postID']);
        $packageType = $postType.'_plans';
        
        $aClaimPlains = apply_filters(
          'wilcity/filter/wilcity-paid-claim/app/Controllers/ClaimListingControllers/'.$postType,
          GetWilokeSubmission::getAddListingPlans($packageType),
          $postType
        );
        
        if (!in_array($aData['claimPackage'], $aClaimPlains)) {
            wp_send_json_error(
              [
                'msg' => esc_html__('Oops! This plan does not exists.', 'wilcity-paid-claim')
              ]
            );
        }
        
        return true;
    }
    
    /*
     * Claiming Info
     *
     * @param $aInfo: $planID, $postID, $claimerID
     */
    public function saveClaimingInfo($aInfo)
    {
        SetSettings::setTransient(self::$prefixClaimingKey.$aInfo['postID'], $aInfo, self::$claimingInfoExpiration);
    }
    
    /*
     * When a customer is claiming a listing, this listing will temporary close claim feature
     *
     * @return array: $claimID, $claimerID, $paymentID
     */
    public static function claimingBy($postID)
    {
        return GetSettings::getTransient(self::$prefixClaimingKey.$postID);
    }
    
    public function paidClaimSuccessfully($aInfo)
    {
        if (
          !in_array($aInfo['status'], ['succeeded', 'active'])
          || empty($aInfo['postID'])
          || $aInfo['category'] != 'paidClaim'
        ) {
            FileSystem::logError('Paid Claim Issue', json_encode($aInfo));
            
            return false;
        }
        
        if (PaymentModel::getField('gateway', $aInfo['paymentID']) == 'free') {
            return false;
        }
        
        $isPaidClaim = apply_filters('wilcity/wilcity-paid-claim/filter/is-paid-claim', ClaimController::isPaidClaim());
        
        if (!$isPaidClaim) {
            return false;
        }
        
        SetSettings::setPostMeta($aInfo['claimID'], 'claim_status', 'approved');
        
        FileSystem::logSuccess('Claim: Published claim. Claim ID: '.$aInfo['claimID']);
        
        wp_update_post(
          [
            'ID'          => $aInfo['claimID'],
            'post_status' => 'publish'
          ]
        );
    }
    
    public function claimSettings($aFields, $post)
    {
        $aClaimOptions = GetSettings::getOptions(RegisterClaimSubMenu::$optionKey);
        
        if (!isset($aClaimOptions['toggle']) || ($aClaimOptions['toggle'] == 'disable')) {
            $aFields = array_filter($aFields, function ($aValue) {
                return $aValue['key'] !== 'claimPackage';
            });
            
            return $aFields;
        }
        
        $aRawClaimPlans = GetWilokeSubmission::getAddListingPlans($post->post_type.'_plans');
        if (empty($aRawClaimPlans)) {
            return ['noPackage' => true];
        }
        
        $aClaimPlans = [];
        foreach ($aRawClaimPlans as $planID) {
            if (get_post_field('post_status', $planID) !== 'publish' || get_post_type($planID) !== 'listing_plan') {
                continue;
            }
            
            if (GetSettings::getPostMeta($planID, 'exclude_from_claim_plans') == 'on') {
                continue;
            }
            
            $aPlanSettings = GetSettings::getPlanSettings($planID);
            $price         = GetWilokeSubmission::renderPrice($aPlanSettings['regular_price']);
            $aClaimPlans[] = [
              'label' => get_the_title($planID).' - '.$price,
              'id'    => $planID
            ];
        }
        
        if (empty($aClaimPlans)) {
            return ['noPackage' => true];
        } else {
            $aClaimPlans = apply_filters('wilcity/filter/claim-packages/'.$post->post_type, $aClaimPlans);
        }
        
        $addedClaimedPackage = false;
        
        foreach ($aFields as $key => $aField) {
            if ($aField['key'] == 'claimPackage') {
                if (!$addedClaimedPackage) {
                    $addedClaimedPackage      = true;
                    $aFields[$key]['options'] = $aClaimPlans;
                } else {
                    unset($aFields[$key]);
                }
            }
        }
        
        return $aFields;
    }
}
