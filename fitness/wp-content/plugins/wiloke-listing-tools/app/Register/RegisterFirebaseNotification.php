<?php

namespace WilokeListingTools\Register;

use WilokeListingTools\Framework\Helpers\Firebase;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\Inc;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Upload\Upload;

class RegisterFirebaseNotification
{
    use ListingToolsGeneralConfig;
    use GetAvailableSections;
    use ParseSection;

    private $slug = 'wiloke-firebase-notifications';
    private $aListOfChatConfiguration
                  = [
            'apiKey'            => '',
            'authDomain'        => '',
            'projectID'         => '',
            'storageBucket'     => '',
            'databaseURL'       => '',
            'appId'             => '',
            'messagingSenderId' => ''
        ];
    private $aTestInfoConfiguration
                  = [
            'deviceToken' => ''
        ];

    public function __construct()
    {
        add_action('admin_menu', [$this, 'register']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('wp_ajax_saving_customer_receive_notification', [$this, 'saveCustomerReceiveNotifications']);
        add_action('wp_ajax_saving_admin_receive_notification', [$this, 'saveAdminReceiveNotifications']);
        add_action('wp_ajax_wilcity_upload_firebase', [$this, 'uploadFirebase']);
        add_action('wp_ajax_wilcity_firease_chat_configuration', [$this, 'saveFirebaseChatConfiguration']);
        add_action('wp_ajax_wilcity_firease_test_info', [$this, 'saveFirebaseTestInfo']);
        add_action('wp_ajax_wilcity_send_test_notification', [$this, 'sendNotificationToTestDevice']);
        add_action('wp_ajax_wilcity_save_toggle_debug_status', [$this, 'saveToggleDebugStatus']);
    }

    public function saveToggleDebugStatus()
    {
        if (!current_user_can('administrator')) {
            wp_send_json_error([
                'msg' => 'You do not have permission to upload this file'
            ]);
        }

        SetSettings::setOptions('toggle_notification_debug', sanitize_text_field($_POST['status']));

        wp_send_json_error([
            'msg' => sprintf('The debug has been %s', $_POST['status'])
        ]);
    }

    public function sendNotificationToTestDevice()
    {
        if (!current_user_can('administrator')) {
            wp_send_json_error([
                'msg' => 'You do not have permission to upload this file'
            ]);
        }

        if (!isset($_POST['settings']) || empty($_POST['settings'])) {
            wp_send_json_error([
                'msg' => 'The setting information is required'
            ]);
        }

        if (!defined('WILCITY_MOBILE_APP')) {
            wp_send_json_error([
                'msg' => 'Wilcity Mobile App plugin is required. Click on Appearance -> Install Plugins to setup it'
            ]);
        }

        $aTestInfo = GetSettings::getOptions('firebase_test_info');
        if (!isset($aTestInfo['deviceToken']) || empty($aTestInfo['deviceToken'])) {
            wp_send_json_error([
                'msg' => 'The device token is required. To setup it, Navigate to Firebase Settings -> Device Token'
            ]);
        }

        $aResponse = apply_filters(
            'wilcity/filter/wilcity-mobile-app/test-send-push-notification',
            [
                'status' => 'error',
                'msg'    => 'We found no filter'
            ],
            [
                'body' => $_POST['settings']['msg']
            ],
            $aTestInfo['deviceToken']
        );

        if ($aResponse['status'] === 'error') {
            wp_send_json_error($aResponse);
        }

        wp_send_json_success($aResponse);
    }

    public function saveFirebaseTestInfo()
    {
        if (!current_user_can('administrator')) {
            wp_send_json_error([
                'msg' => 'You do not have permission to upload this file'
            ]);
        }

        if (empty($_POST['aConfigurations'])) {
            wp_send_json_error(
                [
                    'msg' => 'The configuration is required'
                ]
            );
        }

        $aConfiguration = [];
        foreach ($this->aTestInfoConfiguration as $key => $nothing) {
            if (!isset($_POST['aConfigurations'][$key]) || empty($_POST['aConfigurations'][$key])) {
                wp_send_json_error(
                    [
                        'msg' => 'The ' . $key . ' is required'
                    ]
                );
            }
            $aConfiguration[$key] = sanitize_text_field(trim($_POST['aConfigurations'][$key]));
        }

        SetSettings::setOptions('firebase_test_info', $aConfiguration);
        wp_send_json_success([
            'msg' => 'Congratulations! The firebase chat configuration has been saved successfully'
        ]);
    }

    public function saveFirebaseChatConfiguration()
    {
        if (!current_user_can('administrator')) {
            wp_send_json_error([
                'msg' => 'You do not have permission to upload this file'
            ]);
        }

        if (empty($_POST['aConfigurations'])) {
            wp_send_json_error(
                [
                    'msg' => 'The configuration is required'
                ]
            );
        }

        $aConfiguration = [];
        foreach ($this->aListOfChatConfiguration as $key => $nothing) {
            if (!isset($_POST['aConfigurations'][$key]) || empty($_POST['aConfigurations'][$key])) {
                wp_send_json_error(
                    [
                        'msg' => 'The ' . $key . ' is required'
                    ]
                );
            }
            $aConfiguration[$key] = sanitize_text_field(trim($_POST['aConfigurations'][$key]));
        }

        SetSettings::setOptions('firebase_chat_configuration', $aConfiguration);
        wp_send_json_success([
            'msg' => 'Congratulations! The firebase chat configuration has been saved successfully'
        ]);
    }

    public function uploadFirebase()
    {
        if (!current_user_can('administrator')) {
            wp_send_json_error([
                'msg' => 'You do not have permission to upload this file'
            ]);
        }

        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        if ($_FILES['wilcity_upload_filebase']['type'] != 'application/json') {
            wp_send_json_error([
                'msg' => 'The file must be json format'
            ]);
        }
        $status = move_uploaded_file($_FILES['wilcity_upload_filebase']['tmp_name'],
            Upload::getFolderDir('wilcity') . 'firebaseConfig.json');

        if (!$status) {
            wp_send_json_error([
                'msg' => 'Oops! We could not upload this file. Please rename this file to firebaseConfig.json then upload it manually to Your WordPress folder -> wp-content -> uploads -> wilcity folder.'
            ]);
        }

        if (function_exists('chmod')) {
            chmod(Upload::getFolderDir('wilcity') . 'firebaseConfig.json', 0644);
        }

        //		SetSettings::setOptions('is_uploaded_firebasefile', current_time('timestamp'));

        wp_send_json_success([
            'msg' => 'The file uploaded successfully'
        ]);
    }

    public function saveCustomerReceiveNotifications()
    {
        if (!current_user_can('administrator')) {
            wp_send_json_error();
        }

        $toggle = sanitize_text_field($_POST['toggle']);
        $aSettings = General::unSlashDeep($_POST['aSettings']);

	    SetSettings::setOptions('toggle_customers_receive_notifications', $toggle, true);
	    SetSettings::setOptions('customers_receive_notifications_settings', $aSettings, true);
        wp_send_json_success();
    }

    public function saveAdminReceiveNotifications()
    {
        if (!current_user_can('administrator')) {
            wp_send_json_error();
        }

        $toggle = sanitize_text_field($_POST['toggle']);
        $aSettings = General::unSlashDeep($_POST['aSettings']);

        SetSettings::setOptions('toggle_admin_receive_notifications', $toggle,true);
        SetSettings::setOptions('admin_receive_notifications_settings', $aSettings, true);
        wp_send_json_success();
    }

    public function enqueueScripts($hook)
    {
        if (strpos($hook, $this->slug) === false) {
            return false;
        }
        $this->requiredScripts();
        wp_enqueue_script('general-design-tool', WILOKE_LISTING_TOOL_URL . 'admin/source/js/general.js', ['jquery'],
            WILOKE_LISTING_TOOL_VERSION, true);
        //        $this->generalScripts();

        wp_enqueue_script('push-notifications', WILOKE_LISTING_TOOL_URL . 'admin/source/js/push-notifications.js',
            ['jquery'], WILOKE_LISTING_TOOL_VERSION, true);

        $aCustomerNotificationSettings = GetSettings::getOptions('customers_receive_notifications_settings', false, true);

        if (empty($aCustomerNotificationSettings)) {
            $aCustomerNotificationSettings = wilokeListingToolsRepository()->get('push-notifications:customers');
        } else {
            $aCustomerNotificationSettings = array_merge(
                wilokeListingToolsRepository()->get('push-notifications:customers'),
                $aCustomerNotificationSettings
            );
        }

        $aAdminNotifications = GetSettings::getOptions('admin_receive_notifications_settings', false, true);

        if (empty($aAdminNotifications)) {
            $aAdminNotifications = wilokeListingToolsRepository()->get('push-notifications:customers');
        } else {
            $aAdminNotifications = array_merge(
                wilokeListingToolsRepository()->get('push-notifications:admin'),
                $aAdminNotifications
            );
        }

        $oChatConfiguration = GetSettings::getOptions('firebase_chat_configuration');
        $aTestInfo = GetSettings::getOptions('firebase_test_info');
        $aTestInfo = is_array($aTestInfo) ? $aTestInfo : [];

        wp_localize_script(
            'push-notifications',
            'WILOKE_PUSH_NOTIFICATIONS',
            [
                'toggleDebug'                            => GetSettings::getOptions('toggle_notification_debug'),
                'toggle_admin_receive_notifications'     => empty(GetSettings::getOptions('toggle_admin_receive_notifications', false, true)) ?
                    'disable' : GetSettings::getOptions('toggle_admin_receive_notifications', false, true),
                'toggle_customers_receive_notifications' => empty(GetSettings::getOptions('toggle_customers_receive_notifications', false, true)) ?
                    'disable' : GetSettings::getOptions('toggle_customers_receive_notifications', false, true),
                'oCustomerReceive',
                'oCustomerNotifications'                 => $aCustomerNotificationSettings,
                'oAdminNotifications'                    => $aAdminNotifications,
                'isFirebaseFileUploaded'                 => Firebase::getFirebaseFile() ? 'yes' : 'no',
                'oFirebaseChatConfiguration'             => empty($oChatConfiguration) ||
                !is_array($oChatConfiguration) ?
                    $this->aListOfChatConfiguration : $oChatConfiguration,
                'oTestInfo'                              => $aTestInfo
            ]
        );
    }

    public function register()
    {

        add_submenu_page($this->parentSlug, 'Notification Settings', 'Notification Settings', 'administrator',
            $this->slug, [$this, 'pushNotificationSettings']);
    }

    public function pushNotificationSettings()
    {
        Inc::file('push-notifications:index');
    }
}
