<?php

namespace WilokeListingTools\Models;

use WilokeListingTools\AlterTable\AlterTableNotifications;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Frontend\User;

class NotificationsModel
{
    public static $countNewKey = 'count_new_notifications';

    public static function getField($field, $id)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . AlterTableNotifications::$tblName;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT %s FROM %s WHERE ID = %d",
            $field, $tbl, $id
        ));
    }

    public static function delete($id)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . AlterTableNotifications::$tblName;

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
            $receiverID = self::getField('receiverID', $id);
            if (!empty($receiverID)) {
                $countNewsNotifications = GetSettings::getUserMeta($receiverID, self::$countNewKey);
                $countNewsNotifications = empty($countNewsNotifications) ? 0 : absint($countNewsNotifications);
                $countNewsNotifications = $countNewsNotifications - 1;

                SetSettings::setUserMeta($receiverID, self::$countNewKey, $countNewsNotifications);
            }
        }
    }

    public static function get($receiverID, $limit = 10, $offset = 0)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . AlterTableNotifications::$tblName;

        $aResults = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT SQL_CALC_FOUND_ROWS * FROM $tbl WHERE receiverID=%d ORDER BY ID DESC LIMIT $offset,$limit",
                $receiverID
            )
        );

        if (empty($aResults) || is_wp_error($aResults)) {
            return false;
        }

        return [
            'aResults' => $aResults,
            'total'    => $wpdb->get_var("SELECT FOUND_ROWS()")
        ];
    }

    public static function deleteOfReceiver($id, $receiverID)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . AlterTableNotifications::$tblName;

        return $wpdb->delete(
            $tbl,
            [
                'ID'         => $id,
                'receiverID' => $receiverID
            ],
            [
                '%d',
                '%d'
            ]
        );
    }

    public static function isJustNotifiedOneMinutesAgo($receiverID, $type, $objectID, $xMinutesAgo = 1): bool
    {
        global $wpdb;
        $tbl = $wpdb->prefix . AlterTableNotifications::$tblName;

        $timestamp = current_time('timestamp');
        $endDate = date('Y-m-d H:i:s', $timestamp);
        $startDate = date('Y-m-d H:i:s', strtotime($endDate . ' -' . $xMinutesAgo . ' minutes'));


        $val = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM " . $tbl .
                " WHERE type=%s AND objectID=%d AND receiverID=%d AND date >=%s AND date<=%s",
                $type,
                $objectID,
                $receiverID,
                $startDate,
                $endDate
            )
        );

        return !empty($val);
    }

    public static function add($receiverID, $type, $objectID = null, $senderID = null)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . AlterTableNotifications::$tblName;
        $senderID = empty($senderID) ? User::getCurrentUserID() : $senderID;

        if (empty($objectID)) {
            $status = $wpdb->insert(
                $tbl,
                [
                    'receiverID' => $receiverID,
                    'senderID'   => $senderID,
                    'type'       => $type,
                    'date'       => date('Y-m-d H:i:s', current_time('timestamp'))
                ],
                [
                    '%d',
                    '%d',
                    '%s',
                    '%s'
                ]
            );
        } else {
            $status = $wpdb->insert(
                $tbl,
                [
                    'receiverID' => $receiverID,
                    'senderID'   => $senderID,
                    'type'       => $type,
                    'objectID'   => $objectID,
                    'date'       => date('Y-m-d H:i:s', current_time('timestamp'))
                ],
                [
                    '%d',
                    '%d',
                    '%s',
                    '%d',
                    '%s'
                ]
            );
        }

        if (!$status) {
            return false;
        }

        $countNewsNotifications = GetSettings::getUserMeta($receiverID, self::$countNewKey);
        $countNewsNotifications = empty($countNewsNotifications) ? 0 : absint($countNewsNotifications);
        $countNewsNotifications = $countNewsNotifications + 1;

        SetSettings::setUserMeta($receiverID, self::$countNewKey, $countNewsNotifications);

        return $wpdb->insert_id;
    }

    public static function countAllNotificationOfUser($userID)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . AlterTableNotifications::$tblName;

        $status = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(ID) FROM $tbl WHERE receiverID = %d ",
                $userID
            )
        );

        return empty($status) ? 0 : absint($status);
    }

    public static function update($id, $aArgs)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . AlterTableNotifications::$tblName;

        return $wpdb->update(
            $tbl,
            $aArgs['aInfo'],
            [
                'ID' => $id
            ],
            $aArgs['aPrepare'],
            [
                '%d'
            ]
        );
    }
}
