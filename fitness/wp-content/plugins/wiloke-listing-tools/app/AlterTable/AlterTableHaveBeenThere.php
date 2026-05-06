<?php
/*
 |--------------------------------------------------------------------------
 | Favorite Table
 |--------------------------------------------------------------------------
 | This table container list of favorite of user
 |
 */

namespace WilokeListingTools\AlterTable;

class AlterTableHaveBeenThere implements AlterTableInterface
{
    public static $tblName = 'wilcity_have_been_there';
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
        $postTbl = $wpdb->posts;

        $charsetCollate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $tblName (
          ID bigint(20) NOT NULL AUTO_INCREMENT,
          objectID bigint(20) UNSIGNED NOT NULL,
          ipAddress VARCHAR( 200 ) NULL,
          userId bigint(20) UNSIGNED NULL,
          date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
