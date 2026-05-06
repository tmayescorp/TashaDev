<?php
namespace WilokeListingTools\AlterTable;


class AlterTableBusinessHourMeta implements AlterTableInterface{
	public static $tblName = 'wilcity_business_hour_meta';
	public $version = '1.0';
	use TableExists;

	public function __construct() {
        $this->createTable();
//		add_action('plugins_loaded', array($this, 'createTable'));
	}

	public function createTable() {
		if ( $this->isTableExists() ){
			return false;
		}

		global $wpdb;
		$tblName = $wpdb->prefix . self::$tblName;
		$postTbl = $wpdb->posts;

		$charsetCollate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $tblName (
          ID bigint(20) NOT NULL AUTO_INCREMENT,
          objectID bigint(20) UNSIGNED NOT NULL,
          meta_key VARCHAR (225)  NOT NULL,
          meta_value LONGTEXT NULL,
          PRIMARY  KEY (ID),
          FOREIGN KEY (objectID) REFERENCES $postTbl(ID) ON DELETE CASCADE
        ) $charsetCollate";

//        Alter Table wp_wilcity_business_hour_meta ADD FOREIGN KEY (objectID) REFERENCES wp_posts(ID);

//        ALTER TABLE wp_wilcity_business_hour_meta DROP FOREIGN KEY `#sql-1_503b51_ibfk_2`;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);

		update_option(self::$tblName, $this->version);
	}

	public function deleteTable() {
		// TODO: Implement deleteTable() method.
	}
}
