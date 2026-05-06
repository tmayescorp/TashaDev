<?php

namespace WilokeListingTools\Register;

use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\Inc;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\SetSettings;

class RegisterSettings
{
    use ListingToolsGeneralConfig;
    use GetAvailableSections;
    use ParseSection;

    public static $slug                    = 'wiloke-listing-tools';
    protected     $usedSectionsKey         = 'wiloke_lt_addlisting_sections';
    protected     $aUsedSections           = [];
    protected     $aAvailableSections      = [];
    protected     $aReviewSettings         = [];
    protected     $aAllSections            = [];
    protected     $designSingleListingsKey = 'wiloke_lt_design_single_listing_tab';
    protected     $aDefaultUsedSections;
    protected     $isResetDefault          = false;
    protected     $oPredis;
    protected     $aCustomPostTypes;
    protected     $aCustomPostTypesKey;
    protected     $aSingleNav;
    protected     $aSidebarUsedSections;
    protected     $aSidebarAvailableSections;
    protected     $aSidebarAllSections;
    protected     $aSearchUsedFields;
    protected     $aAvailableSearchFields;

    public function __construct()
    {
        add_action('admin_menu', [$this, 'register']);
        add_action('admin_footer', [$this, 'footerCode']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('wp_ajax_wiloke_install_submission_pages', [$this, 'installSubmissionPages']);
        add_action('admin_notices', [$this, 'deleteUnpaidListingIsEnabling']);
        add_action('admin_notices', [$this, 'makeSureThatBillingTypeIsSetToRecurring']);
        add_action('admin_notices', [$this, 'makeSureThatEndpointSecretIsSetup']);
        add_action('admin_notices', [$this, 'wrongWooCommerceCheckoutPage']);
    }

    public function wrongWooCommerceCheckoutPage()
    {
        $checkoutID = GetWilokeSubmission::getField('checkout');
        if (GetWilokeSubmission::isGatewaySupported('woocommerce')) {
            if (function_exists('is_woocommerce') && $checkoutID != get_option('woocommerce_checkout_page_id')) {
                ?>
                <div class="notice notice-error is-dismissible" style="margin-top: 20px; margin-bottom: 20px">
                    <p>Wiloke Submission Warning: Invalid Checkout page setting. You are using Purchase Listing through
                        WooCommerce, Checkout page must be WooCommerce Checkout page. To correct this setting, please
                        go to Wiloke Submission -> Checkout -> Replace the current Checkout page setting with a
                        WooCommerce Checkout page</p>
                </div>
                <?php
            }
        } else {
            if ($checkoutID == get_option('woocommerce_checkout_page_id')) {
                ?>
                <div class="notice notice-error is-dismissible" style="margin-top: 20px; margin-bottom: 20px">
                    <p>Wiloke Submission Warning: Invalid Checkout page setting. You are using Purchase Listing through
                        Wiloke Submission Supported payment, Checkout page must be Wiloke Submission Checkout page. To
                        correct this setting, please go to Wiloke Submission -> Checkout -> Replace the current
                        Checkout page setting with a Wiloke Submission Checkout page</p>
                </div>
                <?php
            }
        }
    }

    public function makeSureThatEndpointSecretIsSetup()
    {
        $gateways = GetWilokeSubmission::getField('payment_gateways');
        if (empty($gateways)) {
            return '';
        }

        $aGateways = explode(',', $gateways);

        if (in_array('stripe', $aGateways)) {
            if (empty(GetWilokeSubmission::getField('stripe_endpoint_secret'))) :
                ?>
                <div class="notice notice-error is-dismissible" style="margin-top: 20px; margin-bottom: 20px">
                    <p>Warning: Since Wilcity 1.1.7.5, Stripe Enpoint Secret is required. To complete this feature,
                        click on Wiloke Submission -> Search for Endpoint Secret</p>
                </div>
            <?php
            endif;
        }
    }

    public function deleteUnpaidListingIsEnabling()
    {
        $autoDeleteListingAfter = GetWilokeSubmission::getField('delete_listing_conditional');
        if (!empty($autoDeleteListingAfter)) {
            ?>
            <div class="notice notice-error is-dismissible" style="margin-top: 20px; margin-bottom: 20px">
                <p>Warning: You are enabling <strong>Automatically Delete Unpaid Listing</strong> feature. Which means
                    an Unpaid Listing will be deleted automatically after <?php echo $autoDeleteListingAfter; ?> days
                    from submitted day. To disable this feature, please click on <strong>Wiloke Submission ->
                        Automatically Delete Unpaid Listing</strong> -> Leave it to empty</p>
            </div>
            <?php
        }
    }

    /*
     * If WooCommerce Subscription is enabling, We should remind customers that they need to enable Recurring Payment
     * on Wiloke Submission as well
     *
     * @since 1.2.0
     */
    public function makeSureThatBillingTypeIsSetToRecurring()
    {
        if (class_exists('\WC_Subscriptions_Coupon')) {
            if (GetWilokeSubmission::isNonRecurringPayment()) {
                ?>
                <div class="notice notice-warning is-dismissible" style="margin-top: 20px; margin-bottom: 20px">
                    <p>Warning: <strong>WooCommerce Subscription</strong> is enabling. If you want to use <strong>Recurring
                            Add Listing Payment</strong>, please click on <strong>Wiloke Submission -> Billing
                            Type</strong> -> Select <strong>Recurring Payment (Subscription)</strong> mode.</p>
                </div>
                <?php
            }
        }
    }

    public function installSubmissionPages()
    {
        if (!current_user_can('edit_theme_options')) {
            wp_send_json_error([
                [
                    'msg'    => 'You do not have permission to access this page.',
                    'status' => 'error'
                ]
            ]);
        }

        $aConfigs = wilokeListingToolsRepository()->get('submission-pages');

        $aResponse = [];
        $aWilokeSubmission = GetWilokeSubmission::getAll();
        $hasUpdated = false;

        foreach ($aConfigs as $aPage) {
            $check = isset($aWilokeSubmission[$aPage['key']]) ? $aWilokeSubmission[$aPage['key']] : '';
            if (!empty($check)) {
                if (get_post_status($check) == 'publish') {
                    continue;
                }
            }

            $postID = wp_insert_post([
                'post_title'   => $aPage['title'],
                'post_content' => $aPage['content'],
                'post_status'  => 'publish',
                'post_type'    => 'page'
            ]);

            if (empty($postID) || is_wp_error($postID)) {
                $aResponse[] = [
                    'status' => 'error',
                    'msg'    => 'We could not create ' . $aPage['title']
                ];
            } else {
                if (!empty($aPage['template'])) {
                    update_post_meta($postID, '_wp_page_template', $aPage['template']);
                }
                $aWilokeSubmission[$aPage['key']] = $postID;
                $hasUpdated = true;

                $aResponse[] = [
                    'status' => 'success',
                    'msg'    => $aPage['title'] . ' has been installed success fully'
                ];
            }
        }

        $aResponse[] = [
            'status' => 'success',
            'msg'    => 'Congratulations! The Wiloke Submission Pages have been installed successfully!'
        ];

        if ($hasUpdated) {
            update_option('wiloke_submission_configuration', maybe_serialize($aWilokeSubmission));
        }

        wp_send_json_success($aResponse);
    }

    public function footerCode()
    {
        if (!isset($_REQUEST['page']) || strpos($_REQUEST['page'], $this->parentSlug) == -1) {
            return '';
        }

        Inc::file('footer:icon-model');
    }

    public function removeFields($aSettings)
    {
        if (!General::isPostType('event')) {
            return $aSettings;
        }
    }

    public function enqueueScripts($hook)
    {
        if (!$this->matchedSlug($hook)) {
            return false;
        }
        $this->requiredScripts();
        wp_enqueue_script('wiloke-listing-tools', WILOKE_LISTING_TOOL_URL . 'admin/source/js/listing-tools.js',
            ['jquery'], WILOKE_LISTING_TOOL_VERSION, true);
    }

    public function settingsArea()
    {
        Inc::file('general:index');
    }

    public function register()
    {
        add_menu_page(
            'Wiloke Tools',
            'Wiloke Tools',
            'edit_theme_options',
            self::$slug, [$this, 'settingsArea'],
            '',
            25
        );

        do_action('wilcity/wiloke-listing-tools/register-menu', $this);
    }
}
