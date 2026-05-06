<?php

namespace WilokeListingTools\Controllers;

use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Models\UserModel;

class FacebookLoginController extends AbstractRegisterLoginController
{
    private $api       = 'https://graph.facebook.com/v3.2/';
    private $fbMetaKey = 'wilcity_facebook_user_id';
    private $fbAccessToken;
    private $fbUserID;
    private $userID;
    private $aFBInfo   = [];
    private $fbFields  = 'id,first_name,last_name,email,link,picture';

    public function __construct()
    {
        add_action('wp_ajax_nopriv_wilcity_facebook_login', [$this, 'loginWithFacebookViaAjax']);
        add_filter('wilcity/wilcity-mobile-app/filter/fb-login', [$this, 'loginWithFacebookViaApp'], 10, 2);

        // FB delete account
        add_action('init', [$this, 'fbFixedFacebookLoginOnChromeiOS']);
    }

    /**
     * @return string
     */
    private function generateFakeEmail()
    {
        $homeURL = trim(home_url('/'), '/');
        $aParseHomeURL = explode('/', $homeURL);

        return uniqid('private').'@'.end($aParseHomeURL);

    }

    /**
     * Simple pass sanitazing functions to a given string
     *
     * @param $username
     *
     * @return string
     */
    private function cleanUsername($username)
    {
        return sanitize_title(str_replace('_', '-', sanitize_user($username)));
    }

    /**
     * Generated a friendly username for facebook users
     *
     * @param $user
     *
     * @return string
     */
    private function generateUsername($user)
    {
        global $wpdb;

        $username = '';
        if (!empty($user['first_name']) && !empty($user['last_name'])) {
            $username = $this->cleanUsername(trim($user['first_name']) . '-' . trim($user['last_name']));
        }

        if (!validate_username($username)) {
            $username = '';
            // use email
            $email = explode('@', $user['email']);
            if (validate_username($email[0])) {
                $username = $this->cleanUsername($email[0]);
            }
        }

        // User name can't be on the blacklist or empty
        $illegal_names = get_site_option('illegal_names');
        if (empty($username) || in_array($username, (array)$illegal_names)) {
            // we used all our options to generate a nice username. Use id instead
            $username = 'fbl_' . $user['id'];
        }

        // "generate" unique suffix
        $suffix = $wpdb->get_var($wpdb->prepare(
            "SELECT 1 + SUBSTR(user_login, %d) FROM $wpdb->users WHERE user_login REGEXP %s ORDER BY 1 DESC LIMIT 1",
            strlen($username) + 2, '^' . $username . '(-[0-9]+)?$'));

        if (!empty($suffix)) {
            $username .= "-{$suffix}";
        }

        return $username;
    }

    private function buildApiRequest()
    {
        return add_query_arg(
            [
                'fields'       => $this->fbFields,
                'access_token' => $this->fbAccessToken,
            ],
            $this->api . 'me'
        );
    }

    public function setFBUserID($userID)
    {
        $this->fbUserID = $userID;

        return $this;
    }

    public function setAccessToken($accessToken)
    {
        $this->fbAccessToken = $accessToken;

        return $this;
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
                    'meta_key'    => $this->fbMetaKey,
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

    /*
     * @since 1.2.1.2
     */
    public function fbFixedFacebookLoginOnChromeiOS()
    {
        if (!class_exists('\WilokeThemeOptions')) {
            return false;
        }

        if (is_admin() || is_user_logged_in() || !\WilokeThemeOptions::isEnable('fb_toggle_login', true)) {
            return false;
        }

        if (Session::getSession('social_login', false) !== 'facebook') {
            return false;
        }

        if (isset($_GET['code']) && !empty($_GET['code'])) {
            if (!class_exists('Facebook\Facebook')) {
                return false;
            }

            try {
                $oFacebook = new Facebook([
                    'app_id'                => \WilokeThemeOptions::getOptionDetail('fb_api_id'),
                    'app_secret'            => \WilokeThemeOptions::getOptionDetail('fb_app_secret'),
                    'default_graph_version' => 'v3.3'
                ]);

                $oHelpers = $oFacebook->getRedirectLoginHelper();

                if (isset($_GET['state'])) {
                    $oHelpers->getPersistentDataHandler()->set('state', $_GET['state']);
                }

                try {
                    $accessToken = $oHelpers->getAccessToken();
                    $oResponse = $oFacebook->get('/me?fields=' . $this->fbFields, $accessToken);
                    $oUser = $oResponse->getGraphUser();
                    $this->aFBInfo['email'] = $oUser->getEmail();
                    $this->aFBInfo['id'] = $oUser->getId();
                    $this->aFBInfo['first_name'] = $oUser->getFirstName();
                    $this->aFBInfo['last_name'] = $oUser->getLastName();

                    $aStatus = $this->setFBUserID($oUser->getId())->setAccessToken($accessToken)->loginWithFacebook();
                    if ($aStatus['status'] == 'success') {
                        wp_safe_redirect(General::loginRedirectTo());
                        exit;
                    }
                }
                catch (\Exception $oException) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        echo "Error: " . $oException->getMessage();
                        die();
                    }
                }
            }
            catch (FacebookSDKException $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    echo $e->getMessage();
                    die;
                }
            }
        }
    }

    public function loginWithFacebook($redirectTo = '')
    {
        if (empty($this->aFBInfo)) {
            $aThemeOptions = \Wiloke::getThemeOptions(true);
            // Get user from Facebook with given access token
            $fbUrl = $this->buildApiRequest();

            if (!empty($aThemeOptions['fb_app_secret'])) {
                $appsecretProof = hash_hmac('sha256', $this->fbAccessToken, $aThemeOptions['fb_app_secret']);
                $fbUrl = add_query_arg(
                    [
                        'appsecret_proof' => $appsecretProof
                    ],
                    $fbUrl
                );
            }
            $oFBResponse = wp_remote_get(esc_url_raw($fbUrl), ['timeout' => 30]);

            if (is_wp_error($oFBResponse)) {
                return [
                    'status' => 'error',
                    'msg'    => $oFBResponse->get_error_message()
                ];
            }
            $aFBUserInfo = json_decode(wp_remote_retrieve_body($oFBResponse), true);
        } else {
            $aFBUserInfo = $this->aFBInfo;
        }

        $aFBUserInfo = apply_filters(
            'wiloke-login-with-social/facebook/auth_data',
            wp_parse_args(
                $aFBUserInfo,
                [
                    'email' => $this->generateFakeEmail()
                ]
            )
        );

        //check if user at least provided email
        if (empty($aFBUserInfo['email'])) {
            return [
                'status' => 'error',
                'msg'    => esc_html__('It seems you still not verify your facebook account, please complete it before logging into the website',
                    'wiloke-listing-tools')
            ];
        }

        // Map our FB response fields to the correct user fields as found in wp_update_user
        $aUser = apply_filters(
            'wiloke-login-with-social/facebook/user_data_login',
            [
                'fb_user_id' => $aFBUserInfo['id'],
                'first_name' => $aFBUserInfo['first_name'],
                'last_name'  => $aFBUserInfo['last_name'],
                'email'      => $aFBUserInfo['email'],
                'user_url'   => '',
                'password'   => wp_generate_password(),
                'avatar'     => isset($aFBUserInfo['picture']['data']['url']) ? $aFBUserInfo['picture']['data']['url'] :
                    ''
            ]
        );

        do_action('wiloke-login-with-social/facebook/before_login', $aUser);

        if (empty($aUser['fb_user_id'])) {
            wp_send_json_error(
                [
                    'message' => esc_html__('Invalid User', 'login-with-social')
                ]
            );
        }

        $oUserInfo = $this->getUserBy($aUser);

        $isFirstTimeLogin = false;
        if ($oUserInfo) {
            $userID = absint($oUserInfo->ID);
            $this->userID = $userID;
            if (empty($oUserInfo->user_email) && !email_exists($aUser['user_email'])) {
                wp_update_user(
                    (object)['ID' => $oUserInfo->ID, 'user_email' => $aUser['user_email']]
                );
            }

            if (empty($oUserInfo->first_name) && empty($oUserInfo->last_name)) {
                wp_update_user(
                    (object)[
                        'ID'         => $oUserInfo->ID,
                        'first_name' => $aUser['first_name'],
                        'last_name'  => $aUser['last_name']
                    ]
                );
            }

            if (!empty($aUser['avatar'])) {
                $this->updateUserData($oUserInfo->ID, [
                    'avatar' => $aUser['avatar']
                ],true);
                delete_user_meta($oUserInfo->ID, 'avatar_id');
            }

        } else {
            $aUser['username'] = $this->generateUsername($aUser);
            $aUser['isAgreeToPrivacyPolicy'] = 'yes';
            $aUser['isAgreeToTermsAndConditionals'] = 'yes';

            $aStatus = UserModel::createNewAccount($aUser, true);

            if ($aStatus['status'] == 'success') {
                $userID = absint($aStatus['userID']);
                $this->userID = $userID;
                SetSettings::setUserMeta($userID, $this->fbMetaKey, $aUser['fb_user_id']);

                /*
                 * @hooked EmailController@sendPasswordToEmail
                 */

                do_action('wilcity-login-with-social/after_insert_user', $userID, $aUser, 'facebook');
                $isFirstTimeLogin = true;

                if (isset($aUser['link'])) {
                    $aSocialNetworks['facebook'] = esc_url($aUser['link']);
                    SetSettings::setUserMeta($userID, 'social_networks', $aSocialNetworks);
                }

                if (!empty($aUser['avatar'])) {
                    $this->updateUserData($userID, [
                        'avatar' => $aUser['avatar']
                    ],true);
                    delete_user_meta($userID, 'avatar_id');
                }

                do_action('wilcity/after/created-account', $userID, $aUser['username'], false, ['loginWith' => 'fb', 'isApp' => false]);

                EmailController::sendPasswordIfSocialLogin($aUser, 'facebook');
            } else {
                return [
                    'status' => 'error',
                    'msg'    => esc_html__('Sorry, We could not create your account. Please try it later',
                        'wiloke-listing-tools')
                ];
            }
        }

        if ($userID) {
            wp_set_auth_cookie($userID, true);
            if (!$isFirstTimeLogin) {
                do_action(
                    'wiloke-google-authenticator/check-otp',
                    new \WP_User($userID)
                );
            }
        }

        if ($isFirstTimeLogin) {
            $redirectTo = $this->getRegisterRedirectTo($redirectTo);
        } else {
            $redirectTo = $this->getLoginRedirectTo($redirectTo);
        }

        return apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Controllers/RegisterLoginController/handleLogin/response',
            [
                'status'     => 'success',
                'redirectTo' => $redirectTo,
                'msg'        => esc_html__('Congratulation! Your account has been created successfully',
                    'wiloke-listing-tools')
            ]
        );
    }

    public function loginWithFacebookViaAjax()
    {
        if (!\WilokeThemeOptions::isEnable('fb_toggle_login')) {
            wp_send_json_error(
                [
                    'msg' => esc_html__('We do not support this feature.', 'wiloke-listing-tools')
                ]
            );
        }

        $aFBResponse = [];
        if (isset($_POST['fb_response'])) {
            if (is_array($_POST['fb_response'])) {
                $aFBResponse = $_POST['fb_response'];
            } elseif (\WilokeListingTools\Framework\Helpers\Validation::isValidJson($_POST['fb_response'])) {
                $aFBResponse = \WilokeListingTools\Framework\Helpers\Validation::getJsonDecoded();
            }
        }

        if (empty($aFBResponse)) {
            wp_send_json_error(['msg' => esc_html__('Invalid Access Token', 'wiloke-listing-tools')]);
        }

        $accessToken =
            isset($aFBResponse['authResponse']['accessToken']) ? $aFBResponse['authResponse']['accessToken'] : '';
        $fbUserID    = $aFBResponse['authResponse']['userID'];

        $redirectTo =
            isset($_POST['redirectTo']) && $_POST['redirectTo'] !== home_url('/') ? urldecode($_POST['redirectTo']) :
                '';

        $aStatus = $this->setFBUserID($fbUserID)->setAccessToken($accessToken)->loginWithFacebook($redirectTo);

        if ($aStatus['status'] == 'success') {
            Session::setSession('social_login', 'facebook');
            wp_send_json_success($aStatus);
        } else {
            wp_send_json_error($aStatus);
        }
    }

    public function loginWithFacebookViaApp($fbUserID, $accessToken)
    {
        if (!\WilokeThemeOptions::isEnable('fb_toggle_login')) {
            return [
                'status' => 'error',
                'msg'    => esc_html__('We do not support this feature.', 'wiloke-listing-tools')
            ];
        }

        $aStatus = $this->setFBUserID($fbUserID)->setAccessToken($accessToken)->loginWithFacebook();

        $aStatus['userID'] = $this->userID;

        return $aStatus;
    }
}
