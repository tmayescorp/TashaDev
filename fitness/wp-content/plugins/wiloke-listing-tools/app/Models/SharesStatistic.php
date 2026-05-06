<?php

namespace WilokeListingTools\Models;

use WilokeListingTools\AlterTable\AlterTableSharesStatistic;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\Time;

class SharesStatistic
{
    private static $cacheFile = 'count-shares.txt';
    protected static $tableName;

    public static function tableName()
    {
        global $wpdb;
        self::$tableName = $wpdb->prefix.AlterTableSharesStatistic::$tblName;
    }

    public static function getShareToday($userID)
    {
        $today      = Time::mysqlDate(\time());
        $shareToday = SharesStatistic::getTotalSharesOfAuthorInDay($userID, $today);
        if (empty($shareToday)) {
            $shareToday = 0;
        }

        return $shareToday;
    }

    public static function compareSharesByWeek($authorID, $postID)
    {
        $mondayThisWeek = Time::mysqlDate(strtotime('monday this week'));
        $sundayThisWeek = Time::mysqlDate(strtotime('sunday this week'));

        $mondayLastWeek = Time::mysqlDate(strtotime('monday last week'));
        $sundayLastWeek = Time::mysqlDate(strtotime('sunday last week'));

        $totalSharesLastWeek = self::getTotalSharesInRange($authorID, $mondayLastWeek, $sundayLastWeek, $postID);
        $totalSharesThisWeek = self::getTotalSharesInRange($authorID, $mondayThisWeek, $sundayThisWeek, $postID);

        $shareToday          = self::getShareToday($authorID);
        $totalSharesThisWeek = $totalSharesThisWeek + $shareToday;

        return [
            'current' => $totalSharesThisWeek,
            'past'    => $totalSharesLastWeek
        ];
    }

    public static function compare($authorID, $postID = null, $compareBy = 'week')
    {
        $totalShares = self::getTotalSharesOfAuthor($authorID);

        switch ($compareBy) {
            case 'week':
                $aTotalShares = self::compareSharesByWeek($authorID, $postID);
                break;
        }
        $changing = $aTotalShares['current'] - $aTotalShares['past'];

        $status = 'up';
        if ($changing == 0) {
            $representColor = '';
        } else if ($changing > 0) {
            $representColor = 'green';
        } else {
            $representColor = 'red';
            $status         = 'down';
        }

        return [
            'total'          => $totalShares,
            'totalCurrent'   => $aTotalShares['current'], // EG: Total views on this week
            'diff'           => $changing,
            'representColor' => $representColor,
            'status'         => $status
        ];
    }

    public static function countAllShared()
    {
        global $wpdb;
        self::tableName();
        $statisticTbl = self::$tableName;

        $total = $wpdb->get_var("SELECT SUM($statisticTbl.countShares) FROM $statisticTbl");

        return absint($total);
    }

    public static function getTotalSharesOfAuthorInDay($userID, $day)
    {
        global $wpdb;
        $postsTbl = $wpdb->posts;
        self::tableName();
        $statisticTbl = self::$tableName;

        $post_types = \WilokeListingTools\Framework\Helpers\General::getPostTypeKeys(false, false);
        $post_types = implode("','", $post_types);

        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM($statisticTbl.countShares) FROM $statisticTbl LEFT JOIN $postsTbl ON ($postsTbl.ID = $statisticTbl.objectID) WHERE $postsTbl.post_status=%s AND $postsTbl.post_type IN ('".
                $post_types."') AND $postsTbl.post_author=%d AND $statisticTbl.date=%s",
                'publish', $userID, $day
            )
        );

        return $total ? absint($total) : 0;
    }

    public static function getTotalSharesInRange($userID, $start, $end, $postID = null)
    {
        global $wpdb;
        $postsTbl = $wpdb->posts;
        self::tableName();
        $statisticTbl = self::$tableName;
        $query        =
            "SELECT SUM($statisticTbl.countShares) FROM $statisticTbl LEFT JOIN $postsTbl ON ($postsTbl.ID = $statisticTbl.objectID) WHERE $postsTbl.post_status=%s AND $postsTbl.post_author=%d AND $statisticTbl.date BETWEEN %s AND %s";

        if (!empty($postID)) {
            $query .= " AND $statisticTbl.objectID=%d";
            $total = $wpdb->get_var(
                $wpdb->prepare(
                    $query,
                    'publish', $userID, $start, $end, $postID
                )
            );
        } else {
            $post_types = \WilokeListingTools\Framework\Helpers\General::getPostTypeKeys(false, false);
            $post_types = implode("','", $post_types);

            $query .= " AND $postsTbl.post_type IN ('".$post_types."')";

            $total = $wpdb->get_var(
                $wpdb->prepare(
                    $query,
                    'publish', $userID, $start, $end
                )
            );
        }

        return $total ? absint($total) : 0;
    }

    public static function getTotalSharesOfAuthor($userID)
    {
        global $wpdb;
        $postsTbl = $wpdb->posts;
        self::tableName();
        $statisticTbl = self::$tableName;
        $post_types   = \WilokeListingTools\Framework\Helpers\General::getPostTypeKeys(false, false);
        $post_types   = implode("','", $post_types);

        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM($statisticTbl.countShares) FROM $statisticTbl LEFT JOIN $postsTbl ON ($postsTbl.ID = $statisticTbl.objectID) WHERE $postsTbl.post_status=%s AND $postsTbl.post_type IN ('".
                $post_types."') AND $postsTbl.post_author=%d",
                'publish', $userID
            )
        );

        return $total ? absint($total) : 0;
    }

    public static function insert($postID, $totalViews = 1)
    {
        global $wpdb;
        self::tableName();

        $status = $wpdb->insert(
            self::$tableName,
            [
                'objectID'    => $postID,
                'countShares' => $totalViews,
                'date'        => current_time('mysql')
            ],
            [
                '%d',
                '%d',
                '%s'
            ]
        );

        return $status ? $wpdb->insert_id : false;
    }

    public static function countSharedInDay($postID, $day)
    {
        global $wpdb;
        self::tableName();

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(countShares) FROM ".self::$tableName." WHERE objectID=%d AND date=%s",
                $postID, $day
            )
        );
    }

    public static function countShared($postID)
    {
        global $wpdb;
        self::tableName();

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(countShares) FROM ".self::$tableName." WHERE objectID=%d",
                $postID
            )
        );

        return absint($count);
    }

    public static function update($postID)
    {
        global $wpdb;
        self::tableName();

        $aData = self::isTodayCreated($postID);

        if (!$aData) {
            self::insert($postID);
        } else {
            $countShared = absint($aData['countShares']);
            $countShared = $countShared + 1;

            return $wpdb->update(
                self::$tableName,
                [
                    'countShares' => $countShared
                ],
                [
                    'ID' => $aData['ID']
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

    public static function isTodayCreated($postID)
    {
        self::tableName();
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT ID, countShares FROM ".self::$tableName." WHERE objectID=%d AND date=%s",
                $postID, Time::mysqlDate(current_time('timestamp'))
            ),
            ARRAY_A
        );
    }

    public static function countSharesToday($postID, $day)
    {
        global $wpdb;
        self::tableName();

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(countShares) FROM ".self::$tableName." WHERE objectID=%d AND date=%s",
                $postID, $day
            )
        );
    }
}
