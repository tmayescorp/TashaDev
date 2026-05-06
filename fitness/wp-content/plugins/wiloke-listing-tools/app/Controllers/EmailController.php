<?php

namespace WilokeListingTools\Controllers;

use Wiloke;
use WilokeListingTools\Framework\Helpers\App;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\Firebase;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\QRCodeGenerator;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;
use WilokeThemeOptions;
use WP_Post;
use WP_User;

class EmailController extends Controller
{
    private              $aConfiguration;
    private              $aBankAccounts;
    private              $aBankAccountFields;
    private              $aThemeOptions;
    private              $customerID;
    private              $aSentEmails               = [];
    private              $isSendingConfirmation     = false;
    private              $message                   = '';
    private static       $aSocialNetworks           = ['facebook', 'twitter', 'google', 'apple'];
    public               $aSentHistory              = [];
    private              $scheduleSendListingStatus = 'wilcity_schedule_listing_status_email';
    public static string $emailContent              = '';

    public function __construct()
    {
        add_action('wilcity/became-an-author', [$this, 'becameAnAuthor']);
        add_action('post_updated', [$this, 'maybeScheduleSendListingStatus'], 10, 3);
        add_action('wilcity_after_reupdated_post', [$this, 'maybeScheduleSendListingStatus'], 10, 3);
        add_action('save_post', [$this, 'maybeScheduleSendListingStatusAfterSubmitting'], 10, 3);
        add_action($this->scheduleSendListingStatus, [$this, 'sendEmailNotificationToCustomerAndAdmin']);

        add_action('post_almost_expiry', [$this, 'almostExpired']);
        add_action('wiloke/free-claim/submitted', [$this, 'claimSubmitted'], 10, 2);
        add_action('wilcity/handle-claim-request', [$this, 'notifyCustomerClaimedListingToAdmin'], 10);
        add_action('wilcity/wiloke-listing-tools/claim-approved', [$this, 'notifyClaimHasBeenApprovedToCustomer'], 10);
        add_action('wilcity/wiloke-listing-tools/claim-approved', [$this, 'notifyClaimHasBeenApprovedToAdmin'], 10);
        add_action('updated_post_meta', [$this, 'claimRejected'], 10, 4);
        add_action('wilcity/wiloke-listing-tools/before/insert-payment', [$this, 'orderProcessing'], 10);
        add_action('wilcity/wiloke-listing-tools/before/insert-payment', [$this, 'notifyAdminAnOrderCreated'], 10);
        add_action('wilcity/stripe/invoice/payment-failed', [$this, 'paymentFailed'], 10);
        add_action('wiloke-listing-tools/subscription-created', [$this, 'subscriptionCreated'], 10);
        add_action('wiloke-listing-tools/subscription-created', [$this, 'changedPlan'], 10);
        add_action('wiloke/promotion/submitted', [$this, 'promotionCreated'], 10, 2);
        add_action('wiloke/promotion/submitted', [$this, 'notifyPromotionToAdmin'], 10, 3);
        add_action(
            'wilcity/wiloke-listing-tools/app/Controllers/PromotionController/after-promotion-position-expired',
            [$this, 'sendToCustomerToNotifyAPromotionPlanExpired']
        );
        add_action(
            'wilcity/wiloke-listing-tools/app/Controllers/PromotionController/deleteAllPlansOfListing/after-promotion-expired',
            [$this, 'sendToCustomerToNotifyPromotionPlanExpired']
        );

        add_action(
            'wilcity/wiloke-listing-tools/app/Controllers/PromotionController/movePromotionToTrashAfterAllPlansExpirationDate/after-promotion-expired',
            [$this, 'sendToCustomerToNotifyPromotionPlanExpired']
        );

        add_action(
            'wilcity/wiloke-listing-tools/app/Controllers/PromotionController/addPromotionPlansToListing/after-added',
            [$this, 'promotionApproved']
        );
        add_action('mailtpl/sections/test/before_content', [$this, 'addTestMailTarget']);
        add_action('customize_controls_enqueue_scripts', [$this, 'enqueueCustomizeScripts'], 99);
        add_action('wp_ajax_wiloke_mailtpl_send_email', [$this, 'testMail']);

        add_action('user_register', [$this, 'sayWelcome'], 1);
        add_action('wilcity/after/created-account', [$this, 'sendConfirmation'], 99, 3);
        add_action('wp_ajax_wilcity_send_confirmation_code', [$this, 'resendConfirmation']);
        //		add_action('wilcity-login-with-social/after_insert_user', array($this, 'sendPasswordIfSocialLogin'), 10, 3);
        add_action('wilcity/submitted-new-review', [$this, 'sendReviewNotification'], 10, 3);
        add_action('wilcity/submitted-report', [$this, 'sendReportNotificationToAdmin'], 10, 3);
        add_filter('wilcity/theme-options/configurations', [$this, 'addEmailTemplateSettingsToThemeOptions']);

        add_action('wilcity/action/after-sent-message', [$this, 'sendMessageToEmail'], 10, 3);
        add_action('wilcity/wiloke-listing-tools/observerSendMessage', [$this, 'ajaxSendMessageToEmail'], 10);
        //        add_action('wilcity/action/after-sent-message', [$this, 'ajaxSendMessageToEmail'], 10, 2);
        add_action('wilcity/inserted-invoice', [$this, 'sendEmailInvoice']);
        //		add_action('admin_init', array($this, 'sendTestInvoice'));

        add_action('woocommerce_email_attachments', [$this, 'dokanAddQrcodeToAttachment'], 10, 3);
        add_action('woocommerce_email_customer_details', [$this, 'dokanSendQRCodeToCustomer'], 100);

        add_action(App::get('PostController')->fNotificationAlmostDeletePost,
            [$this, 'sendNotificationListingAlmostDeleted']);
        add_action(App::get('PostController')->sNotificationAlmostDeletePost,
            [$this, 'sendNotificationListingAlmostDeleted']);
        add_action(App::get('PostController')->tNotificationAlmostDeletePost,
            [$this, 'sendNotificationListingAlmostDeleted']);

        add_action('wilcity/wiloke-listing-tools/app/Framework/Payment/Stripe/StripeWebhook/error',
            [$this, 'stripeWebhookError']);

        $aBillingTypes = wilokeListingToolsRepository()->get('payment:billingTypes');

        foreach ($aBillingTypes as $billingType) {
            add_action('wilcity/wiloke-listing-tools/' . $billingType . '/payment-dispute',
                [$this, 'sendDisputePaymentWarningToCustomer']);
            add_action('wilcity/wiloke-listing-tools/' . $billingType . '/payment-dispute',
                [$this, 'sendDisputePaymentWarningToAdmin']);
            add_action('wilcity/wiloke-listing-tools/' . $billingType . '/payment-failed',
                [$this, 'sendFailedPaymentNotificationToAdmin']);
            add_action('wilcity/wiloke-listing-tools/' . $billingType . '/payment-suspended',
                [$this, 'sendSuspendedPaymentNotificationToAdmin']);
            add_action('wilcity/wiloke-listing-tools/' . $billingType . '/payment-cancelled',
                [$this, 'sendCancelledPaymentNotificationToAdmin']);
            add_action('wilcity/wiloke-listing-tools/' . $billingType . '/payment-cancelled',
                [$this, 'sendCancelledPaymentNotificationToCustomer']);
            add_action('wilcity/wiloke-listing-tools/' . $billingType . '/payment-refunded',
                [$this, 'sendRefundedPaymentNotificationToCustomer']);
        }

        add_action(
            'wilcity/wiloke-listing-tools/app/Framework/Payment/Stripe/StripeWebhook/error',
            [
                $this,
                'sendStripePaymentIssueToAdmin'
            ]
        );

        add_action('wilcity/site-error', [$this, 'sendErrorMessageToSiteOwner'], 10, 3);

        add_action('wilcity/wiloke-listing-tools/app/Controllers/DirectBankTransferPaymentScheduleController/
	handlePaymentCompleted/bank-transfer/almost-billing-date/first', [$this, 'almostBankTransferBillingDate']);

        add_action('wilcity/wiloke-listing-tools/app/Controllers/DirectBankTransferPaymentScheduleController/
	handlePaymentCompleted/bank-transfer/almost-billing-date/second', [$this, 'almostBankTransferBillingDate']);
        add_action('wilcity/wiloke-listing-tools/app/Controllers/DirectBankTransferPaymentScheduleController/handlePaymentCompleted/bank-transfer/almost-billing-date/third',
            [$this, 'almostBankTransferBillingDate']);
        add_filter('wlicity/wiloke-listing-tools/app/Controllers/EmailController/generate-replace',
            [$this, 'generateReplace'], 10, 2);

        add_action('wilcity/wiloke-listing-tools/app/Controllers/DirectBankTransferPaymentScheduleController/
	handlePaymentCompleted/bank-transfer/out-of-billing-date/first', [$this, 'outOfBankTransferBillingDate']);
        add_action('wilcity/wiloke-listing-tools/app/Controllers/DirectBankTransferPaymentScheduleController/
	handlePaymentCompleted/bank-transfer/out-of-billing-date/second', [$this, 'outOfBankTransferBillingDate']);
        add_action('wilcity/wiloke-listing-tools/app/Controllers/DirectBankTransferPaymentScheduleController/
	handlePaymentCompleted/bank-transfer/out-of-billing-date/third', [$this, 'outOfBankTransferBillingDate']);

        add_action('wilcity/wiloke-listing-tools/app/Controllers/DirectBankTransferPaymentScheduleController/cancelSubscription',
            [$this, 'canceledBankTransferBillingDate']);

        if (defined('WILOKE_EMAIL_CREATOR_HOOK_PREFIX')) {
            add_filter(
                WILOKE_EMAIL_CREATOR_HOOK_PREFIX . 'src/Shared/TraitEmailTypes/addedEmailType',
                [$this, 'addWilcityType']
            );

            add_filter(
                'wiloke-listing-tools/app/Controllers/EmailControllers/cleanContentBeforeSending',
                [$this, 'modifyEmailContentWithEmailCreator']
            );

            add_action('mailtpl/add_template', [$this, 'maybeFilterByEmailTemplateBefore']);
            add_filter('mailtpl/return_template', [$this, 'replaceEmailTemplateWithEmailCreatorContent']);
        }
    }

    public function maybeFilterByEmailTemplateBefore($email) {
        self::$emailContent = $email;
    }

    public function replaceEmailTemplateWithEmailCreatorContent($emailContent)
    {
        if (!empty(self::$emailContent)) {
            $emailContent = self::$emailContent;
        }
        return $emailContent;
    }

    public function modifyEmailContentWithEmailCreator($content)
    {
        $firstName = null;
        if (!empty($this->customerID)) {
            $oUser = new WP_User($this->customerID);
            if (!empty($oUser->first_name)) {
                $firstName = $oUser->first_name;
            } else {
                $firstName = $oUser->user_login;
            }
        }
        $aResponseEmail = apply_filters(
            WILOKE_EMAIL_CREATOR_HOOK_PREFIX . 'src/Email/Controllers/TemplateController/getDataTemplates',
            [],
            [
                "emailType" => "wilcity",
                "pluck"     => "emailSubject,html",
                "status"    => "active",
            ]
        );

        if (($aResponseEmail['status'] == 'success') && isset($aResponseEmail['data']['items'][0])) {
            $content = str_replace(
                [
                    '{{body_wilcity}}',
                    '{admin_phone}',
                    '{admin_email}',
                    '{first_name}',
                    '{website_url}'
                ],
                [
                    $content,
                    '',
                    get_option('admin_email'),
                    $firstName,
                    home_url('/')
                ],
                $aResponseEmail['data']['items'][0]['html']
            );
            self::$emailContent = $content;
        }

        return $content;
    }

    public function addWilcityType($aTypes): array
    {
        $aWilcityType = [
            "label" => "Wilcity Theme",
            "value" => "wilcity",
            "type"  => "wilcity"
        ];

        if (is_array($aTypes) && !empty($aTypes)) {
            $aTypes[] = $aWilcityType;
            return $aTypes;
        }

        return $aWilcityType;
    }

    public function isContributor($postAuthorId): bool
    {
        $oUser = new WP_User($postAuthorId);

        return in_array('contributor', $oUser->roles);
    }

    public function isVendor($postAuthorId): bool
    {
        $oUser = new WP_User($postAuthorId);

        return in_array('seller', $oUser->roles);
    }

    /**
     * @param $msg
     * @return mixed|string
     */
    public function resolveEmptyConfirmEmailIssue($msg): string
    {
        if (empty($msg)) {
            if ($this->isSendingConfirmation) {
                $msg = ($this->message);
            }
        }
        return $msg;
    }

    public function maybeScheduleSendListingStatusAfterSubmitting($postId, $post, $isUpdate): bool
    {
        if (
            !in_array($post->post_type, General::getPostTypeKeys(false, false)) ||
            $isUpdate ||
            wp_is_post_revision($post) ||
            !$this->isContributor($post->post_author)
        ) {
            return false;
        }

        $postId = (int)$postId;
        wp_clear_scheduled_hook($this->scheduleSendListingStatus, [$postId]);
        wp_schedule_single_event(\time() + 120, $this->scheduleSendListingStatus, [$postId]);
        return true;
    }

    public function maybeScheduleSendListingStatus($postId, $oPostAfter, $oPostBefore): bool
    {
        if (
            !in_array($oPostBefore->post_type, General::getPostTypeKeys(false, false)) ||
            $oPostBefore->post_status == $oPostAfter->post_status ||
            wp_is_post_revision($oPostAfter) ||
            !$this->isContributor($oPostBefore->post_author)
        ) {
            return false;
        }
        $postId = (int)$postId;
        wp_clear_scheduled_hook($this->scheduleSendListingStatus, [$postId]);
        wp_schedule_single_event(\time() + 120, $this->scheduleSendListingStatus, [$postId]);
        return true;
    }

    public function sendEmailNotificationToCustomerAndAdmin($postId)
    {
        $postStatus = get_post_status($postId);
        $post = get_post($postId);

        switch ($postStatus) {
            case 'unpaid':
                $this->submittedListing($post);
                break;
            case 'pending':
                $this->submittedListing($post);
                $this->notifyListingPendingToAdmin($post);
                break;
            case 'rejected':
                $this->rejectListing($post);
                break;
            case 'publish':
                $this->notifyListingApprovedToCustomer($post);
                $this->notifyListingApprovedToAdmin($post);
                break;
            case 'expired':
                $this->listingExpired($post);
                break;
        }
    }

    /**
     * @param WP_Post $post
     */
    public function rejectListing(\WP_Post $post)
    {
        $this->sendEmailToCustomerAboutListingRejected($post);
    }

    /**
     * @param WP_Post $post
     * @return bool
     */
    public function sendEmailToCustomerAboutListingRejected(WP_Post $post): bool
    {
        $aThemeOptions = $this->getOptions();
        $message = $aThemeOptions['email_listing_rejected'];

        if (empty($message)) {
            return false;
        }

        $this->customerID = $post->post_author;
        $message = $this->generateReplace($message, $post->ID);
        $subject = $aThemeOptions['email_listing_rejected_subject'] ?? $aThemeOptions['email_from'];
        $subject = $this->generateReplace($subject, $post->ID);

        wp_mail($this->to(), $subject, $message);
        $this->addSentHistory(__FUNCTION__, ['postId' => $post->ID]);
        return true;
    }

    private function addSentErrorMessageTodayToStore($errId)
    {
        $errId = strtolower($errId);
        set_transient($errId, 'yes', 24 * HOUR_IN_SECONDS);
    }

    public function sendErrorMessageToSiteOwner($subject, $content, $errId)
    {
        if (!get_transient($errId)) {
            wp_mail($this->toAdmin(), $subject, $content);
            $this->addSentErrorMessageTodayToStore($errId);
        }
    }

    private function isListingPostType($postType): bool
    {
        $aPostTypes = GetSettings::getFrontendPostTypes(true, false);

        return in_array($postType, $aPostTypes);
    }

    public function sendStripePaymentIssueToAdmin($aData)
    {
        $msg = sprintf(
            esc_html__(
                'Warning: Wilcity could not handle Stripe Event Hook. This is a detail information from Stripe: %s. Make sure that you followed all Stripe configuration<a href="%s">%s</a>',
                'wiloke-listing-tools'
            ),
            $aData['msg'],
            'https://documentation.wilcity.com/knowledgebase/setting-up-stripe-gateway-in-wilcity/',
            'https://documentation.wilcity.com/knowledgebase/setting-up-stripe-gateway-in-wilcity/'
        );

        wp_mail($this->toAdmin(), esc_html__('Stripe Payment Issue', 'wiloke-listing-tools'), $msg);
    }

    public function almostExpired($postID): bool
    {
        if (!$this->isContributor(get_post_field('post_author', $postID))) {
            return false;
        }

        if (get_post_status($postID) !== 'publish') {
            return false;
        }

        $status = apply_filters('wilcity/email/sending-almost-expired', true);
        if (!$status) {
            return false;
        }
        $aThemeOptions = $this->getOptions();
        $message = $aThemeOptions['email_listing_almost_expired'];
        if (empty($message)) {
            return false;
        }
        $this->customerID = get_post_field('post_author', $postID);
        $message = $this->generateReplace($message, $postID);
        $subject = $this->createMailSubject($aThemeOptions, 'email_listing_almost_expired_subject');
        wp_mail($this->to(), $subject, $this->cleanContentBeforeSending($message));

        return true;
    }

    /**
     * @param $aMsg
     */
    public function stripeWebhookError($aMsg)
    {
        wp_mail(get_option('admin_url'), 'Stripe Webhook Error', $aMsg['msg']);
    }

    public function dokanAddQrcodeToAttachment($aAttachments, $id, $oOrder)
    {
        if ($id != 'customer_completed_order') {
            return $aAttachments;
        }
        $aTickets = QRCodeGenerator::generateTicket($oOrder->get_id());
        if (empty($aTickets)) {
            return $aAttachments;
        }

        foreach ($aTickets as $aTicket) {
            $aAttachments[] = $aTicket['dir'];
        }

        return $aAttachments;
    }

    private function sendQRCodeToCustomers($aProducts): bool
    {
        foreach ($aProducts as $productID) {
            if (QRCodeGenerator::isSendQRCodeToEmail($productID)) {
                return true;
            }
        }

        return false;
    }

    public function dokanSendQRCodeToCustomer($oOrder)
    {
        if (empty($oOrder) || is_wp_error($oOrder)) {
            return false;
        }

        if ($oOrder->get_status() !== 'completed') {
            return false;
        }
        $orderID = $oOrder->get_id();

        $aProducts = GetSettings::getDokanProductIDsByOrderID($orderID);
        if (empty($aProducts) || !$this->sendQRCodeToCustomers($aProducts)) {
            return false;
        }

        $emailContent = GetSettings::getPostMeta($aProducts[0], 'qrcode_description');
        if (empty($emailContent)) {
            $aThemeOptions = Wiloke::getThemeOptions(true);
            $emailContent = $aThemeOptions['email_qr_code'];
        }
        $emailContent = $this->generateReplace($emailContent);
        echo $emailContent;
    }

    public function addEmailTemplateSettingsToThemeOptions($aOptions)
    {
        if (!defined('MAILTPL_VERSION') && !defined('WILOKE_EMAIL_CREATOR_HOOK_PREFIX')) {
            return $aOptions;
        }

        $aOptions[] = wilokeListingToolsRepository()->getAllFileConfigs('email');

        return $aOptions;
    }

    public function testMail()
    {
        $subject = __('Wilcity Test Mail', 'email-templates');

        if (empty($_POST['target'])) {
            ob_start();
            include_once(MAILTPL_PLUGIN_DIR . '/admin/templates/partials/default-message.php');
            $message = ob_get_contents();
            ob_end_clean();
            echo wp_mail(get_bloginfo('admin_email'), $subject, $message);
            die;
        } else {
            $this->customerID = get_current_user_id();
            switch ($_POST['target']) {
                case 'email_subscription_created':
                    $this->subscriptionCreated([
                        'planTitle' => 'Test Plan',
                        'paymentID' => 1,
                        'gateway'   => 'stripe',
                        'isTrial'   => 'yes'
                    ]);
                    die;
                case 'email_changed_plan':
                    $this->changedPlan([
                        'onChangedPlan' => 'yes',
                        'newPlan'       => 'Test Mail 2'
                    ]);
                    die();
                case 'email_confirm_account':
                    $this->sendConfirmation($this->customerID, 'test', true);
                    die();
                case 'email_review_notification':
                    $aThemeOptions = $this->getOptions();
                    $this->customerID = get_current_user_id();
                    $message = $aThemeOptions['email_review_notification'];
                    $message = $this->generateReplace($message, '');
                    $message = str_replace(
                        '%reviewTitle%',
                        'Test Review Notification',
                        $message
                    );
                    wp_mail($this->to(), $aThemeOptions['email_from'], $message);
                    die();
                case 'email_report_notification':
                    $aThemeOptions = $this->getOptions();
                    $this->customerID = get_current_user_id();
                    $message = $aThemeOptions['email_report_notification'];
                    $message = $this->generateReplace($message, '');
                    $message = str_replace(
                        '%reportTitle%',
                        'Test Report Notification',
                        $message
                    );
                    wp_mail($this->to(), $aThemeOptions['email_from'], $message);
                    die();
                case 'email_order_processing':
                    $this->aBankAccountFields = [
                        'bank_transfer_account_name'   => esc_html__('Bank Account Name', 'wiloke-listing-tools'),
                        'bank_transfer_account_number' => esc_html__('Bank Account Number', 'wiloke-listing-tools'),
                        'bank_transfer_name'           => esc_html__('Bank Name', 'wiloke-listing-tools'),
                        'bank_transfer_short_code'     => esc_html__('Shortcode', 'wiloke-listing-tools'),
                        'bank_transfer_iban'           => esc_html__('IBAN', 'wiloke-listing-tools'),
                        'bank_transfer_swift'          => esc_html__('Swift', 'wiloke-listing-tools')
                    ];

                    $this->orderProcessing([
                        'planTitle'   => 'Test Mail 1',
                        'paymentID'   => 1,
                        'gateway'     => 'stripe',
                        'billingType' => 'Recurring Payment'
                    ]);
                    break;
                case 'email_receive_message':
                    $subject = '[%brand] You receive an message from A';
                    $subject = $this->generateReplace($subject);
                    $message = 'This is an Test Mail';
                    break;
                case 'email_template_creator':
                    $aResponseEmail = apply_filters(WILOKE_EMAIL_CREATOR_HOOK_PREFIX .
                        'src/Email/Controllers/TemplateController/getDataTemplates',
                        [],
                        [
                            "emailType" => "wilcity",
                            "pluck"     => "emailSubject,html",
                            "status"    => "active",
                        ]
                    );
                    if (($aResponseEmail['status'] == 'success') && isset($aResponseEmail['data']['items'][0])) {
                        $subject = $aResponseEmail['data']['items'][0]['emailSubject'];
                        $message = $aResponseEmail['data']['items'][0]['html'];
                    } else {
                        $subject = '[%brand] You receive an message from A';
                        $subject = $this->generateReplace($subject);
                        $message = 'This is an Test Mail';
                    }
                    break;
                default:
                    $aThemeOptions = $this->getOptions();
                    $message = $aThemeOptions[$_POST['target']];
                    $message = $this->generateReplace($message);
                    break;
            }
        }

        if (isset($message)) {
            echo wp_mail(get_bloginfo('admin_email'), $subject, $this->cleanContentBeforeSending($message));
            die;
        }
    }

    private function cleanContentBeforeSending($content): string
    {
        return apply_filters(
            'wiloke-listing-tools/app/Controllers/EmailControllers/cleanContentBeforeSending',
            stripslashes($content)
        );
    }

    public function enqueueCustomizeScripts()
    {
        wp_dequeue_script('mailtpl-js');
        wp_enqueue_script('wiloke-mailtpl-js', WILOKE_LISTING_TOOL_URL . 'admin/source/js/mailtpl-admin.js', ['jquery'],
            WILOKE_LISTING_TOOL_VERSION, true);
    }

    public function getOptions()
    {
        if (!class_exists('\Wiloke')) {
            return [];
        }

        if (empty($this->aThemeOptions) || !is_array($this->aThemeOptions)) {
            $this->aThemeOptions = Wiloke::getThemeOptions(true);
        }

        return $this->aThemeOptions;
    }

    public function addTestMailTarget($wp_customize)
    {
        $wp_customize->add_setting('mailtpl_opts[send_mail_target]', [
            'type'                 => 'option',
            'default'              => '',
            'transport'            => 'postMessage',
            'capability'           => 'edit_theme_options',
            'sanitize_callback'    => '',
            'sanitize_js_callback' => '',
        ]);

        $wp_customize->add_control('mailtpl_opts[send_mail_target]', [
            'type'                 => 'select',
            'default'              => '',
            'section'              => 'section_mailtpl_test',
            'transport'            => 'postMessage',
            'capability'           => 'edit_theme_options',
            'choices'              => [
                'email_welcome'                => 'Welcome Message',
                'email_confirm_account'        => 'Confirm account',
                'email_become_an_author'       => 'Became An Author Message',
                'email_review_notification'    => 'Review Notification',
                'email_report_notification'    => 'Report Notification',
                'email_listing_submitted'      => 'Listing Submitted Message',
                'email_listing_approved'       => 'Listing Approved Message',
                'email_listing_almost_expired' => 'Listing Almost Expired Message',
                'email_listing_expired'        => 'Listing Expired Message',
                'email_claim_submitted'        => 'Claim Submitted Message',
                'email_claim_approved'         => 'Claim Approved Message',
                'email_claim_rejected'         => 'Claim Rejected Message',
                'email_order_processing'       => 'Email Order Processing Message',
                'email_subscription_created'   => 'Subscription Created Message',
                'email_subscription_cancelled' => 'Subscription Cancelled Message',
                'email_changed_plan'           => 'Plan Changed Message',
                'email_promotion_submitted'    => 'Promotion Submitted Message',
                'email_promotion_approved'     => 'Email Approved Message',
                'email_receive_message'        => 'Email Receive Message From Customer',
                'email_template_creator'       => 'Email Template Creator',
            ],
            'sanitize_callback'    => '',
            'sanitize_js_callback' => '',
        ]);
    }

    private function to()
    {
        return User::getField('user_email', $this->customerID);
    }

    public function generateReplace($content, $postID = '')
    {
        $aThemeOptions = $this->getOptions();
        $displayName = User::getField('display_name', $this->customerID);
        $postTitle = '';
        $postLink = '';
        $listingEmail = '';
        $listingPhone = '';

        if (!empty($postID)) {
            $postTitle = apply_filters('wiloke/mail/postTitle', get_the_title($postID));
            $postLink = apply_filters('wiloke/mail/postLink', get_permalink($postID));
            $listingEmail = apply_filters('wiloke/mail/listingEmail', GetSettings::getPostMeta($postID, 'email'),
                $listingEmail);
            $listingPhone = apply_filters('wiloke/mail/listingPhone', GetSettings::getPostMeta($postID, 'phone'),
                $listingPhone);
        }

        $dateExpiration = GetSettings::getPostMeta($postID, 'post_expiry');
        $rejectReason
            = $_POST['wilcity_listing_rejected_reason'] ?? GetSettings::getPostMeta($postID, 'listing_rejected_reason');

        if ($dateExpiration) {
            $dateExpiration = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $dateExpiration);
        }

        return str_replace(
            [
                '%postExpiration%',
                '%customerName%',
                '%userName%',
                '%brand%',
                '%breakDown%',
                '%postTitle%',
                '%postLink%',
                '%websiteUrl%',
                '%adminEmail%',
                '%close_h1%',
                '%close_h2%',
                '%close_h3%',
                '%close_h4%',
                '%close_h5%',
                '%close_h6%',
                '%close_strong%',
                '%h1%',
                '%h2%',
                '%h3%',
                '%h4%',
                '%h5%',
                '%h6%',
                '%strong%',
                '%rejectedReason%',
                '%listingEmail%',
                '%listingPhone%'
            ],
            [
                $dateExpiration,
                $displayName,
                $displayName,
                $aThemeOptions['email_brand'],
                '<br />',
                $postTitle,
                $postLink,
                home_url('/'),
                $aThemeOptions['email_from'],
                '</h1>',
                '</h2>',
                '</h3>',
                '</h4>',
                '</h5>',
                '</h6>',
                '</strong>',
                '<h1>',
                '<h2>',
                '<h3>',
                '<h4>',
                '<h5>',
                '<h6>',
                '<strong>',
                $rejectReason,
                $listingEmail,
                $listingPhone
            ],
            $content
        );
    }

    public function createMailSubject($aThemeOptions, $key)
    {
        if (isset($aThemeOptions[$key]) && !empty($aThemeOptions[$key])) {
            $subject = $this->generateReplace($aThemeOptions[$key]);
        } else {
            $subject = $aThemeOptions['email_from'];
        }

        return $subject;
    }

    public function sayWelcome($userID): bool
    {
        $aThemeOptions = $this->getOptions();
        if (!isset($aThemeOptions['email_welcome']) || empty(trim($aThemeOptions['email_welcome']))) {
            return false;
        }

        $welcome = $aThemeOptions['email_welcome'];
        $this->customerID = $userID;

        $welcome = $this->generateReplace($welcome);
        $subject = $this->createMailSubject($aThemeOptions, 'email_welcome_subject');

        return wp_mail(
            $this->to(),
            $subject,
            $this->cleanContentBeforeSending($welcome)
        );
    }

    public function sendDisputePaymentWarningToCustomer($aInfo): bool
    {
        $userID = PaymentModel::getField('userID', $aInfo['paymentID']);
        if (empty($userID)) {
            return false;
        }
        $aThemeOptions = $this->getOptions();
        $this->customerID = $userID;
        $msg = $aThemeOptions['email_payment_dispute'];
        $msg = $this->generateReplace($msg);

        $msg = str_replace(
            [
                '%paymentID%'
            ],
            [
                $aInfo['paymentID']
            ],
            $msg
        );

        $subject = $this->createMailSubject($aThemeOptions, 'email_payment_dispute_subject');
        return wp_mail($this->to(), $subject, $this->cleanContentBeforeSending($msg));
    }

    public function sendFailedPaymentNotificationToAdmin($aInfo): bool
    {
        $userID = PaymentModel::getField('userID', $aInfo['paymentID']);
        if (empty($userID)) {
            return false;
        }

        $msg
            = sprintf(esc_html__('Payment for order %d from %s has failed.', 'wiloke-listing-tools'),
            $aInfo['paymentID'],
            User::getField('display_name', $userID));
        $subject = esc_html__('Payment Failed', 'wiloke-listing-tools');
        return wp_mail($this->toAdmin(), $subject, $this->cleanContentBeforeSending($msg));
    }

    public function sendSuspendedPaymentNotificationToAdmin($aInfo): bool
    {
        $userID = PaymentModel::getField('userID', $aInfo['paymentID']);
        if (empty($userID)) {
            return false;
        }

        $msg = sprintf(esc_html__('Payment for order %d from %s has suspended.', 'wiloke-listing-tools'),
            $aInfo['paymentID'], User::getField('display_name', $userID));
        $subject = esc_html__('Payment Suspended', 'wiloke-listing-tools');
        return wp_mail($this->toAdmin(), $subject, $this->cleanContentBeforeSending($msg));
    }

    public function sendCancelledPaymentNotificationToCustomer($aInfo): bool
    {
        $userID = PaymentModel::getField('userID', $aInfo['paymentID']);
        if (empty($userID)) {
            return false;
        }

        $this->customerID = $userID;
        $msg
            = $this->generateReplace(WilokeThemeOptions::getOptionDetail('email_subscription_cancelled'));
        $planName = !empty($aInfo['planID']) ? get_the_title($aInfo['planID']) :
            PaymentMetaModel::get($aInfo['paymentID'], 'planName');

        $msg = str_replace(
            '%planName%',
            $planName,
            $msg
        );

        $subject = esc_html__('Payment Cancelled', 'wiloke-listing-tools');
        return wp_mail($this->to(), $subject, $this->cleanContentBeforeSending($msg));
    }

    public function sendRefundedPaymentNotificationToCustomer($aInfo): bool
    {
        $userID = PaymentModel::getField('userID', $aInfo['paymentID']);
        if (empty($userID)) {
            return false;
        }

        $this->customerID = $userID;
        $msg
            = $this->generateReplace(WilokeThemeOptions::getOptionDetail('email_subscription_refunded'));
        $planName = !empty($aInfo['planID']) ? get_the_title($aInfo['planID']) :
            PaymentMetaModel::get($aInfo['paymentID'], 'planName');

        $msg = str_replace(
            '%planName%',
            $planName,
            $msg
        );

        $subject = esc_html__('Payment Refunded', 'wiloke-listing-tools');
        return wp_mail($this->to(), $subject, $this->cleanContentBeforeSending($msg));
    }

    public function sendCancelledPaymentNotificationToAdmin($aInfo): bool
    {
        $userID = PaymentModel::getField('userID', $aInfo['paymentID']);
        if (empty($userID)) {
            return false;
        }

        $msg = sprintf(
            esc_html__('%s canceled automatic payments to you. This means we\'ll no longer automatically draw money from their account to pay you. If you have any questions, you may ask the customer directly about this cancellation. Payment ID: %d, Gateway: %s',
                'wiloke-listing-tools'),
            User::getField('display_name', $userID),
            $aInfo['paymentID'],
            PaymentModel::getField('gateway', $aInfo['paymentID'])
        );

        $subject = esc_html__('Payment Cancelled', 'wiloke-listing-tools');
        return wp_mail($this->toAdmin(), $subject, $this->cleanContentBeforeSending($msg));
    }

    public function sendDisputePaymentWarningToAdmin($aInfo): bool
    {
        $aPaymentMetaInfo = PaymentMetaModel::getPaymentInfo($aInfo['paymentID']);
        if (empty($aPaymentMetaInfo)) {
            FileSystem::logError('We could not send warning dispute to admin email');

            return false;
        }

        $msg
            = sprintf(esc_html__('%s customer purchased %s plan, but there was a dispute in that payment session. To check the detail information, please go to Wiloke Submission -> Sales / Subscriptions -> Check for this payment ID: %d',
            'wiloke-listing-tools'), User::getField('display_name', $aPaymentMetaInfo['userID']), get_the_title
        ($aPaymentMetaInfo['planID']), $aInfo['paymentID']);

        $subject = esc_html__('Payment Dispute', 'wiloke-listing-tools');
        return wp_mail($this->toAdmin(), $subject, $this->cleanContentBeforeSending($msg));
    }

    public function sendNotificationListingAlmostDeleted($postID): bool
    {
        $aThemeOptions = $this->getOptions();
        if (!isset($aThemeOptions['email_listing_almost_deleted']) ||
            empty($aThemeOptions['email_listing_almost_deleted'])
        ) {
            return false;
        }

        $msg = $aThemeOptions['email_listing_almost_deleted'];
        $msg = $this->generateReplace($msg);

        $deletedAt = GetSettings::getPostMeta($postID, 'fwarning_delete_listing');
        if (empty($deletedAt)) {
            $deletedAt = GetSettings::getPostMeta($postID, 'swarning_delete_listing');
            if (empty($deletedAt)) {
                $deletedAt = GetSettings::getPostMeta($postID, 'twarning_delete_listing');
            }
        }

        $msg = str_replace(
            [
                '%date%',
                '%hour%'
            ],
            [
                date_i18n(get_option('date_format'), $deletedAt),
                date_i18n(get_option('time_format'), $deletedAt),
            ],
            $msg
        );

        $this->customerID = get_post_field('post_author', $postID);
        $subject = $this->createMailSubject($aThemeOptions, 'email_listing_almost_deleted_subject');
        return wp_mail($this->to(), $subject, $this->cleanContentBeforeSending($msg));
    }

    public function ajaxSendMessageToEmail($aInfo)
    {
        $aRequires = [
            'chattedWithId',
            'msg'
        ];
        foreach ($aRequires as $field) {
            if (empty($aInfo[$field])) {
                return false;
            }
        }

        $email = User::getField('user_email', $aInfo['chattedWithId']);
        if (!empty($email)) {
            $this->sendMessageToEmail($aInfo['chattedWithId'], User::getCurrentUserID(), $aInfo['msg']);
        }
    }

    public function sendMessageToEmail($receiverID, $senderID, $content)
    {
        if (Firebase::isFirebaseEnable()) {
            if (!Firebase::isCustomerEnable('privateMessages', $receiverID)) {
                return false;
            }
        }

        $allow = GetSettings::getUserMeta($receiverID, 'send_email_if_reply_message');
        if ($allow != 'yes') {
            return '';
        }
        $aThemeOptions = Wiloke::getThemeOptions(true);

        if (user_can($receiverID, 'administrator')) {
            $subject = '[%brand%]' . sprintf(__(' You receive a message from %s', 'wiloke-listing-tools'),
                    User::getField('display_name', $senderID));
            $to = GetSettings::adminEmail();
        } else {
            if (!isset($aThemeOptions['email_when_reply_to_customer'])) {
                $subject = '[%brand%] replied on your inbox';
            } else {
                $subject = $aThemeOptions['email_when_reply_to_customer'];
            }
            $to = User::getField('user_email', $receiverID);
        }

        $subject = $this->generateReplace($subject);

        $dashboardURL = GetWilokeSubmission::getField('dashboard_page', true);
        $dashboardURL .= '#messages?u=' . urlencode(User::getField('user_login', $senderID));
        $content .= "\r\n" . sprintf(__('To reply this message, please click on %s', 'wiloke-listing-tools'),
                $dashboardURL);
        wp_mail($to, $subject, $this->cleanContentBeforeSending($content));
    }

    private function toAdmin()
    {
        $aThemeOptions = $this->getOptions();
        return $aThemeOptions['email_from'] ?? get_option('admin_email');
    }

    public function replaceEmailFrom()
    {
        return $this->toAdmin();
    }

    public function addSentHistory($method, $aArgs = [])
    {
        if (!isset($this->aSentHistory[$method])) {
            $this->aSentHistory[$method] = [
                'items' => [
                    [
                        'args' => $aArgs,
                        'time' => date('Y-m-d H:i:a')
                    ]
                ],
                'count' => 1
            ];
        } else {
            $aItems = $this->aSentHistory[$method]['items'];
            $aItems[] = [
                'args' => $aArgs,
                'time' => date('Y-m-d H:i:a')
            ];

            $this->aSentHistory[$method] = [
                'items' => $aItems,
                'count' => absint($this->aSentHistory[$method]['count']) + 1
            ];
        }

        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG && function_exists('error_log')) {
            error_log(var_export($this->aSentHistory, true));
        }
    }

    public function becameAnAuthor($userID)
    {
        $aThemeOptions = $this->getOptions();
        $content = $aThemeOptions['email_become_an_author'];

        if (empty($content)) {
            return false;
        }

        $this->customerID = $userID;

        $content = $this->generateReplace($content);
        $subject = $this->createMailSubject($aThemeOptions, 'email_become_an_author_subject');
        wp_mail($this->to(), $subject, $this->cleanContentBeforeSending($content));

        $this->addSentHistory(__FUNCTION__, ['userId' => $userID]);
    }

    public function submittedListing(WP_Post $post): bool
    {
        $this->sendEmailToCustomerAboutANewListingSubmitted($post);
        $this->addSentHistory(__FUNCTION__, ['postId' => $post->ID]);

        return true;
    }

    public function sendEmailToCustomerAboutANewListingSubmitted(WP_Post $post): bool
    {
        if (!$this->isContributor($post->post_author)) {
            return false;
        }

        $aThemeOptions = $this->getOptions();
        if (empty($aThemeOptions['email_listing_submitted'])) {
            return false;
        }

        $message = $aThemeOptions['email_listing_submitted'];

        $this->customerID = $post->post_author;
        $message = $this->generateReplace($message, $post->ID);
        $subjectSubmitted = $aThemeOptions['email_listing_submitted_subject'] ?? $aThemeOptions['email_from'];
        $subjectSubmitted = $this->generateReplace($subjectSubmitted, $post->ID);

//        file_put_contents( plugin_dir_path(__FILE__) ."email.log", json_encode([
//            'email'   => $this->to(),
//            'content' => $message,
//            'subject' => $subjectSubmitted
//        ]));
        return wp_mail($this->to(), $subjectSubmitted, $message);
    }

    private function sendEmailToAdminAboutANewListingSubmitted(WP_Post $post): bool
    {
        if (!$this->isContributor($post->post_author)) {
            return false;
        }

        $displayName = User::getField('display_name', $post->post_author);
        $subject = sprintf(
            esc_html__('%s just submitted a Listing to your site %s', 'wiloke-listing-tools'),
            $displayName,
            get_option('blogname')
        );

        $content = sprintf(
            __('%s just submitted %s to your site at %s. <a href="%s">Click here</a> to review this listing',
                'wiloke-listing-tools'),
            $displayName,
            $post->post_title,
            date(
                get_option('date_format') . ' ' . get_option('time_format'),
                strtotime(get_post_field('post_date', $post->ID))
            ),
            add_query_arg(['post' => $post->ID, 'action' => 'edit'], admin_url('post.php'))
        );

        return wp_mail($this->toAdmin(), $subject, $content);
    }

    private function sendEmailToAdminAboutANewListingApproved(WP_Post $post): bool
    {
        if (!$this->isContributor($post->post_author)) {
            return false;
        }

        $subject = sprintf(
            esc_html__('%s has been published on your site %s', 'wiloke-listing-tools'),
            $post->post_title,
            get_option('blogname')
        );

        $content = sprintf(
            __('%s has been published on your site at %s. <a href="%s">Click here</a> to check the listing',
                'wiloke-listing-tools'),
            $post->post_title,
            date(
                get_option('date_format') . ' ' . get_option('time_format'),
                strtotime(get_post_field('post_date', $post->ID))
            ),
            add_query_arg(['post' => $post->ID, 'action' => 'edit'], admin_url('post.php'))
        );

        return wp_mail($this->toAdmin(), $subject, $content);
    }

    /**
     * @param WP_Post $post
     * @return bool
     */
    public function notifyListingPendingToAdmin(\WP_Post $post): bool
    {
//        if (!$this->isContributor($oAfter->post_author) || !$this->isListingPostType($oAfter->post_type)) {
//            return false;
//        }
//
//        if ($oAfter->post_status !== 'pending' || ($isUpdated && $oAfter->post_status == $oBefore->post_status)) {
//            return false;
//        }

        $this->sendEmailToAdminAboutANewListingApproved($post);
        $this->addSentHistory(__FUNCTION__, ['postId' => $post->ID]);

        return true;
    }

//    public function notifyListingApprovedToAdminAnotherHook($postID, $oAfter, $isUpdated, $oBefore): bool
//    {
//        return $this->notifyListingApprovedToAdmin($postID, $oAfter, $oBefore);
//    }

    public function notifyListingApprovedToAdmin(\WP_Post $post): bool
    {
//        if (!$this->isContributor($oAfter->post_author)) {
//            return false;
//        }
//
//        if ($oAfter->post_status !== 'publish' ||
//            ($oBefore instanceof WP_Post && $oAfter->post_status == $oBefore->post_status)) {
//            return false;
//        }

        $this->sendEmailToAdminAboutANewListingApproved($post);

        $this->addSentHistory(__FUNCTION__, ['postId' => $post->ID]);

        return true;
    }

//    public function notifyListingApprovedToCustomerAnotherHook($postID, $oAfter, $isUpdated, $oBefore): bool
//    {
//        return $this->notifyListingApprovedToCustomer($postID, $oAfter, $oBefore);
//    }

    public function notifyListingApprovedToCustomer(WP_Post $post): bool
    {
//        if (!$this->isContributor($oAfter->post_author)) {
//            return false;
//        }
//
//        if ($oAfter->post_status !== 'publish' ||
//            ($oBefore instanceof \WP_Post && $oBefore->post_status === 'publish')) {
//            return false;
//        }

//        $aListingKeys = General::getPostTypeKeys(true, false, true);

//        if (!in_array($oAfter->post_type, $aListingKeys)) {
//            return false;
//        }

//        $planID = GetSettings::getListingBelongsToPlan($post->ID);
//
//        if (empty($planID)) {
//            return false;
//        }

        $message = WilokeThemeOptions::getOptionDetail('email_listing_approved');
        if (empty($message)) {
            return false;
        }

        $this->customerID = $post->post_author;
        $message = $this->generateReplace($message, $post->ID);

        $subject = $this->createMailSubject(Wiloke::getThemeOptions(true), 'email_listing_approved_subject');
        wp_mail($this->to(), $subject, $this->cleanContentBeforeSending($message));

        $this->addSentHistory(__FUNCTION__, ['postId' => $post->ID]);
        return true;
    }

    public function notifyCustomerClaimedListingToAdmin($aInfo)
    {
        $message = sprintf(
            __('%s asked for claiming %s on your site %s.<a href="%s">View Claim Detail</a>', 'wiloke-listing-tools'),
            User::getField('display_name', $aInfo['claimerID']),
            get_the_title($aInfo['postID']),
            get_option('siteurl'),
            add_query_arg(
                [
                    'action' => 'edit',
                    'post'   => $aInfo['claimID']
                ],
                admin_url('post.php')
            )
        );

        wp_mail($this->toAdmin(), get_the_title($aInfo['claimID']), $this->cleanContentBeforeSending($message));

        $this->addSentHistory(__FUNCTION__, ['info' => $aInfo]);
    }

    public function listingExpired(\WP_Post $post): bool
    {
        $aThemeOptions = $this->getOptions();
        $message = WilokeThemeOptions::getOptionDetail('email_listing_expired');
        if (empty($message)) {
            return false;
        }

        $this->customerID = $post->post_author;
        $message = $this->generateReplace($message, $post->ID);
        $subject = $this->createMailSubject($aThemeOptions, 'email_listing_expired_subject');
        wp_mail($this->to(), $subject, $this->cleanContentBeforeSending($message));

        $this->addSentHistory(__FUNCTION__, ['postId' => $post->ID]);

        return true;
    }

    public function notifyClaimToAdmin($userID, $postID, $claimID)
    {
        $message = sprintf(
            __('%s wants to claim %s on your site %s.<a href="%s">View Claim Detail</a>', 'wiloke-listing-tools'),
            GetSettings::getUserMeta($userID, 'display_name'),
            get_the_title($postID),
            get_option('siteurl'),
            add_query_arg(
                [
                    'action' => 'edit',
                    'post'   => $claimID
                ],
                admin_url('post.php')
            )
        );

        wp_mail($this->toAdmin(), get_the_title($claimID), $this->cleanContentBeforeSending($message));
        $this->addSentHistory(__FUNCTION__, ['postId' => $postID]);

        return true;
    }

    public function claimSubmitted($userID, $postID): bool
    {
        $aThemeOptions = $this->getOptions();
        $message = $aThemeOptions['email_claim_submitted'];
        if (empty($message)) {
            return false;
        }

        $this->customerID = $userID;
        $message = $this->generateReplace($message, $postID);

        $subject = $this->createMailSubject($aThemeOptions, 'email_claim_submitted_subject');
        wp_mail($this->to(), $subject, $this->cleanContentBeforeSending($message));
        $this->addSentHistory(__FUNCTION__, ['postId' => $postID]);

        return true;
    }

    public function notifyClaimHasBeenApprovedToAdmin($aInfo): bool
    {
        $aThemeOptions = $this->getOptions();
        $message = WilokeThemeOptions::getOptionDetail('email_to_admin_claim_approved');
        if (empty($message)) {
            return false;
        }
        $this->customerID = $aInfo['claimerID'];
        $message = $this->generateReplace($message, $aInfo['postID']);
        $subject = $this->createMailSubject($aThemeOptions, 'email_to_admin_claim_approved_subject');
        wp_mail($this->toAdmin(), $subject, $this->cleanContentBeforeSending($message));
        $this->addSentHistory(__FUNCTION__, ['info' => $aInfo]);

        return true;
    }

    public function notifyClaimHasBeenApprovedToCustomer($aInfo): bool
    {
        $aThemeOptions = $this->getOptions();
        $message = WilokeThemeOptions::getOptionDetail('email_claim_approved');
        if (empty($message)) {
            return false;
        }
        //        info@scraplocal.co.uk
        $this->customerID = $aInfo['claimerID'];
        $message = $this->generateReplace($message, $aInfo['postID']);
        $subject = $this->createMailSubject($aThemeOptions, 'email_claim_approved_subject');
        wp_mail($this->to(), $subject, $this->cleanContentBeforeSending($message));
        $this->addSentHistory(__FUNCTION__, ['info' => $aInfo]);

        return true;
    }

    public function claimRejected($metaID, $objectID, $metaKey, $metaValue): bool
    {
        if ($metaKey !== 'wilcity_claim_status') {
            return false;
        }

        if ($metaValue != 'cancelled') {
            return false;
        }

        $aThemeOptions = $this->getOptions();
        $message = $aThemeOptions['email_claim_rejected'];
        if (empty($message)) {
            return false;
        }

        $this->customerID = GetSettings::getPostMeta($objectID, 'wilcity_claimer_id');
        $postID = GetSettings::getPostMeta($objectID, 'wilcity_claimed_listing_id');
        $message = $this->generateReplace($message, $postID);

        $subject = $this->createMailSubject($aThemeOptions, 'email_claim_rejected_subject');
        wp_mail($this->to(), $subject, $this->cleanContentBeforeSending($message));
        $this->addSentHistory(__FUNCTION__, ['postId' => $postID]);

        return true;
    }

    private function getBankAccount()
    {
        $this->aConfiguration = GetWilokeSubmission::getAll();

        $aInfo = [
            'bank_transfer_account_name'   => esc_html__('Bank Account Name', 'wiloke-listing-tools'),
            'bank_transfer_account_number' => esc_html__('Bank Account Number', 'wiloke-listing-tools'),
            'bank_transfer_name'           => esc_html__('Bank Name', 'wiloke-listing-tools'),
            'bank_transfer_short_code'     => esc_html__('Shortcode', 'wiloke-listing-tools'),
            'bank_transfer_iban'           => esc_html__('IBAN', 'wiloke-listing-tools'),
            'bank_transfer_swift'          => esc_html__('Swift', 'wiloke-listing-tools')
        ];

        for ($i = 1; $i <= 4; $i++) {
            if (!empty($this->aConfiguration['bank_transfer_account_name_' . $i]) &&
                !empty($this->aConfiguration['bank_transfer_account_number_' . $i]) &&
                !empty($this->aConfiguration['bank_transfer_name_' . $i])
            ) {
                foreach ($aInfo as $bankInfo => $name) {
                    if (!empty($this->aConfiguration[$bankInfo . '_' . $i])) {
                        $this->aBankAccountFields[$bankInfo] = $name;
                        $this->aBankAccounts[$i][$bankInfo] = $this->aConfiguration[$bankInfo . '_' . $i];
                    }
                }
            }
        }
    }

    public function sendTestInvoice()
    {
        $aData = [
            'paymentID' => 65,
            'total'     => 59,
            'subTotal'  => 30,
            'tax'       => 30,
            'currency'  => '$',
            'discount'  => 0,
            'invoiceID' => 59
        ];

        $this->sendEmailInvoice($aData);
    }

    public function sendEmailInvoice($aData)
    {
        $sendInvoice = apply_filters('wilcity/filter/is-send-invoice', true);

        if (!$sendInvoice) {
            return false;
        }

        if (in_array('invoice_' . $aData['invoiceID'], $this->aSentEmails)) {
            return false;
        }

        $this->aSentEmails[] = 'invoice_' . $aData['invoiceID'];

        $planID = PaymentModel::getField('planID', $aData['paymentID']);
        if (!empty($planID)) {
            $planName = get_the_title($planID);
        }

        $this->customerID = PaymentModel::getField('userID', $aData['paymentID']);
        if (!isset($planName) || empty($planName)) {
            $planName = PaymentMetaModel::get($aData['paymentID'], 'planName');
        }
        $aOptions = Wiloke::getThemeOptions(true);
        ob_start();
        ?>

        <h3><?php esc_html_e('INVOICE', 'wiloke-listing-tools'); ?></h3>
        <p><strong><?php echo esc_html(GetWilokeSubmission::getField('brandname')); ?></strong></p>
        <?php
        if (!empty($aOptions['email_send_invoice'])):
            $aOptions['email_send_invoice'] = $this->generateReplace($aOptions['email_send_invoice']);
            ?>
            <p><?php Wiloke::ksesHTML($aOptions['email_send_invoice']); ?></p>
        <?php endif; ?>
        <p><strong><?php esc_html_e('Invoice ID', 'wiloke-listing-tools'); ?>
                :</strong> <?php echo esc_html($aData['invoiceID']); ?></p>
        <p><strong><?php esc_html_e('Invoice date', 'wiloke-listing-tools'); ?>
                :</strong> <?php echo date_i18n(get_option('date_format'), current_time('timestamp')); ?></p>

        <?php do_action('wilcity/invoice/before-table', $aData, $aOptions); ?>
        <table width="100%">
            <thead>
            <tr>
                <th><?php esc_html_e('Description', 'wiloke-listing-tools'); ?></th>
                <th><?php esc_html_e('Total', 'wiloke-listing-tools'); ?></th>
                <th><?php esc_html_e('Discount', 'wiloke-listing-tools'); ?></th>
                <th><?php esc_html_e('TAX/VAT', 'wiloke-listing-tools'); ?></th>
                <th><?php esc_html_e('Sub Total', 'wiloke-listing-tools'); ?></th>
                <?php do_action('wilcity/invoice/add-more-th', $aData, $aOptions); ?>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><?php echo esc_html($planName); ?></td>
                <td><?php echo GetWilokeSubmission::renderPrice($aData['total'], $aData['currency']); ?></td>
                <td><?php echo GetWilokeSubmission::renderPrice($aData['discount'], $aData['currency']); ?></td>
                <td><?php echo GetWilokeSubmission::renderPrice($aData['tax'], $aData['currency']); ?></td>
                <td><?php echo GetWilokeSubmission::renderPrice($aData['subTotal'], $aData['currency']); ?></td>
                <?php do_action('wilcity/invoice/add-more-td', $aData, $aOptions); ?>
            </tr>
            </tbody>
        </table>
        <?php
        $aData['userID'] = $this->customerID;
        $downloadAttachmentURL = add_query_arg(
            [
                'action'    => 'new_download_invoice',
                'invoiceID' => $aData['invoiceID'],
                'userID'    => $aData['userID']
            ],
            home_url('/')
        );

        echo '<br /><a href="' . esc_url($downloadAttachmentURL) . '">' . esc_html__('Download as PDF',
                'wiloke-listing-tools') . '</a>' . '<br />';
        echo esc_html__('Warning: In order to download the Invoice You will have to log into your account.',
            'wiloke-listing-tools');

        do_action('wilcity/invoice/after-table', $aData, $aOptions);
        $message = ob_get_contents();
        ob_end_clean();

        $subject = $this->createMailSubject($aOptions, 'email_send_invoice_subject');
        wp_mail($this->to(), $subject, $this->cleanContentBeforeSending($message));

        $this->addSentHistory(__FUNCTION__);
    }

    private function getBankAccounts()
    {
        $this->getBankAccount();
        $bankAccount = '';
        if (!empty($this->aBankAccounts)):
            $total = count($this->aBankAccountFields);
            ob_start();
            ?>
            <table width="100%">
                <tr>
                    <?php foreach ($this->aBankAccountFields as $class => $name) : ?>
                        <th class="<?php echo esc_attr($class); ?>"
                            width="(100/<?php echo esc_attr($total); ?>)"><?php echo esc_html($name); ?></th>
                    <?php endforeach; ?>
                </tr>
                <?php foreach ($this->aBankAccounts as $aBankAccount) : ?>
                    <tr>
                        <td width="(100/<?php echo esc_attr($total); ?>)"><?php echo esc_html($aBankAccount['bank_transfer_account_name']); ?></td>
                        <td width="(100/<?php echo esc_attr($total); ?>)"><?php echo esc_html($aBankAccount['bank_transfer_account_number']); ?></td>
                        <td width="(100/<?php echo esc_attr($total); ?>)"><?php echo esc_html($aBankAccount['bank_transfer_name']); ?></td>
                        <?php if (!empty($aBankAccount['bank_transfer_short_code'])) : ?>
                            <td width="(100/<?php echo esc_attr($total); ?>)"><?php echo esc_html($aBankAccount['bank_transfer_short_code']); ?></td>
                        <?php endif; ?>
                        <?php if (!empty($aBankAccount['bank_transfer_iban'])) : ?>
                            <td width="(100/<?php echo esc_attr($total); ?>)"><?php echo esc_html($aBankAccount['bank_transfer_iban']); ?></td>
                        <?php endif; ?>
                        <?php if (!empty($aBankAccount['bank_transfer_swift'])) : ?>
                            <td width="(100/<?php echo esc_attr($total); ?>)"><?php echo esc_html($aBankAccount['bank_transfer_swift']); ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?php
            $bankAccount = ob_get_contents();
            ob_end_clean();
        endif;

        return $bankAccount;
    }

    public function paymentFailed($aData)
    {
        $aThemeOptions = $this->getOptions();
        $message = $aThemeOptions['email_stripe_payment_failed'];
        if (empty($message)) {
            return false;
        }

        $this->customerID = PaymentModel::getField('userID', $aData['paymentID']);

        $planName = get_the_title($aData['paymentID']);
        if (empty($planName)) {
            $planName = PaymentMetaModel::get($aData['paymentID'], 'planName');
        }

        $message = str_replace(
            [
                '%paymentID%',
                '%planName%'
            ],
            [
                $aData['paymentID'],
                $planName
            ],
            $message
        );

        wp_mail($this->toAdmin(), $aThemeOptions['email_stripe_payment_subject'],
            sprintf(__('There was a failed payment on %s. Payment ID: %d. Customer Email: %s. Customer ID: %d.',
                'wiloke-listing-tools'), $planName, $aData['paymentID'],
                User::getField('user_email', $this->customerID), $this->customerID));

        wp_mail($this->to(), $aThemeOptions['email_stripe_payment_subject'],
            $this->cleanContentBeforeSending($message));
        $this->addSentHistory(__FUNCTION__, $aData);
    }

    public function notifyAdminAnOrderCreated($aOrderInfo)
    {
        $oUser = new WP_User($aOrderInfo['userID']);
        if (isset($aOrderInfo['ID']) && !empty($aOrderInfo['ID'])) {
            $msg = sprintf(
                esc_html__('You just received an order from %s. The order id is %d', 'wiloke-listing-tools'),
                $oUser->display_name,
                $aOrderInfo['ID']
            );
        } else {
            $msg = sprintf(
                esc_html__('You just received an order from %s.', 'wiloke-listing-tools'),
                $oUser->display_name
            );
        }

        $subject = esc_html__('You just received a new order', 'wiloke-listing-tools');

        wp_mail($this->toAdmin(), $subject, $msg);
        $this->addSentHistory(__FUNCTION__);
    }

    public function orderProcessing($aOrderInfo)
    {
        $allowedToSend
            = apply_filters('wilcity/filter/wiloke-listing-tools/app/Controllers/EmailController/orderProcessing/allowed-to-send',
            true);
        if (!$allowedToSend || !isset($aOrderInfo['gateway']) || $aOrderInfo['gateway'] != 'banktransfer') {
            return false;
        }

        $aThemeOptions = $this->getOptions();

        if (!isset($aThemeOptions['email_order_processing']) || empty($aThemeOptions['email_order_processing'])) {
            return false;
        }

        $message = $aThemeOptions['email_order_processing'];
        $billingType = GetWilokeSubmission::isNonRecurringPayment(
            $aOrderInfo['billingType']
        ) ? esc_html__('Non Recurring Payment', 'wiloke-listing-tools') :
            esc_html__('Recurring Payment', 'wiloke-listing-tools');

        $this->customerID = $aOrderInfo['userID'];
        $paymentID = PaymentMetaModel::getPaymentIDByToken($aOrderInfo['token']);
        $aPaymentInfo = PaymentMetaModel::getPaymentInfo($paymentID);

        ob_start();
        do_action('wilcity/email/banktransfer/order-processing/before-table', $aThemeOptions, $this->customerID);
        ?>
        <table width="100%">
            <tr>
                <th width="100/5"><?php esc_html_e('Payment ID', 'wiloke-listing-tools'); ?></th>
                <th width="100/5"><?php esc_html_e('Billing Type', 'wiloke-listing-tools'); ?></th>
                <th width="100/5"><?php esc_html_e('Plan Name', 'wiloke-listing-tools'); ?></th>
                <th width="100/5"><?php esc_html_e('Gateway', 'wiloke-listing-tools'); ?></th>
                <th width="100/5"><?php esc_html_e('Created At', 'wiloke-listing-tools'); ?></th>
            </tr>
            <tr>
                <td width="100/5"><?php echo esc_html($paymentID); ?></td>
                <td width="100/5"><?php echo esc_html($billingType); ?></td>
                <td width="100/5"><?php echo esc_html($aPaymentInfo['planName']); ?></td>
                <td width="100/5"><?php echo esc_html__('Bank Transfer', 'wiloke-listing-tools'); ?></td>
                <td width="100/5"><?php echo date_i18n(get_option('date_format'), current_time('timestamp')); ?></td>
            </tr>
        </table>
        <?php
        do_action('wilcity/email/banktransfer/order-processing/after-table', $aThemeOptions, $this->customerID);
        $orderDetail = ob_get_contents();
        ob_end_clean();

        $message = str_replace(
            [
                '%orderDetails%',
                '%adminBankAccount%',
            ],
            [
                $orderDetail,
                $this->getBankAccounts()
            ],
            $message
        );

        $message = apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Controller/email-processing/content',
            $this->generateReplace($message),
            $aPaymentInfo,
            $paymentID,
            $aOrderInfo
        );

        $subject = $this->createMailSubject($aThemeOptions, 'email_order_processing_subject');
        wp_mail($this->to(), $subject, $this->cleanContentBeforeSending($message));
        $this->addSentHistory(__FUNCTION__);
    }


    public function subscriptionCreated($aData)
    {
        $aThemeOptions = $this->getOptions();
        $message = $aThemeOptions['email_subscription_created'];
        if (empty($message)) {
            return false;
        }

        $this->customerID = PaymentModel::getField('userID', $aData['paymentID']);
        $message = $this->generateReplace($message);

        ob_start();
        ?>
        <table width="100%">
            <tr>
                <th width="100/5"><?php esc_html_e('Subscription ID', 'wiloke-listing-tools'); ?></th>
                <th width="100/5"><?php esc_html_e('Gateway', 'wiloke-listing-tools'); ?></th>
                <th width="100/5"><?php esc_html_e('Is Trial?', 'wiloke-listing-tools'); ?></th>
                <th width="100/5"><?php esc_html_e('Plan Name', 'wiloke-listing-tools'); ?></th>
                <th width="100/5"><?php esc_html_e('Created At', 'wiloke-listing-tools'); ?></th>
            </tr>
            <tr>
                <td width="100/5"><?php echo esc_html($aData['paymentID']); ?></td>
                <td width="100/5">
                    <?php
                    if ($aData['gateway'] == 'banktransfer') {
                        esc_html_e('Bank Transfer', 'wiloke-listing-tools');
                    } else {
                        echo esc_html($aData['gateway']);
                    }
                    ?>
                </td>
                <td width="100/5">
                    <?php
                    if (isset($aData['isTrial']) && $aData['isTrial']) {
                        esc_html_e('Yes', 'wiloke-listing-tools');
                    } else {
                        esc_html_e('No', 'wiloke-listing-tools');
                    }
                    ?>
                </td>
                <td width="100/5"><?php echo isset($aData['planTitle']) ? esc_html($aData['planTitle']) :
                        get_the_title($aData['planID']); ?></td>
                <td width="100/5"><?php echo date_i18n(get_option('date_format'), current_time('timestamp')); ?></td>
            </tr>
        </table>
        <?php
        $content = ob_get_contents();
        ob_end_clean();

        $message = str_replace(
            [
                '%subscriptionDetails%',
                '%subscriptionNumber%',
            ],
            [
                $content,
                $aData['paymentID']
            ],
            $message
        );

        $message = $this->generateReplace($message);
        $subject = $this->createMailSubject($aThemeOptions, 'email_subscription_created_subject');
        wp_mail($this->to(), $subject, $this->cleanContentBeforeSending($message));
        $this->addSentHistory(__FUNCTION__);
    }

    public function changedPlan($aData)
    {
        if (!isset($aData['onChangedPlan']) || $aData['onChangedPlan'] !== 'yes') {
            return false;
        }

        $aThemeOptions = $this->getOptions();
        $message = $aThemeOptions['email_changed_plan'];
        if (empty($message)) {
            return false;
        }

        $message = str_replace(
            [
                '%subscriptionNumber%',
                '%oldPlan%',
                '%newPlan%'
            ],
            [
                $aData['paymentID'],
                get_the_title($aData['oldPlanID']),
                get_the_title($aData['planID']),
            ],
            $message
        );

        $message = $this->generateReplace($message);
        $subject = $this->createMailSubject($aThemeOptions, 'email_changed_plan_subject');
        wp_mail($this->to(), $subject, $this->cleanContentBeforeSending($message));
        $this->addSentHistory(__FUNCTION__, $aData);
    }

    public function promotionCreated($userID, $postID)
    {
        $aThemeOptions = $this->getOptions();
        $message = $aThemeOptions['email_promotion_submitted'];
        if (empty($message)) {
            return false;
        }
        $this->customerID = $userID;
        $message = $this->generateReplace($message, $postID);
        $subject = $this->createMailSubject($aThemeOptions, 'email_promotion_submitted_subject');

        wp_mail($this->to(), $subject, $this->cleanContentBeforeSending($message));
        $this->addSentHistory(__FUNCTION__, ['postID' => $postID]);
    }

    /**
     * @param $aInfo ['listingId', 'promotionId']
     *
     * @return bool
     */
    public function sendToCustomerToNotifyPromotionPlanExpired($aInfo): bool
    {
        $aThemeOptions = $this->getOptions();
        if (!isset($aThemeOptions['email_promotion_expired']) || empty($aThemeOptions['email_promotion_expired'])) {
            return false;
        }

        $message = $aThemeOptions['email_promotion_expired'];

        $subject = $aThemeOptions['email_promotion_expired_subject'];

        if (empty($subject)) {
            return false;
        }

        $subject = $this->generateReplace(str_replace(
            [
                '%promotionTitle%',
                '%postTitle%'
            ],
            [
                get_the_title($aInfo['promotionId']),
                get_the_title($aInfo['listingId'])
            ],
            $subject
        ));

        $message = $this->generateReplace(str_replace(
            [
                '%promotionTitle%',
                '%postTitle%'
            ],
            [
                get_the_title($aInfo['promotionId']),
                get_the_title($aInfo['listingId'])
            ],
            $message
        ));

        $this->customerID = get_post_field('post_author', $aInfo['listingId']);
        $this->addSentHistory(__FUNCTION__);
        return wp_mail($this->to(), $subject, $this->cleanContentBeforeSending($message));
    }

    /**
     * @param $aInfo ['promotionInfo', 'listingId', 'promotionId']
     *
     * @return bool
     */
    public function sendToCustomerToNotifyAPromotionPlanExpired($aInfo): bool
    {
        $aThemeOptions = $this->getOptions();
        $message = $aThemeOptions['email_promotion_position_expired'];

        if (empty($message)) {
            return false;
        }

        $subject = $aThemeOptions['email_promotion_position_expired_subject'];
        if (empty($subject)) {
            return false;
        }

        if (get_post_status($aInfo['promotionId']) != 'publish') {
            return false;
        }

        $message = $this->generateReplace(str_replace(
            [
                '%promotionTitle%',
                '%promotionPosition%',
                '%postTitle%'
            ],
            [
                get_the_title($aInfo['promotionId']),
                $aInfo['promotionInfo']['name'],
                get_the_title($aInfo['listingId'])
            ],
            $message
        ));

        $subject = str_replace(
            [
                '%promotionTitle%',
                '%promotionPosition%',
                '%postTitle%'
            ],
            [
                get_the_title($aInfo['promotionId']),
                $aInfo['promotionInfo']['name'],
                get_the_title($aInfo['listingId'])
            ],
            $subject
        );

        $this->customerID = get_post_field('post_author', $aInfo['listingId']);

        $this->addSentHistory(__FUNCTION__);
        return wp_mail($this->to(), $subject, $this->cleanContentBeforeSending($message));
    }

    public function notifyPromotionToAdmin($userID, $postID, $promotionID)
    {
        $displayName = User::getField('display_name', $userID);
        $subject = sprintf(
            esc_html__(
                'Promotion: %s wants to promote his/her listing on your site',
                'wiloke-listing-tools'),
            $displayName
        );

        $content = sprintf(
            __('<a href="%s">Click here</a> to sign in and review it', 'wiloke-listing-tools'),
            add_query_arg(
                [
                    'post'   => $promotionID,
                    'action' => 'edit'
                ],
                admin_url('post.php')
            )
        );

        $this->addSentHistory(__FUNCTION__);
        wp_mail($this->toAdmin(), $subject, $subject . '. ' . $content);
    }

    /**
     * @param $aInfo
     * @return bool
     */
    public function promotionApproved($aInfo): bool
    {
        if ($aInfo['isUpdated']) {
            return false;
        }
        $oPost = get_post($aInfo['listingId']);
        if (empty($oPost) || is_wp_error($oPost)) {
            return false;
        }

        $aThemeOptions = $this->getOptions();
        $message = WilokeThemeOptions::getOptionDetail('email_promotion_approved');

        if (empty($message)) {
            return false;
        }

        $this->customerID = $oPost->post_author;
        $message = $this->generateReplace($message, $oPost->ID);
        $message = str_replace(
            '%promotionTitle%',
            get_the_title($oPost->ID),
            $message
        );
        $subject = $this->createMailSubject($aThemeOptions, 'email_promotion_approved_subject');
        $this->addSentHistory(__FUNCTION__);
        return wp_mail($this->to(), $subject, $this->cleanContentBeforeSending($message));
    }

    public function resendConfirmation()
    {
        $userID = User::getCurrentUserID();
        $this->sendConfirmation($userID, User::getField('user_login', $userID), true);

        $email = User::getField('user_email', $userID);
        $aParse = explode('@', $email);
        $email = substr($email, 0, 2);
        $email = $email . '***@' . $aParse[1];

        wp_send_json_success([
            'msg' => sprintf(esc_html__('We sent an email to %s.  If you do not find the email in your inbox, please check your spam filter or bulk email folder.',
                'wiloke-listing-tools'), $email)
        ]);
    }

    public function sendConfirmation($userID, $userName, $needConfirm): bool
    {
        if (!$needConfirm) {
            return false;
        }

        $aThemeOptions = $this->getOptions();

        if (!isset($aThemeOptions['confirmation_page']) || empty($aThemeOptions['confirmation_page'])) {
            return false;
        }

        $this->customerID = $userID;
        $message = $aThemeOptions['email_confirm_account'];

        $message = $this->generateReplace($message);
        $redirectTo = get_permalink($aThemeOptions['confirmation_page']);

        $confirmationLink = add_query_arg(
            [
                'action'        => 'confirm_account',
                'activationKey' => urlencode(User::getField('user_activation_key', $this->customerID)),
                'userName'      => urlencode($userName)
            ],
            $redirectTo
        );

        $message = str_replace(
            [
                '%confirmationLink%',
                '%confirmLink%',
                '%userName%'
            ],
            [
                $confirmationLink,
                $confirmationLink,
                $userName
            ],
            $message
        );

        $subject = $this->createMailSubject($aThemeOptions, 'email_confirm_account_subject');
        $this->isSendingConfirmation = true;
        $this->message = $message;

        wp_mail($this->to(), $subject, $message);
        $this->isSendingConfirmation = false;
        $this->addSentHistory(__FUNCTION__);
        return true;
    }

    public static function sendPasswordIfSocialLogin($aUserInfo, $socialName)
    {
        if (!in_array($socialName, self::$aSocialNetworks)) {
            return false;
        }

        $message = WilokeThemeOptions::getOptionDetail('email_password_content');

        if (empty($message)) {
            return '';
        }

        $subject = WilokeThemeOptions::getOptionDetail('email_password_title');
        $message = str_replace(
            [
                '%userName%',
                '%userPassword%'
            ],
            [
                $aUserInfo['username'],
                $aUserInfo['password']
            ],
            $message
        );

        wp_mail($aUserInfo['email'], $subject, stripslashes($message));
    }

    public function sendReviewNotification($reviewID, $parentID, $reviewerID)
    {
        $aThemeOptions = $this->getOptions();
        $this->customerID = get_post_field('post_author', $parentID);
        $message = $aThemeOptions['email_review_notification'];
        $message = $this->generateReplace($message, $parentID);
        $customerReviewName = User::getField('display_name', $reviewerID);
        $message = str_replace(
            [
                '%customerReviewName%',
                '%reviewTitle%'
            ],
            [
                $customerReviewName,
                get_the_title($reviewID),
            ],
            $message
        );
        $subject = sprintf(esc_html__('%s has left a Review', 'wiloke-listing-tools'), $customerReviewName);
        $this->addSentHistory(__FUNCTION__);
        wp_mail($this->to(), $subject, $this->cleanContentBeforeSending($message));
    }

    public function sendReportNotificationToAdmin($postID, $reportedID)
    {
        $aThemeOptions = $this->getOptions();
        $this->customerID = get_post_field('post_author', $postID);
        $authorReportID = get_post_field('post_author', $reportedID);
        $customerReport = get_user_by('id', $authorReportID);
        $message = $aThemeOptions['email_report_notification'];
        $message = $this->generateReplace($message, $postID);
        $message = str_replace(
            ['%reportTitle%', '%customerReportName%'],
            [get_the_title($reportedID), $customerReport->user_login],
            $message
        );

        $subject = sprintf(
            __('%s reported an issue of %s. <a href="%s">Click here</a> to check more details', 'wiloke-listing-tool'),
            $customerReport->user_login,
            get_post_field('post_title', $postID),
            add_query_arg(
                [
                    'action' => 'edit',
                    'post'   => $reportedID
                ],
                admin_url('post.php')
            )
        );

        $this->addSentHistory(__FUNCTION__);
        wp_mail($this->toAdmin(), $subject, $this->cleanContentBeforeSending($message));
    }

    /**
     * @param $aParams
     * @return bool
     */
    public function almostBankTransferBillingDate($aParams)
    {
        $paymentID = $aParams['paymentID'];
        if (empty($paymentID)) {
            return false;
        }
        $aThemeOptions = get_option('wiloke_themeoptions');
        $userID = PaymentModel::getField('userID', $paymentID);
        $this->customerID = $userID;

        if (!empty($this->to())) {
            $subject = $aThemeOptions['email_bank_transfer_almost_billing_date_subject'];
            if (empty($subject)) {
                return false;
            }
            $message = $aThemeOptions['email_bank_transfer_almost_billing_date'];
            if (empty($message)) {
                return false;
            }
            $postID = PaymentMetaModel::getPaymentInfo($paymentID)['postID'];
            $message = $this->generateReplace($message, $postID);
            $nextBillingDate = Time::toDateFormat(PaymentMetaModel::getNextBillingDateGMT($paymentID)) . ' ' .
                Time::toTimeFormat(PaymentMetaModel::getNextBillingDateGMT($paymentID));

            $message = str_replace(
                [
                    '%paymentID%',
                    '%nextBillingDate%'
                ],
                [
                    $paymentID,
                    $nextBillingDate
                ],
                $message
            );

            $this->addSentHistory(__FUNCTION__);
            wp_mail($this->to(), $subject, $this->cleanContentBeforeSending($message));
        }
    }

    /**
     * @param $aParams
     * @return bool
     */
    public function outOfBankTransferBillingDate($aParams)
    {
        $paymentID = $aParams['paymentID'];
        if (empty($paymentID)) {
            return false;
        }
        $aThemeOptions = get_option('wiloke_themeoptions');
        $userID = PaymentModel::getField('userID', $paymentID);
        $postID = PaymentMetaModel::getPaymentInfo($paymentID)['postID'];
        $this->customerID = $userID;
        if (!empty($this->to())) {
            $subject = $aThemeOptions['email_bank_transfer_out_of_billing_date_customer_subject'];
            if (empty($subject)) {
                return false;
            }
            $message = $aThemeOptions['email_bank_transfer_out_of_billing_date_customer'];
            if (empty($message)) {
                return false;
            }
            $message = $this->generateReplace($message, $postID);
            $nextBillingDate = Time::toDateFormat(PaymentMetaModel::getNextBillingDateGMT($paymentID)) . ' ' .
                Time::toTimeFormat(PaymentMetaModel::getNextBillingDateGMT($paymentID));
            $message = str_replace(
                [
                    '%paymentID%',
                    '%nextBillingDate%'
                ],
                [
                    $paymentID,
                    $nextBillingDate,
                ],
                $message
            );

            wp_mail($this->to(), $subject, $this->cleanContentBeforeSending($message));
        }

        if (!empty($this->toAdmin())) {
            $subject = $aThemeOptions['email_bank_transfer_out_of_billing_date_admin_subject'];
            if (empty($subject)) {
                return false;
            }
            $message = $aThemeOptions['email_bank_transfer_out_of_billing_date_admin'];
            if (empty($message)) {
                return false;
            }
            $message = $this->generateReplace($message);
            $nextBillingDate = Time::toDateFormat(PaymentMetaModel::getNextBillingDateGMT($paymentID)) . ' ' .
                Time::toTimeFormat(PaymentMetaModel::getNextBillingDateGMT($paymentID));
            $message = str_replace(
                [
                    '%paymentID%',
                    '%nextBillingDate%',
                    '%paymentDetailUrl%'
                ],
                [
                    $paymentID,
                    $nextBillingDate,
                    sprintf('<a href="%s">%s</a>',
                        add_query_arg(['page' => 'details', 'paymentID' => 1], admin_url('admin.php')),
                        esc_html__('Here', 'wiloke-listing-tools'))
                ],
                $message
            );
            wp_mail($this->toAdmin(), $subject, $this->cleanContentBeforeSending($message));
        }

        $this->addSentHistory(__FUNCTION__);
    }

    /**
     * @param $aParams
     * @return bool
     */
    public function canceledBankTransferBillingDate($aParams)
    {
        $paymentID = $aParams['paymentID'];
        if (empty($paymentID)) {
            return false;
        }
        $aThemeOptions = get_option('wiloke_themeoptions');
        $userID = PaymentModel::getField('userID', $paymentID);
        $postID = PaymentMetaModel::getPaymentInfo($paymentID)['postID'];
        $this->customerID = $userID;
        if (!empty($this->to())) {

            $subject = $aThemeOptions['email_bank_transfer_canceled_customer_subject'];
            if (empty($subject)) {
                return false;
            }
            $message = $aThemeOptions['email_bank_transfer_canceled_customer'];

            if (empty($message)) {
                return false;
            }
            $message = $this->generateReplace($message, $postID);
            $nextBillingDate = Time::toDateFormat(PaymentMetaModel::getNextBillingDateGMT($paymentID)) . ' ' .
                Time::toTimeFormat(PaymentMetaModel::getNextBillingDateGMT($paymentID));
            $message = str_replace(
                [
                    '%paymentID%',
                    '%nextBillingDate%',
                    '%paymentDetailUrl%'
                ],
                [
                    $paymentID,
                    $nextBillingDate,
                    sprintf('<a href="%s">%s</a>',
                        add_query_arg(['page' => 'details', 'paymentID' => 1], admin_url('admin.php')),
                        esc_html__('Here', 'wiloke-listing-tools'))
                ],
                $message
            );

            wp_mail($this->to(), $subject, $this->cleanContentBeforeSending($message));
        }

        if (!empty($this->toAdmin())) {
            $subject = $aThemeOptions['email_bank_transfer_canceled_admin_subject'];
            if (empty($subject)) {
                return false;
            }
            $message = $aThemeOptions['email_bank_transfer_canceled_admin'];
            if (empty($message)) {
                return false;
            }
            $message = $this->generateReplace($message, $postID);
            $nextBillingDate = Time::toDateFormat(PaymentMetaModel::getNextBillingDateGMT($paymentID)) . ' ' .
                Time::toTimeFormat(PaymentMetaModel::getNextBillingDateGMT($paymentID));
            $message = str_replace(
                [
                    '%paymentID%',
                    '%nextBillingDate%',
                    '%paymentDetailUrl%'
                ],
                [
                    $paymentID,
                    $nextBillingDate,
                    sprintf('<a href="%s">%s</a>',
                        add_query_arg(['page' => 'details', 'paymentID' => 1], admin_url('admin.php')),
                        esc_html__('Here', 'wiloke-listing-tools'))
                ],
                $message
            );

            wp_mail($this->toAdmin(), $subject, $this->cleanContentBeforeSending($message));

            $this->addSentHistory(__FUNCTION__);
        }
    }
}
