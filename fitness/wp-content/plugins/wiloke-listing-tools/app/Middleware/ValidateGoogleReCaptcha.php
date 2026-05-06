<?php

namespace WilokeListingTools\Middleware;

use WilokeListingTools\Framework\Routing\InterfaceMiddleware;
use WilokeThemeOptions;

class ValidateGoogleReCaptcha implements InterfaceMiddleware
{
    public $msg;

    private function checkAnswer($responsenKey, $secretKey)
    {

        $verifyURL = 'https://www.google.com/recaptcha/api/siteverify';

        // Collect and build POST data
        $queryData = [
            'secret'   => $secretKey,
            'response' => $responsenKey,
            'remoteip' => (isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] :
                $_SERVER['REMOTE_ADDR'])
        ];

        $queryUrl = http_build_query($queryData, '', '&');

        // Send data on the best possible way
        if (function_exists('curl_init') && function_exists('curl_setopt') && function_exists('curl_exec')) {
            // Use cURL to get data 10x faster than using file_get_contents or other methods
            $ch = curl_init($verifyURL);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $queryUrl);
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
            // If server not have active cURL module, use file_get_contents
            $opts     = [
                'http' =>
                    [
                        'method'  => 'POST',
                        'header'  => 'Content-type: application/x-www-form-urlencoded',
                        'content' => $queryUrl
                    ]
            ];
            $context  = stream_context_create($opts);
            $response = file_get_contents($verifyURL, false, $context);
        }

        // Verify all reponses and avoid PHP errors
        if ($response) {
            $result = json_decode($response);

            return $result->success;
        }

        // Dead end
        return false;

    }

    public function handle(array $aOptions): bool
    {
        if (WilokeThemeOptions::isEnable('toggle_google_recaptcha')) {
            if (isset($aOptions['action']) && $aOptions['action'] == 'wilcity_login') {
                if (WilokeThemeOptions::getOptionDetail('using_google_recaptcha_on') != 'both') {
                    return true;
                }
            }

            $siteKey   = WilokeThemeOptions::getOptionDetail('recaptcha_site_key');
            $secretKey = WilokeThemeOptions::getOptionDetail('recaptcha_secret_key');
            if (empty($siteKey) || empty($secretKey)) {
                return true;
            } else {
                $this->msg = esc_html__(
                    'The reCAPTCHA wasn\'t entered correctly. Please try it again.',
                    'wiloke-listing-tools'
                );
                if (!isset($aOptions['g-recaptcha-response']) || empty($aOptions['g-recaptcha-response'])) {
                    return false;
                }

                $isValid = $this->checkAnswer($aOptions['g-recaptcha-response'], $secretKey);
                if (!$isValid) {
                    return false;
                }
            }
        }

        return true;
    }
}
