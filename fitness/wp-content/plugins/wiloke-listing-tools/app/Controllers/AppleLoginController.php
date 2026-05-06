<?php

namespace WilokeListingTools\Controllers;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpKernel\HttpClientKernel;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\Message;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\UserModel;
use WilokeMessage;
use WilokeThemeOptions;
use WP_User;

class AppleLoginController extends AbstractRegisterLoginController
{
    public function __construct()
    {
        add_filter('wilcity/theme-options/configurations', [$this, 'addAppleLoginToThemeOptions']);

        add_filter(
            'wilcity/wiloke-listing-tools/app/Controllers/UserController/printLoginConfiguration',
            [$this, 'addAppleToLoginConfiguration']
        );

        add_action('wp_enqueue_scripts', [$this, 'registerAppleJS']);
        add_action('init', [$this, 'listenToAppleLoginRedirection']);

        add_action(
            'wilcity/wilcity-shortcodes/default-sc/wilcity-custom-login/socials-login',
            [$this, 'addAppleLoginToCustomLoginPage']
        );

        add_action('wilcity/after-navigation', [$this, 'displayLoginError']);
        add_filter(
            'wilcity/filter/wiloke-listing-tools/app/Controllers/AppleLoginController/handleAppleLogin',
            [$this, 'filterHandleAppleLogin'],
            10,
            3
        );
    }

    private function getAppleConfiguration()
    {
        $nonce = wp_create_nonce('apple-login');
        Session::setSession('applelogin', $nonce);

        return [
            'response_type' => 'code',
            'response_mode' => 'form_post',
            'clientId'      => WilokeThemeOptions::getOptionDetail('apple_client_id'),
//            'redirectURI'   => add_query_arg(['redirect' => 'apple'], home_url()),
            'redirectURI'   => home_url(),
            'scope'         => 'email',
            'state'         => $nonce
        ];
    }

    public function addAppleLoginToCustomLoginPage()
    {
        if (WilokeThemeOptions::isEnable('general_apple_login')):
            ?>
            <apple-login :configs='<?php echo json_encode($this->getAppleConfiguration()); ?>'></apple-login>
        <?php
        endif;
    }

    /**
     * @param $aInfo ['access_token', 'token_type', 'expires_in', 'refresh_token', 'id_token']
     */
    private function saveCustomerInfo($userID, $aInfo)
    {
        SetSettings::setUserMeta($userID, 'apple_info', $aInfo);
    }

    /**
     * @param $sub string sub is an unique string that created by Apple
     */
    private function saveCustomerSub($userID, $sub)
    {
        SetSettings::setUserMeta($userID, 'apple_sub', $sub);
    }

    private function http($url, $params = false)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($params) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: curl', # Apple requires a user agent header at the token endpoint
        ]);

        $response = curl_exec($ch);

        return json_decode($response);
    }

    private function createAccount($claims)
    {
        $aUser['username'] = str_replace('@privaterelay.appleid.com', '', $claims['email']);
        if (username_exists($aUser['username'])) {
            $aUser['username'] = $claims['email'];
        }

        $aUser['email'] = $claims['email'];
        $aUser['password'] = wp_generate_password();
        $aUser['isAgreeToPrivacyPolicy'] = 'yes';
        $aUser['isAgreeToTermsAndConditionals'] = 'yes';

        $aStatus = UserModel::createNewAccount($aUser, true);
        if ($aStatus['status'] === 'error') {
            return false;
        }

        if (!$aStatus['isNeedConfirm'] || (isset($claims['email_verified']) && $claims['email_verified'])) {
            SetSettings::setUserMeta($aStatus['userID'], 'confirmed', true);
        } else {
            SetSettings::setUserMeta($aStatus['userID'], 'confirmed', false);
        }

        if (!isset($claims['is_private_email']) || !$claims['is_private_email']) {
            EmailController::sendPasswordIfSocialLogin($aUser, 'apple');
        }

        return $aStatus['userID'];
    }

    private function isAccountExists($sub)
    {
        global $wpdb;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key='wilcity_apple_sub' AND meta_value=%s",
                $sub
            )
        );
    }

    /**
     * Customer may registered an account with this email before
     *
     * @param $sub
     *
     * @return string|null
     */
    private function isEmailExists($email)
    {
        return email_exists($email);
    }

    /**
     * @param array $aAdditionaInfo It contains some information like refresh token, access token
     *
     * @return array
     */
    private function handleAppleLogin($idToken, $aAdditionalInfo)
    {
        $aClaims = explode('.', $idToken);
        $claims = $aClaims[1];
        /**
         * array( 'iss' => 'https://appleid.apple.com', 'aud' => '', 'exp' => 123, 'iat' => 345, 'sub' =>
         * 'uniquestring', 'at_hash' => 'hashhere', 'email' => '4pirateemail',
         * 'email_verified' => 'true', 'is_private_email' => 'true', 'auth_time' => 1580713732, )
         */
        $aParsedClaims = json_decode(base64_decode($claims), true);

        if ($userID = $this->isAccountExists($aParsedClaims['sub'])) {
            wp_set_auth_cookie($userID, true);
            return [
                'status'    => 'success',
                'userID'    => $userID,
                'isNewUser' => false
            ];
        } else {
            if ($this->isEmailExists($aParsedClaims['email'])) {
                $oUser = get_user_by('email', $aParsedClaims['email']);
                //                if ($claims['email_verified']) {
                SetSettings::setUserMeta($oUser->ID, 'confirmed', false);
                //                }
                $this->saveCustomerSub($userID, $aParsedClaims['sub']);
                $this->saveCustomerInfo($userID, $aAdditionalInfo);

                return [
                    'status'    => 'success',
                    'userID'    => $oUser->ID,
                    'isNewUser' => true
                ];
            } else {
                $userID = $this->createAccount($aParsedClaims);

                if ($userID) {
                    $this->saveCustomerSub($userID, $aParsedClaims['sub']);
                    $this->saveCustomerInfo($userID, array_merge($aClaims, $aAdditionalInfo));
                    wp_set_auth_cookie($userID, true);

                    return [
                        'status'    => 'success',
                        'userID'    => $userID,
                        'isNewUser' => true
                    ];
                }
            }
        }

        return [
            'status' => 'error',
            'msg'    => 'somethingWentWrong'
        ];
    }

    public function filterHandleAppleLogin($aStatus, $idToken, $aAdditionalInfo)
    {
        return $this->handleAppleLogin($idToken, $aAdditionalInfo);
    }

    private function currentUrl()
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $url = "https://";
        } else {
            $url = "http://";
        }
        // Append the host(domain name, ip) to the URL.
        $url .= $_SERVER['HTTP_HOST'];

        // Append the requested resource location to the URL
        return $url . $_SERVER['REQUEST_URI'];
    }

    public function listenToAppleLoginRedirection()
    {
//        if (is_user_logged_in() || !isset($_GET['redirect']) || $_GET['redirect'] !== 'apple' ||
//            !isset($_POST['state'])) {
//            return false;
//        }

        if (is_user_logged_in() || !isset($_POST['state'])) {
            return false;
        }

        if (Session::getSession('applelogin') !== $_POST['state']) {
            return false;
        }

        $oResponse = $this->http('https://appleid.apple.com/auth/token', [
            // defining data using an array of parameters
            'client_id'     => trim(WilokeThemeOptions::getOptionDetail('apple_client_id')),
            'client_secret' => trim(WilokeThemeOptions::getOptionDetail('apple_client_secret')),
            'code'          => $_POST['code'],
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => home_url()
        ]);

        if (isset($oResponse->error)) {
            Session::setSession('apple_login_error', $oResponse->error);
            wp_redirect($this->currentUrl());
        }

        $aStatus = $this->handleAppleLogin($oResponse->id_token, get_object_vars($oResponse));

        if ($aStatus['status'] === 'success') {
            Session::destroySession('apple_login_error');
            if ($aStatus['isNewUser']) {
                $redirectTo = $this->getRegisterRedirectTo();
                if (empty($redirectTo)) {
                    wp_redirect($this->currentUrl());
                    exit;
                } else {
                    wp_redirect($redirectTo);
                    exit;
                }
            } else {
                do_action(
                    'wiloke-google-authenticator/check-otp',
                    new WP_User($aStatus['userID'])
                );

                $redirectTo = WilokeThemeOptions::getOptionDetail('login_redirect_type') == 'specify_page' ?
                    get_permalink(WilokeThemeOptions::getOptionDetail('login_redirect_to')) : $this->currentUrl();

                $aResponse = apply_filters(
                    'wilcity/filter/wiloke-listing-tools/app/Controllers/AppleLoginController/listenToAppleLoginRedirection/response',
                    [
                        'redirectTo' => $redirectTo
                    ]
                );

                wp_redirect($aResponse['redirectTo']);
                exit;
            }
        } else {
            Session::setSession('apple_login_error', $aStatus['msg']);
            wp_redirect($this->currentUrl());
        }
    }

    public function displayLoginError()
    {
        if (!class_exists('\WilokeMessage')) {
            return false;
        }

        $err = Session::getSession('apple_login_error');
        if (!empty($err)) {
            WilokeMessage::message(
                [
                    'status' => 'danger',
                    'msg'    => $err
                ]
            );
        }
    }

    public function registerAppleJS()
    {
        if (is_user_logged_in() || !WilokeThemeOptions::isEnable('general_apple_login')) {
            return false;
        }

        wp_enqueue_script(
            'apple-js',
            'https://appleid.cdn-apple.com/appleauth/static/jsapi/appleid/1/en_US/appleid.auth.js',
            [],
            '1.0',
            true
        );
    }

    public function addAppleToLoginConfiguration($aSocialsLogin)
    {

        if (WilokeThemeOptions::isEnable('general_apple_login')) {
            $aSocialsLogin[] = [
                'social'  => 'apple-login',
                'configs' => $this->getAppleConfiguration()
            ];
        }

        return $aSocialsLogin;
    }

    public function addAppleLoginToThemeOptions($aSections)
    {
        return array_map(function ($aSection) {
            if (isset($aSection['id']) && $aSection['id'] === 'register_login') {
                $aSection['fields'] = array_merge(
                    $aSection['fields'],
                    wilokeListingToolsRepository()->get('apple:login', true)->sub('fields')
                );
            }

            return $aSection;
        }, $aSections);
    }
}
