<?php

namespace WilokeListingTools\Middleware;

use WilokeListingTools\Controllers\RegisterLoginController;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Routing\InterfaceMiddleware;

class CanRegister implements InterfaceMiddleware
{
    public $msg = '';

    public function handle(array $aOptions)
    {
        if (is_user_logged_in() || !GetSettings::userCanRegister()) {
            $this->msg = sprintf(esc_html__('You are ineligible to register for %s', 'wiloke-listing-tools'),
                get_option('blogname'));

            return false;
        }

        if (\WilokeThemeOptions::isEnable('toggle_privacy_policy', false)) {
            if (!isset($aOptions['isAgreeToPrivacyPolicy']) || $aOptions['isAgreeToPrivacyPolicy'] != 'yes') {
                $this->msg = esc_html__('In order to register an account, You need to agree to our privacy policy',
                    'wiloke-listing-tools');

                return false;
            }
        }

        if (\WilokeThemeOptions::isEnable('toggle_terms_and_conditionals', false)) {
            if (!isset($aOptions['isAgreeToTermsAndConditionals']) ||
                $aOptions['isAgreeToTermsAndConditionals'] != 'yes') {
                $this->msg = esc_html__('In order to register an account, You need to agree to our term conditionals',
                    'wiloke-listing-tools');

                return false;
            }
        }

        if (!isset($aOptions['user_email']) || !is_email($aOptions['user_email'])) {
            $this->msg = esc_html__('Invalid Email', 'wiloke-listing-tools');

            return false;
        }

        if (email_exists($aOptions['user_email'])) {
            $this->msg = esc_html__('This email is already exists', 'wiloke-listing-tools');

            return false;
        }

        if (!isset($aOptions['user_login']) || empty($aOptions['user_login'])) {
            $this->msg = esc_html__('Invalid Username', 'wiloke-listing-tools');
            return false;
        }

        if (username_exists($aOptions['user_login'])) {
            $this->msg = esc_html__('This username is already exists', 'wiloke-listing-tools');

            return false;
        }

        if (!isset($aOptions['user_password']) || empty($aOptions['user_password'])) {
            $this->msg = esc_html__('The password is required', 'wiloke-listing-tools');

            return false;
        }

        return true;
    }
}
