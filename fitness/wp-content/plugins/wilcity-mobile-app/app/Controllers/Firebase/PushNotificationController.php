<?php

namespace WILCITY_APP\Controllers\Firebase;

use ExponentPhpSDK\Exceptions\ExpoException;
use ExponentPhpSDK\Exceptions\UnexpectedResponseException;
use ExponentPhpSDK\Expo;
use Kreait\Firebase\ServiceAccount;
use WeatherStation\UI\Widget\Fire;
use WILCITY_APP\Controllers\JsonSkeleton;
use WILCITY_APP\Database\FirebaseDB;
use WILCITY_APP\Database\FirebaseDeviceToken;
use WILCITY_APP\Database\FirebaseMsgDB;
use WILCITY_APP\Database\FirebaseUser;
use Wiloke;
use WilokeListingTools\Controllers\FollowController;
use WilokeListingTools\Controllers\ReviewController;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\Firebase;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Framework\Upload\Upload;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\FavoriteStatistic;
use WilokeListingTools\Models\ReviewMetaModel;
use WilokeListingTools\Models\ReviewModel;
use WilokeListingTools\Models\UserModel;
use WilokeListingTools\Framework\Helpers\WPML;
use WP_Post;

class PushNotificationController
{
	use JsonSkeleton;

	private $aAdminSettings         = null;
	private $aCustomerSettings;
	private $msg;
	private $aAdminDeviceTokens     = null;
	private $pushNotificationCenter = 'https://exp.host/--/api/v2/push/send';
	private $aAdminKeys             = ['someoneSubmittedAListingToYourSite', 'someoneSubmittedAProductYourSite'];
	private $resendNotificationKey  = 'wilcity_resend_notification';

	private function isDisableAllNotification($userID)
	{
		return !FirebaseMsgDB::getNotificationStatus($userID, 'toggleAll');
	}

	private function getAdminSettings()
	{
		if ($this->aAdminSettings !== null) {
			$this->aAdminSettings = GetSettings::getOptions('admin_receive_notifications_settings', false, true);

			return $this->aAdminSettings;
		}

		return false;
	}

	private function isEnableNotificationStatusOnDevice($userID, $key, $isAdmin = false)
	{
		if ($this->isDisableAllNotification($userID)) {
			return false;
		}

		if ($isAdmin) {
			return true;
		}

		return FirebaseMsgDB::getNotificationStatus($userID, $key);
	}

	private function getAdminMsg($key)
	{
		$this->getAdminSettings();

		return isset($this->aAdminSettings[$key]) && isset($this->aAdminSettings[$key]['msg']) ?
			$this->aAdminSettings[$key]['msg'] : '';
	}

	private function generalReplacements($string, $userID = null, $postID = null)
	{
		$postTitle = '';
		$displayName = '';
		if (!empty($postID)) {
			$postTitle = get_the_title($postID);
		}

		if (!empty($userID)) {
			$displayName = User::getField('display_name', $userID);
		}

		return str_replace([
			'%userName%',
			'%postTitle%'
		], [
			$displayName,
			$postTitle
		], $string);
	}

	/**
	 * @refer https://docs.expo.io/push-notifications/sending-notifications/
	 *
	 * @param string $id
	 */
	private function pushReceipt(string $id)
	{
		$response = wp_remote_post('https://exp.host/--/api/v2/push/getReceipts', [
			'headers' => [
				'Content-Type'    => 'application/json',
				'Accept'          => 'application/json',
				'Accept-encoding' => 'gzip, deflate'
			],
			'body'    => json_encode([
				'ids' => [$id]
			])
		]);

		if (is_wp_error($response)) {
			Firebase::updateDebug('pushNotification', $response->get_error_message());
		} else {
			$body = wp_remote_retrieve_body($response);
			$aBody = json_decode($body, true);
			if (is_array($aBody) && isset($aBody['data']) && isset($aBody['data'][$id])) {
				$aMessage = $aBody['data'][$id];
				if ($aMessage['status'] === 'error') {
					Firebase::updateDebug('pushNotification', $aMessage['message']);
				}
			}
		}
	}

	private function push($aBody, $userID = null, $postID = null)
	{
		$aBody['body'] = $this->generalReplacements($aBody['body'], $userID, $postID);
		$aBody['to'] = Firebase::getDeviceToken();
		$aBody['sound'] = 'default';
		$aBody['badge'] = 1;

		$response = wp_remote_post($this->pushNotificationCenter, [
			'headers' => [
				'Content-Type'    => 'application/json',
				'Accept'          => 'application/json',
				'Accept-encoding' => 'gzip, deflate'
			],
			'body'    => json_encode($aBody)
		]);

		if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
			FileSystem::writeLog(print_r($response, true), true);
		}

		if (Firebase::isEnableDebug()) {
			$aResponse = json_decode(wp_remote_retrieve_body($response), true);
			if (is_array($aResponse) && isset($aResponse['id'])) {
				$this->pushReceipt($aResponse['id']);
			}
		}

		return $response;
	}

	public function resendNotification($aMsg, $isResend)
	{
		$this->pushArray($aMsg, $isResend);
	}

	private function postMessageToSlack($message, array $aBlocks = [])
	{
		if (!empty($aBlocks)) {
			$aBlocks = [
				[
					'type' => 'section',
					'text' => [
						'type' => 'mrkdwn',
						'text' => sprintf(
							'%s Thông tin cụ thể: %s',
							$message, json_encode($aBlocks)
						)
					]
				]
			];
		}

		wp_remote_post('https://hooks.slack.com/services/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', [
			'headers'  => [
				'Content-Type' => 'application/json',
			],
			'blocking' => false,
			'body'     => json_encode([
				'channel' => 'C0290MJ5F08',
				'text'    => $message,
				'blocks'  => $aBlocks
			])
		]);
	}

	private function pushArray($aMsg, $isResend = false): array
	{
		$response = wp_remote_post($this->pushNotificationCenter, [
			'headers' => [
				'Content-Type'    => 'application/json',
				'Accept'          => 'application/json',
				'Accept-encoding' => 'gzip, deflate'
			],
			'body'    => json_encode($aMsg)
		]);

		if (wp_remote_retrieve_response_code($response) != 200) {
			return [
				'status' => 'error'
			];
		}

		$aMessages = json_decode(wp_remote_retrieve_body($response), true);

		if (empty($aMessages)) {
			return ['status' => 'success'];
		}

		$aErrors = [];

		if (!$isResend) {
			foreach ($aMessages['data'] as $order => $aMessage) {
				if ($aMessage['status'] !== 'ok') {
					$aErrors[] = $aMsg[$order];
				}
			}
			if (!empty($aErrors)) {
				wp_schedule_single_event(time() + 30, $this->resendNotificationKey, [$aErrors, true]);

				$this->postMessageToSlack(
					'Lỗi gửi Push Notification lúc ' . date('Y-m-d H:i'),
					$aErrors
				);
			}
		}

		if (empty($aErrors)) {
			$this->postMessageToSlack(
				'Bắn thành công lúc ' . date('Y-m-d H:i'),
				$aMsg
			);
		}

		return ['status' => 'success'];
	}

	private function getAdminDeviceTokens($key)
	{
		$aSuperAdmins = User::getSuperAdmins();
		if ($this->aAdminDeviceTokens !== null) {
			return $this->aAdminDeviceTokens;
		}

		foreach ($aSuperAdmins as $oAdmin) {
			$firebaseID = FirebaseUser::getFirebaseID($oAdmin->ID);
			if (!empty($firebaseID)) {
				$isAdmin = in_array($key, $this->aAdminKeys);
				if (!$this->isEnableNotificationStatusOnDevice($oAdmin->ID, $key, $isAdmin)) {
					continue;
				}

				$deviceToken = FirebaseDeviceToken::getDeviceToken($oAdmin->ID, $firebaseID);
				if (!empty($deviceToken)) {
					$this->aAdminDeviceTokens[$oAdmin->ID] = $deviceToken;
				}
			}
		}

		if (empty($this->aAdminDeviceTokens)) {
			$this->aAdminDeviceTokens = false;

			return false;
		}
		$this->msg = $this->getAdminMsg($key);

		return $this->aAdminDeviceTokens;
	}

	public function __construct()
	{
		add_action('wilcity/wilcity-mobile-app/notifications/someone-is-following-you', [$this, 'someoneFollowedYou'],
			10, 2);
		add_action('wilcity/wilcity-mobile-app/notifications/send-published-post-to-followers',
			[$this, 'startSendNotificationToFollowers'], 10, 2);

		add_action('wilcity/wilcity-mobile-app/notifications/submitted-new-review',
			[$this, 'someoneLeftAReviewOnYourSite'], 10, 3);
		add_action('wilcity/wilcity-mobile-app/notifications/review-discussion',
			[$this, 'someLeftADiscussionOnYourReview'], 10, 2);
		add_action('wilcity/wilcity-mobile-app/notifications/someone-left-an-event-comment',
			[$this, 'someoneLeftACommentOnYourSite'], 10, 3);

		//		add_action('wilcity/action/send-welcome-message', array($this, 'sendWelcomeMessage'), 10, 3);
		add_action('wilcity/action/received-message', [$this, 'someoneSendAMessageToYou'], 10, 3);
		add_action('wilcity/wiloke-listing-tools/observerSendMessage', [$this, 'ajaxObserverSendMessage'], 10, 1);

		add_action('wilcity/wilcity-mobile-app/notifications/claim-approved', [$this, 'yourClaimHasBeenApproved'], 10,
			2);
		add_action('wilcity/wilcity-mobile-app/notifications/post-status-changed', [$this, 'postChanged'], 10, 2);

		add_action('wilcity/wilcity-mobile-app/notifications/product-published', [$this, 'productPublished'], 10, 3);

		add_action('wilcity/wilcity-mobile-app/notifications/someone-comment-on-product',
			[$this, 'someoneLeftARatingOnYourProduct'], 10, 3);
		add_action('wilcity/wilcity-mobile-app/notifications/sold-product', [$this, 'someonePurchasedYourProduct'], 10,
			2);

		// Admin
		add_action('wilcity/wilcity-mobile-app/notifications/submitted-new-listing',
			[$this, 'someoneSubmittedAListingToYourSite'], 10, 1);
		add_action('wilcity/wilcity-mobile-app/notifications/inserted-new-product',
			[$this, 'someoneSubmittedANewProductToYourSite'], 10, 1);

		add_action(
			'wilcity/wilcity-mobile-app/send-push-notification-directly',
			[$this, 'sendPushNotificationDirectly'],
			10,
			3
		);

		add_filter(
			'wilcity/filter/wilcity-mobile-app/test-send-push-notification',
			[$this, 'testSendPushNotification'],
			10,
			3
		);

		add_action(
			'wilcity/wilcity-mobile-app/send-page-stack-notifications',
			[$this, 'sendPageStackNotifications'],
			10,
			2
		);

		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/v2', 'notification-settings', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getNotificationSettings'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'firebase-configuration', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getFirebaseConfiguration'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'notification-settings', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getNotificationSettings'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'firebase-configuration', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getFirebaseConfiguration'],
				'permission_callback' => '__return_true'
			]);
		});

		add_action($this->resendNotificationKey, [$this, 'resendNotification'], 10, 2);
	}

	public function getFirebaseConfiguration()
	{
		WPML::switchLanguageApp();
		$aConfigurations = Firebase::getFirebaseChatConfiguration();
		if (empty($aConfigurations)) {
			return [
				'status' => 'error',
				'msg'    => 'Firebase configuration is required: Log into your site -> Wiloke Tools -> Firebase Configuration'
			];
		}

		return [
			'status'         => 'success',
			'oConfiguration' => $aConfigurations
		];
	}

	public function getNotificationSettings(): array
	{
		WPML::switchLanguageApp();
		if (empty($this->aCustomerSettings)) {
			$this->aCustomerSettings = GetSettings::getOptions('customers_receive_notifications_settings', false, true);
		}

		$aNotificationSettings = [];
		foreach ($this->aCustomerSettings as $key => $aSettings) {
			if ($key !== 'toggleAll' && (empty($aSettings['msg']) || $aSettings['status'] == 'off')) {
				continue;
			}

			unset($aSettings['msg']);
			unset($aSettings['status']);
			$aSettings['key'] = $key;

			$aNotificationSettings[] = $aSettings;
		}

		if (empty($aNotificationSettings)) {
			return [
				'status' => 'error',
				'msg'    => 'thisFeatureIsNotAvailable'
			];
		}

		return [
			'status'    => 'success',
			'aSettings' => $aNotificationSettings
		];
	}

	public function someoneSubmittedAListingToYourSite($oPost)
	{
		if (!$this->getAdminDeviceTokens('someoneSubmittedAListingToYourSite')) {
			return false;
		}

		$this->msg = str_replace(
			[
				'%userName%',
				'%postTitle%',
				'%postDate%',
				'%postType%',
				'%postID%'
			],
			[
				User::getField('display_name', $oPost->post_author),
				$oPost->post_title,
				get_the_date(get_option('date_format') . ' ' . get_option('time_format'), $oPost),
				$oPost->post_type,
				$oPost->ID
			],
			Firebase::getMessage()
		);

		$aBuildMsg = [];

		foreach ($this->aAdminDeviceTokens as $deviceToken) {
			$aBuildMsg[] = [
				'to'    => $deviceToken,
				'body'  => $this->msg,
				'sound' => 'default',
				'data'  => $oPost->post_type == 'event' ? $this->eventScreenSkeleton($oPost) :
					$this->listingScreenSkeleton($oPost)
			];
		}

		$this->pushArray($aBuildMsg);
		Firebase::resetInfo();
	}

	public function someoneSubmittedANewProductToYourSite($oPost)
	{
		if (!$this->getAdminDeviceTokens('someoneSubmittedAProductYourSite')) {
			return false;
		}

		$this->msg = str_replace(
			[
				'%userName%',
				'%postTitle%',
				'%postDate%',
				'%postType%',
				'%postID%'
			],
			[
				User::getField('display_name', $oPost->post_author),
				$oPost->post_title,
				get_the_date(get_option('date_format') . ' ' . get_option('time_format'), $oPost),
				$oPost->post_type,
				$oPost->ID
			],
			Firebase::getMessage()
		);

		$aBuildMsg = [];

		foreach ($this->aAdminDeviceTokens as $deviceToken) {
			$aBuildMsg[] = [
				'to'    => $deviceToken,
				'body'  => $this->msg,
				'sound' => 'default'
			];
		}

		$this->pushArray($aBuildMsg);
		Firebase::resetInfo();
	}

	public function someonePurchasedYourProduct($orderID, $productID)
	{
		if (empty(Firebase::getDeviceToken())) {
			return false;
		}

		$isSend = apply_filters(
			'wilcity/filter/wilcity-mobile-app/controller/firebase/push-notification-controller/is-send-someone-purchased-your-product',
			true,
			$orderID,
			$productID
		);

		if (!$isSend) {
			return false;
		}

		$this->push(
			[
				'body' => str_replace([
					'%orderID%',
					'%postTitle%'
				], [
					$orderID,
					get_the_title($productID),
				], Firebase::getMessage())
			]
		);

		Firebase::resetInfo();
	}

	/**
	 * @param $aResponse
	 * @param $deviceToken
	 * @param $aBody
	 *
	 * @return array
	 */
	public function testSendPushNotification($aResponse, $aBody, $deviceToken)
	{
		Firebase::setDeviceToken($deviceToken);
		$response = $this->push($aBody);

		if (is_wp_error($response)) {
			return [
				'status' => 'error',
				'msg'    => $response->get_error_message(),
				'code'   => $response->get_error_code()
			];
		}

		$response = wp_remote_retrieve_body($response);

		if (empty($response) || $response === 'Not Found') {
			return [
				'status' => 'error',
				'msg'    => 'It seems the device does not exists or the device has been removed'
			];
		}

		$oResponse = json_decode($response);

		if ($oResponse->errors) {
			return [
				'status' => 'error',
				'msg'    => $oResponse->errors[0]->message
			];
		}

		return [
			'status' => 'success',
			'data'   => json_decode($response, true),
			'msg'    => 'The notification has been sent to device successfully'
		];
	}

	/**
	 * @param $aUserIDs
	 * @param $aInfo {uri: string}
	 * @return bool
	 */
	public function sendPageStackNotifications($aUserIDs, $aInfo): bool
	{
		$aInfo = wp_parse_args($aInfo, [
			'title' => 'Message',
			'body'  => 'Message',
			'sound' => 'default',
			'ttl'   => 1
		]);

		if (empty($aInfo['body'])) {
			return false;
		}
		$aInfo['screen'] = 'PageScreen';
		$aBuildMsg = [];
		foreach ($aUserIDs as $userID) {
			$deviceToken = Firebase::focusGetDeviceToken($userID);
			if (!empty($deviceToken)) {
				$aInfo['to'] = $deviceToken;
				$aBuildMsg[] = $aInfo;
			}
		}
		if ($aBuildMsg) {
			$this->pushArray($aBuildMsg);
		}
		Firebase::resetInfo();
		return true;
	}

	/**
	 * @param               $aUserIDs
	 * @param               $message
	 * @param WP_Post|null $oPost
	 */
	public function sendPushNotificationDirectly($aUserIDs, $message, WP_Post $oPost = null)
	{
		$aInfo = [
			'body'  => $message,
			'sound' => 'default'
		];

		if (!empty($aPost)) {
			$aInfo['data'] = $oPost->post_type == 'event' ? $this->eventScreenSkeleton($oPost) :
				$this->listingScreenSkeleton($oPost);
		}

		$aBuildMsg = [];
		$hasDeviceToken = false;
		foreach ($aUserIDs as $userID) {
			$deviceToken = Firebase::focusGetDeviceToken($userID);
			if (!empty($deviceToken)) {
				$aInfo['to'] = $deviceToken;
				$aBuildMsg[] = $aInfo;
				$hasDeviceToken = true;
			}
		}

		if ($hasDeviceToken) {
			$this->pushArray($aBuildMsg);
		}

		Firebase::resetInfo();
	}

	public function someoneLeftARatingOnYourProduct($metaID, $commentID, $metaKey)
	{
		$oComment = get_comment($commentID);

		if (empty($oComment) || is_wp_error($oComment)) {
			return false;
		}

		if (!Firebase::isCustomerEnable('productReview', $oComment->comment_post_ID)) {
			Firebase::resetInfo();

			return false;
		}

		if (empty(Firebase::getDeviceToken())) {
			return false;
		}

		$this->push(
			[
				'body' => str_replace([
					'%postTitle%',
					'%rating%',
					'%reviewExcerpt%',
					'%review%'
				], [
					get_the_title($oComment->comment_post_ID),
					get_comment_meta($commentID, 'rating', true),
					Wiloke::contentLimit(50, $oComment, true, $oComment->comment_content),
					$oComment->comment_content
				], Firebase::getMessage())
			]
		);
		Firebase::resetInfo();
	}

	public function yourClaimHasBeenApproved($claimerID, $listingID)
	{
		if (!Firebase::isCustomerEnable('claimApproved', $claimerID)) {
			Firebase::resetInfo();

			return false;
		}

		if (empty(Firebase::getDeviceToken())) {
			return false;
		}

		$this->push(
			[
				'body' => Firebase::getMessage(),
				'data' => get_post_type($listingID) == 'event' ? $this->eventScreenSkeleton(get_post($listingID)) :
					$this->listingScreenSkeleton(get_post($listingID))
			],
			$claimerID,
			$listingID
		);

		Firebase::resetInfo();
	}

	public function someoneSendAMessageToYou($receiverID, $senderID, $msg)
	{
		if (!Firebase::isCustomerEnable('privateMessages', $receiverID)) {
			Firebase::resetInfo();

			return false;
		}

		if (empty(Firebase::getDeviceToken())) {
			Firebase::resetInfo();

			return false;
		}

		$this->push(
			[
				'body' => str_replace([
					'%senderName%',
					'%message%'
				], [
					User::getField('display_name', $senderID),
					$msg
				], Firebase::getMessage()),
				'data' => [
					'screen'      => 'SendMessageScreen',
					'userID'      => $senderID,
					'displayName' => User::getField('display_name', $senderID)
				]
			]
		);

		Firebase::resetInfo();
	}

	public function ajaxObserverSendMessage($aInfo)
	{
		$this->someoneSendAMessageToYou($aInfo['chattedWithId'], User::getCurrentUserID(), $aInfo['msg']);
	}

	public function sendWelcomeMessage($receiverID, $senderID, $msg)
	{
		$deviceToken = Firebase::focusGetDeviceToken($receiverID);
		if (empty($deviceToken)) {
			return false;
		}
		self::someoneSendAMessageToYou($receiverID, $senderID, $msg);
	}

	public function productPublished($postID, $oPostAfter, $oPostBefore)
	{
		if (empty(Firebase::getDeviceToken())) {
			return false;
		}

		$this->push(
			[
				'body' => Firebase::getMessage()
			],
			$oPostAfter->post_author,
			$postID
		);

		Firebase::resetInfo();
	}

	private function listingScreenSkeleton($oListing)
	{
		return [
			'screen'  => 'ListingDetailScreen',
			'id'      => $oListing->ID,
			'name'    => $oListing->post_title,
			'tagline' => GetSettings::getTagLine($oListing),
			'link'    => get_permalink($oListing->ID),
			'author'  => [
				'ID'          => $oListing->post_author,
				'avatar'      => User::getAvatar($oListing->post_author),
				'displayName' => User::getField('display_name', $oListing->post_author)
			],
			'image'   => GetSettings::getFeaturedImg($oListing->ID, 'thumbnail'),
			'logo'    => GetSettings::getLogo($oListing->ID)
		];
	}

	private function eventScreenSkeleton($oEvent)
	{
		return [
			'screen'     => 'EventDetailScreen',
			'id'         => $oEvent->ID,
			'link'       => get_permalink($oEvent->ID),
			'name'       => $oEvent->post_title,
			'address'    => GetSettings::getAddress($oEvent->ID, true),
			'hosted'     => GetSettings::getEventHostedByName($oEvent->ID),
			'interested' => FavoriteStatistic::countFavorites($oEvent->ID),
			'image'      => GetSettings::getFeaturedImg($oEvent->ID, 'thumbnail')
		];
	}

	public function startSendNotificationToFollowers($oPost, $aFollowers)
	{
		if (empty($aFollowers)) {
			return false;
		}

		$aBuildMessages = [];

		$msg = str_replace(
			[
				'%userName%',
				'%postTitle%',
				'%postExcerpt%'
			],
			[
				User::getField('display_name', $oPost->post_author),
				$oPost->post_title,
				Wiloke::contentLimit(60, $oPost, true, $oPost->post_content)
			],
			Firebase::getMessage()
		);

		foreach ($aFollowers as $aFollow) {
			$firebaseID = FirebaseDB::getFirebaseID($aFollow['followerID']);
			if ($firebaseID) {
				if ($deviceToken = FirebaseDeviceToken::getDeviceToken($aFollow['followerID'], $firebaseID)) {
					if (!Firebase::isCustomerEnable('followerPublishedNewListing', $aFollow['followerID'])) {
						$aBuildMessages = [
							'to'    => $deviceToken,
							'sound' => 'default',
							'body'  => $msg
						];
					}
				}
			}
		}
		if (!empty($aBuildMessages)) {
			$this->pushArray($aBuildMessages);
		}

		Firebase::resetInfo();
	}

	public function postChanged($oPostAfter, $oPostBefore)
	{
		if (!Firebase::isCustomerEnable('listingStatus', $oPostAfter->post_author)) {
			Firebase::resetInfo();

			return false;
		}

		if (empty(Firebase::getDeviceToken())) {
			return false;
		}
		$aMessage = [
			'body' => str_replace([
				'%postTitle%',
				'%beforeStatus%',
				'%afterStatus%'
			], [
				$oPostAfter->post_title,
				$oPostBefore->post_status,
				$oPostAfter->post_status
			], Firebase::getMessage())
		];

		if ($oPostAfter->post_status == 'publish') {
			$aMessage['data'] = $oPostAfter->post_type == 'event' ? $this->eventScreenSkeleton($oPostAfter) :
				$this->listingScreenSkeleton($oPostAfter);
		}

		$this->push($aMessage);
		Firebase::resetInfo();
	}

	public function someoneLeftACommentOnYourSite($commentID, $commenterID, $eventID)
	{
		if (!Firebase::isCustomerEnable('eventComment', $eventID)) {
			Firebase::resetInfo();

			return false;
		}

		if (empty(Firebase::getDeviceToken())) {
			return false;
		}

		$oPost = get_post($commentID);
		$this->push([
			'body' => str_replace([
				'%commentExcerpt%',
				'%comment%'
			], [
				Wiloke::contentLimit(50, $oPost, true, $oPost->post_content),
				$oPost->post_content
			], Firebase::getMessage()),
			'data' => [
				'screen'    => 'EventCommentDiscussionScreen',
				'item'      => $this->eventCommentItem(get_post($eventID)),
				'autoFocus' => true
			]
		], $commenterID, $eventID);

		Firebase::resetInfo();

	}

	public function someoneFollowedYou($followerID, $authorID)
	{
		if (!Firebase::isCustomerEnable('newFollowers', $authorID)) {
			Firebase::resetInfo();

			return false;
		}

		if (empty(Firebase::getDeviceToken())) {
			return false;
		}

		$msg = str_replace('%userName%', User::getField('display_name', $followerID), Firebase::getMessage());
		$this->push([
			'body' => $msg
		]);

		Firebase::resetInfo();
	}

	public function someLeftADiscussionOnYourReview($discussionID, $reviewID)
	{
		$receiverId = get_post_field('post_author', $reviewID);
		if (!Firebase::isCustomerEnable('reviewDiscussion', $receiverId)) {
			Firebase::resetInfo();

			return false;
		}

		if (empty(Firebase::getDeviceToken())) {
			return false;
		}

		$postAuthor = get_post_field('post_author', $reviewID);
		$oPost = get_post($discussionID);

		$listingID = wp_get_post_parent_id($reviewID);
		$postType = get_post_field('post_type', $listingID);
		$aDetails = GetSettings::getOptions(General::getReviewKey('details', $postType), false, true);

		$this->push([
			'body' => str_replace([
				'%reviewExcerpt%',
				'%review%'
			], [
				Wiloke::contentLimit(50, $oPost),
				$oPost->post_content
			], Firebase::getMessage()),
			'data' => [
				'screen'    => 'CommentListingScreen',
				'id'        => $listingID,
				'key'       => 'reviews',
				'item'      => $this->getReviewItem(get_post($listingID), $listingID, $aDetails),
				'autoFocus' => true,
				'mode'      => ReviewController::getMode($postType)
			]
		], $postAuthor, $reviewID);
		Firebase::resetInfo();
	}

	public function someoneLeftAReviewOnYourSite($reviewID, $parentID, $reviewerID)
	{
		$receiverId = get_post_field('post_author', $parentID);

		if (!Firebase::isCustomerEnable('review', $receiverId)) {
			Firebase::resetInfo();

			return false;
		}

		if (empty(Firebase::getDeviceToken())) {
			return false;
		}

		$postType = get_post_field('post_type', $parentID);
		$this->msg = $this->generalReplacements(Firebase::getMessage(), $reviewerID, $parentID);
		$aDetails = GetSettings::getOptions(General::getReviewKey('details', $postType), false, true);

		$this->push([
			'body' => str_replace(
				[
					'%reviewExcerpt%',
					'%averageRating%'
				],
				[
					Wiloke::contentLimit(50, get_post($reviewID)),
					ReviewMetaModel::getAverageReviewsItem($reviewID)
				], Firebase::getMessage()
			),
			'data' => [
				'screen'    => 'CommentListingScreen',
				'id'        => $parentID,
				'key'       => 'reviews',
				'item'      => $this->getReviewItem(get_post($parentID), $parentID, $aDetails),
				'autoFocus' => true,
				'mode'      => ReviewController::getMode($postType)
			]
		], $receiverId, $reviewID);
		Firebase::resetInfo();
	}
}
