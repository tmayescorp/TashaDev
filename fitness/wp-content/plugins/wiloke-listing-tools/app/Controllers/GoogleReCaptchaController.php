<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Routing\Controller;

class GoogleReCaptchaController extends Controller
{
    const RECAPTCHA_API_SERVER        = 'http://www.google.com/recaptcha/api';
    const RECAPTCHA_API_SECURE_SERVER = 'https://www.google.com/recaptcha/api';
    const RECAPTCHA_VERIFY_SERVER     = 'www.google.com';

    public function __construct()
    {
//        add_filter('wilcity/filter/wiloke-listing-tools/validate-before-insert-account',
//            [$this, 'validateGoogleReCaptcha']);
//        add_filter('wilcity/filter/wiloke-listing-tools/validate-before-login',
//            [$this, 'validateGoogleReCaptchaOnLoginPage']);
        add_action('wilcity/wiloke-listing-tools/custom-login-form', [$this, 'renderCaptchaOnLoginPage']);
        add_action('wilcity/wiloke-listing-tools/custom-register-form', [$this, 'renderCaptchaOnRegisterPage']);
    }

    private function isUsingGoogleCatpchaOnLoginPage()
    {
        global $wiloke;
        if (!isset($wiloke->aThemeOptions['using_google_recaptcha_on']) ||
            $wiloke->aThemeOptions['using_google_recaptcha_on'] == 'register_page') {
            return false;
        }

        return true;
    }

    private function renderGoogleReCaptcha()
    {
        global $wiloke;
        if (\WilokeThemeOptions::isEnable('toggle_google_recaptcha')) {
            if (!empty($wiloke->aThemeOptions['recaptcha_site_key']) &&
                !empty($wiloke->aThemeOptions['recaptcha_secret_key'])) {
                echo '<div class="wilcity-google-recaptcha-wrapper mt-20 mb-20"><div id="wilcity-render-google-repcatcha" class="g-recaptcha"></div></div>';
            }
        }
    }

    public function renderCaptchaOnLoginPage()
    {
        if (!$this->isUsingGoogleCatpchaOnLoginPage()) {
            return '';
        }

        $this->renderGoogleReCaptcha();
    }

    public function renderCaptchaOnRegisterPage()
    {
        $this->renderGoogleReCaptcha();
    }

    private function checkAnswer($responsenKey)
    {
        global $wiloke;

        $userIP = $_SERVER['REMOTE_ADDR'];
        $secretKey = $wiloke->aThemeOptions['recaptcha_secret_key'];
        $verifyURL = 'https://www.google.com/recaptcha/api/siteverify';

        // Collect and build POST data
        $post_data = http_build_query(
            [
                'secret'   => $secretKey,
                'response' => $responsenKey,
                'remoteip' => (isset($_SERVER["HTTP_CF_CONNECTING_IP"]) ? $_SERVER["HTTP_CF_CONNECTING_IP"] :
                    $_SERVER['REMOTE_ADDR'])
            ]
        );

        if (function_exists('curl_init') && function_exists('curl_setopt') && function_exists('curl_exec')) {
            $ch = curl_init($verifyURL);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_HTTPHEADER,
                ['Accept: application/json', 'Content-type: application/x-www-form-urlencoded']);
            $response = curl_exec($ch);
            curl_close($ch);
        } else {
            $opts = [
                'http' =>
                    [
                        'method'  => 'POST',
                        'header'  => 'Content-type: application/x-www-form-urlencoded',
                        'content' => $post_data
                    ]
            ];
            $context = stream_context_create($opts);
            $response = file_get_contents($verifyURL, false, $context);
        }

        $response = json_decode($response);

        return $response->success;
    }

    public function validateGoogleReCaptcha($aStatus)
    {
        global $wiloke;
        if (\WilokeThemeOptions::isEnable('toggle_google_recaptcha')) {
            if (empty($wiloke->aThemeOptions['recaptcha_site_key']) ||
                empty($wiloke->aThemeOptions['recaptcha_secret_key'])) {
                return [
                    'status' => 'error',
                    'msg'    => esc_html__('Recaptcha Key is required, please go to Appearance -> Theme Options -> Register And Login to complete this setting',
                        'wiloke-listing-tools')
                ];
            } else {
                $isvalid = $this->checkAnswer($_POST['g-recaptcha-response']);
                if (!$isvalid) {
                    return ['status' => 'error',
                            'msg'    => esc_html__('The reCAPTCHA wasn\'t entered correctly. Please try it again.',
                                'wiloke-listing-tools')
                    ];
                }
            }
        }

        return $aStatus;
    }

    public function validateGoogleReCaptchaOnLoginPage($aStatus)
    {
        if (!$this->isUsingGoogleCatpchaOnLoginPage()) {
            return $aStatus;
        }

        return $this->validateGoogleReCaptcha($aStatus);
    }
}
