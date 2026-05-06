<?php

namespace WilokeListingTools\Controllers;

class AdminNoticeController
{
    public function __construct()
    {
        add_action('admin_notices', [$this, 'renderAdminNotices']);
        add_action('admin_init', [$this, 'handleClickUpdate'], 1000);
    }

    public function handleClickUpdate() {
        if (current_user_can('administrator')) {
            if ( isset( $_POST['wilcity_update_db_nonce_field'] ) && wp_verify_nonce( $_POST['wilcity_update_db_nonce_field'], 'wilcity_update_db_action' )
            ) {
                do_action("wilcity/update-db");
                update_option("wilcity_listing_tools_version", WILOKE_LISTING_TOOL_VERSION);
            }
        }
    }

    public function renderAdminNotices()
    {
        $currentVersion = get_option("wilcity_listing_tools_version");
        $currentVersion = $currentVersion ?? "1.0";

        if (version_compare($currentVersion, WILOKE_LISTING_TOOL_VERSION, "<")) {
            ?>
            <div class="notice notice-warning settings-error is-dismissible">
                <form action="" method="post">
                    <p><strong>Wilcity Update Database is required!</strong></p>
                    <p>Wilcity has been updated! To keep things running smoothly, We have to update database to the
                        newest version.</p>
                    <input type="hidden" name="wiloke_listing_tools_update_db" value="1">
                    <?php wp_nonce_field( 'wilcity_update_db_action', 'wilcity_update_db_nonce_field' ); ?>
                    <button type="submit" style="margin-top: 5px; margin-bottom: 5px;" class="button
                    button-primary">Update</button>
                </form>
            </div>
            <?php
        }
    }
}
