<?php


namespace WilokeListingTools\Models;


class ClaimModel
{
    public static function getListingIdByClaimId($claimId)
    {
        global $wpdb;

        return $wpdb->get_var(
          $wpdb->prepare(
              "SELECT meta_value FROM $wpdb->postmeta WHERE post_id = %d AND meta_key=%s",
              $claimId, 'wilcity_claimed_listing_id'
          )
        );
    }
}
