<?php
/*
 |--------------------------------------------------------------------------
 | Favorite Table
 |--------------------------------------------------------------------------
 | This table container list of favorite of user
 |
 */

namespace WilokeListingTools\AlterTable;

class AlterTableUserLatLng implements AlterTableInterface
{
    public static $tblName = 'wilcity_user_latlng';
    public        $version = '1.0';
    use TableExists;

    public function __construct()
    {
        $this->createTable();
    }

    public function createTable()
    {
        if ($this->isTableExists()) {
            return false;
        }

        global $wpdb;
        $tblName = $wpdb->prefix . self::$tblName;
        $userTbl = $wpdb->users;

        $charsetCollate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $tblName (
          ID bigint(20) NOT NULL AUTO_INCREMENT,
          userId bigint(20) UNSIGNED NOT NULL,
          lat FLOAT( 10, 6 )  NOT NULL,
          lng FLOAT( 10, 6 ) NOT NULL,
          PRIMARY  KEY (ID),
          FOREIGN KEY (userId) REFERENCES $userTbl(ID) ON DELETE CASCADE
        ) $charsetCollate";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option(self::$tblName, $this->version);
    }

    public function deleteTable()
    {
        // TODO: Implement deleteTable() method.
    }
}
