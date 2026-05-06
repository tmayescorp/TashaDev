<?php

namespace WilokeListingTools\Controllers;

use WeDevs\Dokan\Vendor\Vendor;
use WilokeListingTools\AlterTable\AlterTablePaymentHistory;
use WilokeListingTools\AlterTable\AlterTablePaymentMeta;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Models\PaymentMetaModel;

class RunUpdateDBToLatestVersionController extends Controller
{
    private $optionKey   = 'updated_db';
    private $nonceAction = 'wilcity_update_db_nonce';
    private $action      = 'wilcity_update_db';

    public function __construct()
    {
//        add_action('admin_init', [$this, 'updateDatabase']);
//        add_action('admin_notices', [$this, 'requireUpdateAnnouncement']);
    }

    public function requireUpdateAnnouncement()
    {
        $updatedDBVersion = GetSettings::getOptions($this->optionKey);

        if ($updatedDBVersion != WILOKE_LISTING_TOOL_DB_VERSION):
            $url = add_query_arg(
                [
                    'page'     => 'wiloke-submission',
                    'security' => wp_create_nonce($this->nonceAction),
                    'action'   => $this->action
                ],
                admin_url('admin.php')
            );
            ?>
            <div class="notice notice-error is-dismissible" style="padding: 10px;">
                <p>
                    <strong>
                        Wilcity Database Update: We need to update your database to latest version
                    </strong>
                </p>
                <p>
                    <a class="button button-primary" href="<?php echo esc_url($url); ?>">Run the updater</a>
                </p>
            </div>
        <?php
        else:
            if (Session::getSession('updated_db') == 'yes') :
                ?>
                <div class="notice notice-success is-dismissible" style="margin-top: 20px; margin-bottom: 20px">
                    <p>Thank for updating to the latest version of Wiloke Listing Tools</p>
                </div>
            <?php
            endif;
        endif;

        if (class_exists('Classic_Editor') || class_exists('DisableGutenberg')) {
            ?>
            <p><strong style="color: red">Warning: Classic Editor and Disable Gutenberg are not allowed in
                    Wilcity. Please disable these plugins by clicking on Plugins from admin sidebar ->
                    Disable Classic Editor / Disable Gutenberg</strong></p>
            <?php
        }
    }

    private function convertStripeSubscriptionIDToSubscriptionID()
    {
        global $wpdb;
        $paymentTbl = $wpdb->prefix . AlterTablePaymentHistory::$tblName;

        $aStripePaymentIDs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $paymentTbl WHERE gateway=%s AND billingType=%s ORDER BY ID DESC LIMIT 3000",
                'stripe', 'RecurringPayment'
            ),
            ARRAY_A
        );

        if (!empty($aStripePaymentIDs)) {
            foreach ($aStripePaymentIDs as $aPayment) {
                $stripeSubscriptionID = PaymentMetaModel::get($aPayment['ID'], 'stripe_subscription_ID');
                if (!empty($stripeSubscriptionID)) {
                    PaymentMetaModel::setPaymentSubscriptionID($aPayment['ID'], $stripeSubscriptionID);
                    PaymentMetaModel::delete($aPayment['ID'], 'stripe_subscription_ID');
                }
            }
        }

        return true;
    }

    private function convertPayPalTokenAndStoreDataToPaymentTokenID()
    {
        global $wpdb;
        $paymentTbl = $wpdb->prefix . AlterTablePaymentHistory::$tblName;

        $aPaymentIDs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $paymentTbl WHERE gateway=%s ORDER BY ID DESC LIMIT 3000",
                'paypal'
            ),
            ARRAY_A
        );

        if (!empty($aPaymentIDs)) {
            foreach ($aPaymentIDs as $aPayment) {
                $token = PaymentMetaModel::get($aPayment['ID'], 'paypal_token_id_relationship');
                if (!empty($token)) {
                    PaymentMetaModel::setPaymentToken($aPayment['ID'], $token);
                    PaymentMetaModel::delete($aPayment['ID'], 'paypal_token_id_relationship');
                }
            }
        }

        return true;
    }

    private function convertPayPalPaymentIDToIntentID()
    {
        global $wpdb;
        $paymentTbl = $wpdb->prefix . AlterTablePaymentHistory::$tblName;

        $aPaymentIDs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $paymentTbl WHERE gateway=%s AND billingType = %s ORDER BY ID DESC LIMIT 3000",
                'paypal', 'NonRecurringPayment'
            ),
            ARRAY_A
        );

        if (!empty($aPaymentIDs)) {
            foreach ($aPaymentIDs as $aPayment) {
                $intentID = PaymentMetaModel::get($aPayment['ID'], 'paypal_payment_id');
                if (!empty($intentID)) {
                    PaymentMetaModel::setPaymentIntentID($aPayment['ID'], $intentID);
                    PaymentMetaModel::delete($aPayment['ID'], 'paypal_payment_id');
                }
            }
        }

        return true;
    }

    public function updateDokanVendorGeocoder()
    {
        $updatedDBVersion = GetSettings::getOptions($this->optionKey);
        if (version_compare($updatedDBVersion, '2.0', '<') && function_exists('dokan_get_sellers')) {
            $aSellers = dokan_get_sellers([
                'number' => 1000,
                'offset' => 0
            ]);

            if (!empty($aSellers) && !empty($aSellers['users'])) {
                foreach ($aSellers['users'] as $oSeller) {
                    /**
                     * @var \Dokan_Vendor $oVendor
                     */
                    $oVendor = dokan()->vendor->get($oSeller->ID);
                    $location = $oVendor->get_location();
                    if (!empty($location)) {
                        do_action('dokan_store_profile_saved', $oSeller->ID, ['location' => $location]);
                    }
                }
            }
        }
    }

    private function convertStripeAndPayPalIdToToken()
    {
        $this->convertStripeSubscriptionIDToSubscriptionID();
        $this->convertPayPalTokenAndStoreDataToPaymentTokenID();
        $this->convertPayPalPaymentIDToIntentID();

        Session::setSession('updated_db', 'yes');
    }

    public function updateDatabase()
    {
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == $this->action) {
            if (wp_verify_nonce($_REQUEST['security'], $this->nonceAction)) {
                if (current_user_can('administrator')) {
                    $this->convertPayPalPaymentIDToIntentID();
                    $this->updateDokanVendorGeocoder();
                    SetSettings::setOptions($this->optionKey, WILOKE_LISTING_TOOL_DB_VERSION);
                }
            }
        }
    }
}
