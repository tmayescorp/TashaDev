<?php

namespace WilokeListingTools\Models;

use WilokeListingTools\AlterTable\AlterTablePaymentHistory;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Frontend\User;

class PaymentModel
{
    public static $aOrderOrderAndPaymentsRelationship = [];

    public static function getPackageTypeByOrderID($orderID)
    {
        global $wpdb;
        $tblName = $wpdb->prefix . AlterTablePaymentHistory::$tblName;

        $packageType = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT packageType FROM $tblName WHERE wooOrderID=%d ORDER BY ID DESC",
                $orderID
            )
        );

        return $packageType;
    }

    public static function delete($paymentID)
    {
        global $wpdb;
        $tblName = $wpdb->prefix . AlterTablePaymentHistory::$tblName;

        return $wpdb->delete(
            $tblName,
            [
                'ID' => $paymentID
            ],
            [
                '%d'
            ]
        );
    }

    public static function getPaymentIDsByWooOrderID($orderID, $isGetLatestPaymentID = false)
    {
        if (isset(self::$aOrderOrderAndPaymentsRelationship[$orderID])) {
            if ($isGetLatestPaymentID) {
                $aLastItem = end(self::$aOrderOrderAndPaymentsRelationship[$orderID]);

                return $aLastItem['ID'];
            }

            return self::$aOrderOrderAndPaymentsRelationship[$orderID];
        } else {
            global $wpdb;
            $tblName = $wpdb->prefix . AlterTablePaymentHistory::$tblName;
            self::$aOrderOrderAndPaymentsRelationship[$orderID] = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT ID FROM $tblName WHERE wooOrderID=%d",
                    $orderID
                ),
                ARRAY_A
            );

            if ($isGetLatestPaymentID) {
                $aLastItem = end(self::$aOrderOrderAndPaymentsRelationship[$orderID]);

                return $aLastItem['ID'];
            }

            return self::$aOrderOrderAndPaymentsRelationship[$orderID];
        }
    }

    public static function getPaymentIDByOrderIDAndPlanID($orderID, $planID)
    {
        global $wpdb;
        $tblName = $wpdb->prefix . AlterTablePaymentHistory::$tblName;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM $tblName WHERE wooOrderID=%d AND planID=%d",
                $orderID, $planID
            )
        );
    }

    /**
     * @param $planID
     *
     * @return array|bool
     */
    public static function getPaymentIDHasRemainingItemsByPlanID($planID)
    {
        global $wpdb;
        $tblName = $wpdb->prefix . AlterTablePaymentHistory::$tblName;

        $aPayments = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, gateway, billingType FROM $tblName WHERE userID=%d AND planID=%d AND status NOT IN('pending', 'suspended', 'failed') ORDER BY ID DESC",
                User::getCurrentUserID(), $planID
            ),
            ARRAY_A
        );

        if (empty($aPayments)) {
            return false;
        }

        $oRemainingItems = new RemainingItems;
        $oRemainingItems->setUserID(User::getCurrentUserID())->setPlanID($planID);

        foreach ($aPayments as $aPayment) {
            $remainingItems
                = $oRemainingItems->setGateway($aPayment['gateway'])->setBillingType($aPayment['billingType'])
                ->setPaymentID($aPayment['ID'])
                ->getRemainingItems();
            if ($remainingItems > 0) {
                return [
                    'paymentID'      => $aPayment['ID'],
                    'remainingItems' => $remainingItems
                ];
            }
        }

        return false;
    }

    public static function getLastDirectBankTransferStatus($userID, $planID)
    {
        global $wpdb;
        $tblName = $wpdb->prefix . AlterTablePaymentHistory::$tblName;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT status FROM $tblName WHERE userID=%d AND planID=%d and gateway=%s ORDER BY ID DESC",
                $userID, $planID, 'banktransfer'
            )
        );
    }

    public static function getLastDirectBankTransferID($userID, $planID)
    {
        global $wpdb;
        $tblName = $wpdb->prefix . AlterTablePaymentHistory::$tblName;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM $tblName WHERE userID=%d AND planID=%d and gateway=%s ORDER BY ID DESC",
                $userID, $planID, 'banktransfer'
            )
        );
    }

    /**
     * Get Payment Field by specifying payment id
     *
     * @param $paymentID @number
     */
    public static function getField($fieldName, $paymentID)
    {
        global $wpdb;
        $tblName = $wpdb->prefix . AlterTablePaymentHistory::$tblName;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT $fieldName FROM $tblName WHERE ID=%d",
                $paymentID
            )
        );
    }

    /**
     * @param $paymentID
     *
     * @return array|null|object
     */
    public static function getPaymentInfo($paymentID)
    {
        global $wpdb;
        $tblName = $wpdb->prefix . AlterTablePaymentHistory::$tblName;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $tblName WHERE ID=%d",
                $paymentID
            ),
            ARRAY_A
        );
    }

    public static function getUserPaymentStatus($userID, $paymentID)
    {
        global $wpdb;
        $tblName = $wpdb->prefix . AlterTablePaymentHistory::$tblName;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT status FROM $tblName WHERE userID=%d AND ID=%d",
                $userID, $paymentID
            )
        );
    }

    public static function isMyPaymentSession($userID, $paymentID)
    {
        global $wpdb;
        $tblName = $wpdb->prefix . AlterTablePaymentHistory::$tblName;

        $id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM $tblName WHERE userID=%d AND ID=%d",
                $userID, $paymentID
            )
        );

        return !empty($id);
    }

    public static function insertPaymentHistory($aData, $orderID = 0)
    {
        global $wpdb;
        $tblName = $wpdb->prefix . AlterTablePaymentHistory::$tblName;

        $status = $wpdb->insert(
            $tblName,
            [
                'userID'      => $aData['userID'],
                'planID'      => $aData['planID'],
                'packageType' => $aData['packageType'],
                'gateway'     => $aData['gateway'],
                'status'      => !isset($aData['status']) ? 'pending' : trim($aData['status']),
                'wooOrderID'  => $orderID, // for woocommerce
                'billingType' => $aData['billingType'],
                'updatedAt'   => Time::mysqlDateTimeWithWebsiteTimezone()
            ],
            [
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
                '%d',
                '%s',
                '%s'
            ]
        );

        if ($status) {
            $paymentID = $wpdb->insert_id;
            if (isset($aData['planName']) && !empty($aData['planName'])) {
                PaymentMetaModel::set($paymentID, 'planName', $aData['planName']);
            }

            return $paymentID;
        }

        return false;
    }

    /**
     * Inserting a new data to wilcity_payment_history table
     *
     * @param $that     object an instance of payment gateway
     * @param $oReceipt object
     *
     * @return $sessionID
     */
    public static function setPaymentHistory($that, $oReceipt, $orderID = null)
    {
        global $wpdb;
        $tblName = $wpdb->prefix . AlterTablePaymentHistory::$tblName;

        $status = $wpdb->insert(
            $tblName,
            [
                'userID'      => $oReceipt->userID,
                'planID'      => $oReceipt->planID,
                'packageType' => $oReceipt->getPackageType(),
                'gateway'     => $that->gateway,
                'status'      => 'pending',
                'wooOrderID'  => !empty($orderID) ? $orderID : 0,
                'billingType' => $that->getBillingType(),
                'updatedAt'   => Time::mysqlDateTimeWithWebsiteTimezone()
            ],
            [
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
                '%d',
                '%s',
                '%s'
            ]
        );
        $paymentID = $wpdb->insert_id;
        if ($status) {
            if (empty($oReceipt->planID)) {
                PaymentMetaModel::set($paymentID, 'planName', $oReceipt->getPlanName());
            }

            return $paymentID;
        }

        return false;
    }

    /*
     * Updating Payment Status
     *
     * @param $paymentID number
     * @param $status string
     *
     * @return bool
     */
    public static function updatePaymentStatus($status, $paymentID)
    {
        global $wpdb;
        $tblName = $wpdb->prefix . AlterTablePaymentHistory::$tblName;

        $updateStatus = $wpdb->update(
            $tblName,
            [
	            'status'    => $status,
	            'updatedAt' => Time::mysqlDateTimeWithWebsiteTimezone(),
            ],
            [
                'ID' => $paymentID
            ],
            [
                '%s',
                '%s'
            ],
            [
                '%d'
            ]
        );

        if ($updateStatus) {
            FileSystem::logSuccess(sprintf('Updated payment %s to new status %s', $paymentID, $status),
                __CLASS__);
            do_action('wilcity/wiloke-listing-tools/app/Models/PaymentModel/updatePaymentStatus/' . $status,
                $paymentID);
            return true;
        }

        FileSystem::logError(sprintf('We could update payment %s to new status %s', $paymentID, $status),
            __CLASS__);

        return false;
    }

    public static function getLastSuspendedByPlan($planID, $userID)
    {
        global $wpdb;
        $tblName = $wpdb->prefix . AlterTablePaymentHistory::$tblName;

        $id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM $tblName WHERE planID=%d AND userID=%d AND  status=%s ORDER BY ID DESC",
                $planID, $userID, 'suspended'
            )
        );

        return absint($id);
    }

    public static function getLastPaymentID($userID)
    {
        global $wpdb;
        $tblName = $wpdb->prefix . AlterTablePaymentHistory::$tblName;

        $id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM $tblName WHERE userID=%d AND  status=%s ORDER BY ID DESC",
                $userID, 'active'
            )
        );

        return absint($id);
    }

    public static function getLastRecurringPaymentID($userID, $gateway = '')
    {
        global $wpdb;
        $tblName = $wpdb->prefix . AlterTablePaymentHistory::$tblName;

        $sql = $wpdb->prepare(
            "SELECT ID FROM $tblName WHERE userID=%d AND status=%s AND billingType=%s",
            $userID, 'active', wilokeListingToolsRepository()->get('payment:billingTypes', true)->sub('recurring')
        );

        if (!empty($gateway)) {
            $sql = $wpdb->prepare(
                $sql . " AND gateway=%s",
                $gateway
            );
        }


        $id = $wpdb->get_var(
            $sql
        );

        return absint($id);
    }

    public static function getLastNonRecurringPaymentID($userID, $gateway = '')
    {
        global $wpdb;
        $tblName = $wpdb->prefix . AlterTablePaymentHistory::$tblName;

        $sql = $wpdb->prepare(
            "SELECT ID FROM $tblName WHERE userID=%d AND  status=%s AND billingType=%s",
            $userID, 'active', wilokeListingToolsRepository()->get('payment:billingTypes', true)->sub('nonrecurring')
        );

        if (!empty($gateway)) {
            $sql = $wpdb->prepare(
                $sql . " AND gateway=%s",
                $gateway
            );
        }


        $id = $wpdb->get_var(
            $sql
        );

        return absint($id);
    }

    public static function getPaymentSessionsOfUser($userID, $aStatus, $offset = 0, $limit = 10)
    {
        global $wpdb;
        $tblName = $wpdb->prefix . AlterTablePaymentHistory::$tblName;

        $aStatus = array_map(function ($status) {
            global $wpdb;

            return $wpdb->_real_escape($status);
        }, $aStatus);
        $aStatus = '"' . implode('","', $aStatus) . '"';

        $aPayments = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $tblName WHERE userID=%d AND status IN (" . $aStatus . ") ORDER BY ID DESC LIMIT %d, %d",
                $userID, $offset, $limit
            )
        );

        return empty($aPayments) ? [] : $aPayments;
    }

    public static function getWooCommercePaymentGateway($wooOrderId): ?string
    {
        $oOrder = wc_get_order($wooOrderId);
        if (empty($oOrder) || is_wp_error($oOrder)) {
            return null;
        }
        return $oOrder->get_payment_method();
    }

    public static function isRecurringPayment($paymentID)
    {
        return self::getField('billingType', $paymentID) ===
            wilokeListingToolsRepository()->get('payment:billingTypes', true)->sub('recurring');
    }
}
