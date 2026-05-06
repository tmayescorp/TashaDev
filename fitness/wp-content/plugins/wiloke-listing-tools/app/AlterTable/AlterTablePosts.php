<?php

namespace WilokeListingTools\AlterTable;

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\SetSettings;

class AlterTablePosts
{
    use TableExists;
    
    public function __construct()
    {
        $this->addFullTextToListingTitleAndContent();
        //        add_action('plugins_loaded', array($this, 'addFullTextToListingTitleAndContent'));
    }
    
    public function addFullTextToListingTitleAndContent()
    {
        global $wpdb;
        
        if (GetSettings::getOptions('added_ft_to_listing_tac')) {
            return false;
        }
        
        $wpdb->query("Alter TABLE {$wpdb->posts} ADD FULLTEXT (post_title, post_content)");
        SetSettings::setOptions('added_ft_to_listing_tac', true);
    }
    
    public function deleteTable()
    {
        // TODO: Implement deleteTable() method.
    }
}
