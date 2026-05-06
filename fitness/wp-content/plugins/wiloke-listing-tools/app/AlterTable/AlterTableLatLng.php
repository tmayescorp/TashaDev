<?php
/*
 |--------------------------------------------------------------------------
 | Favorite Table
 |--------------------------------------------------------------------------
 | This table container list of favorite of user
 |
 */

namespace WilokeListingTools\AlterTable;

class AlterTableLatLng implements AlterTableInterface
{
    public static $tblName = 'wilcity_latlng';
    public        $version = '1.1';
    use TableExists;

    public function __construct()
    {
        $this->createTable();
    }

    public function increaseAddressCharacters()
    {
        if (get_option(self::$tblName) !== $this->version) {
            global $wpdb;
            $tblName = $wpdb->prefix . self::$tblName;

            $sql = "Alter Table $tblName MODIFY Column address VARCHAR( 500 ) NOT NULL";

            $wpdb->query($sql);
            update_option(self::$tblName, $this->version);
        }
    }

    public function createTable()
    {
        if ($this->isTableExists()) {
            return false;
        }

        global $wpdb;
        $tblName = $wpdb->prefix . self::$tblName;
        $postTbl = $wpdb->posts;

        $charsetCollate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $tblName (
          ID bigint(20) NOT NULL AUTO_INCREMENT,
          objectID bigint(20) UNSIGNED NOT NULL,
          address VARCHAR( 500 ) NOT NULL,
          lat FLOAT( 10, 6 )  NOT NULL,
          lng FLOAT( 10, 6 ) NOT NULL,
          PRIMARY  KEY (ID),
          FOREIGN KEY (objectID) REFERENCES $postTbl(ID) ON DELETE CASCADE
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
