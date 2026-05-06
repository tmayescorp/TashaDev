<?php

namespace WilokeListingTools\Models;

use WilokeListingTools\AlterTable\AlterTableFollower;
use WilokeListingTools\Framework\Helpers\HTML;

class FollowerModel
{
    public static function countFollowings($authorID = null, $isStyleNumber = false)
    {
        global $wpdb;
        $authorID  = empty($authorID) ? get_current_user_id() : $authorID;
        $followTbl = $wpdb->prefix.AlterTableFollower::$tblName;
        
        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(authorID) FROM $followTbl WHERE followerID=%d",
                $authorID
            )
        );
        
        $total = !$total ? 0 : $total;
        
        return $isStyleNumber ? HTML::reStyleText($total) : $total;
    }
    
    public static function countFollowers($authorID = null, $isStyleNumber = false)
    {
        global $wpdb;
        $followTbl = $wpdb->prefix.AlterTableFollower::$tblName;
        $authorID  = empty($authorID) ? get_current_user_id() : $authorID;
        $total     = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(followerID) FROM $followTbl WHERE authorID=%d",
                $authorID
            )
        );
        
        $total = !$total ? 0 : $total;
        
        return $isStyleNumber ? HTML::reStyleText($total) : $total;
    }
}
