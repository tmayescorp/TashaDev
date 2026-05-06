<?php

namespace WilokeListingTools\Models;

use WilokeListingTools\AlterTable\AlterTablePaymentHistory;
use WilokeListingTools\AlterTable\AlterTablePlanRelationships;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Frontend\User;

final class PlanRelationshipModel
{
	public static $tbl;

	public static function updatePaymentID($planRelationshipID, $newPaymentID)
	{
		global $wpdb;
		$table = $wpdb->prefix . AlterTablePlanRelationships::$tblName;

		return $wpdb->update(
			$table,
			[
				'paymentID' => $newPaymentID
			],
			[
				'ID' => $planRelationshipID
			],
			[
				'%d'
			],
			[
				'%d'
			]
		);
	}

	public static function deleteObjectIdFromRelationship($id)
	{
		self::delete($id);
	}

	public static function getLastRelationshipIdOfObject($objectID, $planID, $authorID)
	{
		global $wpdb;
		$table = $wpdb->prefix . AlterTablePlanRelationships::$tblName;
		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM $table WHERE objectID = %d AND planID = %d AND userID = %d ORDER BY ID",
				$objectID, $planID, $authorID
			)
		);

		return $id;
	}

	/**
	 * @param $objectID
	 * @param $planID
	 * @param $userID
	 *
	 * @return bool
	 */
	public static function hasTemporaryRelationshipID($objectID, $planID, $userID)
	{
		global $wpdb;
		$table = $wpdb->prefix . AlterTablePlanRelationships::$tblName;
		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM $table WHERE objectID = %d AND planID = %d AND userID = %d AND paymentID=%d",
				$objectID, $planID, $userID, 0
			)
		);

		FileSystem::logAddListing($wpdb->prepare(
				"SELECT ID FROM $table WHERE objectID = %d AND planID = %d AND userID = %d AND paymentID=%d",
				$objectID, $planID, $userID, 0
			) . ' ' . $id);

		return !empty($id);
	}

	/**
	 * For change plan
	 *
	 * var $userID Only administrator can use this param
	 * @since 1.2.0
	 */
	public static function upgradeToNewPaymentID($newPaymentID, $oldPaymentID, $oldPlanID, $newPlanID, $userID = null)
	{
		global $wpdb;

		$table = $wpdb->prefix . AlterTablePlanRelationships::$tblName;

		if (!empty($userID)) {
			if (!current_user_can('administrator')) {
				die;
			}
		}

		$userID = empty($userID) ? User::getCurrentUserID() : absint($userID);

		return $wpdb->update(
			$table,
			[
				'planID'    => $newPlanID,
				'paymentID' => $newPaymentID
			],
			[
				'userID'    => $userID,
				'planID'    => $oldPlanID,
				'paymentID' => $oldPaymentID
			],
			[
				'%d',
				'%d'
			],
			[
				'%d',
				'%d',
				'%d'
			]
		);
	}

	/**
	 * For change plan
	 *
	 * var $userID Only administrator can use this param
	 * @since 1.2.0
	 */
	public static function upgradeListingToNewPlan($aInfo, $userID = null)
	{
		global $wpdb;

		$table = $wpdb->prefix . AlterTablePlanRelationships::$tblName;

		if (!empty($userID)) {
			if (!current_user_can('administrator')) {
				return false;
			}
		}

		$userID = empty($userID) ? User::getCurrentUserID() : absint($userID);

		return $wpdb->update(
			$table,
			[
				'planID'    => $aInfo['planID'],
				'paymentID' => $aInfo['paymentID']
			],
			[
				'userID'    => $userID,
				'planID'    => $aInfo['oldPlanID'],
				'paymentID' => $aInfo['oldPaymentID'],
				'objectID'  => $aInfo['objectID']
			],
			[
				'%d',
				'%d'
			],
			[
				'%d',
				'%d',
				'%d',
				'%d'
			]
		);
	}

	public static function countListingsUserSubmittedInPlan($planID, $userID = '')
	{
		$userID = empty($userID) ? get_current_user_id() : $userID;
		global $wpdb;
		$tbl = $wpdb->prefix . AlterTablePlanRelationships::$tblName;

		$total = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(ID) FROM $tbl WHERE planID=%d AND userID=%d",
				$planID, $userID
			)
		);

		return absint($total);
	}

	public static function getFirstObjectIDByPaymentID($paymentID)
	{
		global $wpdb;
		$tbl = $wpdb->prefix . AlterTablePlanRelationships::$tblName;

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT objectID FROM $tbl WHERE paymentID=%d ORDER BY ID ASC",
				$paymentID
			)
		);
	}

	public static function getLastObjectIDByPaymentID($paymentID)
	{
		global $wpdb;
		$tbl = $wpdb->prefix . AlterTablePlanRelationships::$tblName;

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT objectID FROM $tbl WHERE paymentID=%d ORDER BY ID DESC",
				$paymentID
			)
		);
	}

	/**
	 * @param      $paymentID
	 * @param bool $isParse
	 * @param bool $excludeEvent
	 *
	 * @return array|null|object
	 */
	public static function getObjectIDsByPaymentID($paymentID, $isParse = false, $excludeEvent = false)
	{
		global $wpdb;
		$tbl = $wpdb->prefix . AlterTablePlanRelationships::$tblName;
		$postTbl = $wpdb->posts;

		if ($excludeEvent) {
			$sql = $wpdb->prepare(
				"SELECT objectID FROM $tbl LEFT JOIN $postTbl ON($postTbl.ID = $tbl.objectID) WHERE paymentID=%d AND $postTbl.post_type != %s",
				$paymentID, 'event'
			);
		} else {
			$sql = $wpdb->prepare(
				"SELECT objectID FROM $tbl WHERE paymentID=%d",
				$paymentID
			);
		}

		$aRawObjectIds = $wpdb->get_results(
			$sql,
			ARRAY_A
		);

		if (empty($aRawObjectIds) || is_wp_error($aRawObjectIds)) {
			return [];
		}

		if (!$isParse) {
			return $aRawObjectIds;
		}

		return array_map(function ($aObject) {
			return $aObject['objectID'];
		}, $aRawObjectIds);
	}

	/**
	 * @param $paymentID
	 * @param $paged
	 * @param $limit
	 * @return array|object|null
	 */
	public static function getObjectIDs($paymentID, $paged, $limit)
	{
		global $wpdb;
		$tbl = $wpdb->prefix . AlterTablePlanRelationships::$tblName;
		$postTbl = $wpdb->posts;
		$offset = ($paged - 1) * $limit;
		$sql = $wpdb->prepare(
			"SELECT $postTbl.ID,$postTbl.post_title,$postTbl.post_status FROM $postTbl LEFT JOIN $tbl ON($postTbl.ID = $tbl.objectID) WHERE $tbl.paymentID=%d
				LIMIT $limit OFFSET $offset",
			$paymentID
		);

		return $wpdb->get_results(
			$sql,
			ARRAY_A
		);
	}

	/**
	 * @param $paymentID
	 * @return string|null
	 */
	public static function getObjectIDTotal($paymentID)
	{
		global $wpdb;
		$tbl = $wpdb->prefix . AlterTablePlanRelationships::$tblName;
		$postTbl = $wpdb->posts;

		$sql = $wpdb->prepare(
			"SELECT count(*) FROM $postTbl LEFT JOIN $tbl ON($postTbl.ID = $tbl.objectID) WHERE $tbl.paymentID=%d",
			$paymentID
		);

		return $wpdb->get_var($sql);
	}

	public static function isPlanExisting($aInfo)
	{
		global $wpdb;
		$tbl = $wpdb->prefix . AlterTablePlanRelationships::$tblName;

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM $tbl WHERE planID=%d AND objectID=%d AND paymentID=%d AND userID=%d",
				$aInfo['planID'], $aInfo['objectID'], $aInfo['paymentID'], $aInfo['userID']
			)
		);
	}

	public static function isObjectExisting($objectID, $userID = null)
	{
		global $wpdb;
		$tbl = $wpdb->prefix . AlterTablePlanRelationships::$tblName;
		$userID = empty($userID) ? User::getCurrentUserID() : $userID;

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM $tbl WHERE objectID=%d AND userID=%d",
				$objectID, $userID
			)
		);
	}

	public static function getField($field, $planRelationshipID)
	{
		global $wpdb;
		$tbl = $wpdb->prefix . AlterTablePlanRelationships::$tblName;

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT $field FROM $tbl WHERE ID=%d",
				$planRelationshipID
			)
		);
	}

	public static function getFields($planRelationshipID)
	{
		global $wpdb;
		$tbl = $wpdb->prefix . AlterTablePlanRelationships::$tblName;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $tbl WHERE ID=%d",
				$planRelationshipID
			),
			ARRAY_A
		);
	}

	public static function getPlanIDByProductID($productID)
	{
		global $wpdb;
		$tbl = $wpdb->postmeta;

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM $tbl WHERE meta_key=%s AND meta_value=%d",
				'wilcity_woocommerce_association', $productID
			)
		);
	}

	public static function deleteAllRelationshipBetweenProductAndPlan($productID)
	{
		global $wpdb;
		$tbl = $wpdb->postmeta;

		return $wpdb->delete(
			$tbl,
			[
				'meta_key'   => 'wilcity_woocommerce_association',
				'meta_value' => $productID
			],
			[
				'%s',
				'%d'
			]
		);
	}

	public static function getIDByObjectID($objectID)
	{
		global $wpdb;
		$tbl = $wpdb->prefix . AlterTablePlanRelationships::$tblName;

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM $tbl WHERE objectID = %d",
				$objectID
			)
		);
	}

	public static function getUserIDByObjectID($objectID)
	{
		global $wpdb;
		$tbl = $wpdb->prefix . AlterTablePlanRelationships::$tblName;

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT userID FROM $tbl WHERE objectID = %d",
				$objectID
			)
		);
	}

	public static function getPaymentIDByObjectID($objectID)
	{
		global $wpdb;
		$tbl = $wpdb->prefix . AlterTablePlanRelationships::$tblName;

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT paymentID FROM $tbl WHERE objectID = %d",
				$objectID
			)
		);
	}

	public static function update($id, $aInfo)
	{
		$aValues = [];
		$aPrepares = [];
		foreach ($aInfo as $key => $val) {
			$aValues[$key] = $val;
			$aPrepares[] = '%d';
		}

		$aValues['updatedAtGMT'] = Time::getAtomUTCString();
		$aPrepares[] = '%s';

		global $wpdb;
		$tbl = $wpdb->prefix . AlterTablePlanRelationships::$tblName;

		$status = $wpdb->update(
			$tbl,
			$aValues,
			[
				'ID' => $id
			],
			$aPrepares,
			[
				'%d'
			]
		);

		if ($status) {
			/**
			 * @hooked WilokeListingTools\Controllers\UserPlanController:updateRemainingItems 10
			 */
			$aInfo['planRelantionshipID'] = $id;
			do_action('wilcity/wiloke-listing-tools/updated-plan-relationship', $aInfo, $logIfError = true);
		}

		return $status;
	}

	/*
	 * @param array $aInfo: planID, objectID, userID, paymentID
	 */
	public static function setPlanRelationship($aInfo)
	{
		global $wpdb;
		$tbl = $wpdb->prefix . AlterTablePlanRelationships::$tblName;

		if (empty($aInfo['objectID'])) {
			return false;
		}

		$isPlanExisting = self::isObjectExisting($aInfo['objectID'], $aInfo['userID']);
		if ($isPlanExisting) {
			$planRelationshipID = $isPlanExisting;
			$wpdb->update(
				$tbl,
				[
					'updatedAtGMT' => Time::getAtomUTCString(),
					'planID'       => $aInfo['planID'],
					'paymentID'    => $aInfo['paymentID']
				],
				[
					'ID' => $planRelationshipID
				],
				[
					'%s',
					'%d',
					'%d'
				],
				[
					'%d'
				]
			);
		} else {
			$wpdb->insert(
				$tbl,
				[
					'planID'       => $aInfo['planID'],
					'objectID'     => $aInfo['objectID'],
					'userID'       => $aInfo['userID'],
					'paymentID'    => $aInfo['paymentID'],
					'updatedAtGMT' => Time::getAtomUTCString()
				],
				[
					'%d',
					'%d',
					'%d',
					'%d',
					'%s'
				]
			);
			$planRelationshipID = $wpdb->insert_id;
		}

		Session::setSession(wilokeListingToolsRepository()->get('payment:sessionRelationshipStore'),
			$planRelationshipID);

		/**
		 * @hooked WilokeListingTools\Controllers\UserPlanController:updateRemainingItems
		 */
		$aInfo['planRelantionshipID'] = $planRelationshipID;
		do_action('wilcity/wiloke-listing-tools/added-plan-relationship', $aInfo, $logIfError = true);

		return $planRelationshipID;
	}

	public static function getPaymentIDByPlanIDAndObjectID($planID, $objectID)
	{
		global $wpdb;
		$tbl = $wpdb->prefix . AlterTablePlanRelationships::$tblName;

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT paymentID FROM $tbl WHERE planID=%d AND objectID=%d ORDER BY ID DESC",
				$planID, $objectID
			)
		);
	}

	public static function delete($id)
	{
		global $wpdb;
		$tbl = $wpdb->prefix . AlterTablePlanRelationships::$tblName;
		$aInfo = self::getFields($id);

		$status = $wpdb->delete(
			$tbl,
			[
				'ID' => $id
			],
			[
				'%d'
			]
		);

		if ($status) {
			/**
			 * @hooked WilokeListingTools\Controllers\UserPlanController:updateRemainingItems
			 */
			do_action('wilcity/wiloke-listing-tools/deleted-plan-relationship', $aInfo, $logIfError = true);
		}

		return $status;
	}

	public static function getUsedNonRecurringPlan(RemainingItems $that)
	{
		global $wpdb;
		$planRelationshipTbl = $wpdb->prefix . AlterTablePlanRelationships::$tblName;
		$postTbl = $wpdb->posts;
		$historyTbl = $wpdb->prefix . AlterTablePaymentHistory::$tblName;

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT($planRelationshipTbl.objectID) FROM $planRelationshipTbl LEFT JOIN $postTbl ON ($postTbl
.ID=$planRelationshipTbl.objectID) LEFT JOIN $historyTbl ON ($historyTbl.ID=$planRelationshipTbl
.paymentID) WHERE $planRelationshipTbl.objectID != 0 AND $planRelationshipTbl.planID=%d AND
$planRelationshipTbl.paymentID=%d AND $planRelationshipTbl.userID=%d",
				$that->getPlanID(), $that->getPaymentID(), $that->getUserID()
			)
		);
	}

	public static function getUsedRecurringPlan(RemainingItems $that): int
    {
		global $wpdb;
		$planRelationshipTbl = $wpdb->prefix . AlterTablePlanRelationships::$tblName;
		$timestampToDate = Time::toAtomUTC($that->getNextBillingDateGMT());

		return (int)$wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT($planRelationshipTbl.objectID) FROM $planRelationshipTbl WHERE $planRelationshipTbl.objectID !=
 0 AND $planRelationshipTbl.planID=%d AND $planRelationshipTbl.paymentID=%d AND $planRelationshipTbl.userID=%d AND $planRelationshipTbl.updatedAtGMT >= (%s - INTERVAL %d DAY)",
				$that->getPlanID(), $that->getPaymentID(), $that->getUserID(), $timestampToDate, $that->getDuration()
			)
		);
	}
}
