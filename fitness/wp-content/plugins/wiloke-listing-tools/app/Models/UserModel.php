<?php

namespace WilokeListingTools\Models;

use WilokeListingTools\AlterTable\AlterTableFollower;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\Message;
use WilokeListingTools\Framework\Helpers\PlanHelper;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Helpers\Validation;
use WilokeListingTools\Frontend\User;

class UserModel
{
	public static $aPlans;

	protected $userID;
	protected $nextBillingDateGMT;
	protected $gateway;
	protected $paymentID;
	protected $billingType;
	protected $planID;
	protected $newPlanID;
	protected $oldPlanID;
	protected $postType;
	protected $planType;
	protected $remainingItems;
	protected $isTrial = false;

	private static function staticFindPlanByPostType($userID, $planID)
	{
		$aAllPlans = self::getAllPlans($userID, true);
		if (empty($aAllPlans)) {
			return false;
		}

		foreach ($aAllPlans as $planKey => $aPlans) {
			if (!isset($aPlans[$planID])) {
				continue;
			}

			return $planKey;
		}

		return false;
	}

	private function findPlanTypeByPlanID($planID)
	{
		$aAllPlans = self::getAllPlans($this->userID, true);
		if (empty($aAllPlans)) {
			return false;
		}

		foreach ($aAllPlans as $planKey => $aPlans) {
			if (!isset($aPlans[$planID])) {
				continue;
			}

			return $planKey;
		}

		return false;
	}

	public static function addSubmissionRole($userID, $isUseRoleDefault = false)
	{
		$oGetUser = new \WP_User($userID);
		$defaultRole = get_option('default_role');

		if ($isUseRoleDefault) {
			if ($defaultRole == 'administrator') {
				$oGetUser->remove_role('administrator');
				$oGetUser->add_role('subscriber');
			}

			return true;
		}

		$oGetUser->remove_role('subscriber');

		if ($defaultRole == 'seller') {
			$oGetUser->add_role('seller');
			$oGetUser->add_role('contributor');
			if (function_exists('dokan_get_option') && dokan_get_option('new_seller_enable_selling',
					'dokan_selling') != 'off'
			) {
				update_user_meta($userID, 'dokan_enable_selling', 'yes');
			}
		} else {
			$oGetUser->add_role('contributor');
		}
	}

	protected static function generateHashPassword()
	{
		$key = wp_generate_password(20, false);
		if (empty($wp_hasher)) {
			require_once ABSPATH . WPINC . '/class-phpass.php';
			$wp_hasher = new \PasswordHash(8, true);
		}
		$hashed = time() . ':' . $wp_hasher->HashPassword($key);
		if (strpos($hashed, '.') === false) {
			return $hashed;
		}

		return self::generateHashPassword();
	}

	protected static function insertActivationKey($oUser)
	{
		global $wpdb;
		$hashed = self::generateHashPassword();
		$wpdb->update($wpdb->users, ['user_activation_key' => $hashed], ['user_login' => $oUser->user_login]);
	}

    /**
     * @param array{username: '', password: '', email: ''} $aData
     * @param false $isSocialLogin
     * @return array
     */
	public static function createNewAccount($aData, $isSocialLogin = false): array
    {
		$aThemeOptions = \Wiloke::getThemeOptions();
		if (!isset($aData['username']) && isset($aData['user_login'])) {
			$aData['username'] = trim($aData['user_login']);
		}

		if (!isset($aData['password']) && isset($aData['user_password'])) {
			$aData['password'] = trim($aData['user_password']);
		}
		$userID = wp_create_user($aData['username'], $aData['password'], $aData['email']);

		if (empty($userID) || is_wp_error($userID)) {
			return [
				'status' => 'error',
				'msg'    => $userID === 0 ?
					esc_html__('Sorry, We could not create your account', 'wiloke-listing-tools') :
					$userID->get_error_message()
			];
		}

		update_user_meta($userID, 'agree_with_privacy', $aData['isAgreeToPrivacyPolicy']);
		update_user_meta($userID, 'agree_with_terms', $aData['isAgreeToTermsAndConditionals']);
		update_user_meta($userID, 'user_ip', General::clientIP());

		wp_new_user_notification($userID, null, 'admin');
		if (GetWilokeSubmission::getField('toggle_become_an_author') == 'disable') {
			self::addSubmissionRole($userID, true);
		} else if (GetWilokeSubmission::getField('toggle_become_an_author') == 'enable') {
			$oSubscriber = (object)[
				'ID'         => $userID,
				'role'       => 'subscriber',
				'first_name' => isset($aData['first_name']) ? $aData['first_name'] : '',
				'last_name'  => isset($aData['last_name']) ? $aData['last_name'] : ''
			];
			wp_update_user($oSubscriber);
		}
		$needConfirm = !$isSocialLogin && (isset($aThemeOptions['toggle_confirmation']) &&
				$aThemeOptions['toggle_confirmation'] == 'enable');
		if ($needConfirm) {
			SetSettings::setUserMeta($userID, 'confirmed', false);
			$successMsg = $aThemeOptions['confirmation_notification'];
			$oUser = get_user_by('id', $userID);
			self::insertActivationKey($oUser);
		} else {
			SetSettings::setUserMeta($userID, 'confirmed', true);
			$successMsg = esc_html__(
				'Congratulations! Your account has been created successfully.',
				'wiloke-listing-tools'
			);
		}

		do_action(
			'wilcity/after/created-account',
			$userID,
			$aData['username'],
			$needConfirm,
			[
				'isApp'     => false,
				'loginWith' => 'wp'
			],
			$aData
		);

		return [
			'status'        => 'success',
			'userID'        => $userID,
			'isNeedConfirm' => $needConfirm,
			'msg'           => $successMsg
		];
	}

	protected function resetObject()
	{
		foreach ($this as $key => $val) {
			unset($this->$key);
		}
	}

	public static function getFollowings($authorID, $limit = 100, $offset = 0)
	{
		global $wpdb;
		$followTbl = $wpdb->prefix . AlterTableFollower::$tblName;


		$query = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT followerID FROM $followTbl WHERE authorID=%d ORDER BY date LIMIT %d,%d",
				$authorID, $offset, $limit
			),
			ARRAY_A
		);

		if (empty($query) || is_wp_error($query)) {
			return 0;
		}

		return $query;
	}

	public static function deleteFollowingUser($userID)
	{
		global $wpdb;

		$followTbl = $wpdb->prefix . AlterTableFollower::$tblName;

		$wpdb->delete(
			$followTbl,
			[
				'followerID' => absint($userID),
				'authorID'   => absint(User::getCurrentUserID())
			],
			[
				'%d',
				'%d'
			]
		);
	}

	public static function getFollowingsWithExcludes($authorID, $aExcludes, $limit = 100)
	{
		if (empty($aExcludes)) {
			return self::getFollowings($authorID, $limit);
		}
		global $wpdb;

		$followTbl = $wpdb->prefix . AlterTableFollower::$tblName;

		$excludes = implode(',', $aExcludes);
		$excludes = $wpdb->_real_escape($excludes);

		$query = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT followerID FROM $followTbl WHERE authorID=%d AND followerID NOT IN ($excludes) ORDER BY date LIMIT %d",
				$authorID, $limit
			),
			ARRAY_A
		);

		if (empty($query) || is_wp_error($query)) {
			return 0;
		}

		return $query;
	}

	public static function searchUsersByUsername($s, $returnType = 'object', $isExcludeMe = true)
	{
		global $wpdb;
		$s = $wpdb->_real_escape('%' . $s . '%');


		if ($isExcludeMe && User::isUserLoggedIn()) {
			$aAuthors = $wpdb->get_results(
				$wpdb->prepare("SELECT ID, display_name  FROM $wpdb->users WHERE user_login LIKE %s OR display_name LIKE %s AND ID NOT IN(%d) ORDER BY ID LIMIT 10",
					$s, $s, User::getCurrentUserID()),
				$returnType == 'object' ? OBJECT : ARRAY_A
			);
		} else {
			$aAuthors = $wpdb->get_results(
				$wpdb->prepare("SELECT ID, display_name  FROM $wpdb->users WHERE user_login LIKE %s OR display_name LIKE %s ORDER BY ID LIMIT 10",
					$s, $s),
				$returnType == 'object' ? OBJECT : ARRAY_A
			);
		}

		if (empty($aAuthors)) {
			return false;
		}

		return $aAuthors;
	}

	public function __get($name)
	{
		if (property_exists($this, $name)) {
			return $this->{$name};
		}

		return false;
	}

	public function __set($name, $value)
	{
		$this->{$name} = $value;
	}

	public static function isPlanExist($userID, $planID)
	{
		$aUserPlans = self::getAllPlans($userID);
		if (empty($aUserPlans)) {
			return false;
		}

		foreach ($aUserPlans as $planKey => $aPlans) {
			if (isset($aPlans[$planID])) {
				return true;
			}
		}

		return false;
	}

	public static function getStripeID($userID = null)
	{
		if (!is_user_logged_in()) {
			return false;
		}

		if (!empty($userID) && !current_user_can('administrator')) {
			return false;
		}

		$userID = empty($userID) ? get_current_user_id() : $userID;

		return GetSettings::getUserMeta($userID, wilokeListingToolsRepository()->get('user:stripeCustomerID'));
	}

	public static function setStripeID($val, $userID = null)
	{
		$userID = empty($userID) ? get_current_user_id() : $userID;

		return SetSettings::setUserMeta($userID, wilokeListingToolsRepository()->get('user:stripeCustomerID'), $val);
	}

	public static function deleteStripeID($userID = null)
	{
		$userID = empty($userID) ? get_current_user_id() : $userID;

		return SetSettings::deleteUserMeta($userID, wilokeListingToolsRepository()->get('user:stripeCustomerID'));
	}

	public static function getAllPlans($userID = null, $isFocus = false)
	{
		if (current_user_can('administrator') && empty($userID)) {
			$userID = User::getCurrentUserID();
		} else {
			$userID = empty($userID) ? User::getCurrentUserID() : $userID;
		}

		if (empty($userID)) {
			return false;
		}
		self::$aPlans = GetSettings::getUserPlans($userID, $isFocus);

		return self::$aPlans;
	}

	public static function getSpecifyUserPlanType($planType, $userID = null, $isFocus = false)
	{
		$aPlans = self::getAllPlans($userID, $isFocus);

		return isset($aPlans[$planType]) ? $aPlans[$planType] : false;
	}

	public static function getUserPlanByPostType($postType, $userID = null, $isFocus = false)
	{
		if (empty($postType)) {
			return false;
		}

		$aPlans = self::getAllPlans($userID, $isFocus);
		$planType = $postType . '_plan';

		return isset($aPlans[$planType]) ? $aPlans[$planType] : false;
	}

	public static function getSpecifyUserPlanID($planID, $userID = null, $isFocus = false)
	{
		$userID = empty($userID) ? User::getCurrentUserID() : $userID;
		$planType = self::staticFindPlanByPostType($userID, $planID);

		if (!$planType) {
			return false;
		}

		$aPlans = self::getSpecifyUserPlanType($planType, $userID, $isFocus);

		return isset($aPlans[$planID]) ? $aPlans[$planID] : false;
	}

	public function setUserID($userID)
	{
		$this->userID = $userID;

		return $this;
	}

	public function setNewPlanID($planID)
	{
		$this->newPlanID = $planID;

		return $this;
	}

	public function setOldPlanID($planID)
	{
		$this->oldPlanID = $planID;

		return $this;
	}

	public function setNextBillingDateGMT($nextBillingDateGMT)
	{
		if (!is_numeric($nextBillingDateGMT)) {
			//            date_default_timezone_set('UTC');
			$this->nextBillingDateGMT = strtotime($nextBillingDateGMT);
		} else {
			$this->nextBillingDateGMT = $nextBillingDateGMT;
		}

		return $this;
	}

	public function setGateway($gateway)
	{
		$this->gateway = $gateway;

		return $this;
	}

	public function setIsTrial($isTrial)
	{
		$this->isTrial = $isTrial;

		return $this;
	}

	public function setPaymentID($paymentID)
	{
		$this->paymentID = $paymentID;

		return $this;
	}

	public function setBillingType($billingType)
	{
		$this->billingType = $billingType;

		return $this;
	}

	public function setPlanID($planID)
	{
		$this->planID = $planID;

		return $this;
	}

	public function setPostType($postType)
	{
		$this->postType = $postType;

		return $this;
	}

	public function deleteUserPlan($planID)
	{
		$aPlans = self::getAllPlans($this->userID, true);
		if (empty($aPlans)) {
			return true;
		}

		$this->planType = $this->getPlanType($planID);
		//        $this->planType = $this->findPlanTypeByPlanID($planID);

		if (!isset($aPlans[$this->planType])) {
			return true;
		}

		if (!isset($aPlans[$this->planType][$planID])) {
			return true;
		}

		unset($aPlans[$this->planType][$planID]);

		if (count($aPlans[$this->planType]) == 0) {
			unset($aPlans[$this->planType]);
		}

		if (empty($aPlans)) {
			SetSettings::deleteUserPlan($this->userID);
		} else {
			SetSettings::setUserPlans($this->userID, $this->planType, $aPlans);
		}

	}

	public function calculateRemainingItems()
	{
		$instRemainingItems = new RemainingItems();
		$instRemainingItems->setUserID($this->userID)
			->setGateway($this->gateway)
			->setPlanID($this->planID)
			->setBillingType($this->billingType)
			->setPaymentID($this->paymentID);

		$this->remainingItems = $instRemainingItems->getRemainingItems();
	}

	public static function getRemainingItemsOfPlans($planID)
	{
		$userID = User::getCurrentUserID();

		$aUserPlan = self::getSpecifyUserPlanID($planID, $userID);

		if (empty($aUserPlan)) {
			return 0;
		}

		$oRemainingItems = new RemainingItems();
		$oRemainingItems->setPaymentID($aUserPlan['paymentID']);
		$oRemainingItems->setUserID($userID);
		$oRemainingItems->setPlanID($planID);
		$oRemainingItems->setBillingType($aUserPlan['billingType']);
		$oRemainingItems->setGateway($aUserPlan['gateway']);

		return $oRemainingItems->getRemainingItems();
	}

	public function updateRemainingItemsUserPlan($planID, $userID = null, $logIfError = false)
	{
		$userID = empty($userID) ? get_current_user_id() : $userID;
		$aUserPlan = self::getSpecifyUserPlanID($planID, $userID);

		if (empty($aUserPlan)) {
			FileSystem::logError('We could not update remaining items because User Plan does not exist. Plan ID:' .
				$planID . ' User ID:' . $userID);

			return false;
		}

		$this->userID = $userID;
		foreach ($aUserPlan as $property => $val) {
			$this->{$property} = $val;
		}

		$this->calculateRemainingItems();

		$aUserPlan['remainingItems'] = $this->remainingItems <= 0 ? 0 : absint($this->remainingItems);
		$aUserPlans = self::getAllPlans($userID);
		//        $this->planType              = $this->findPlanTypeByPlanID($planID);

		$this->planType = $this->getPlanType($planID);

		if (empty($this->remainingItems)) {
			if (GetWilokeSubmission::isNonRecurringPayment($this->billingType)) {
				unset($aUserPlans[$this->planType][$planID]);

				if (empty($aUserPlans[$this->planType])) {
					unset($aUserPlans[$this->planType]);
				}
			} else {
				$aUserPlans[$this->planType][$planID] = $aUserPlan;
			}
		} else {
			$aUserPlans[$this->planType][$planID] = $aUserPlan;
		}

		SetSettings::setUserPlans($userID, $aUserPlans);
	}

	public function updateNextBillingDateGMT($nextBillingDateGMT, $planID, $userID = null, $paymentID = null)
	{
		if (empty($nextBillingDateGMT)) {
			try {
				Message::error(esc_html__('Wrong the next billing date value.', 'wiloke-listing-tools'));
			}
			catch (\Exception $e) {
				return false;
			}
		}

		if (!is_numeric($nextBillingDateGMT)) {
			//            date_default_timezone_set('UTC');
			$nextBillingDateGMT = strtotime($nextBillingDateGMT);
		}

		$currentTimeStamp = current_time('timestamp', 1);

		$nextBillingDateGMTCache = absint($nextBillingDateGMT);

		if ($nextBillingDateGMTCache < $currentTimeStamp) {
			try {
				Message::error(esc_html__('The next billing date must be bigger than the current date. Next Billing Date:' .
					$nextBillingDateGMTCache,
					'wiloke-listing-tools'));
			}
			catch (\Exception $e) {
			}
		}
		//        $this->planType = $this->findPlanTypeByPlanID($planID);

		$this->planType = $this->getPlanType($planID);

		if (!isset($aUserPlans[$this->planType]) || !isset($aUserPlans[$this->planType][$planID])) {
			FileSystem::logPayment('wilcity-error.log', 'We could not update payment. Plan Type ' . $this->planType
				. ' Plan ID: ' . $planID . ' UserID: ' . $userID);
			return false;
		}

		$this->userID = empty($userID) ? get_current_user_id() : $userID;
		$aUserPlans = self::getAllPlans($this->userID);
		$aUserPlans = empty($aUserPlans) ? [] : $aUserPlans;

		$this->nextBillingDateGMT = $nextBillingDateGMT;
		$aUserPlans[$this->planType][$planID]['nextBillingDateGMT'] = $nextBillingDateGMT;

		$this->gateway = $aUserPlans[$this->planType][$planID]['gateway'];
		$this->planID = $planID;
		$this->billingType = $aUserPlans[$this->planType][$planID]['billingType'];
		$this->paymentID = empty($paymentID) ? $aUserPlans[$this->planType][$planID]['paymentID'] : $paymentID;


		PaymentMetaModel::setNextBillingDateGMT($this->nextBillingDateGMT, $this->paymentID);

		$this->calculateRemainingItems();
		$aUserPlans[$this->planType][$planID]['remainingItems']
			= $this->remainingItems <= 0 ? 0 : $this->remainingItems;
		SetSettings::setUserPlans($userID, $aUserPlans);
	}

	public function updateUserPlan()
	{
		$aPlans = self::getAllPlans($this->userID, true);
		$aPlans = empty($aPlans) ? [] : $aPlans;
		if (empty($aPlans)) {
			return false;
		}

		if (empty($this->planID)) {
			return false;
		}

		$this->calculateRemainingItems();

		$aNewPlan = $aPlans[$this->planType][$this->oldPlanID];
		if ($aNewPlan['gateway'] == 'free') {
			$aNewPlan['billingType'] = $this->billingType;
		}
		$aNewPlan['remainingItems'] = $this->remainingItems;
		$aNewPlan['nextBillingDateGMT'] = $this->nextBillingDateGMT;

		$aNewPlan['paymentID'] = $this->paymentID;
		$aNewPlan['gateway'] = $this->gateway;
		$aNewPlan['planID'] = $this->planID;

		$aPlans[$this->planType][$this->planID] = $aNewPlan;

		unset($aPlans[$this->planType][$this->oldPlanID]);

		SetSettings::setUserPlans($this->userID, $aPlans);

		return true;
	}

	private function getPlanType($planId)
	{
		$category = PlanHelper::getPlanCategory($planId);

		return $category === 'addlisting' ? 'listing_plan' : $category;
	}

	public function removeUserPlan()
	{
		$aPlans = self::getAllPlans($this->userID, true);
		$aPlans = empty($aPlans) ? [] : $aPlans;
		if (empty($aPlans)) {
			return false;
		}

		if (empty($this->planID)) {
			return false;
		}

		foreach ($aPlans as $planType => $aPlan) {
			if (isset($aPlan[$this->planID])) {
				unset($aPlans[$planType][$this->planID]);
			}
		}

		SetSettings::setUserPlans($this->userID, $aPlans);

		return true;
	}

	public function setUserPlan()
	{
		if (empty($this->planID)) {
			FileSystem::logError('Missing plan id.', __CLASS__, __METHOD__);

			return false;
		}

		$aPlans = self::getAllPlans($this->userID, true);
		$aPlans = empty($aPlans) ? [] : $aPlans;

		$this->planType = $this->getPlanType($this->planID);
		$aNewPlan = [
			'nextBillingDateGMT' => $this->nextBillingDateGMT,
			'gateway'            => $this->gateway,
			'paymentID'          => $this->paymentID,
			'billingType'        => $this->billingType,
			'planID'             => $this->planID,
			'isTrial'            => $this->isTrial,
			'postType'           => $this->postType
		];

		if (empty($aPlans)) {
			$aPlans = [
				$this->planType => [
					$this->planID => $aNewPlan
				]
			];
		} else {
			if (isset($aPlans[$this->planType]) && isset($aPlans[$this->planType][$this->planID])) {
				unset($aPlans[$this->planType][$this->planID]);
			}
			$aPlans[$this->planType][$this->planID] = $aNewPlan;
		}

		$this->calculateRemainingItems();
		$aPlans[$this->planType][$this->planID]['remainingItems'] = $this->remainingItems;

		SetSettings::setUserPlans($this->userID, $aPlans);
		do_action('wiloke-submission/updated-new-user-plan', $this);

		return true;
	}

	public static function setUsedTrialPlans($planID, $userID = null)
	{
		$userID = empty($userID) ? get_current_user_id() : $userID;

		$usedPlanIDs = GetSettings::getUserMeta($userID, wilokeListingToolsRepository()->get('user:usedTrialPlans'));
		if (empty($usedPlanIDs)) {
			$aPlansIDs = [];
			$aPlansIDs[] = $planID;
		} else {
			$aPlansIDs = is_array($usedPlanIDs) ? $usedPlanIDs : explode(',', $usedPlanIDs);
			array_push($aPlansIDs, $planID);
		}

		SetSettings::setUserMeta($userID, wilokeListingToolsRepository()->get('user:usedTrialPlans'), $aPlansIDs);
	}

	public static function isMyFavorite($postID, $isApp = false, $userID = '')
	{
		if (!User::isUserLoggedIn($isApp) && empty($userID)) {
			return false;
		}

		if (empty($userID)) {
			if (!$isApp) {
				$userID = get_current_user_id();
			} else {
				$userID = User::getUserID();
			}
		}

		$aFavorites = GetSettings::getUserMeta($userID, 'my_favorites');
		if (empty($aFavorites)) {
			return false;
		}

		return in_array($postID, $aFavorites);
	}

	public static function getLatestUserPlan($planType)
	{
		$aUserPlans = self::getSpecifyUserPlanType($planType);
		if (is_array($aUserPlans) && !empty($aUserPlans)) {
			$aUserPlan = end($aUserPlans);

			return $aUserPlan;
		}

		return false;
	}

	public static function getLatestPlanID($planType)
	{
		$aUserPlan = self::getLatestUserPlan($planType);
		if (!empty($aUserPlan)) {
			return $aUserPlan['planID'];
		}

		return false;
	}

	public static function isExceededRecurringPaymentPlan($planType)
	{
		$aUserPlan = self::getLatestUserPlan($planType);
		if (!empty($aUserPlan)) {
			if (GetWilokeSubmission::isNonRecurringPayment($aUserPlan['billingType'])) {
				return false;
			}
			if (empty($aUserPlan['remainingItems']) || GetWilokeSubmission::isPlanExists($aUserPlan['planID'])) {
				return true;
			}
		}

		return false;
	}
}
