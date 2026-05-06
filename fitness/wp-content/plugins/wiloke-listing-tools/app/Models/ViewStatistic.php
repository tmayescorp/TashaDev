<?php

namespace WilokeListingTools\Models;

use phpDocumentor\Reflection\File;
use WilokeListingTools\AlterTable\AlterTableViewStatistic;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\Time;

class ViewStatistic
{
    protected static $tableName;
    protected static $countAllViews = null;

    public static function tableName()
    {
        global $wpdb;
        self::$tableName = $wpdb->prefix . AlterTableViewStatistic::$tblName;
    }


    public static function compare($authorID, $postID = null, $compareBy = 'week')
    {
        $totalViews = self::getTotalViewsOfAuthor($authorID);
        switch ($compareBy) {
            case 'week':
                $aTotalViews = self::compareViewsByWeek($authorID, $postID);
                break;
        }

        $changing = $aTotalViews['current'] - $aTotalViews['past'];
        $status = 'up';
        if ($changing == 0) {
            $representColor = '';
        } else if ($changing > 0) {
            $representColor = 'green';
        } else {
            $representColor = 'red';
            $status = 'down';
        }

        return [
            'total'          => $totalViews,
            'totalCurrent'   => $aTotalViews['current'], // EG: Total views on this week
            'diff'           => $changing,
            'representColor' => $representColor,
            'status'         => $status
        ];
    }

    public static function compareViewsByWeek($authorID, $postID)
    {
        $mondayThisWeek = Time::mysqlDate(strtotime('monday this week'));
        $sundayThisWeek = Time::mysqlDate(strtotime('sunday this week'));

        $mondayLastWeek = Time::mysqlDate(strtotime('monday last week'));
        $sundayLastWeek = Time::mysqlDate(strtotime('sunday last week'));

        $totalViewLastWeek = self::getTotalViewsInRange($authorID, $mondayLastWeek, $sundayLastWeek, $postID);
        $totalViewThisWeek = self::getTotalViewsInRange($authorID, $mondayThisWeek, $sundayThisWeek, $postID);

        return [
            'current' => $totalViewThisWeek,
            'past'    => $totalViewLastWeek
        ];
    }


    public static function countAllViews()
    {
        if (self::$countAllViews !== null) {
            return self::$countAllViews;
        }
        global $wpdb;
        self::tableName();
        $statisticTbl = self::$tableName;
        self::$countAllViews = $wpdb->get_var("SELECT SUM($statisticTbl.countView) FROM $statisticTbl");

        return absint(self::$countAllViews);
    }

    public static function deleteAllViews()
    {
        global $wpdb;
        self::tableName();
        $statisticTbl = self::$tableName;
        return $wpdb->query("DELETE * FROM $statisticTbl");
    }

    public static function getTotalViewsOfAuthorInDay($userID, $day)
    {
        global $wpdb;
        $postsTbl = $wpdb->posts;
        self::tableName();
        $statisticTbl = self::$tableName;

        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT $statisticTbl.countView FROM $statisticTbl LEFT JOIN $postsTbl ON ($postsTbl.ID = $statisticTbl.objectID) WHERE $postsTbl.post_status=%s AND $postsTbl.post_author=%d AND $statisticTbl.date=%s",
                'publish', $userID, $day
            )
        );

        return $total ? absint($total) : 0;
    }

    public static function getTotalViewsInRange($userID, $start, $end, $postID = null)
    {
        global $wpdb;
        $postsTbl = $wpdb->posts;
        self::tableName();
        $statisticTbl = self::$tableName;

        $query
            = "SELECT $statisticTbl.countView FROM $statisticTbl LEFT JOIN $postsTbl ON ($postsTbl.ID = $statisticTbl.objectID) WHERE $postsTbl.post_status=%s AND $postsTbl.post_author=%d AND $statisticTbl.date BETWEEN %s AND %s";

        if (!empty($postID)) {
            $query .= " AND $statisticTbl.objectID=%d";
            $total = $wpdb->get_var(
                $wpdb->prepare(
                    $query,
                    'publish', $userID, $start, $end, $postID
                )
            );
        } else {
            $total = $wpdb->get_var(
                $wpdb->prepare(
                    $query,
                    'publish', $userID, $start, $end
                )
            );
        }

        return $total ? absint($total) : 0;
    }

    public static function getTotalViewsOfAuthor($userID, $startFrom = null)
    {
        global $wpdb;
        $postsTbl = $wpdb->posts;
        self::tableName();
        $statisticTbl = self::$tableName;

        $post_types = \WilokeListingTools\Framework\Helpers\General::getPostTypeKeys(false, false);
        $post_types = implode("','", $post_types);

        $query = $wpdb->prepare(
            "SELECT SUM($statisticTbl.countView) FROM $statisticTbl LEFT JOIN $postsTbl ON ($postsTbl.ID = $statisticTbl.objectID) WHERE $postsTbl.post_status=%s AND $postsTbl.post_type IN ('" .
            $post_types . "') AND $postsTbl.post_author=%d",
            'publish', $userID
        );

        if ($startFrom) {
            $query = $wpdb->prepare(
                "$query AND $statisticTbl.date >= %s",
                $startFrom
            );
        }

        $total = $wpdb->get_var($query);

        return $total ? absint($total) : 0;
    }

    public static function insert($postID, $totalViews = 1)
    {
        global $wpdb;
        self::tableName();

        $status = $wpdb->insert(
            self::$tableName,
            [
                'objectID'  => $postID,
                'countView' => $totalViews,
                'date'      => current_time('mysql')
            ],
            [
                '%d',
                '%d',
                '%s'
            ]
        );

        return $status ? $wpdb->insert_id : false;
    }

    public static function countViewsInDay($postID, $day)
    {
        global $wpdb;
        self::tableName();

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT countView FROM " . self::$tableName . " WHERE objectID=%d AND date=%s",
                $postID, $day
            )
        );
    }

    public static function countViews($postID)
    {
        global $wpdb;
        self::tableName();

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(countView) FROM " . self::$tableName . " WHERE objectID=%d",
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
            $countView = absint($aData['countView']);
            $countView = $countView + 1;

            return $wpdb->update(
                self::$tableName,
                [
                    'countView' => $countView
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
                "SELECT ID, countView FROM " . self::$tableName . " WHERE objectID=%d AND date=%s",
                $postID, Time::mysqlDate(current_time('timestamp'))
            ),
            ARRAY_A
        );
    }
}
