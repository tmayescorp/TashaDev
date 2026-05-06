<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\AlterTable\AlterTableFollower;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\HTML;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\FollowerModel;

class FollowController extends Controller
{
    public static $isIamFollowing = null;

    public function __construct()
    {
        add_action('wp_ajax_wilcity_update_following', [$this, 'updateFollow']);
    }

    public static function toggleFollow()
    {
        global $wiloke;

        return $wiloke->aThemeOptions['general_toggle_follow'] == 'enable';
    }

    public static function isIamFollowing($authorID)
    {
        if (!is_user_logged_in()) {
            return false;
        }

        if (self::$isIamFollowing !== null) {
            return self::$isIamFollowing;
        }

        global $wpdb;
        $followTbl = $wpdb->prefix.AlterTableFollower::$tblName;

        self::$isIamFollowing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT followerID FROM $followTbl WHERE authorID=%d AND followerID=%d",
                $authorID, get_current_user_id()
            )
        );

        return self::$isIamFollowing;
    }

    public static function insertFollower($authorID)
    {
        global $wpdb;
        $followTbl = $wpdb->prefix.AlterTableFollower::$tblName;

        return $wpdb->insert(
            $followTbl,
            [
                'authorID'   => $authorID,
                'followerID' => get_current_user_id(),
                'date'       => Time::mysqlDateTimeWithWebsiteTimezone()
            ],
            [
                '%d',
                '%d',
                '%s'
            ]
        );
    }

    public static function deleteFollower($authorID)
    {
        global $wpdb;
        $followTbl = $wpdb->prefix.AlterTableFollower::$tblName;

        return $wpdb->delete(
            $followTbl,
            [
                'authorID'   => $authorID,
                'followerID' => get_current_user_id()
            ],
            [
                '%d',
                '%d'
            ]
        );
    }

    public static function countFollowings($authorID = null, $isStyleNumber = false)
    {
        return FollowerModel::countFollowings($authorID, $isStyleNumber);
    }

    public static function countFollowers($authorID = null, $isStyleNumber = false)
    {
        return FollowerModel::countFollowers($authorID, $isStyleNumber);
    }

    public function updateFollow()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error([
                'msg' => esc_html__('Please log into your account before following', 'wiloke-listing-tool')
            ]);
        }

        if (empty($_POST['authorID'])) {
            wp_send_json_error([
                'msg' => esc_html__('The author data is required.', 'wiloke-listing-tool')
            ]);
        }

        if (get_current_user_id() == $_POST['authorID']) {
            wp_send_json_error([
                'msg' => esc_html__('You can not follow yourself.', 'wiloke-listing-tool')
            ]);
        }

        $status = self::isIamFollowing($_POST['authorID']);

        if ($status) {
            self::deleteFollower($_POST['authorID']);
        } else {
            self::insertFollower($_POST['authorID']);

            do_action('wilcity/wiloke-listing-tools/app/Controllers/FollowController/new-follower',
                User::getCurrentUserID(), $_POST['authorID']);
        }

        do_action(
            'wilcity/wiloke-listing-tools/app/Controllers/FollowController/afterFollowedOrUnfollowed',
            $status,
            User::getCurrentUserID(),  // follower
            $_POST['authorID'], // owner
            get_post_field('post_author', $_POST['authorID'])
        );
        $total = self::countFollowers($_POST['authorID']);
        wp_send_json_success(HTML::reStyleText($total));
    }
}
