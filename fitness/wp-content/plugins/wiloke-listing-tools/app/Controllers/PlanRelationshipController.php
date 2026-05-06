<?php
namespace WilokeListingTools\Controllers;

use Stripe\File;
use WilokeListingTools\AlterTable\AlterTablePlanRelationships;
use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PlanRelationshipModel;

class PlanRelationshipController extends Controller
{
	public function __construct()
	{
		add_filter(
			'wilcity/wiloke-listing-tools/change-listings-to-another-purchased-plan',
			[$this, 'changeListingsToNewPlan'],
			1,
			2
		);

		$aBillingTypes = wilokeListingToolsRepository()->get('payment:billingTypes', false);
		foreach ($aBillingTypes as $billingType) {
			add_action('wilcity/wiloke-listing-tools/'.$billingType.'/payment-completed', [
				$this,
				'updatePostPaymentRelationship'
			], 10);
		}

		add_action('wilcity/wiloke-listing-tools/claimed-listing-with-purchased-plan', [$this, 'afterClaimApproved']);
		add_action('wilcity/wiloke-listing-tools/claim-cancelled', [$this, 'afterClaimCancelled']);
		add_action('wiloke/submitted-listing', [$this, 'addPlanRelationshipUserPurchasedPlan'], 10);
		add_action('wilcity/wiloke-listing-tools/claim-approved', [$this, 'afterClaimApproved']);
		add_action('after_delete_post', [$this, 'deletePostFromListingRelationship']);
	}

	private function setPlanRelationship($planID, $objectID, $userID, $paymentID)
	{
		$status = PlanRelationshipModel::setPlanRelationship([
			'planID'    => $planID,
			'objectID'  => $objectID,
			'userID'    => $userID,
			'paymentID' => $paymentID
		]);

		if (!$status) {
			FileSystem::logError(
				'We could not insert relationship. Data: '.json_encode([
					'planID'    => $planID,
					'objectID'  => $objectID,
					'userID'    => $userID,
					'paymentID' => $paymentID
				]),
				__CLASS__,
				__METHOD__
			);
		} else {
			FileSystem::logSuccess('Insert New Relationship '.$status, __CLASS__);
		}

		return $status;
	}

	private function updatePaymentID($id, $paymentID)
	{
		$status = PlanRelationshipModel::updatePaymentID(
			$id,
			$paymentID
		);

		if ($status) {
			FileSystem::logSuccess('Updated Temporary Relationship '.$id, __CLASS__);
		} else {
			$msg = 'Could not update payment relationship at '.$id.
				' The new Payment ID is '.$paymentID;
			FileSystem::logError(
				$msg,
				__CLASS__,
				__METHOD__
			);
		}

		return $status;

	}

	public function afterClaimApproved($aInfo)
	{
		$maybePlanID = PlanRelationshipModel::getIDByObjectID($aInfo['postID']);

		if (empty($maybePlanID)) {
			$this->setPlanRelationship(
				$aInfo['planID'],
				$aInfo['postID'],
				$aInfo['userID'],
				$aInfo['aUserPlan']['paymentID']
			);

			FileSystem::logPayment('claim.log', 'Inserted Claim Relationship. ClaimID: '.$maybePlanID);
		} else {
			PlanRelationshipModel::update($maybePlanID, [
				'planID'    => $aInfo['planID'],
				'objectID'  => $aInfo['postID'],
				'userID'    => $aInfo['userID'],
				'paymentID' => $aInfo['aUserPlan']['paymentID']
			]);

			FileSystem::logSuccess('Updated Claim Relationship. ClaimID: '.$maybePlanID);
		}
	}

	public function deletePostFromListingRelationship($postId) {
        $planRelationshipID = PlanRelationshipModel::getIDByObjectID($postId);

        if (empty($planRelationshipID)) {
            return false;
        }

        PlanRelationshipModel::delete($planRelationshipID);
    }

	public function afterClaimCancelled($aInfo)
	{
		if (isset($aInfo['postStatusBefore']) && $aInfo['postStatusBefore'] != 'publish') {
			return false;
		}

		$planRelationshipID = PlanRelationshipModel::getIDByObjectID($aInfo['postID']);

		if (empty($planRelationshipID)) {
			return false;
		}

		$userIDInThePlan = PlanRelationshipModel::getField('userID', $planRelationshipID);
		if ($userIDInThePlan == $aInfo['claimerID']) {
			PlanRelationshipModel::delete($planRelationshipID);
			FileSystem::logSuccess('Claim: Deleted Plan Relationship after rejected claim. Plan Relationship ID: '
				.$planRelationshipID);
		}
	}

	public function updatePostPaymentRelationship($aInfo)
	{
		if (!isset($aInfo['paymentID']) || empty($aInfo['paymentID'])) {
			FileSystem::logError(
				'The payment id is required',
				__CLASS__,
				__METHOD__
			);
		}

		$aPaymentMetaInfo = PaymentMetaModel::getPaymentInfo($aInfo['paymentID']);
		if (!isset($aPaymentMetaInfo['postID']) || empty($aPaymentMetaInfo['postID']) || !isset
			($aPaymentMetaInfo['planID']) || empty($aPaymentMetaInfo['planID'])
		) {
			FileSystem::logSuccess('AddListing: We could not insert relationship '.json_encode($aPaymentMetaInfo));

			return false;
		}

		if (!empty($aPaymentMetaInfo['planRelationshipID'])) {
			$status = $this->updatePaymentID($aPaymentMetaInfo['planRelationshipID'], $aInfo['paymentID']);
		} else {
			if (empty($aInfo['paymentID'])) {
				FileSystem::logError(
					'The payment id is required',
					__CLASS__,
					__METHOD__
				);

				return false;
			}

			$aPostIDs = explode(',', $aPaymentMetaInfo['postID']);
			$aPostIDs = array_map(function ($postID) {
				return trim($postID);
			}, $aPostIDs);

			foreach ($aPostIDs as $postID) {
				$status = $this->setPlanRelationship($aPaymentMetaInfo['planID'], $postID, $aPaymentMetaInfo['userID'], $aInfo['paymentID']);
				FileSystem::logSuccess('Updating payment relationship. Post ID: ' . $postID);
			}
		}

		if (!$status) {
			return false;
		}
	}

	public function addPlanRelationshipUserPurchasedPlan($aInfo)
	{
		if (empty($aInfo['aUserPlan'])) {
			return false;
		}

		$planRelationshipID = PlanRelationshipModel::getIDByObjectID($aInfo['postID']);
		if (!empty($planRelationshipID)) {
			return false;
		}

		PlanRelationshipModel::setPlanRelationship([
			'planID'    => $aInfo['planID'],
			'objectID'  => $aInfo['postID'],
			'userID'    => $aInfo['postAuthor'],
			'paymentID' => $aInfo['aUserPlan']['paymentID']
		]);

		FileSystem::logSuccess('AddListing: Added Plan Relationship. Information: '.json_encode($aInfo));
	}

	/**
	 * Switch Listings Relationship Belongs To New Plan
	 */
	public function switchListingsBelongsToOldPaymentIDToNewPaymentID($aInformation)
	{
		$aRequires = [
			'oldPlanID'    => 'The old Plan ID is required',
			'planID'       => 'The new Plan ID is required',
			'paymentID'    => 'The new Payment ID is required',
			'oldPaymentID' => 'The old payment ID is required'
		];

		$oRetrieve = new RetrieveController(new NormalRetrieve());
		foreach ($aRequires as $param => $msg) {
			if (!isset($aInformation[$param]) || empty($aInformation[$param])) {
				return $oRetrieve->error(['msg' => $msg]);
			}
		}

		PlanRelationshipModel::upgradeToNewPaymentID(
			$aInformation['paymentID'],
			$aInformation['oldPaymentID'],
			$aInformation['oldPlanID'],
			$aInformation['planID']
		);

		return $oRetrieve->success([
			'msg' => esc_html__('The plan has been upgraded successfully', 'wiloke-listing-tools')
		]);
	}

	private function upgradeListingToNewPlan($aParams, $postID)
	{
		$oRetrieve = new RetrieveController(new NormalRetrieve());

		$aListingTypes = General::getPostTypeKeys(false, false);
		if (!in_array(get_post_type($postID), $aListingTypes)) {
			return $oRetrieve->error([
				'msg' => esc_html__('The article must be a Listing Types', 'wiloke-listing-tools')
			]);
		}

		$aParams['objectID'] = $postID;
		$status              = PlanRelationshipModel::upgradeListingToNewPlan($aParams);
		if ($status) {
			return $oRetrieve->success([
				'msg' => esc_html__('The plan has been upgraded successfully', 'wiloke-listing-tools')
			]);
		}

		return $oRetrieve->error([
			'msg' => esc_html__('Something went error! We could not upgrade the Listing to new Plan',
				'wiloke-listing-tools')
		]);
	}

	/**
	 * @param $aResponse
	 * @param $aInformation
	 *
	 * @return mixed
	 */
	public function changeListingsToNewPlan($aResponse, $aInformation)
	{
		$oRetrieve = new RetrieveController(new NormalRetrieve());
		if ($aResponse['status'] == 'error') {
			return $oRetrieve->error($aResponse);
		}

		$aRequires = [
			'oldPlanID'    => esc_html__('The old Plan ID is required', 'wiloke-listing-tools'),
			'oldPaymentID' => esc_html__('The old payment ID is required', 'wiloke-listing-tools'),
			'planID'       => esc_html__('The new Plan ID is required', 'wiloke-listing-tools'),
			'paymentID'    => esc_html__('The new Payment ID is required', 'wiloke-listing-tools')
		];

		$oRetrieve = new RetrieveController(new NormalRetrieve());
		$aParams   = [];
		foreach ($aRequires as $param => $msg) {
			if (!isset($aInformation[$param]) || empty($aInformation[$param])) {
				return $oRetrieve->error(['msg' => $msg]);
			}

			$aParams[$param] = absint($aInformation[$param]);
		}

		if (isset($aInformation['objectID']) && !empty($aInformation['objectID'])) {
			return $this->upgradeListingToNewPlan($aParams, $aInformation['objectID']);
		} else if (isset($aInformation['postIDs']) && !empty($aInformation['postIDs'])) {
			foreach ($aInformation['postIDs'] as $postID) {
				$aStatus = $this->upgradeListingToNewPlan($aParams, $postID);
				if ($aStatus['status'] == 'error') {
					return $aStatus;
				}
			}

			return $aStatus;
		}

		return $oRetrieve->error([
			'msg' => esc_html__('Something went error! We could not upgrade the Listing to new Plan',
				'wiloke-listing-tools')
		]);
	}

	/*
	 * Check whether this product is WooCommerce Subscription or not
	 *
	 * @return bool
	 * @since 1.2.0
	 */
	private function isWooCommerceSubscription($post)
	{
		return class_exists('\WC_Subscriptions_Product') && $post->post_type == 'product';
	}

	/*
	 * Get Listing Plan ID by Product ID
	 *
	 * @return int
	 * @since 1.2.0
	 */
	private function getPlanIDByProductID($productID)
	{
		global $wpdb;

		$productID = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT $wpdb->posts.ID FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON ($wpdb->postmeta.post_id = $wpdb->posts.ID) WHERE $wpdb->posts.post_type=%s AND $wpdb->postmeta.meta_key = %s AND $wpdb->postmeta.meta_value=%s",
				'listing_plan', 'wilcity_woocommerce_association', $productID
			)
		);

		return empty($productID) ? 0 : absint($productID);
	}

	/*
	 * Auto-update Period day and Trial Period day after Production Subscription changed
	 *
	 * @since 1.2.0
	 */
	public function woocommerceSubscriptionReflectPlanPeriodDay($productID, $oAfterPost, $oBeforePost)
	{
		if (!$this->isWooCommerceSubscription($oAfterPost)) {
			return false;
		}

		$listingPlanID = $this->getPlanIDByProductID($productID);
		if (empty($listingPlanID)) {
			return false;
		}

		$periodDays = \WC_Subscriptions_Product::get_length($productID);
	}

	/*
	 * If the session is failed, we will delete this field
	 */
	public function delete($aInfo)
	{
		if ($aInfo['status'] !== 'failed') {
			return false;
		}

		if (!isset($aInfo['planRelationshipID']) || empty($aInfo['planRelationshipID'])) {
			return false;
		}

		global $wpdb;
		$tbl = $wpdb->prefix.AlterTablePlanRelationships::$tblName;

		return $wpdb->delete(
			$tbl,
			[
				'ID' => $aInfo['planRelationshipID']
			],
			[
				'%d'
			]
		);
	}

	/**
	 * After Payment has been completed, We should update Plan Relationship
	 *
	 * @param $aInfo : status, gateway, billingType, paymentID, planID, isTrial, planRelationshipID
	 *
	 * @return bool
	 */
	public function update($aInfo)
	{
		if (!in_array($aInfo['status'], ['active', 'succeeded', 'pending'])) {
			return false;
		}

		if (!isset($aInfo['planRelationshipID']) || empty($aInfo['planRelationshipID'])) {
			return false;
		}

		global $wpdb;
		$tbl = $wpdb->prefix.AlterTablePlanRelationships::$tblName;

		return $wpdb->update(
			$tbl,
			[
				'paymentID' => $aInfo['paymentID']
			],
			[
				'ID' => $aInfo['planRelationshipID']
			],
			[
				'%d'
			],
			[
				'%d'
			]
		);

	}
}
