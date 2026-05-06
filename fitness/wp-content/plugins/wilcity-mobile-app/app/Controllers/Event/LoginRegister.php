<?php

namespace WILCITY_APP\Controllers;

use Dolondro\GoogleAuthenticator\GoogleAuthenticator;
use PHPUnit\Exception;
use ReallySimpleJWT\Token;
use WILCITY_APP\Helpers\App;
use Wiloke;
use WilokeListingTools\Controllers\DashboardController;
use WilokeListingTools\Controllers\RegisterLoginController;
use WilokeListingTools\Controllers\SearchFormController;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\WPML;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Frontend\User;
use ReallySimpleJWT\TokenBuilder;
use WilokeListingTools\Models\UserModel;
use WP_REST_Request;
use WP_User;

class LoginRegister
{
	use VerifyToken;
	use JsonSkeleton;
	use BuildToken;
	use ParsePost;

	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/v2', 'auth', [
				'methods'             => 'POST',
				'callback'            => [$this, 'handleSignIn'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'verify-password', [
				'methods'             => 'POST',
				'callback'            => [$this, 'verifyPassword'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'wc/temp-auth', [
				'methods'             => 'POST',
				'callback'            => [$this, 'temporaryAuthentication'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'signup', [
				'methods'             => 'POST',
				'callback'            => [$this, 'signUp'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'fb-signin', [
				'methods'             => 'POST',
				'callback'            => [$this, 'fbSingIn'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'update-password', [
				'methods'             => 'POST',
				'callback'            => [$this, 'updatePassword'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'is-token-living', [
				'methods'             => 'GET',
				'callback'            => [$this, 'isTokenLiving'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'get-signup-fields', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getSingupFields'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'auth', [
				'methods'             => 'POST',
				'callback'            => [$this, 'handleSignIn'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'me/auth', [
				'methods'             => 'POST',
				'callback'            => [$this, 'handleSignIn'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'wc/temp-auth', [
				'methods'             => 'POST',
				'callback'            => [$this, 'temporaryAuthentication'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'me/wc/temp-auth', [
				'methods'             => 'POST',
				'callback'            => [$this, 'temporaryAuthentication'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'me/signup', [
				'methods'             => 'POST',
				'callback'            => [$this, 'signUp'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'signup', [
				'methods'             => 'POST',
				'callback'            => [$this, 'signUp'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'me/fb-signin', [
				'methods'             => 'POST',
				'callback'            => [$this, 'fbSingIn'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'fb-signin', [
				'methods'             => 'POST',
				'callback'            => [$this, 'fbSingIn'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'me/update-password', [
				'methods'             => 'POST',
				'callback'            => [$this, 'updatePassword'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'update-password', [
				'methods'             => 'POST',
				'callback'            => [$this, 'updatePassword'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'me/is-token-living', [
				'methods'             => 'GET',
				'callback'            => [$this, 'isTokenLiving'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'is-token-living', [
				'methods'             => 'GET',
				'callback'            => [$this, 'isTokenLiving'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'me/get-signup-fields', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getSingupFields'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'get-signup-fields', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getSingupFields'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'me/verify-password', [
				'methods'             => 'POST',
				'callback'            => [$this, 'verifyPassword'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'verify-password', [
				'methods'             => 'POST',
				'callback'            => [$this, 'verifyPassword'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'otp/verify', [
				'methods'             => 'POST',
				'callback'            => [$this, 'verifyOTP'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'me/otp/verify', [
				'methods'             => 'POST',
				'callback'            => [$this, 'verifyOTP'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'otp/qrcode', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getQrCode'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'me/otp/qrcode', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getQrCode'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'otp/unlock-enable-otp', [
				'methods'             => 'POST',
				'callback'            => [$this, 'handleUnlockEnableOTP'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'me/otp/unlock-enable-otp', [
				'methods'             => 'POST',
				'callback'            => [$this, 'handleUnlockEnableOTP'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'otp/enable', [
				'methods'             => 'POST',
				'callback'            => [$this, 'handleEnableOTP'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'me/otp/enable', [
				'methods'             => 'POST',
				'callback'            => [$this, 'handleEnableOTP'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'otp/disable', [
				'methods'             => 'POST',
				'callback'            => [$this, 'handleDisableOTP'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'me/otp/disable', [
				'methods'             => 'POST',
				'callback'            => [$this, 'handleDisableOTP'],
				'permission_callback' => '__return_true'
			]);
		});

		add_action('after_password_reset', [$this, 'afterPasswordReset'], 10);
		add_action('wilcity/user/after_reset_password', [$this, 'afterPasswordReset'], 10);
		add_action('init', [$this, 'loginWithURLToken']);
		add_filter('wilcity/filter/wiloke-mobile-app/createPermanentToken', [$this, 'createPermanentToken'], 10, 2);
	}

	public function createPermanentToken($response, $oUser): array
	{
		return $this->buildPermanentLoginToken($oUser);
	}

	private function getCurrentOrderUrl($orderID)
	{
		$oOrder = wc_get_order($orderID);
		if (is_wp_error($oOrder) || empty($oOrder)) {
			return home_url('/');
		}

		$aActions = wc_get_account_orders_actions($oOrder);

		return add_query_arg(
			[
				'iswebview' => 'yes'
			],
			$aActions['pay']['url']
		);
	}

	public function loginWithURLToken()
	{
		if (is_admin()) {
			return false;
		}

		if (!isset($_GET['token']) || empty($_GET['token'])) {
			return false;
		}

		if (!is_user_logged_in()) {
			$token = trim($_GET['token']);
			$oToken = $this->verifyTemporaryToken($token);

			if (!$oToken) {
				$oToken = $this->verifyPermanentToken($token);
			}

			if (!$oToken) {
				return false;
			} else {
				$oToken->getUserID();
			}
			$isLoggedInBefore = false;
		} else {
			$isLoggedInBefore = true;
		}

		if (isset($_GET['orderID'])) {
			$redirectTo = $this->getCurrentOrderUrl($_GET['orderID']);
		} else {
			if (isset($_GET['redirectTo']) && filter_var($_GET['redirectTo'], FILTER_VALIDATE_URL)) {
				$redirectTo = $_GET['redirectTo'];
			} else {
				global $post;
				if (isset($post->ID)) {
					$redirectTo = get_permalink($post->ID);
				} else {
					$redirectTo = (is_ssl() ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
					$redirectTo = preg_replace_callback('/(token=([^&]+))(&?)/', function ($match) {
						return '';
					}, $redirectTo);

					$redirectTo = trim($redirectTo, '&');
				}
			}
		}

		if (is_user_logged_in()) {
			wp_safe_redirect($redirectTo);
		} else {
			$this->getUserID();
			wp_set_auth_cookie(abs($this->userID), true, true);
			wp_safe_redirect($redirectTo);
		}
		exit;
	}

	public function firebaseListenUserStatusAnchor()
	{
		if ($userID = Session::getSession(wilokeListingToolsRepository()->get('user:firebaseTriggerCheckUserStatus'),
			true)
		) {
			$status = is_user_logged_in() ? 'login' : 'logout';
			?>
            <div id="wilcity-firebase-trigger-update-user-status">
                <firebase-update-user-status email="<?php echo esc_attr(User::getField('user_email', $userID)); ?>"
                                             password="<?php echo esc_attr(User::getField('user_pass')); ?>"
                                             user-id="<?php echo esc_attr($userID); ?>"
                                             status="<?php echo esc_attr($status); ?>"></firebase-update-user-status>
            </div>
			<?php
		}
	}

	public function getSingupFields()
	{
		WPML::switchLanguageApp();
		$aThemeOptions = Wiloke::getThemeOptions(true);

		return [
			'status'  => 'success',
			'oFields' => [
				[
					'type'           => 'text',
					'key'            => 'username',
					'label'          => 'username',
					'required'       => true,
					'validationType' => 'username'
				],
				[
					'type'           => 'text',
					'key'            => 'email',
					'label'          => 'email',
					'required'       => true,
					'validationType' => 'email'
				],
				[
					'type'           => 'password',
					'key'            => 'password',
					'label'          => 'password',
					'required'       => true,
					'validationType' => 'password'
				],
				[
					'type'           => 'checkbox2',
					'key'            => 'isAgreeToPrivacyPolicy',
					'label'          => isset($aThemeOptions['mobile_policy_label']) ?
						$aThemeOptions['mobile_policy_label'] : 'Agree To our Policy Privacy',
					'required'       => true,
					'link'           => get_permalink($aThemeOptions['mobile_policy_page']),
					'validationType' => 'agreeToPolicy'
				],
				[
					'type'           => 'checkbox2',
					'key'            => 'isAgreeToTermsAndConditionals',
					'label'          => isset($aThemeOptions['mobile_term_label']) ?
						$aThemeOptions['mobile_term_label'] : 'Agree To our Terms and Conditional',
					'required'       => true,
					'link'           => get_permalink($aThemeOptions['mobile_term_page']),
					'validationType' => 'agreeToTerms'
				]
			]
		];
	}

	public function fbSingIn()
	{
		$oToken = $this->verifyPermanentToken();
		if ($oToken) {
			return [
				'status' => 'error',
				'msg'    => 'youAreLoggedInAlready'
			];
		}

		$aData = $this->parsePost();
		$aData = wp_parse_args($aData, [
			'fbUserID'    => '',
			'accessToken' => ''
		]);

		/*
		 * FacebookLoginController@loginWithFacebookViaApp
		 */
		$aStatus = apply_filters(
			'wilcity/wilcity-mobile-app/filter/fb-login',
			$aData['fbUserID'],
			$aData['accessToken']
		);

		if ($aStatus['status'] == 'error') {
			unset($aStatus['userID']);

			return $aStatus;
		}

		$oUser = new WP_User($aStatus['userID']);
		$aResponse = $this->buildPermanentLoginToken($oUser);
		if ($aResponse['status'] == 'error') {
			return $aResponse;
		}
		return apply_filters(
			'wilcity/filter/wilcity-mobile-app/app/Controllers/LoginRegister/authentication',
			App::get('UserInfo')->buildUserInfo($oUser, $aResponse['token'])
		);
	}

	public function signUp()
	{
		WPML::switchLanguageApp();
		$oToken = $this->verifyPermanentToken();
		if ($oToken) {
			return [
				'status' => 'error',
				'msg'    => 'youAreLoggedInAlready'
			];
		}

		$aData = $this->parsePost();
		$aData = wp_parse_args($aData, [
			'email'                         => '',
			'username'                      => '',
			'password'                      => '',
			'isAgreeToPrivacyPolicy'        => false,
			'isAgreeToTermsAndConditionals' => false
		]);

		do_action('wilcity/before/register', $aData);

		if (!RegisterLoginController::canRegister()) {
			return [
				'status' => 'error',
				'msg'    => 'disabledLogin'
			];
		}

		if (!$aData['isAgreeToPrivacyPolicy'] || !$aData['isAgreeToTermsAndConditionals']) {
			return [
				'status' => 'error',
				'msg'    => 'needAgreeToTerm'
			];
		}

		if (empty($aData['username']) || empty($aData['email']) || empty($aData['password'])) {
			return [
				'status' => 'error',
				'msg'    => 'needCompleteAllRequiredFields'
			];
		}

		if (!is_email($aData['email'])) {
			return [
				'status' => 'error',
				'msg'    => 'invalidEmail'
			];
		}

		if (email_exists($aData['email'])) {
			return [
				'status' => 'error',
				'msg'    => 'emailExists'
			];
		}

		if (username_exists($aData['username'])) {
			return [
				'status' => 'error',
				'return' => 'usernameExists'
			];
		}

		$aStatus = UserModel::createNewAccount($aData);
		if ($aStatus['status'] == 'error') {
			return [
				'status' => 'error',
				'return' => 'couldNotCreateAccount'
			];
		}

		do_action(
			'wilcity/after/created-account',
			$aStatus['userID'],
			$aData['username'],
			$aStatus['isNeedConfirm'],
			[
				'loginWith' => 'default',
				'isApp'     => true
			]
		);

		if ($aStatus['status'] == 'success' && !$aStatus['isNeedConfirm']) {
			$successMsg = 'createdAccountSuccessfully';
		} else {
			$successMsg = $aStatus['msg'];
		}

		$oUser = new WP_User($aStatus['userID']);
		$aResponse = $this->buildPermanentLoginToken($oUser);
		if ($aResponse['status'] == 'error') {
			return $aResponse;
		}

		$aResponse = App::get('UserInfo')->buildUserInfo($oUser, $aResponse['token']);
		$aResponse['msg'] = $successMsg;
		return $aResponse;
	}

	public function isTokenLiving()
	{
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}

		return [
			'status' => 'success'
		];
	}

	public function updatePassword()
	{
		WPML::switchLanguageApp();
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}

		$oToken->getUserID();

		$aData = $this->parsePost();

		if (isset($aData['new_password']) && !empty($aData['new_password'])) {
			wp_set_password($aData['new_password'], $oToken->userID);
			$oUser = new WP_User($this->userID);
			do_action('wilcity/user/after_reset_password', $oUser);

			return [
				'status' => 'success'
			];
		}

		return [
			'status' => 'error'
		];
	}

	public function afterPasswordReset($oUser)
	{
		$this->buildToken($oUser, '+1 seconds');
	}

	public function temporaryAuthentication(WP_REST_Request $oRequest): array
	{
		WPML::switchLanguageApp();
		$oValidate = $this->verifyPermanentToken();
		if ($oValidate !== false) {
			$oUser = new WP_User($this->userID);
			$aResponse = $this->buildTemporaryLoginToken($oUser);

			if ($aResponse['status'] == 'error') {
				return $aResponse;
			}

			return [
				'status'      => 'success',
				'checkoutURL' => add_query_arg(
					[
						'token'          => $aResponse['token'],
						'orderID'        => $oRequest->get_param('orderID'),
						'payment_method' => $oRequest->get_param('payment_method')
					],
					home_url()
				)
			];
		}

		return [
			'status' => 'error',
			'msg'    => 'Invalid Token'
		];
	}

	public function handleDisableOTP(WP_REST_Request $oRequest)
	{
		$oValidate = $this->verifyPermanentToken();
		if (!$oValidate) {
			return [
				'status' => 'error',
				'msg'    => 403
			];
		}
		$this->getUserID();
		$aResponse = $this->verifyPassword($oRequest);

		if ($aResponse === 'error') {
			return $aResponse;
		}

		try {
			\WilokeGoogleAuthenticator\Helpers\User::disableGoogleAuth($this->userID);

			return [
				'status' => 'success'
			];
		}
		catch (\Exception $e) {
			return [
				'status' => 'error',
				'msg'    => $e->getMessage()
			];
		}
	}

	public function handleUnlockEnableOTP(WP_REST_Request $oRequest)
	{
		$aStatus = $this->verifyPassword($oRequest);
		if ($aStatus['status'] === 'error') {
			return $aStatus;
		}

		try {
			if (!\WilokeGoogleAuthenticator\Helpers\User::isLockedQrCode($this->userID)) {
				Session::setSession('verified-password', 'yes');

				return [
					'status'    => 'success',
					'qrCodeUrl' => \WilokeGoogleAuthenticator\Helpers\User::getField('qrCodeUrl', $this->userID),
					'next'      => 'otp/enable'
				];
			} else {
				\WilokeGoogleAuthenticator\Helpers\User::enableGoogleAuth($this->userID);

				return [
					'status' => 'success',
					'msg'    => 'turnOnTwoFactor'
				];
			}
		}
		catch (\Exception $e) {
			return [
				'status' => 'error',
				'msg'    => $e->getMessage()
			];
		}
	}

	public function handleEnableOTP(WP_REST_Request $oRequest)
	{
		$oValidate = $this->verifyPermanentToken();
		if (!$oValidate) {
			return [
				'status' => 'error',
				'msg'    => 403
			];
		}
		$this->getUserID();

		if (!Session::getSession('verified-password')) {
			return [
				'status' => 'error',
				'msg'    => 403
			];
		}

		try {
			if (\WilokeGoogleAuthenticator\Helpers\GoogleAuthenticator::verifyTwoFactorCode($oRequest->get_param('otp_code'),
				$this->userID)) {
				Session::destroySession('verified-password');
				\WilokeGoogleAuthenticator\Helpers\User::enableGoogleAuth($this->userID);
				\WilokeGoogleAuthenticator\Helpers\User::setLockedQrCode($this->userID);

				return [
					'status' => 'success',
					'msg'    => 'turnOnTwoFactor'
				];
			}
		}
		catch (\Exception $e) {
			return [
				'status' => 'error',
				'msg'    => $e->getMessage(),
				'next'   => 'otp/enable'
			];
		}

		return [
			'status' => 'error',
			'msg'    => 'verificationFailed',
			'next'   => 'otp/enable'
		];
	}

	public function getQrCode(WP_REST_Request $oRequest)
	{
		$oValidate = $this->verifyPermanentToken();
		if (!$oValidate) {
			return [
				'status' => 'error',
				'msg'    => 403
			];
		}
		$this->getUserID();

		try {
			if (\WilokeGoogleAuthenticator\Helpers\User::isLockedQrCode($this->userID)) {
				return [
					'status' => 'error',
					'msg'    => 403
				];
			}
		}
		catch (\Exception $exception) {
			return [
				'status' => 'error',
				'msg'    => $exception->getMessage()
			];
		}

		try {
			return [
				'status'    => 'success',
				'qrCodeUrl' => \WilokeGoogleAuthenticator\Helpers\User::getField('qrCodeUrl', $this->userID)
			];
		}
		catch (\Exception $exception) {
			return [
				'status' => 'error',
				'msg'    => $exception->getMessage()
			];
		}
	}

	public function verifyOTP(WP_REST_Request $oRequest)
	{
		if (empty($oRequest->get_param('otp_code'))) {
			return [
				'status' => 'error',
				'msg'    => 'verificationFailed'
			];
		}

		$aResponse = apply_filters('wilcity/filter/wiloke-mobile-app/verify-otp', [
			'status' => 'error',
			'msg'    => 'verificationFailed'
		], $oRequest->get_params());

		if ($aResponse['status'] === 'error') {
			return $aResponse;
		}

		$aResponse = $this->buildPermanentLoginToken($aResponse['oUser']);

		if ($aResponse['status'] == 'error') {
			return $aResponse;
		}


		return App::get('UserInfo')->buildUserInfo($aResponse['oUser'], $aResponse['token']);
	}

	public function handleSignIn(WP_REST_Request $oRequest)
	{
		WPML::switchLanguageApp();
		return $this->authentication($oRequest);
	}

	public function verifyPassword(WP_REST_Request $oRequest)
	{
		WPML::switchLanguageApp();
		$oValidate = $this->verifyPermanentToken();
		if (!$oValidate) {
			return [
				'status' => 'error',
				'msg'    => 403
			];
		}
		$this->getUserID();

		if (empty($oRequest->get_param('password'))) {
			return [
				'status' => 'error',
				'msg'    => 'invalidPassword'
			];
		}

		$oUser = new WP_User($this->userID);

		if ($oUser && wp_check_password($oRequest->get_param('password'), User::getUserPass($oUser), $oUser->ID)) {
			return [
				'status' => 'success'
			];
		} else {
			return [
				'status' => 'error',
				'msg'    => 'invalidPassword'
			];
		}
	}

	private function isValidAccount($aData)
	{
		$aError = [
			'status' => 'error',
			'msg'    => 'invalidUserNameOrPassword'
		];

		if (empty($aData)) {
			return $aError;
		}

		if (!isset($aData['password']) || empty($aData['username']) ||
			empty($aData['password'])
		) {
			return [
				'status' => 'error',
				'msg'    => 'invalidUserNameOrPassword'
			];
		}
		$oUser = wp_authenticate($aData['username'], $aData['password']);

		if (is_wp_error($oUser)) {
			return [
				'status' => 'error',
				'msg'    => 'invalidUserNameOrPassword'
			];
		}

		if (strpos($aData['username'], '@') !== false) {
			$oUser = get_user_by('email', $aData['username']);
		} else {
			$oUser = get_user_by('login', $aData['username']);
		}

		if (empty($oUser) || is_wp_error($oUser)) {
			return [
				'status' => 'error',
				'msg'    => 'invalidUserNameOrPassword'
			];
		}

		return [
			'status' => 'success',
			'oUser'  => $oUser
		];
	}

	public function authentication(WP_REST_Request $oRequest)
	{
		$oValidate = $this->verifyPermanentToken();
		if ($oValidate !== false) {
			return [
				'status' => 'error',
				'msg'    => 'loggedIn'
			];
		}

		$aData = $oRequest->get_params();
		$aResponse = $this->isValidAccount($aData);
		if ($aResponse['status'] === 'error') {
			return $aResponse;
		}

		$token = GetSettings::getUserMeta($aResponse['oUser']->ID, 'app_token');
		if (empty($token) || !$this->verifyTemporaryToken($token)) {
			$aResponse = $this->buildPermanentLoginToken($aResponse['oUser']);
			if ($aResponse['status'] == 'error') {
				return $aResponse;
			}
		}

		return apply_filters(
			'wilcity/filter/wilcity-mobile-app/app/Controllers/LoginRegister/authentication',
			App::get('UserInfo')->buildUserInfo($aResponse['oUser'], $aResponse['token'])
		);
	}
}
