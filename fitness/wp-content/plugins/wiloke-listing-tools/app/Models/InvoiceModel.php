<?php

namespace WilokeListingTools\Models;

use WilokeListingTools\AlterTable\AlterTableInvoices;
use WilokeListingTools\AlterTable\AlterTablePaymentHistory;
use WilokeListingTools\Framework\Helpers\Price;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Frontend\User;

class InvoiceModel
{
    public static function delete($invoiceID)
    {
        global $wpdb;
        $table = $wpdb->prefix . AlterTableInvoices::$tblName;

        return $wpdb->delete(
            $table,
            [
                'ID' => $invoiceID
            ],
            [
                '%d'
            ]
        );
    }

    public static function getMyInvoices($limit = 20, $offset = 0, $userID = null)
    {
        $userID = empty($userID) ? User::getCurrentUserID() : $userID;

        global $wpdb;
        $invoiceTbl = $wpdb->prefix . AlterTableInvoices::$tblName;
        $paymentHistoryTbl = $wpdb->prefix . AlterTablePaymentHistory::$tblName;

        $aResults = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT SQL_CALC_FOUND_ROWS $invoiceTbl.*, $paymentHistoryTbl.gateway FROM $invoiceTbl LEFT JOIN $paymentHistoryTbl ON ($paymentHistoryTbl.ID=$invoiceTbl.paymentID) WHERE $paymentHistoryTbl.userID=%d ORDER BY $invoiceTbl.created_at DESC LIMIT %d, %d",
                $userID, $offset, $limit
            ),
            ARRAY_A
        );

        if (empty($aResults)) {
            return false;
        }

        $total = $wpdb->get_var("SELECT FOUND_ROWS()");

        return [
            'aInvoices' => apply_filters('wiloke-listing-tools/filter/app/Models/InvoiceModel/getMyInvoices', $aResults),
            'total'     => $total,
            'maxPages'  => ceil($total / $limit)
        ];
    }

    public static function getInvoiceDetails($invoiceID, $userID = '', $isPriceFormat = false)
    {
        if (empty($userID)) {
            $userID = User::getCurrentUserID();
        }

        global $wpdb;
        $invoiceTbl = $wpdb->prefix . AlterTableInvoices::$tblName;
        $paymentHistoryTbl = $wpdb->prefix . AlterTablePaymentHistory::$tblName;

        $aResult = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT $invoiceTbl.*, $paymentHistoryTbl.gateway, $paymentHistoryTbl.planID, $paymentHistoryTbl.ID as paymentID FROM $invoiceTbl LEFT JOIN $paymentHistoryTbl ON ($paymentHistoryTbl.ID=$invoiceTbl.paymentID) WHERE $paymentHistoryTbl.userID=%d AND $invoiceTbl.ID=%d",
                $userID, $invoiceID
            ),
            ARRAY_A
        );

        if (empty($aResult)) {
            return false;
        }

        $planName = get_the_title($aResult['planID']);
        if (empty($planName)) {
            $planName = PaymentMetaModel::get($aResult['paymentID'], 'planName');
        }
        $aResult['planName']
            = !empty($planName) ? $planName : esc_html__('This plan might have been deleted', 'wiloke-listing-tools');

        if ($isPriceFormat) {
            $aResult['subTotal'] = Price::moneyFormat($aResult['subTotal']);
            $aResult['total'] = Price::moneyFormat($aResult['total']);
            $aResult['tax'] = Price::moneyFormat($aResult['tax']);
            $aResult['discount'] = Price::moneyFormat($aResult['discount']);
        }

        return apply_filters('wiloke-listing-tools/filter/app/Models/InvoiceModel/getInvoiceDetails', $aResult);
    }

    public static function deleteAll()
    {
        global $wpdb;
        $table = $wpdb->prefix . AlterTableInvoices::$tblName;

        return $wpdb->query("DELETE FROM $table");
    }

    public static function getInvoiceIDByPaymentID($paymentID)
    {
        global $wpdb;
        $table = $wpdb->prefix . AlterTableInvoices::$tblName;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM $table WHERE paymentID=%d",
                $paymentID
            )
        );
    }

    public static function getField($fieldName, $invoiceID)
    {
        global $wpdb;
        $table = $wpdb->prefix . AlterTableInvoices::$tblName;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT $fieldName FROM $table WHERE ID=%d",
                $invoiceID
            )
        );
    }

    public static function getAll($invoiceID)
    {
        global $wpdb;
        $table = $wpdb->prefix . AlterTableInvoices::$tblName;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE ID=%d",
                $invoiceID
            ),
            ARRAY_A
        );
    }

    /*
     * @param $paymentID
     * @param $aData
     */
    public static function set($paymentID, $aData)
    {
        global $wpdb;
        $table = $wpdb->prefix . AlterTableInvoices::$tblName;

        $status = $wpdb->insert(
            $table,
            [
                'paymentID'  => $paymentID,
                'currency'   => strtolower($aData['currency']),
                'subTotal'   => $aData['subTotal'],
                'discount'   => isset($aData['discount']) ? absint($aData['discount']) : 0,
                'tax'        => isset($aData['tax']) ? absint($aData['tax']) : 0,
                'total'      => $aData['total'],
                'updated_at' => Time::mysqlDateTimeWithWebsiteTimezone()
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s'
            ]
        );

        if ($status) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * @param $invoiceID
     * @param $paymentID
     * @param $aData
     *
     * @return bool|int
     */
    public static function update($invoiceID, $paymentID, $aData)
    {
        global $wpdb;
        $table = $wpdb->prefix . AlterTableInvoices::$tblName;

        $status = $wpdb->update(
            $table,
            [
                'paymentID' => $paymentID,
                'currency'  => strtolower($aData['currency']),
                'subTotal'  => $aData['subTotal'],
                'discount'  => isset($aData['discount']) ? absint($aData['discount']) : 0,
                'tax'       => isset($aData['tax']) ? absint($aData['tax']) : 0,
                'total'     => $aData['total']
            ],
            [
                'ID' => $invoiceID
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s'
            ],
            [
                '%d'
            ]
        );

        return $status;
    }
}
