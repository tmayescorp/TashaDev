<?php

namespace WilokeListingTools\Controllers;

use Wiloke;
use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\UserSkeleton;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\InvoiceModel;
use WilokeListingTools\Models\MessageModel;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;
use WilokeListingTools\Models\PlanRelationshipModel;
use WilokeListingTools\Models\RemainingItems;
use WilokeListingTools\Models\UserLatLng;
use WilokeListingTools\Models\UserModel;
use WilokeListingTools\Framework\Helpers\Validation as ValidationHelper;
use WilokeSocialNetworks;
use WilokeThemeOptions;
use WP_User;
use WP_User_Query;

class UserController extends Controller
{
    public  $limit = 4;
    private $oRetrieve;

    public function __construct()
    {
        add_action('wp_ajax_wilcity_fetch_user_profile', [$this, 'fetchUserProfile']);
        add_action('admin_init', [$this, 'addCaps']);
        add_action('ajax_query_attachments_args', [$this, 'mediaAccess']);
        add_filter('manage_users_columns', [$this, 'registerAddlistingLockedColumn']);
        add_filter('manage_users_custom_column', [$this, 'showUpLockedUserReasonOnUserRow'], 10, 3);
        add_action('wp_ajax_wilcity_fetch_my_billings', [$this, 'fetchBillings']);
        add_action('wp_ajax_wilcity_fetch_my_billing_details', [$this, 'fetchBillingDetails']);
        add_action('wp_ajax_wilcity_fetch_my_plan', [$this, 'fetchMyPlan']);
        add_action('wp_ajax_user_short_info', [$this, 'ajaxFetchUserShortInfo']);
        add_filter(
            'wilcity/filter/wiloke-listing-tools/app/Controller/OptimizeScripts/buildScriptCode/user-short-info',
            [$this, 'getUserShortInfo'],
            10,
            2
        );
        add_action('wp_ajax_wilcity_fetch_profile_fields', [$this, 'fetchProfileFields']);
        add_action('wp_ajax_wilcity_fetch_delete_account_fields', [$this, 'fetchDeleteAccountFields']);
        add_action('wp_ajax_wilcity_update_profile', [$this, 'updateProfile']);
        add_action('wp_ajax_wilcity_delete_account', [$this, 'deleteAccount']);
        add_action('wp_ajax_wilcity_is_customer_confirmed', [$this, 'checkIsUserConfirmed']);
        add_action('rest_api_init', function () {
            $this->registerRestRouters();
        });

        add_action('wilcity/after/created-account', [$this, 'maybeSetAccountToConfirmed'], 10, 3);
        add_action('dokan_store_profile_saved', [$this, 'updateUserGeocoder'], 10, 2);

        add_action('wp_ajax_wilcity_admin_search_user', [$this, 'searchUser']);
    }

    public function searchUser()
    {
        global $wpdb;
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $aArgs = [
            'search'         => '*' . $wpdb->_real_escape($_GET['q']) . '*',
            'search_columns' => ['user_login', 'user_email']
        ];

        if (isset($_GET['roles'])) {
            $aRoles = explode(',', $_GET['roles']);
            $aRoles = array_map(function ($role) use ($wpdb) {
                return $wpdb->_real_escape(trim($role));
            }, $aRoles);

            $aArgs['role__in'] = $aRoles;
        }

        $query = new WP_User_Query($aArgs);
        $aRawAuthors = $query->get_results();

        $aAuthors = [];
        $fieldType = isset($_GET['fieldtype']) ? $_GET['fieldtype'] : 'select2';

        if (!empty($aRawAuthors)) {
            foreach ($aRawAuthors as $oAuthor) {
                switch ($fieldType) {
                    case 'select2':
                        $aAuthors[] = [
                            'id'    => $oAuthor->ID,
                            'text'  => $oAuthor->display_name,
                            'label' => $oAuthor->display_name
                        ];
                        break;
                }
            }
        } else {
            $oRetrieve->error(['msg' => esc_html__('We found no author', 'wiloke-listing-tools')]);
        }

        $oRetrieve->success(['results' => $aAuthors]);
    }

    public function updateUserGeocoder($userId, $aDokanSettings)
    {
        if (!isset($aDokanSettings['location']) || empty($aDokanSettings['location'])) {
            UserLatLng::deleteUserLatLng($userId);
        } else {
            $aParse = explode(',', $aDokanSettings['location']);
            if (!isset($aParse[1]) || empty($aParse[1])) {
                return false;
            }

            UserLatLng::updateUserLatLng($userId, $aParse[0], $aParse[1]);
        }
    }

    public function maybeSetAccountToConfirmed($userID, $username, $isNeededConfirmation)
    {
        if (!$isNeededConfirmation) {
            SetSettings::setUserMeta($userID, 'confirmed', 1);
        }
    }

    public function fetchDeleteAccountFields()
    {
        if (class_exists('\WilokeThemeOptions')) {
            $oRetrieve = new RetrieveController(new AjaxRetrieve());
            if (!WilokeThemeOptions::isEnable('toggle_allow_customer_delete_account')) {
                $oRetrieve->error([]);
            }

            $aSections = [
                'heading'     => esc_html__('Permanently Delete Account', 'wiloke-listing-tools'),
                'translation' => 'permanentlyDeleteAccount',
                'icon'        => 'la la-user-times',
                'warning'     => WilokeThemeOptions::getOptionDetail('customer_delete_account_warning')
            ];

            $oRetrieve->success($aSections);
        }
    }

    public function fetchProfileFields()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $userID = get_current_user_id();
        $aBasicInfo = [];
        $oUserData = get_userdata($userID);

        $aBasicInfo[] = [
            'value'      => $oUserData->first_name,
            'type'       => 'wil-input',
            'key'        => 'first_name',
            'isRequired' => 'yes',
            'label'      => esc_html__('First Name', 'wiloke-listing-tools')
        ];

        $aBasicInfo[] = [
            'value'      => $oUserData->last_name,
            'type'       => 'wil-input',
            'key'        => 'last_name',
            'isRequired' => 'yes',
            'label'      => esc_html__('Last Name', 'wiloke-listing-tools')
        ];

        $aBasicInfo[] = [
            'value'      => $oUserData->display_name,
            'type'       => 'wil-input',
            'key'        => 'display_name',
            'isRequired' => 'yes',
            'label'      => esc_html__('Display Name', 'wiloke-listing-tools')
        ];

        $aBasicInfo[] = [
            'value' => get_user_meta($oUserData->ID, 'shipping_company', true),
            'type'  => 'wil-input',
            'key'   => 'company_name',
            'label' => esc_html__('Company Name', 'wiloke-listing-tools')
        ];

        $avatar = GetSettings::getUserMeta($userID, 'avatar');
        $avatarID = GetSettings::getUserMeta($userID, 'avatar_id');
        $aBasicInfo[] = [
            'value'   => !empty($avatar) ?
                ['src' => $avatar, 'fileName' => esc_html__('Avatar', 'wiloke-listing-tools'), 'id' => $avatarID] : [],
            'type'    => 'wil-uploader',
            'key'     => 'avatar',
            'maximum' => 1,
            'label'   => esc_html__('Avatar', 'wiloke-listing-tools')
        ];

        $coverImg = GetSettings::getUserMeta($userID, 'cover_image');
        $coverImgID = GetSettings::getUserMeta($userID, 'cover_image_id');
        $aBasicInfo[] = [
            'value'   => empty($coverImg) ? [] :
                [
                    'src'      => $coverImg,
                    'fileName' => esc_html__('Cover Image', 'wiloke-listing-tools'),
                    'id'       => $coverImgID
                ],
            'type'    => 'wil-uploader',
            'key'     => 'cover_image',
            'maximum' => 1,
            'label'   => esc_html__('Cover Image', 'wiloke-listing-tools')
        ];

        $aBasicInfo[] = [
            'type'       => 'wil-input',
            'inputChild' => 'email',
            'key'        => 'email',
            'isRequired' => 'yes',
            'value'      => $oUserData->user_email,
            'label'      => esc_html__('Email', 'wiloke-listing-tools')
        ];

        $aBasicInfo[] = [
            'type'  => 'wil-input',
            'key'   => 'position',
            'value' => GetSettings::getUserMeta($userID, 'position'),
            'label' => esc_html__('Position', 'wiloke-listing-tools')
        ];

        $aBasicInfo[] = [
            'value'       => get_the_author_meta('user_description', $userID),
            'type'        => 'wil-textarea',
            'key'         => 'description',
            'label'       => esc_html__('Introduce your self', 'wiloke-listing-tools'),
            'desc' => esc_html__('Press Shift + Enter if you want to generate a new line.',
                'wiloke-listing-tools')
        ];

        $aBasicInfo[] = [
            'value' => GetSettings::getUserMeta($userID, 'send_email_if_reply_message'),
            'type'  => 'wil-checkbox',
            'key'   => 'send_email_if_reply_message',
            'label' => esc_html__('Receive message through email.', 'wiloke-listing-tools')
        ];

        $aBasicInfo = apply_filters('wilcity/wiloke-listing-tools/filter/profile-controllers/basic-info', $aBasicInfo);

        $aFollowContact = [];

        $aFollowContact[] = [
            'type'  => 'wil-input',
            'key'   => 'address',
            'value' => GetSettings::getUserMeta($userID, 'address'),
            'label' => esc_html__('Address', 'wiloke-listing-tools')
        ];

        $aFollowContact[] = [
            'type'  => 'wil-input',
            'key'   => 'phone',
            'value' => GetSettings::getUserMeta($userID, 'phone'),
            'label' => esc_html__('Phone', 'wiloke-listing-tools')
        ];

        $aFollowContact[] = [
            'type'  => 'wil-input',
            'key'   => 'website',
            'value' => User::getWebsite($userID),
            'label' => esc_html__('Website', 'wiloke-listing-tools')
        ];

        $aRawSocialNetworks = GetSettings::getUserMeta($userID, 'social_networks');

        $aSocialNetworks = [];
        if (!empty($aRawSocialNetworks)) {
            foreach ($aRawSocialNetworks as $social => $socialUrl) {
                if (!empty($socialUrl)) {
                    $aSocialNetworks[] = [
                        'icon'  => '',
                        'id'    => $social,
                        'label' => ucfirst($social),
                        'value' => $socialUrl
                    ];
                }
            }
        }

        $aFollowContact[] = [
            'type'          => 'wil-pickup-and-set',
            'key'           => 'social_networks',
            'label'         => esc_html__('Social Networks', 'wiloke-listing-tools'),
            'value'         => $aSocialNetworks,
            'pickupOptions' => WilokeSocialNetworks::getPickupSocialOptions()
        ];

        $aFollowContact = apply_filters(
            'wilcity/wiloke-listing-tools/filter/profile-controllers/follow-contact', $aFollowContact
        );

        $aChangePassword = [
            [
                'type'        => 'wil-input',
                'key'         => 'currentPassword',
                'inputType'   => 'password',
                'label'       => esc_html__('Current Password', 'wiloke-listing-tools'),
                'translation' => 'currentPassword',
                'value'       => ''
            ],
            [
                'type'        => 'wil-input',
                'key'         => 'newPassword',
                'inputType'   => 'password',
                'label'       => esc_html__('New Password', 'wiloke-listing-tools'),
                'translation' => 'newPassword',
                'value'       => ''
            ],
            [
                'type'        => 'wil-input',
                'key'         => 'confirmNewPassword',
                'inputType'   => 'password',
                'label'       => esc_html__('Confirm New Password', 'wiloke-listing-tools'),
                'translation' => 'confirmNewPassword',
                'value'       => ''
            ]
        ];

        $aSections = [
            [
                'heading'     => 'Basic Info',
                'translation' => 'basicInfo',
                'key'         => 'basic-info',
                'fields'      => $aBasicInfo
            ],
            [
                'heading'     => 'Follow & Contact',
                'translation' => 'followAndContact',
                'icon'        => 'la la-user-plus',
                'key'         => 'follow-and-contact',
                'fields'      => $aFollowContact
            ],
            [
                'heading'     => 'Change Password',
                'translation' => 'changePassword',
                'icon'        => 'la la-exchange',
                'key'         => 'change-password',
                'fields'      => $aChangePassword
            ]
        ];

        $oRetrieve->success(
            apply_filters('wiloke-listing-tools/filter/dashboard/profile/sections', $aSections, $userID)
        );
    }

    public function registerRestRouters()
    {
        //        register_rest_route('wiloke/v2', '/users/my-info', [
        //            'methods'  => 'GET',
        //            'callback' => [$this, 'fetchMyInfo']
        //        ]);
    }

    public function ajaxFetchUserShortInfo()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        if (empty($_GET['userId'])) {
            $userID = get_current_user_id();
        } else {
            $userID = $_GET['userId'];
        }
        $oRetrieve->success($this->getUserShortInfo([], $userID));
    }

    public function getUserShortInfo($aInfo, $userId): array
    {
        $pluck = $_GET['pluck'] ?? 'avatar,description,displayName,authorPostsUrl,totalFollowings,totalFollowers';
        $oUserSkeleton = new UserSkeleton($userId);
        $aInfo = $oUserSkeleton->pluck($pluck);

        return $aInfo;
    }

    public function fetchMyPlan()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        $aRawUserPlans = PaymentModel::getPaymentSessionsOfUser(User::getCurrentUserID(), ['active', 'succeeded']);

        if (empty($aRawUserPlans)) {
            $oRetrieve->error([
                'msg' => esc_html__('You do not use any plan yet.', 'wiloke-listing-tools')
            ]);
        }

        $aUserPlans = [];
        $order = 0;
        foreach ($aRawUserPlans as $oPayment) {
            $aPaymentInfo = PaymentMetaModel::getPaymentInfo($oPayment->ID);

            if (!empty($oPayment->planID)) {
                $planTitle = get_the_title($oPayment->planID);
            } else {
                $planTitle = '';
            }

            if (empty($planTitle) && isset($aPaymentInfo['planName'])) {
                $planTitle = $aPaymentInfo['planName'];
            }

            if (empty($planTitle)) {
                $planTitle = esc_html__('This plan may have been deleted.', 'wiloke-listing-tools');
            }

            $aUserPlans[$order]['planName'] = $planTitle;
            $aUserPlans[$order]['planID'] = $oPayment->planID;

            if (GetWilokeSubmission::isNonRecurringPayment($oPayment->billingType)) {
                $aUserPlans[$order]['nextBillingDate'] = 'X';
            } else {
                $nextBillingDateGMT = PaymentMetaModel::getNextBillingDateGMT($oPayment->ID);
                if (empty($nextBillingDateGMT)) {
                    $aUserPlans[$order]['nextBillingDate'] = esc_html__('Updating', 'wiloke-listing-tools');
                } else {
                    $aUserPlans[$order]['nextBillingDate'] = date_i18n(get_option('date_format'), $nextBillingDateGMT);
                }
            }

            $aUserPlans[$order]['paymentID'] = $oPayment->ID;
            $aUserPlans[$order]['gateway'] = $oPayment->gateway;
            if ($oPayment->gateway == 'woocommerce') {
                if ($paymentMethod = PaymentModel::getWooCommercePaymentGateway($oPayment->wooOrderID)) {
                    $aUserPlans[$order]['gateway'] = $paymentMethod;
                }
            }

            if (in_array($aPaymentInfo['category'], ['addlisting', 'paidClaim'])) {
                $listingID = PlanRelationshipModel::getLastObjectIDByPaymentID($oPayment->ID);
                $aUserPlans[$order]['postType'] = get_post_type($listingID);
            } else {
                $aUserPlans[$order]['postType'] = $aPaymentInfo['category'];
            }

            $aUserPlans[$order]['billingType'] = $oPayment->billingType;
            $aUserPlans[$order]['isNonRecurringPayment']
                = GetWilokeSubmission::isNonRecurringPayment($oPayment->billingType) ? 'yes' : 'no';
            $aUserPlans[$order]['status'] = $oPayment->status;
            $aUserPlans[$order]['category'] = $aPaymentInfo['category'];

            if ($oPayment->gateway == 'stripe' && !GetWilokeSubmission::isNonRecurringPayment($oPayment->billingType)) {
                $stripePortalUrl
                    = apply_filters('wiloke-listing-tools/filter/app/Controllers/UserController/supportStripePortal',
                    null,
                    [
                        'paymentID' => $oPayment->ID,
                        'userID'    => get_current_user_id()
                    ]
                );
                if (!empty($stripePortalUrl)) {
                    $aUserPlans[$order]['stripePortalUrl'] = $stripePortalUrl;
                }
            }

            if (in_array($aPaymentInfo['category'], ['addlisting', 'paidClaim'])) {
                $oRemainingItems = new RemainingItems();
                $oRemainingItems->setUserID($oPayment->userID)
                    ->setGateway($oPayment->gateway)
                    ->setPlanID($oPayment->planID)
                    ->setBillingType($oPayment->billingType)
                    ->setPaymentID($oPayment->ID);

                $aUserPlans[$order]['remainingItems'] = $oRemainingItems->getRemainingItems();
            } else {
                $aUserPlans[$order]['remainingItems'] = 'x';
            }

            $order++;
        }

        $oRetrieve->success(
            apply_filters('wiloke-listing-tools/app/Controllers/UserControllers/fetchMyPlan/userPlans', $aUserPlans)
        );
    }

    private function updateBasicInfo($aBasicInfo, $userID)
    {
        $aUserInfo = [];
        foreach ($aBasicInfo as $key => $val) {
            switch ($key) {
                case 'first_name':
                case 'last_name':
                case 'display_name':
                case 'description':
                case 'company_name':
                    $aUserInfo[$key] = sanitize_text_field($val);
                    break;
                case 'email':
                    if (!empty($val)) {
                        $currentEmail = User::getField('user_email', $userID);
                        if ($currentEmail != $val) {
                            if (email_exists($val)) {
                                return [
                                    'status' => 'error',
                                    'msg'    => esc_html__('This email is already registered.', 'wiloke-listing-tools')
                                ];
                            }
                            $aUserInfo['user_email'] = sanitize_email($val);
                        }

                    }
                    break;
                case 'send_email_if_reply_message':
                    $aUserMeta['send_email_if_reply_message'] = sanitize_text_field($val);
                    break;
                case 'position':
                    $aUserMeta['position'] = sanitize_text_field($val);
                    break;
                case 'avatar':
                case 'cover_image':
                    if (!empty($val)) {
                        if (is_array($val)) {
                            $aUserMeta[sanitize_text_field($key)] = $val['src'];
                            $aUserMeta[sanitize_text_field($key) . '_id'] = $val['id'];
                        } else {
                            $aUserMeta[sanitize_text_field($key)] = ValidationHelper::deepValidation($val);
                        }
                    }
                    break;
            }
        }

        if (!empty($aUserInfo)) {
            $aUserInfo['ID'] = $userID;
            if (empty($aUserInfo['display_name'])) {
                if (isset($aUserInfo['first_name'])) {
                    $aUserInfo['display_name'] = $aUserInfo['first_name'];
                }

                if (isset($aUserInfo['last_name'])) {
                    if (!empty($aUserInfo['display_name'])) {
                        $aUserInfo['display_name'] .= $aUserInfo['last_name'];
                    } else {
                        $aUserInfo['display_name'] = $aUserInfo['last_name'];
                    }
                }
            }

            if (isset($aUserInfo['company_name'])) {
                update_user_meta($userID, 'shipping_company', $aUserInfo['company_name']);
                unset($aUserInfo['company_name']);
            }

            $userID = wp_update_user((object)$aUserInfo);

            /**
             * @hooked WILCITY_APP\Controllers\Firebase\MessageController:updateUserAvatarToMessageFirebase 10
             */
            do_action('wilcity/wiloke-listing-tools/save-profile-basic-info', $aBasicInfo, $userID);
        }

        if (!empty($aUserMeta)) {
            foreach ($aUserMeta as $metaKey => $val) {
                SetSettings::setUserMeta($userID, $metaKey, $val);
            }
        }

        if (is_wp_error($userID)) {
            return [
                'status' => 'error',
                'msg'    => esc_html__('ERROR: Something went wrong, We could not update your profile.',
                    'wiloke-listing-tools')
            ];
        }

        do_action('wilcity/wiloke-listing-tools/after-save-profile-basic-info', $aBasicInfo, $userID);

        return true;
    }

    private function updateFollowAndContact($aFollowAndContact, $userID)
    {
        $aUserMeta = [];
        $aUserInfo = [];
        foreach ($aFollowAndContact as $key => $val) {
            switch ($key) {
                case 'social_networks':
                    foreach ($val as $socialInfo) {
                        if (!empty($socialInfo['value'])) {
                            $aUserMeta['social_networks'][sanitize_text_field($socialInfo['id'])] = sanitize_text_field
                            ($socialInfo['value']);
                        }
                    }
                    break;
                case 'address':
                case 'phone':
                    $aUserMeta[$key] = sanitize_text_field($val);
                    break;
                case 'website':
                    $aUserInfo['user_url'] = sanitize_text_field($val);
                    break;
            }
        }

        if (!empty($aUserMeta)) {
            foreach ($aUserMeta as $key => $val) {
                SetSettings::setUserMeta($userID, $key, $val);
            }
        }

        if (!empty($aUserInfo)) {
            $aUserInfo['ID'] = $userID;
            $aUserInfo = (object)$aUserInfo;

            wp_update_user($aUserInfo);
        }

        return true;
    }

    private function updatePassword($aPassword, $userID)
    {
        $oUserData = new WP_User($userID);
        if (empty($aPassword['currentPassword']) ||
            !wp_check_password($aPassword['currentPassword'], $oUserData->data->user_pass, $userID)
        ) {
            return [
                'status' => 'error',
                'msg'    => esc_html__('ERROR: Invalid Password.', 'wiloke-listing-tools')
            ];
        }

        if ($aPassword['newPassword'] !== $aPassword['confirmNewPassword']) {
            return [
                'status' => 'error',
                'msg'    => esc_html__('ERROR: The password confirmation must be matched the new password.',
                    'wiloke-listing-tools')
            ];
        }

        reset_password($oUserData, $aPassword['newPassword']);
        do_action('wilcity/user/after_reset_password', $oUserData);

        return true;
    }

    public function checkIsUserConfirmed()
    {
        wp_send_json_success(['isConfirmed' => User::isUserConfirmedAccount() ? 'yes' : 'no']);
    }

    public function deleteAccount()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        if (!WilokeThemeOptions::isEnable('toggle_allow_customer_delete_account')) {
            $oRetrieve->error([
                'msg' => esc_html__('You do not have permission to access this page', 'wiloke-listing-tools')
            ]);
        }

        $oUser = new WP_User(User::getCurrentUserID());

        if (!isset($_POST['current_password']) || empty($_POST['current_password']) ||
            !wp_check_password($_POST['current_password'],
                $oUser->data->user_pass, $oUser->ID)
        ) {
            $oRetrieve->error([
                'msg' => esc_html__('Invalid confirm password.', 'wiloke-listing-tools')
            ]);
        }

        $aPosts = get_posts([
            'numberposts' => -1,
            'post_type'   => 'any',
            'author'      => $oUser->ID
        ]);

        if (!empty($aPosts)) {
            foreach ($aPosts as $oPost) {
                wp_delete_post($oPost->ID, true);
            };
        }
        wp_delete_user($oUser->ID);

        $oRetrieve->success([
            'msg'        => esc_html__('Your account was successfully deleted. We are sorry to see you go!',
                'wiloke-listing-tools'),
            'redirectTo' => home_url('/')
        ]);
    }

    public function updateProfile()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $userID = get_current_user_id();
        $aStatus = $this->middleware(['isUserLoggedIn'], [
            'userID' => $userID
        ]);

        if ($aStatus['status'] === 'error') {
            $oRetrieve->error($aStatus);
        }

        if (empty($_POST['values'])) {
            $oRetrieve->error([
                'msg' => esc_html__('There is nothing change', 'wiloke-listing-tools')
            ]);
        }

        $isChangedPassword = false;

        $aData = json_decode(stripslashes($_POST['values']), true);

        if (!empty($aData['basic-info']['cover_image'])) {
            $size
                = apply_filters('wiloke-listing-tools/user-profile/cover-image-size', 'large');
            $imageSrc
                = wp_get_attachment_image_url($aData['basic-info']['cover_image']['id'], $size);
            $aData['basic-info']['cover_image']['src'] = $imageSrc;
        }

        if (empty($aData)) {
            $oRetrieve->error([
                'msg' => esc_html__('Oops! Something went wrong, so We could not update the profile.',
                    'wiloke-listing-tools')
            ]);
        }

        if (isset($aData['basic-info']) && !empty($aData['basic-info'])) {
            $aStatus = $this->updateBasicInfo($aData['basic-info'], $userID);
            if ($aStatus !== true) {
                $oRetrieve->error($aStatus);
            }
        }

        if (isset($aData['follow-and-contact'])) {
            $this->updateFollowAndContact($aData['follow-and-contact'], $userID);
        }

        if (isset($aData['change-password'])) {
            if (!empty($aData['change-password']['newPassword']) &&
                !empty($aData['change-password']['confirmNewPassword'])) {
                $aStatus = $this->updatePassword($aData['change-password'], $userID);
                if ($aStatus !== true) {
                    $oRetrieve->error($aStatus);
                }

                $isChangedPassword = true;
            }
        }

        do_action('wilcity/wiloke-listing-tools/update-profile', $aData, $userID);
        $aResponse['msg'] = esc_html__('Congratulations! Your profile have been updated', 'wiloke-listing-tools');
        if ($isChangedPassword) {
            $aResponse['redirectTo'] = home_url('/');
        }

        $oRetrieve->success($aResponse);

    }

    public function fetchBillingDetails()
    {
        $aResult = InvoiceModel::getInvoiceDetails($_GET['invoiceID']);
        if (empty($aResult)) {
            wp_send_json_error([
                'msg' => esc_html__('This plan may have been deleted', 'wiloke-listing-tools')
            ]);
        }

        wp_send_json_success($aResult);
    }

    public function fetchBillings()
    {
        $offset = (absint($_GET['page']) - 1) * $this->limit;

        $aInvoices = InvoiceModel::getMyInvoices($this->limit, $offset);
        if (empty($aInvoices)) {
            if ($_GET['page'] > 1) {
                wp_send_json_error([
                    'reachedMaximum' => 'yes'
                ]);
            } else {
                wp_send_json_error(['msg' => esc_html__('There are no invoices', 'wiloke-listing-tools')]);
            }
        }

        foreach ($aInvoices as $order => $aPayments) {
            foreach ($aPayments as $paymentOrder => $aPayment) {
                if ($aPayment['gateway'] == 'woocommerce') {
                    $aPaymentInfo = PaymentModel::getPaymentInfo($aPayment['paymentID']);
                    $gateway = PaymentModel::getWooCommercePaymentGateway($aPaymentInfo['wooOrderID']);
                    if (!empty($gateway)) {
                        $aInvoices[$order][$paymentOrder]['gateway'] = $gateway;
                    }
                }
            }
        }
        wp_send_json_success($aInvoices);
    }

    public function registerAddlistingLockedColumn($aColumns)
    {
        $aColumns['addlisting_locked'] = 'Locked Status';

        return $aColumns;
    }

    public function showUpLockedUserReasonOnUserRow($val, $columnName, $userID)
    {
        switch ($columnName) {
            case 'addlisting_locked':
                $val = GetSettings::getUserMeta($userID, 'locked_addlisting');
                break;
        }

        return $val;
    }

    public function addCaps()
    {
        $oContributor = get_role('contributor');
        $oContributor->add_cap('upload_files');

        if (
            class_exists('\WilokeThemeOptions') &&
            WilokeThemeOptions::getOptionDetail('addlisting_upload_img_via') == 'wp'
        ) {
            $oSubscriber = get_role('subscriber');
            if (!empty($oSubscriber)) {
                if (current_user_can('subscriber')) {
                    $oSubscriber->add_cap('upload_files');
                } else {
                    $oSubscriber->remove_cap('upload_files');
                }
            }
        }
    }

    public function mediaAccess($aArgs)
    {
        $userID = User::getCurrentUserID();
        if (!empty($userID) && class_exists('\WilokeThemeOptions')) {
            if (
                WilokeThemeOptions::isEnable(
                    'user_admin_access_all_media',
                    true
                ) && User::currentUserCan('administrator')
            ) {
                return $aArgs;
            }

            $aArgs['author'] = User::getCurrentUserID();
        }

        return $aArgs;
    }

    public function fetchUserProfile()
    {
        $this->middleware(['isUserLoggedIn'], []);
        $userID = get_current_user_id();

        $aThemeOptions = Wiloke::getThemeOptions();

        wp_send_json_success([
            'display_name'        => User::getField('display_name', $userID),
            'avatar'              => User::getAvatar($userID),
            'position'            => User::getPosition($userID),
            'profile_description' => $aThemeOptions['dashboard_profile_description'] ?? '',
            'author_url'          => get_author_posts_url($userID)
        ]);
    }
}
