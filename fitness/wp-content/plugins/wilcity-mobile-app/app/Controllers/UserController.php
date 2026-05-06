<?php

namespace WILCITY_APP\Controllers;

use WilokeListingTools\Controllers\ProfileController;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Framework\Helpers\WPML;
use WilokeThemeOptions;
use WP_REST_Request;
use WP_User;

class UserController
{
	use VerifyToken;
	use JsonSkeleton;
	use ParsePost;

	private $oToken;

	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'me/short-profile', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getShortProfile']
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'me/search-users', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'searchUsers']
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'list-users', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getShortUsersInfo']
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'me/list-users', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getShortUsersInfo']
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'users/(?P<id>\d+)', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getUserShortInfo']
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'me/users/(?P<id>\d+)', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getUserShortInfo']
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'me/profile-fields',
				[
					'methods'             => 'GET',
					'permission_callback' => '__return_true',
					'callback'            => [$this, 'getProfileFields']
				]
			);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'me/profile',
				[
					[
						'methods'             => 'GET',
						'permission_callback' => '__return_true',
						'callback'            => [$this, 'getProfiles']
					],
					[
						'methods'             => 'POST',
						'permission_callback' => '__return_true',
						'callback'            => [$this, 'putMyProfile']
					]
				]
			);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'get-profile', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getProfiles']
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'get-short-profile', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getShortProfile']
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'search-users', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'searchUsers']
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'list-users', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getShortUsersInfo']
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'users/(?P<id>\d+)', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getUserShortInfo']
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'get-my-profile-fields', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getProfileFields']
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'put-my-profile', [
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'putMyProfile']
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'get-profile', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getProfiles']
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'get-short-profile', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getShortProfile']
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'search-users', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'searchUsers']
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'list-users', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getShortUsersInfo']
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'users/(?P<id>\d+)', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getUserShortInfo']
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'get-my-profile-fields', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getProfileFields'],
				'permission_callback' => '__return_true',
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'put-my-profile', [
				'methods'             => 'POST',
				'callback'            => [$this, 'putMyProfile'],
				'permission_callback' => '__return_true',
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'users', [
				'methods'             => 'DELETE',
				'callback'            => [$this, 'deleteMe'],
				'permission_callback' => '__return_true'
			]);
		});

		add_filter('determine_current_user', [$this, 'filterUserLoggedInStatusInRestAPI']);
	}

	public function deleteMe(WP_REST_Request $request): array
	{
		if (!WilokeThemeOptions::isEnable('toggle_allow_customer_delete_account')) {
			return [
				'status' => 'error',
				'msg'    => 403
			];
		}

		$this->oToken = $this->verifyPermanentToken();
		if (!$this->oToken) {
			return $this->tokenExpiration();
		}

		$oUser = new WP_User($this->oToken->getUserID()->userID);

		if (empty($oUser) || is_wp_error($oUser)) {
			return [
				'status' => 'error',
				'msg'    => 'The user does not exists!'
			];
		}

		if (empty($password = $request->get_param('current_password'))) {
			return [
				'status' => 'error',
				'msg'    => 'The password is required'
			];
		}

		if (!wp_check_password($password, $oUser->data->user_pass, $oUser->ID)) {
			return [
				'status' => 'error',
				'msg'    => 'Invalid password confirmation'
			];
		}

		$aPosts = get_posts([
			'posts_per_page' => -1,
			'post_type'      => 'any',
			'author__in'     => [$this->userID]
		]);

		if (!empty($aPosts)) {
			foreach ($aPosts as $oPost) {
				wp_delete_post($oPost->ID, true);
			};
		}

		if (!function_exists('wp_delete_user')) {
			require_once(ABSPATH.'wp-admin/includes/user.php');
		}
		wp_delete_user($oUser->ID);

		return [
			'msg' => 'accountDeleted',
			'status' => 'success',
		];
	}


	public function filterUserLoggedInStatusInRestAPI($status)
	{
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $status;
		}

		$this->getUserID();

		return $oToken->userID;
	}

	private function getQuickUserInformation($oUser)
	{
		return [
			'userID'      => $oUser->ID,
			'displayName' => $oUser->display_name,
			'avatar'      => User::getAvatar($oUser->ID),
			'firebaseID'  => User::getFirebaseUserID()
		];
	}

	public function getShortUsersInfo()
	{
		WPML::switchLanguageApp();
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}

		$aMessage = [
			'status' => 'error',
			'msg'    => 'The user does not exists'
		];

		if (!isset($_GET['s']) || empty($_GET['s'])) {
			return $aMessage;
		}

		$aParsedUsers = explode(',', $_GET['s']);
		$aParsedUsers = array_map(function ($userName) {
			return trim($userName);
		}, $aParsedUsers);

		if (is_numeric($aParsedUsers[0])) {
			$by = 'ID';
		} else {
			$by = 'login';
		}

		$aUserInfo = [];
		foreach ($aParsedUsers as $username) {
			$oUser = get_user_by($by, $username);
			if (empty($oUser) || is_wp_error($oUser)) {
				continue;
			}

			$aUserInfo[] = [
				'userID'      => $oUser->ID,
				'displayName' => $oUser->display_name,
				'avatar'      => User::getAvatar($oUser->ID)
			];
		}

		return [
			'status'  => 'success',
			'aResult' => $aUserInfo
		];
	}

	public function getUserShortInfo($aData)
	{
		WPML::switchLanguageApp();
		$aMessage = [
			'status' => 'error',
			'msg'    => 'The user does not exists'
		];
		if (!isset($aData['id']) || empty($aData['id'])) {
			return $aMessage;
		}

		if (is_numeric($aData['id'])) {
			$by = 'ID';
		} else {
			$by = 'login';
		}
		$oUser = get_user_by($by, $aData['id']);
		if (empty($oUser) || is_wp_error($oUser)) {
			return $aMessage;
		}

		return [
			'status' => 'success',
			'oInfo'  => $this->getQuickUserInformation($oUser)
		];
	}

	public function searchUsers()
	{
		WPML::switchLanguageApp();
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}
		$oToken->getUserID();

		$aMessage = [
			'status' => 'error',
			'msg'    => 'We found no user info'
		];

		if (!isset($_GET['s']) || empty($_GET['s'])) {
			return $aMessage;
		}

		$q = '*' . esc_attr(trim($_GET['s'])) . '*';

		$args = [
			'search'         => $q,
			'search_columns' => ['user_login', 'display_name', 'first_name', 'last_name'],
			'exclude'        => [$oToken->userID]
		];
		$oUserQuery = new \WP_User_Query(WPML::addFilterLanguagePostArgs($args));
		$aUsers = $oUserQuery->get_results();
		if (empty($aUsers)) {
			return $aMessage;
		}

		$aInfo = [];
		foreach ($aUsers as $oUser) {
			if (username_exists($oUser->login)) {
				continue;
			}

			$aInfo[] = $this->getQuickUserInformation($oUser);
		}

		return [
			'status'   => 'success',
			'aResults' => $aInfo
		];
	}

	public function putMyProfile()
	{
		WPML::switchLanguageApp();
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}
		$oToken->getUserID();

		$aFields = $this->parsePost();
		$msg = 'profileHasBeenUpdated';
		if (isset($aFields['oPassword']) && !empty($aFields['oPassword'])) {
			$aRawPassword = json_decode(stripslashes($aFields['oPassword']), true);

			if (!empty($aRawPassword['confirm_new_password']) && !empty($aRawPassword['current_password']) &&
				!empty($aRawPassword['new_password'])) {
				$aPasswordUpdate = [
					'newPassword'        => $aRawPassword['new_password'],
					'confirmNewPassword' => $aRawPassword['confirm_new_password'],
					'currentPassword'    => $aRawPassword['current_password']
				];
				$aStatus = ProfileController::updatePassword($aPasswordUpdate, $oToken->userID);
				if ($aStatus['status'] == 'error') {
					return [
						'status' => 'error',
						'msg'    => 'errorUpdatePassword'
					];
				} else {
					$msg = 'passwordHasBeenUpdated';
				}
			}
		}

		if (isset($aFields['oBasicInfo']) && !empty($aFields['oBasicInfo'])) {
			$aBasicInfo = json_decode(stripslashes($aFields['oBasicInfo']), true);
			foreach ($aBasicInfo as $key => $aValue) {
				if ($key == 'avatar') {
					if (is_array($aBasicInfo['avatar'])) {
						$aBasicInfo['avatar']['value'][0]['src'] = $aBasicInfo['avatar']['base64'];
						$aBasicInfo['avatar']['value'][0]['fileName'] = $aBasicInfo['avatar']['name'];
						$aBasicInfo['avatar']['value'][0]['fileType'] = 'image/jpg';

						unset($aBasicInfo['avatar']['base64']);
						unset($aBasicInfo['avatar']['name']);
						unset($aBasicInfo['avatar']['type']);
						unset($aBasicInfo['avatar']['uri']);
					} else {
						unset($aBasicInfo['avatar']);
					}
				} else if ($key == 'cover_image') {
					if (is_array($aBasicInfo['cover_image'])) {
						$aBasicInfo['cover_image']['value'][0]['src'] = $aBasicInfo['cover_image']['base64'];
						$aBasicInfo['cover_image']['value'][0]['fileName'] = $aBasicInfo['cover_image']['name'];
						$aBasicInfo['cover_image']['value'][0]['fileType'] = 'image/jpg';
						unset($aBasicInfo['cover_image']['base64']);
						unset($aBasicInfo['cover_image']['name']);
						unset($aBasicInfo['cover_image']['type']);
						unset($aBasicInfo['cover_image']['uri']);
					} else {
						unset($aBasicInfo['cover_image']);
					}
				} else {
					unset($aBasicInfo[$key]);
					if (!empty($aValue)) {
						$aBasicInfo[$key]['value'] = $aValue;
					}
				}
			}

			$aStatus = ProfileController::updateBasicInfo($aBasicInfo, $oToken->userID);
			if ($aStatus !== true) {
				return [
					'status' => 'error',
					'msg'    => 'errorUpdateProfile'
				];
			}
		}

		if (isset($aFields['oFollowAndContact']) && !empty($aFields['oFollowAndContact'])) {
			$aRawFollowAndContact = json_decode(stripslashes($aFields['oFollowAndContact']), true);
			$aFollowAndContact = [];
			foreach ($aRawFollowAndContact as $key => $aVal) {
				if ($key == 'social_networks') {
					if (!empty($aVal) && !empty($key)) {
						$aFollowAndContact[$key] = [
							'value' => []
						];
						foreach ($aVal as $aSocial) {
							$aFollowAndContact[$key]['value'][] = [
								'name' => $aSocial['id'],
								'url'  => $aSocial['url']
							];
						}
					}
				} else {
					$aFollowAndContact[$key]['value'] = $aVal;
				}
			}
			if (!empty($aFollowAndContact)) {
				ProfileController::updateFollowAndContact($aFollowAndContact, $oToken->userID);
			}
		}

		$aNewProfiles = $this->getUserProfile($oToken->userID);

		return [
			'status'   => 'success',
			'msg'      => $msg,
			'oResults' => $aNewProfiles
		];
	}

	public function getProfileFields()
	{
		WPML::switchLanguageApp();
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}

		$aFields = [
			[
				'heading' => 'basicInfo',
				'key'     => 'oBasicInfo',
				'aFields' => apply_filters('wilcity/filter/wilcity-mobile-app/app/Controllers/UserController/getProfileFields/oBasicInfo',
					[
						[
							'label'          => 'firstName',
							'key'            => 'first_name',
							'type'           => 'text',
							'validationType' => 'firstName',
						],
						[
							'label'          => 'lastName',
							'key'            => 'last_name',
							'type'           => 'text',
							'validationType' => 'lastName',
						],
						[
							'label'          => 'displayName',
							'key'            => 'display_name',
							'type'           => 'text',
							'required'       => true,
							'validationType' => 'displayName',
						],
						[
							'label' => 'avatar',
							'key'   => 'avatar',
							'type'  => 'file'
						],
						[
							'label' => 'coverImg',
							'key'   => 'cover_image',
							'type'  => 'file'
						],
						[
							'label'          => 'email',
							'key'            => 'email',
							'type'           => 'text',
							'validationType' => 'email',
							'required'       => true
						],
						[
							'label' => 'position',
							'key'   => 'position',
							'type'  => 'text'
						],
						[
							'label' => 'introYourSelf',
							'key'   => 'description',
							'type'  => 'textarea'
						],
						[
							'label' => 'sendAnEmailIfIReceiveAMessageFromAdmin',
							'key'   => 'send_email_if_reply_message',
							'type'  => 'switch'
						]
					], $this->userID)
			],
			[
				'heading' => 'followAndContact',
				'key'     => 'oFollowAndContact',
				'aFields' => apply_filters('wilcity/filter/wilcity-mobile-app/app/Controllers/UserController/getProfileFields/oFollowAndContact',
					[
						[
							'label' => 'address',
							'key'   => 'address',
							'type'  => 'text'
						],
						[
							'label'          => 'phone',
							'key'            => 'phone',
							'type'           => 'text',
							'validationType' => 'phone',
						],
						[
							'label'          => 'website',
							'key'            => 'website',
							'type'           => 'text',
							'validationType' => 'url'
						],
						[
							'label'   => 'socialNetworks',
							'key'     => 'social_networks',
							'type'    => 'social_networks',
							'options' => $this->buildSelectOptions(\WilokeSocialNetworks::getUsedSocialNetworks())
						]
					], $this->userID)
			],
			[
				'heading' => 'changePassword',
				'key'     => 'oPassword',
				'aFields' => apply_filters('wilcity/filter/wilcity-mobile-app/app/Controllers/UserController/getProfileFields/changePassword',
					[
						[
							'label' => 'currentPassword',
							'key'   => 'current_password',
							'type'  => 'password'
						],
						[
							'label' => 'newPassword',
							'key'   => 'new_password',
							'type'  => 'password'
						],
						[
							'label' => 'confirmNewPassword',
							'key'   => 'confirm_new_password',
							'type'  => 'password'
						]
					], $this->userID)
			]
		];

		return [
			'status'   => 'success',
			'oResults' => $aFields
		];
	}

	public function getShortProfile()
	{
		WPML::switchLanguageApp();
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}
		$oToken->getUserID();

		$oUser = get_user_by('ID', $this->userID);

		return [
			'status'  => 'success',
			'oResult' => $this->getQuickUserInformation($oUser)
		];
	}

	public function getProfiles()
	{
		WPML::switchLanguageApp();
		$oToken = $this->verifyPermanentToken();

		if (!$oToken) {
			$userID = $_GET['userID'] ?? '';
		} else {
			$oToken->getUserID();
			$userID = $this->userID;
		}

		if (empty($userID)) {
			return [
				'status' => 'error',
				'msg'    => 'foundNoUser'
			];
		}

		$aUserInfo = $this->getUserProfile($userID);

		return [
			'status'   => 'success',
			'oResults' => $aUserInfo
		];
	}
}
