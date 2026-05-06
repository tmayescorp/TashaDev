<?php


namespace WilokeListingTools\Models;


use WilokeListingTools\AlterTable\AlterTableHaveBeenThere;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;

class HaveBeenThereModel
{
    public static function table()
    {
        global $wpdb;

        return $wpdb->prefix . AlterTableHaveBeenThere::$tblName;
    }

    public static function count($postId, $isHumanReadable = true)
    {
        global $wpdb;

        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(ID) FROM " . self::table() . " WHERE objectID=%d",
               $postId
            )
        );

        if ($isHumanReadable) {
            if ($total >= 1000) {
                $convertToK = $total % 1000;
                $remainder = ($total - $convertToK * 100) % 100;

                if ($remainder > 0) {
                    $result = $convertToK . 'k' . $remainder;
                } else {
                    $result = $convertToK . 'k';
                }
            } else {
                $result = $total;
            }
        } else {
            $result = $total;
        }
        return apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Models/HaveBeenThereModel/count',
            $result,
            $total
        );
    }

    public static function add($postId)
    {
        global $wpdb;

        return $wpdb->insert(
            self::table(),
            [
                'objectID'  => $postId,
                'ipAddress' => General::clientIP(),
                'userId'    => get_current_user_id()
            ],
            [
                '%d',
                '%s',
                '%d'
            ]
        );
    }

    public static function delete($id)
    {
        global $wpdb;

        return $wpdb->delete(
            self::table(),
            [
                'ID' => $id
            ],
            [
                '%d'
            ]
        );
    }

    public static function isEnabled($thePost = ''): bool
    {
        if (empty($thePost)) {
            global $post;
        } else {
            $post = $thePost;
        }

        return GetSettings::getPostTypeField('hasBeenThere', $post->post_type) === 'enable';
    }

    public static function isChecked($postId): int
    {
        global $wpdb;

        if (is_user_logged_in()) {
            return (int)$wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ID FROM " . self::table() . " WHERE userId=%d AND objectID=%d",
                    get_current_user_id(), $postId
                )
            );
        } else {
            return (int)$wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ID FROM " . self::table() . " WHERE (ipAddress=%s AND userId=%d) AND objectID=%d",
                    General::clientIP(), 0, $postId
                )
            );
        }
    }
}
