<?php

namespace WilokeListingTools\Controllers;

use Facebook\Facebook;
use Google\Cloud\Core\Testing\RegexFileFilter;
use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\Request;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Submission;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Framework\Routing\Redirector;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\UserModel;
use WilokeListingTools\Register\WilokeSubmissionConfiguration;
use WilokeThemeOptions;
use WP_User;

class RegisterLoginController extends Controller
{
    protected static $canRegister = null;
    private static   $fbMetaKey   = 'facebook_user_id';
    use InsertImg;

    public function __construct()
    {
        //Ajax
        add_action('wp_ajax_wilcity_agree_become_to_author', [$this, 'handleBecomeAnAuthorSubmission']);

        add_action('show_admin_bar', [$this, 'showAdminBar'], 999);
        add_action('wilcity/print-need-to-verify-account-message', [$this, 'printNeedToVerifyAccount']);
        add_action('wp_ajax_nopriv_wilcity_send_retrieve_password', [$this, 'sendRetrievePassword']);
        add_action('wp_ajax_nopriv_wilcity_update_password', [$this, 'updatePassword']);
        add_filter('lostpassword_redirect', [$this, 'modifyLostPasswordRedirect'], 10, 2);

        add_action('wilcity/wiloke-listing-tools/claim-approved', [$this, 'addClaimerToWilokeSubmissionGroup']);
        add_action('wilcity/wiloke-listing-tools/claim-approved', [$this, 'autoSwitchConfirmationToApproved']);

        // Custom Login
        add_filter('logout_redirect', [$this, 'modifyLogoutRedirectUrl'], 10);
        add_filter('login_url', [$this, 'modifyLoginURL'], 999);
        add_filter('register_url', [$this, 'modifyRegisterURL'], 999);
        add_filter('lostpassword_url', [$this, 'modifyLostPasswordURL'], 9999);
        add_filter('wp_loaded', [$this, 'handleLoginRegisterOnCustomLoginPage']);

        add_action('wp_ajax_nopriv_wilcity_login', [$this, 'handleAjaxLogin']);
        add_action('wp_ajax_nopriv_wilcity_register', [$this, 'handleAjaxRegister']);
        add_action('wp_ajax_nopriv_wilcity_reset_password', [$this, 'handleAjaxResetPassword']);
        add_action('wp_ajax_signin_firebase', [$this, 'signinFirebase']);
        add_action('wp_enqueue_scripts', [$this, 'printLoginConfiguration']);
        add_action('wilcity/header/after-menu', [$this, 'printRegisterLoginButton'], 20);
        add_shortcode('wilcity_login_register_shortcode', [$this, 'renderRegisterAndLoginWithShortcode']);
        add_filter(
            'wilcity/wiloke-listing-tools/filter/configs/register-login',
            [$this, 'maybeAddAdditionalFieldsToLoginRegister']
        );

        add_filter(
            'wilcity/wiloke-listing-tools/filter/logged-in/redirection',
            [$this, 'filterLoginRedirection'],
            10,
            2
        );
        add_action('wp_ajax_wilcity_check_login', [$this, 'checkLoginStatus']);
        add_action('wp_ajax_nopriv_wilcity_check_login', [$this, 'userIsNotLoggedIn']);
        add_action('init', [$this, 'maybeRedirectToCustomLoginPage']);
    }

    public function maybeRedirectToCustomLoginPage()
    {
        global $pagenow;
        if ('wp-login.php' == $pagenow && GetSettings::isEnableCustomLoginPage()) {
            if (!isset($_GET['action']) || $_GET['action'] != 'logout') {
                wp_redirect(GetSettings::getCustomLoginPage());
                exit();
            }
        }
    }

    public function userIsNotLoggedIn()
    {
        wp_send_json_error();
    }

    public function checkLoginStatus()
    {
        $addListingUrl = '';

        if (!User::canSubmitListing()) {
            if (!GetWilokeSubmission::isEnable('toggle_become_an_author')) {
                $userCan = 'none';
            } else {
                $becomeAnAuthorId = GetWilokeSubmission::getField('become_an_author_page');
                if (get_post_status($becomeAnAuthorId) == 'publish') {
                    $userCan = 'becomeanauthor';
                } else {
                    $userCan = 'none';
                }
            }
        } else {
            $userCan = 'addlisting';
            $addListingUrl = GetWilokeSubmission::getField('package', true);
            $addListingUrl = apply_filters(
                'wilcity/wiloke-listing-tools/addlisting/filter/addlistingurl',
                $addListingUrl
            );
            $aPlans = Submission::getAddListingPostTypeKeys();

            if (count($aPlans) == 1) {
                if (GetWilokeSubmission::isFreeAddListing()) {
                    $addListingUrl = add_query_arg(
                        [
                            'listing_type' => $aPlans[0]
                        ],
                        GetWilokeSubmission::getField('addlisting', true)
                    );
                } else {
                    $addListingUrl = add_query_arg(
                        [
                            'listing_type' => $aPlans[0]
                        ],
                        $addListingUrl
                    );
                }
            }
        }

        $oUser = new WP_User(get_current_user_id());

        wp_send_json_success([
            'userId'            => get_current_user_id(),
            'oUserShortInfo'    => apply_filters(
                'wilcity/filter/wiloke-listing-tools/app/Controller/OptimizeScripts/buildScriptCode/user-short-info',
                [],
                get_current_user_id()
            ),
            'userCan'           => $userCan,
            'becomeAnAuthorUrl' => GetWilokeSubmission::getField('become_an_author_page', true),
            'addListingUrl'     => $addListingUrl,
            'roles'             => array_values($oUser->roles)
        ]);
    }

    public function maybeAddAdditionalFieldsToLoginRegister($aFields)
    {
        if (!class_exists('\WilokeThemeOptions')) {
            return false;
        }

        if (WilokeThemeOptions::isEnable('toggle_privacy_policy')) {
            $aFields['register'][] = [
                'type'      => 'wil-checkbox',
                'name'      => 'isAgreeToPrivacyPolicy',
                'value'     => '',
                'trueValue' => 'yes',
                'label'     => WilokeThemeOptions::getOptionDetail('privacy_policy_desc'),
            ];
        }

        if (WilokeThemeOptions::isEnable('toggle_terms_and_conditionals')) {
            $aFields['register'][] = [
                'type'      => 'wil-checkbox',
                'name'      => 'isAgreeToTermsAndConditionals',
                'trueValue' => 'yes',
                'label'     => WilokeThemeOptions::getOptionDetail('terms_and_conditionals_desc'),
            ];
        }

        return $aFields;
    }

    public function printRegisterLoginButton()
    {
        if (GetWilokeSubmission::isEnable('toggle')) :
            ?>
            <div id="wil-login-register-controller"></div>
        <?php
        endif;
    }

    public function renderRegisterAndLoginWithShortcode()
    {
        ob_start();
        $this->printRegisterLoginButton();
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public function printLoginConfiguration()
    {
        if (!class_exists('\WilokeThemeOptions')) {
            return false;
        }

        if (WilokeThemeOptions::isEnable('toggle_custom_login_page')) {
            $mode = 'custom';
            $loginPageURL = GetSettings::getCustomLoginPage();
        } else {
            $mode = 'popup';
            $loginPageURL = '';
        }

        if (WilokeThemeOptions::getOptionDetail('login_redirect_type') !== 'self_page') {
            $redirectTo = get_permalink(WilokeThemeOptions::getOptionDetail('login_redirect_to'));
            if (empty($redirectTo)) {
                $redirectTo = home_url('/');
            }
        } else {
            global $wp;
            $redirectTo = add_query_arg($wp->request, home_url('/'));
//            $loginPageURL = add_query_arg(
//                ['redirect_to' => urlencode($redirectTo)],
//                $loginPageURL
//            );
        }

        $aGoogleReCaptcha = [];
        if (
            WilokeThemeOptions::isEnable('toggle_google_recaptcha') &&
            !empty(WilokeThemeOptions::getOptionDetail('recaptcha_site_key'))
        ) {
            $aGoogleReCaptcha['siteKey'] = WilokeThemeOptions::getOptionDetail('recaptcha_site_key');
            $aGoogleReCaptcha['on'] = WilokeThemeOptions::getOptionDetail('using_google_recaptcha_on');
        }

        $aSocialsLogin = [];
        if (WilokeThemeOptions::isEnable('fb_toggle_login')) {
            $_SESSION['fbCSRF'] = wp_create_nonce('fbCSRF');
            $aSocialsLogin[] = [
                'social'  => 'facebook',
                'configs' => [
                    'API'        => WilokeThemeOptions::getOptionDetail('fb_api_id'),
                    'fbState'    => $_SESSION['fbCSRF'],
                    'redirectTo' => home_url('/')
                ]
            ];
        }

        wp_localize_script('wilcity-empty', 'WIL_REGISTER_LOGIN', apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Controllers/RegisterLoginController/settings',
            [
                'mode'               => $mode,
                'allowRegister'      => GetSettings::userCanRegister() &&
                WilokeThemeOptions::isEnable('toggle_register') ?
                    'yes' : 'no',
                'customLoginPageUrl' => $loginPageURL,
                'loggedInRedirectTo' => apply_filters('wilcity/filter/custom_login_page_url', $redirectTo),
                'registerFormFields' => wilokeListingToolsRepository()->get('register-login:registerFormFields'),
                'googleReCaptcha'    => $aGoogleReCaptcha,
                'socialsLogin'       => apply_filters(
                    'wilcity/wiloke-listing-tools/app/Controllers/UserController/printLoginConfiguration',
                    $aSocialsLogin
                )
            ]
        ));
    }

    /**
     * @param $aData
     * @param $aAdditionalMiddleware
     *
     * @return array|bool
     * @throws \Exception
     */
    private function handleRegister($aData, $aAdditionalMiddleware = [])
    {
        if (!class_exists('\WilokeThemeOptions')) {
            return [
                'status' => 'error',
                'msg'    => esc_html__('Please activate Wilcity Theme', 'wiloke-listing-tools')
            ];
        }

        $aStatus = $this->middleware(
            array_merge(['validateGoogleReCaptcha', 'canRegister'], $aAdditionalMiddleware),
            $aData
        );

        $aStatus = apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Controllers/RegisterLoginController/handleRegister/validation',
            $aStatus,
            $aData
        );

        if ($aStatus['status'] == 'error') {
            return $aStatus;
        }

        $aData['username'] = $aData['user_login'];
        $aData['password'] = $aData['user_password'];
        $aData['email'] = $aData['user_email'];

        $aStatus = UserModel::createNewAccount($aData, false);
        if ($aStatus['status'] === 'error') {
            return $aStatus;
        }

        $ssl = is_ssl() ? true : false;
        wp_signon([
            'user_login'    => $aData['email'],
            'user_password' => $aData['password'],
            'remember'      => false
        ], $ssl);

        if (!isset($_REQUEST['redirect_to']) || (trim($_REQUEST['redirect_to']) == home_url('/'))) {
            $redirectTo = WilokeThemeOptions::getOptionDetail('created_account_redirect_to');
            $redirectTo = !empty($redirectTo) ? urlencode(get_permalink($redirectTo)) : 'self';
        } else {
            $redirectTo = urlencode(trim($_REQUEST['redirect_to']));
        }

        return array_merge(
            [
                'redirectTo' => apply_filters(
                    'wilcity/filter/wiloke-listing-tools/register-redirect-to',
                    $redirectTo
                )
            ],
            $aStatus
        );
    }

    public function filterLoginRedirection($aResponse, WP_User $oUser)
    {
        return $this->getLoginRedirection($oUser);
    }

    private function getLoginRedirection(WP_User $oUser)
    {
        if (!class_exists('\WilokeThemeOptions')) {
            return false;
        }

        $redirectionType = WilokeThemeOptions::getOptionDetail('login_redirect_type', 'self');
        $redirectionType = empty($redirectionType) || $redirectionType == 'self_page' ? 'self' : $redirectionType;
        $redirectionTo = 'self';

        if ($redirectionType !== 'self') {
            $pageId = WilokeThemeOptions::getOptionDetail('login_redirect_to');
            $link = !empty($pageId) ? get_permalink($pageId) : '';
            if (empty($link)) {
                $redirectionTo = 'self';
            } else {
                $redirectionTo = urlencode($link);
            }
        }

        return apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Controllers/RegisterLoginController/handleLogin/response',
            [
                'status'     => 'success',
                'msg'        => sprintf(
                    esc_html__('Hi %s! Nice to see you back.', 'wiloke-listing-tools'),
                    $oUser->user_login
                ),
                'redirectTo' => apply_filters(
                    'wilcity/filter/wiloke-listing-tools/login-redirect-to',
                    $redirectionTo,
                    $oUser
                )
            ]
        );
    }

    /**
     * @param       $aData
     * @param array $aAdditionalMiddleware
     *
     * @return array|bool|mixed|void
     * @throws \Exception
     */
    private function handleLogin($aData, $aAdditionalMiddleware = [])
    {
        $aStatus = $this->middleware(
            array_merge(
                $aAdditionalMiddleware,
                [
                    'validateGoogleReCaptcha',
                    'verifyLogin'
                ]
            ),
            $aData
        );

        $aStatus = apply_filters(
            'wilcity/filter/wiloke-listing-tools/register-and-login-controller/verify-login',
            $aStatus,
            $aData
        );

        if ($aStatus['status'] === 'error') {
            return $aStatus;
        }

        $aData['remember'] = isset($aData['isRemember']) && $aData['isRemember'] == 'yes';

        do_action('wilcity/before/login', $aData);
        $oUser = wp_signon($aData, is_ssl());
        if (is_wp_error($oUser)) {
            return [
                'status' => 'error',
                'msg'    => $oUser->get_error_message()
            ];
        }

        return $this->getLoginRedirection($oUser);
    }

    public function signinFirebase()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error();
        }

        wp_send_json_success(
            [
                'email'    => User::getField('user_email', get_current_user_id()),
                'password' => User::getField('user_pass', get_current_user_id())
            ]
        );
    }

    /**
     * @throws \Exception
     */
    public function handleAjaxResetPassword()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $aData = $_POST;
//        $aStatus = $this->middleware([], array_merge($aData, ['isBoolean' => true]));
//
//        if ($aStatus['status'] === 'error') {
//            $oRetrieve->error($aStatus);
//        }

        $aStatus = $this->handleResetPassword($aData);
        if ($aStatus['status'] === 'error') {
            $oRetrieve->error($aStatus);
        }

        $oRetrieve->success($aStatus);
    }

    private function handleResetPassword($aData)
    {
        $oRetrieve = new RetrieveController(new NormalRetrieve());
        if (empty($aData['user_login'])) {
            return $oRetrieve->error([
                'msg' => esc_html__('Please provide your username or email address.', 'wiloke-listing-tools')
            ]);
        } else if (strpos($aData['user_login'], '@')) {
            $email = trim($aData['user_login']);
            $oUserData = get_user_by('email', $email);
            if (empty($oUserData)) {
                return $oRetrieve->error([
                    'msg' => esc_html__('If email is registered, you will get a reset link. Please check your mail box / spam box and click on that link.',
                        'wiloke-listing-tools')
                ]);
            }
        } else {
            $login = trim($aData['user_login']);
            $oUserData = get_user_by('login', $login);

            if (empty($oUserData)) {
                return $oRetrieve->error([
                    'msg' => esc_html__('If email is registered, you will get a reset link. Please check your mail box / spam box and click on that link.',
                        'wiloke-listing-tools')
                ]);
            }
        }

        $userEmail = $oUserData->user_email;
        $userLogin = $oUserData->user_login;

        $key = get_password_reset_key($oUserData);

        if (is_wp_error($key)) {
            return $oRetrieve->error([
                'msg' => $key->get_error_message()
            ]);
        }

        $resetPasswordPageID = WilokeThemeOptions::getOptionDetail('reset_password_page');
        if (!empty($resetPasswordPageID) && get_post_status($resetPasswordPageID) == 'publish') {
            $resetURL = get_permalink($resetPasswordPageID);
            $resetURL = add_query_arg(
                [
                    'key'    => $key,
                    'login'  => rawurlencode($userLogin),
                    'action' => 'rp'
                ],
                $resetURL
            );
        } else {
            $resetURL = add_query_arg(
                [
                    'action' => 'rp',
                    'key'    => $key,
                    'login'  => rawurlencode($userLogin)
                ],
                admin_url('wp-login.php')
            );
            network_site_url($resetURL, 'login');
        }

        $message = esc_html__(
                'Someone has requested a password reset for the following account:',
                'wiloke-listing-tools'
            ) . "\r\n\r\n";
        $message .= network_home_url('/') . "\r\n\r\n";
        $message .= sprintf(__('Username: %s'), $userLogin) . "\r\n\r\n";
        $message .= esc_html__(
                'If this was a mistake, just ignore this email and nothing will happen.',
                'wiloke-listing-tools'
            ) . "\r\n\r\n";
        $message .= esc_html__(
                'To reset your password, visit the following address:',
                'wiloke-listing-tools'
            ) . "\r\n\r\n";
        $message .= '<a href="' . $resetURL . '">' . $resetURL . "</a>\r\n";

        if (is_multisite()) {
            $blogname = get_network()->site_name;
        } else {
            /*
         * The blogname option is escaped with esc_html on the way into the database
         * in sanitize_option we want to reverse this for the plain text arena of emails.
         */
            $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        }

        /* translators: Password reset email subject. 1: Site name */
        $title = sprintf(__('[%s] Password Reset', 'wiloke-listing-tools'), $blogname);
        if ($message && !wp_mail($userEmail, wp_specialchars_decode($title), $message)) {
            return $oRetrieve->error(
                [
                    'msg' => __(
                            'The email could not be sent.',
                            'wiloke-listing-tools'
                        ) . "<br />\n" .
                        __(
                            'Possible reason: your host may have disabled the mail() function.',
                            'wiloke-listing-tools'
                        )
                ]
            );
        }
        $aParseMail = explode('@', $userEmail);
        $mailDomain = end($aParseMail);
        $totalLength = strlen($aParseMail[0]);

        if ($totalLength > 5) {
            $truncateIndex = 4;
        } else {
            $truncateIndex = $totalLength - 2;
        }

        $escapeEmail = substr($aParseMail[0], 0, $truncateIndex) . '***' . '@' . $mailDomain;

        return $oRetrieve->success(
            [
                'msg'             => sprintf(esc_html__(
                    'If email is registered, you will get a reset link. Please check your mail box / spam box and click on that link.',
                    'wiloke-listing-tools'
                ), $escapeEmail),
                'isFocusHideForm' => true
            ]
        );
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function handleAjaxLogin()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        $aResponse = $this->handleLogin($_POST, []);

        if ($aResponse['status'] === 'error') {
            $oRetrieve->error($aResponse);
        }

        return $oRetrieve->success($aResponse);
    }

    /**
     * @throws \Exception
     */
    public function handleAjaxRegister()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        $aResponse = $this->handleRegister($_POST, []);
        if ($aResponse['status'] === 'success') {
            $oRetrieve->success($aResponse);
        }

        $oRetrieve->error($aResponse);
    }

    private function autoLogin($userID)
    {
        wp_set_current_user($userID);
        wp_set_auth_cookie($userID, false, is_ssl());
    }

    private function customLoginRedirectTo($aResponse)
    {
        if ($aResponse['redirectTo'] === 'self') {
            if (!isset($_REQUEST['redirect_to'])) {
                return home_url('/');
            } else {
                return urldecode(trim($_REQUEST['redirect_to']));
            }
        }

        return urldecode($aResponse['redirectTo']);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function handleLoginRegisterOnCustomLoginPage()
    {
        if (!class_exists('\WilokeThemeOptions')) {
            return false;
        }

        if (!isset($_POST['form_type']) || $_POST['form_type'] !== 'custom_login') {
            return false;
        }

        Session::destroySession('login-error');
        Session::destroySession('register-error');
        switch ($_POST['action']) {
            case 'wilcity_register':
                $aResponse = $this->handleRegister($_POST);
                if ($aResponse['status'] == 'success') {
                    wp_safe_redirect($this->customLoginRedirectTo($aResponse));
                    exit();
                } else if ($aResponse['status'] == 'error') {
                    Session::setSession('register-error', $aResponse['msg']);
                }
                break;
            case 'wilcity_rp':
                $aResponse = $this->handleResetPassword($_POST);
                Session::setSession('rp-status', maybe_serialize($aResponse));
                Session::setSession('register-error', $aResponse['msg']);
                break;
            case 'wilcity_login':
                $aResponse = $this->handleLogin($_POST);
                if ($aResponse['status'] == 'error') {
                    Session::setSession('login-error', $aResponse['msg']);
                } else {
                    wp_safe_redirect($this->customLoginRedirectTo($aResponse));
                    exit();
                }
                break;
            default:
                do_action(
                    'wilcity/wiloke-listing-tools/app/Register/RegisterLoginController/handleLoginRegisterOnCustomLoginPage/' .
                    $_POST['action'],
                    $_POST
                );
                break;
        }
    }

    public function modifyLoginURL($loginURL)
    {
        if (GetSettings::getCustomLoginPage()) {
            return add_query_arg(
                [
                    'action' => 'login'
                ],
                GetSettings::getCustomLoginPage()
            );
        }

        return $loginURL;
    }

    public function modifyRegisterURL($registerPageURL)
    {
        if (GetSettings::getCustomLoginPage()) {
            return add_query_arg(
                [
                    'action' => 'register'
                ],
                GetSettings::getCustomLoginPage()
            );
        }

        return $registerPageURL;
    }

    private function updateUserData($userID, $aData, $isFocus = false)
    {
        $aUserData = GetSettings::getUserData($userID);

        foreach ($aData as $key => $val) {
            if (!$isFocus) {
                if (empty($aUserData[$key])) {
                    SetSettings::setUserMeta($userID, $key, $val);
                }
            } else {
                SetSettings::setUserMeta($userID, $key, $val);
            }
        }
    }

    public function addClassToLoginButton()
    {
        if (is_page_template('templates/custom-login-page.php')) {
            return 'wil-btn mb-20 wil-btn--gradient wil-btn--md wil-btn--round wil-btn--block';
        }

        return '';
    }

    public function afterLoggedInWithSocialWillRedirectTo($redirectTo, $isFirstTimeLoggedIn)
    {
        $aThemeOptions = \Wiloke::getThemeOptions(true);
        if ($isFirstTimeLoggedIn) {
            $redirectTo = isset($aThemeOptions['created_account_redirect_to']) &&
            !empty($aThemeOptions['created_account_redirect_to']) &&
            $aThemeOptions['created_account_redirect_to'] != 'self_page' ?
                urlencode(get_permalink($aThemeOptions['created_account_redirect_to'])) : 'self';
        } else {
            $redirectTo
                = isset($aThemeOptions['login_redirect_type']) && !empty($aThemeOptions['login_redirect_type']) &&
            $aThemeOptions['login_redirect_type'] !== 'self_page' ?
                urlencode(get_permalink($aThemeOptions['login_redirect_to'])) : 'self';
        }

        return $redirectTo;
    }

    private function getUserBy($aUser)
    {

        // if the user is logged in, pass curent user
        if (is_user_logged_in()) {
            return wp_get_current_user();
        }

        $user_data = get_user_by('email', $aUser['email']);

        if (!$user_data) {
            $users = get_users(
                [
                    'meta_key'    => self::$fbMetaKey,
                    'meta_value'  => $aUser['fb_user_id'],
                    'number'      => 1,
                    'count_total' => false
                ]
            );
            if (is_array($users)) {
                $user_data = reset($users);
            }
        }

        return $user_data;
    }

    public static function canRegister()
    {
        if (self::$canRegister !== null) {
            return self::$canRegister;
        }

        self::$canRegister = GetSettings::userCanRegister();

        return self::$canRegister;
    }

    public function addClaimerToWilokeSubmissionGroup($aInfo)
    {
        $user_meta = get_userdata($aInfo['claimerID']);
        $aUserRoles = $user_meta->roles;
        if (in_array('subscriber', $aUserRoles)) {
            UserModel::addSubmissionRole($aInfo['claimerID']);
        }
    }

    public function autoSwitchConfirmationToApproved($aInfo)
    {
        SetSettings::setUserMeta($aInfo['claimerID'], 'confirmed', true);
    }

    public function modifyLostPasswordURL($url)
    {
        if (!class_exists('\WilokeThemeOptions')) {
            return $url;
        }

        global $wiloke;
        if (WilokeThemeOptions::isEnable('toggle_custom_login_page')) {
            return add_query_arg(
                [
                    'action' => 'rp'
                ],
                get_permalink($wiloke->aThemeOptions['custom_login_page'])
            );
        }

        return $url;
    }

    public function modifyLostPasswordRedirect($url)
    {
        global $wiloke;
        if (isset($wiloke->aThemeOptions['reset_password_page']) &&
            !empty($wiloke->aThemeOptions['reset_password_page'])) {
            if (get_post_status($wiloke->aThemeOptions['reset_password_page']) == 'publish') {
                return get_permalink($wiloke->aThemeOptions['reset_password_page']);
            }
        }

        return $url;
    }

    public function updatePassword()
    {
        if (!isset($_POST['newPassword'])) {
            wp_send_json_error(esc_html__('Please enter your new password', 'wiloke-listing-tools'));
        }
        $aCheckResetPWStatus = check_password_reset_key($_POST['rpKey'], $_POST['user_login']);

        if (is_wp_error($aCheckResetPWStatus) || !$aCheckResetPWStatus) {
            wp_send_json_error(esc_html__('The reset key has been expired', 'wiloke-listing-tools'));
        }

        $oUser = get_user_by('login', sanitize_text_field($_POST['user_login']));

        if (is_wp_error($oUser) || empty($oUser)) {
            wp_send_json_error(esc_html__('This username does not exist.', 'wiloke-listing-tools'));
        }

        reset_password($oUser, $_POST['newPassword']);

        SetSettings::setUserMeta($oUser->ID, 'confirmed', true);
        wp_send_json_success(esc_html__('Congratulations! The new password has been updated successfully. Please click on Login button to Log into the website',
            'wiloke-listing-tools'));
    }

    public function sendRetrievePassword()
    {
        $errors = new \WP_Error();
        $isAjax = wp_doing_ajax();

        if (empty($_POST['user_login']) || !is_string($_POST['user_login'])) {
            $errors->add('empty_username',
                __('<strong>ERROR</strong>: Enter a username or email address.', 'wiloke-listing-tools'));
        } elseif (strpos($_POST['user_login'], '@')) {
            $user_data = get_user_by('email', trim(wp_unslash($_POST['user_login'])));
            if (empty($user_data)) {
                $errors->add('invalid_email',
                    __('<strong>ERROR</strong>: There is no user registered with that email address.',
                        'wiloke-listing-tools'));
            }
        } else {
            $login = trim($_POST['user_login']);
            $user_data = get_user_by('login', $login);
        }

        do_action('lostpassword_post', $errors);

        if ($errors->get_error_code()) {
            if ($isAjax) {
                wp_send_json_error($errors->get_error_message());
            } else {
                return [
                    'status' => 'error',
                    'msg'    => $errors->get_error_message()
                ];
            }
        }

        if (!$user_data) {
            $errors->add('invalidcombo',
                __('<strong>ERROR</strong>: Invalid username or email.', 'wiloke-listing-tools'));
            if ($isAjax) {
                wp_send_json_error($errors->get_error_message());
            } else {
                return [
                    'status' => 'error',
                    'msg'    => $errors->get_error_message()
                ];
            }
        }

        // Redefining user_login ensures we return the right case in the email.
        $user_login = $user_data->user_login;
        $user_email = $user_data->user_email;
        $key = get_password_reset_key($user_data);

        if (is_wp_error($key)) {
            $msg
                = esc_html__('Oops! We could not generate reset key. Please contact the administrator to report this issue',
                'wiloke-listing-tools');
            if ($isAjax) {
                wp_send_json_error($msg);
            } else {
                return [
                    'status' => 'error',
                    'msg'    => $msg
                ];
            }
        }

        if (is_multisite()) {
            $site_name = get_network()->site_name;
        } else {
            $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        }

        $aThemeOptions = \Wiloke::getThemeOptions(true);
        if (!isset($aThemeOptions['reset_password_page']) || empty($aThemeOptions['reset_password_page']) ||
            get_post_status($aThemeOptions['reset_password_page']) != 'publish') {
            $resetPasswordURL = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login),
                'login');
        } else {
            $resetPasswordURL = get_permalink($aThemeOptions['reset_password_page']);
            $resetPasswordURL = add_query_arg(
                [
                    'action' => 'rp',
                    'key'    => $key,
                    'login'  => rawurlencode($user_login)
                ],
                $resetPasswordURL
            );
        }

        //wp_send_json_error(['data' => $resetPasswordURL]);

        $message = __('Someone has requested a password reset for the following account:',
                'wiloke-listing-tools') . "\r\n\r\n";
        /* translators: %s: site name */
        $message .= sprintf(__('Site Name: %s', 'wiloke-listing-tools'), $site_name) . "\r\n\r\n";
        /* translators: %s: user login */
        $message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
        $message .= __('If this was a mistake, just ignore this email and nothing will happen.',
                'wiloke-listing-tools') . "\r\n\r\n";
        $message .= __('To reset your password, visit the following address:', 'wiloke-listing-tools') . "\r\n\r\n";
        $message .= "<a href='" . $resetPasswordURL . "'>" . $resetPasswordURL . "</a>\r\n";

        /* translators: Password reset email subject. %s: Site name */
        $title = sprintf(__('[%s] Password Reset', 'wiloke-listing-tools'), $site_name);
        $title = apply_filters('retrieve_password_title', $title, $user_login, $user_data);
        $message = apply_filters('retrieve_password_message', $message, $key, $user_login, $user_data);

        if ($message && !wp_mail($user_email, wp_specialchars_decode($title), $message)) {
            $msg = __('The email could not be sent.') . "<br />\n" .
                __('Possible reason: your host may have disabled the mail() function.',
                    'wiloke-listing-tools');

            if ($isAjax) {
                wp_send_json_error($msg);
            } else {
                return [
                    'status' => 'error',
                    'msg'    => $msg
                ];
            }
        }

        $msg
            = esc_html__('We sent an email to you with a link to get back into your account. Please check your mailbox and click on the reset link.',
            'wiloke-listing-tools');

        if ($isAjax) {
            wp_send_json_success($msg);
        } else {
            return [
                'status' => 'success',
                'msg'    => $msg
            ];
        }
    }

    public function printNeedToVerifyAccount()
    {
        \WilokeMessage::message(
            [
                'msg'        => __('We have sent an email with a confirmation link to your email address. In order to complete the sign-up process, please click the confirmation link.
If you do not receive a confirmation email, please check your spam folder. Also, please verify that you entered a valid email address in our sign-up form. <a href="#" class="wil-js-send-confirmation-code">Resend confirmation code</a>',
                    'wiloke-listing-tools'),
                'status'     => 'danger',
                'msgIcon'    => 'la la-bullhorn',
                'hasMsgIcon' => true
            ]
        );
    }

    public function verifyConfirmation()
    {
        if (!isset($_REQUEST['confirm_account'])) {
            return false;
        }
    }

    public function handleBecomeAnAuthorSubmission()
    {
        $this->middleware(['iAgreeToPrivacyPolicy', 'iAgreeToTerms'], [
            'agreeToTerms'         => $_POST['agreeToTerms'],
            'agreeToPrivacyPolicy' => $_POST['agreeToPrivacyPolicy']
        ]);

        if (User::canSubmitListing()) {
            wp_send_json_success();
        }

        UserModel::addSubmissionRole(get_current_user_id());

        do_action('wilcity/became-an-author', get_current_user_id());

        $aResponse = apply_filters('wilcity/filter/wiloke-listing-tools/became-an-author/msg', [
            'data' => []
        ]);
        wp_send_json_success($aResponse);
    }

    public function showAdminBar($status): bool
    {
        if (!class_exists('\WilokeThemeOptions')) {
            return $status;
        }

        if (current_user_can('administrator')) {
            if ((!function_exists('wilcityIsWebview') || !wilcityIsWebview())) {
                return WilokeThemeOptions::isEnable('general_toggle_admin_bar', false);
            }

            return false;
        }

        return false;
    }

    public function modifyLogoutRedirectUrl($logout_url)
    {
        return apply_filters('wilcity/wiloke-listing-tools/filter/logout-redirect', home_url('/'));
    }
}
