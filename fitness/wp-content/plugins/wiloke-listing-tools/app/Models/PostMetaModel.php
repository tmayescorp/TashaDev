<?php

namespace WilokeListingTools\Models;

use WilokeListingTools\Framework\Helpers\SetSettings;

class PostMetaModel
{
    public static function getExpiryTimeTemporary($postID)
    {
        global $wpdb;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT meta_value FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s",
                $postID, 'wilcity_expiry_time_temporary'
            )
        );
    }

    /**
     * @param $postID
     * @param $expirationTimestamp
     *
     * @return false|int
     */
    public static function setExpiryTimeTemporary($postID, $expirationTimestamp)
    {
        global $wpdb;

        $metaID = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT meta_id FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s",
                $postID, 'wilcity_expiry_time_temporary'
            )
        );

        if (empty($metaID)) {
            $wpdb->insert(
                $wpdb->postmeta,
                [
                    'post_id'  => $postID,
                    'meta_key' => 'wilcity_expiry_time_temporary',
                    'meta_value' => $expirationTimestamp
                ],
                [
                    '%d',
                    '%s',
                    '%d',
                ]
            );

            return $wpdb->insert_id;
        } else {
            return $wpdb->update(
                $wpdb->postmeta,
                [
                    'meta_value' => $expirationTimestamp
                ],
                [
                    'post_id'  => $postID,
                    'meta_key' => 'wilcity_expiry_time_temporary'
                ],
                [
                    '%d'
                ],
                [
                    '%d',
                    '%s'
                ]
            );
        }
    }

    /**
     * @param $postID
     * @param $expirationTimestamp
     *
     * @return false|int
     */
    public static function updateListingExpiration($postID, $expirationTimestamp)
    {
        global $wpdb;

        $metaID = $wpdb->get_var(
          $wpdb->prepare(
              "SELECT meta_id FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s",
              $postID, 'wilcity_post_expiry'
          )
        );

        if (empty($metaID)) {
            $wpdb->insert(
                $wpdb->postmeta,
                [
                    'post_id'  => $postID,
                    'meta_key' => 'wilcity_post_expiry',
                    'meta_value' => $expirationTimestamp
                ],
                [
                    '%d',
                    '%s',
                    '%d',
                ]
            );

            return $wpdb->insert_id;
        } else {
            return $wpdb->update(
                $wpdb->postmeta,
                [
                    'meta_value' => $expirationTimestamp
                ],
                [
                    'post_id'  => $postID,
                    'meta_key' => 'wilcity_post_expiry'
                ],
                [
                    '%d'
                ],
                [
                    '%d',
                    '%s'
                ]
            );
        }
    }
}
