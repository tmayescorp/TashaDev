<?php


namespace WilokeListingTools\Models;


use WilokeListingTools\AlterTable\AlterTableUserLatLng;

class UserLatLng
{
    private static function getTblName()
    {
        global $wpdb;
        return $wpdb->prefix . AlterTableUserLatLng::$tblName;
    }

    public static function getId($userId)
    {
        global $wpdb;
        $tblName = $wpdb->prefix . AlterTableUserLatLng::$tblName;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM $tblName WHERE userId = %d",
                $userId
            )
        );
    }

    public static function getUserLatLng($userId)
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . self::getTblName() . " WHERE userId=%d",
                $userId
            )
        );
    }

    public static function deleteUserLatLng($userId)
    {
        global $wpdb;

        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM " . self::getTblName() . " WHERE userId=%d",
                $userId
            )
        );
    }

    public static function updateUserLatLng($userId, $lat, $lng)
    {
        global $wpdb;

        if ($id = self::getId($userId)) {
            return $wpdb->update(
                self::getTblName(),
                [
                    'lat' => $lat,
                    'lng' => $lng
                ],
                [
                    'ID' => $id
                ],
                [
                    '%s',
                    '%s'
                ],
                [
                    '%d'
                ]
            );
        } else {
            return self::addUserLatLng($userId, $lat, $lng);
        }
    }

    public static function addUserLatLng($userId, $lat, $lng)
    {
        global $wpdb;
        $tblName = $wpdb->prefix . AlterTableUserLatLng::$tblName;

        return $wpdb->insert(
            $tblName,
            [
                'lat'    => $lat,
                'lng'    => $lng,
                'userId' => $userId
            ],
            [
                '%s',
                '%s',
                '%d'
            ]
        );
    }
}
