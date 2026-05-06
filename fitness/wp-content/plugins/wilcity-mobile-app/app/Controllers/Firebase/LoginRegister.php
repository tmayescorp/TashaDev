<?php

namespace WILCITY_APP\Controllers\Firebase;

use Exception;
use Kreait\Firebase\Auth\SignInResult;
use Kreait\Firebase\Exception\AuthException;
use Kreait\Firebase\Exception\FirebaseException;
use \WILCITY_APP\Database\FirebaseDB;
use WILCITY_APP\Database\FirebaseMsgDB;
use WILCITY_APP\Database\FirebaseUser;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Frontend\User;
use WP_Error;
use WP_User;

class LoginRegister
{
	private static $password;

	/**
	 * @var SignInResult $oAuth
	 */
	private static $oAuth;
	private static $oUser;
	private static $firebaseIDKey = 'firebase_id';
	private static $firebaseID    = '';

	public function __construct()
	{
		add_action('wp_login', [$this, 'afterUserLogin'], 10, 2);
		add_action('clear_auth_cookie', [$this, 'afterUserLogout']);
		add_action('delete_user', [$this, 'cacheUserDataBeforeDeleting']);
		add_action('deleted_user', [$this, 'afterUserDeletedAccount']);
		add_action('wilcity/wilcity-mobile-app/app-signed-up', [$this, 'afterUserLoggedInViaApp'], 10, 2);
		add_action('after_password_reset', [$this, 'updateNewPasswordToFirebase'], 1, 1);
		add_action('profile_update', [$this, 'updateNewPasswordAfterUpdatingProfile'], 10, 1);
		add_filter('wilcity/wilcity-mobile-app/create-firebase-account', [$this, 'filterCreateFireabaseAccount'], 10,
			2);
		add_filter('send_email_change_email', [$this, 'updateEmail'], 10, 3);
		add_action('delete_user', [$this, 'deleteUser']);
		add_action('wilcity/create-firebase-account', [$this, 'createFirebaseAccount'], 10, 2);
		//		add_filter('secure_signon_cookie', array($this, 'getPasswordOnly'), 10, 2);
	}

	public function deleteUser($oUser)
	{
		$firebaseID = FirebaseUser::getFirebaseID($oUser->ID);
		if (empty($firebaseID)) {
			return false;
		}
		$oAuth = FirebaseDB::getAuth();
		try {
			$oAuth->deleteUser($firebaseID);
		}
		catch (Exception $oE) {
		}
	}

	public function updateEmail($isSendEmail, $aUser, $aUserData)
	{
		$firebaseID = FirebaseUser::getFirebaseID($aUser['ID']);
		if (empty($firebaseID)) {
			return $isSendEmail;
		}

		$oAuth = FirebaseDB::getAuth();
		try {
			$oAuth->changeUserEmail($firebaseID, $aUserData['user_email']);
		}
		catch (Exception $oE) {
			$oError = new WP_Error('existing_user_email', __('Sorry, that email address is already used!'));
			echo $oError->get_error_message();
			die();
		}

		return $isSendEmail;
	}

	public function updateNewPasswordToFirebase($oUser)
	{
		$firebaseID = FirebaseUser::getFirebaseID($oUser->ID);

		if (empty($firebaseID)) {
			return false;
		}
		$oAuth = FirebaseDB::getAuth();
		$oUserNewInfo = get_userdata($oUser->ID);
		try {

			$oAuth->changeUserPassword($firebaseID, User::getUserPass($oUserNewInfo));
		}
		catch (Exception $oE) {
			echo $oE->getMessage();
			die();
		}
	}

	public function updateNewPasswordAfterUpdatingProfile($userID)
	{
		$oUser = new WP_User($userID);
		$this->updateNewPasswordToFirebase($oUser);
	}

	public function cacheUserDataBeforeDeleting($userID)
	{
		self::$firebaseID = GetSettings::getUserMeta($userID, 'firebase_id');
	}

	public function afterUserDeletedAccount()
	{
		if (empty(self::$firebaseID)) {
			return false;
		}

		try {
			FirebaseDB::getAuth()->deleteUser(self::$firebaseID);
		}
		catch (Exception $oE) {
			// deleted
		}
	}

	public function getPasswordOnly($secureCookie, $aCredentials)
	{
		self::$password = $aCredentials['user_password'];

		return $secureCookie;
	}

	private function singInToFirebase($email, $password): bool
	{
		try {
			self::$oAuth = FirebaseDB::getAuth()->signInWithEmailAndPassword($email, $password);
			$this->updateUserOnlineStatus(self::$oUser->ID, true);
			SetSettings::setUserMeta(self::$oUser->ID, self::$firebaseIDKey, self::$oAuth->firebaseUserId());

			return true;
		}
		catch (Exception $e) {
			return false;
		}
	}

	public function createFirebaseAccount($email, $password)
	{
		self::$oUser = get_user_by('email', $email);
		$status = $this->singInToFirebase($email, $password);
		if (!$status) {
			$aStatus = $this->signUpToFirebase($email, $password);

			if ($aStatus['status'] == 'error') {
				return false;
			}

			return true;
		}

		return true;
	}

	private function updateUserOnlineStatus($userID, $isOnline)
	{
		FirebaseUser::updateConnectionStatus($userID, $isOnline);
	}

	private function signUpToFirebase($email, $password): array
	{
		$userProperties = [
			'email'         => $email,
			'emailVerified' => true,
			'password'      => $password
		];

		if (empty(self::$oUser)) {
			self::$oUser = get_user_by('email', $email);
		}

		try {
			self::$oAuth = FirebaseDB::getAuth()->createUser($userProperties);
			FirebaseDB::setFirebaseID(self::$oAuth->uid, self::$oUser->ID);
			$this->updateUserOnlineStatus(self::$oUser->ID, true);

			return [
				'status' => 'success'
			];
		}
		catch (AuthException|FirebaseException|Exception $oE) {
			$this->updateUserOnlineStatus(self::$oUser->ID, false);

			return [
				'status' => 'error',
				'msg'    => $oE->getMessage()
			];
		}
	}

	public function filterCreateFireabaseAccount($email, $password)
	{
		return $this->signUpToFirebase($email, $password);
	}

	public function afterUserLoggedInViaApp($userID, $token)
	{
		$oUser = new WP_User($userID);
		$this->afterUserLogin(null, $oUser);
	}

	public function afterUserLogin($idontcare, $oUser)
	{
		self::$oUser = $oUser;
		global $wpdb;
		if (!$this->singInToFirebase($oUser->data->user_email, User::getUserPass($oUser))) {

			$aStatus = $this->signUpToFirebase($oUser->data->user_email, User::getUserPass($oUser));

			if ($aStatus['status'] == 'error') {
				wp_destroy_current_session();
				wp_clear_auth_cookie();

				if (wp_doing_ajax()) {
					wp_send_json_error([
						'msg' => $aStatus['msg']
					]);
				}
			}
		}
	}

	public function afterUserLogout()
	{
		$userID = get_current_user_id();
		if (empty($userID)) {
			return false;
		}
		$this->updateUserOnlineStatus($userID, false);
	}
}
