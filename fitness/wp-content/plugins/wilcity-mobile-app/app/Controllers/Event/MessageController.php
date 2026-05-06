<?php

namespace WILCITY_APP\Controllers;

use WilokeListingTools\Controllers\NotificationsController as ThemeNotificationController;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\MessageModel;
use WilokeListingTools\Models\NotificationsModel;
use \WilokeListingTools\Controllers\MessageController as ThemeMessageController;
use WilokeListingTools\Framework\Helpers\WPML;

class MessageController
{
	use VerifyToken;
	use JsonSkeleton;
	use ParsePost;

	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, '/get-authors-chatted', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getAuthorsChatted'],
				'permission_callback' => '__return_true',
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, '/get-author-messages', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getAuthorMessages']
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, '/delete-message', [
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'deleteMessage']
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, '/delete-author-chatted', [
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'deleteAuthorChatted']
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, '/send-message', [
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'sendMessage']
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, '/count-new-messages', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'countNewMessages']
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/get-authors-chatted', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getAuthorsChatted']
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/get-author-messages', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getAuthorMessages']
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/delete-message', [
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'deleteMessage']
			]);

			register_rest_route(WILOKE_PREFIX . '/2', '/delete-author-chatted', [
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'deleteAuthorChatted']
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/send-message', [
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'sendMessage']
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', '/count-new-messages', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'countNewMessages']
			]);
		});
	}

	public function countNewMessages()
	{
		WPML::switchLanguageApp();
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}
		$oToken->getUserID();

		$total = MessageModel::countMessages($oToken->userID);

		return [
			'status' => 'success',
			'count'  => abs($total)
		];
	}

	public function sendMessage()
	{
		WPML::switchLanguageApp();
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}

		$oToken->getUserID();
		$aData = $this->parsePost();

		if (!isset($aData['chatFriendID']) || empty($aData['chatFriendID'])) {
			return [
				'status' => 'error',
				'msg'    => 'chatFriendIDIsRequired'
			];
		}

		if (!isset($aData['content']) || empty($aData['content'])) {
			return [
				'status' => 'error',
				'msg'    => 'messageContentIsRequired'
			];
		}

		if (!User::userIDExists($aData['chatFriendID'])) {
			return [
				'status' => 'error',
				'msg'    => 'userDoesNotExists'
			];
		}

		$msgID = MessageModel::insertNewMessage($aData['chatFriendID'], $aData['content']);

		if (!$msgID) {
			return [
				'status' => 'error',
				'msg'    => 'couldNotSendMessage'
			];
		} else {
			return [
				'status' => 'success',
				'msg'    => ''
			];
		}
	}

	public function deleteAuthorChatted()
	{
		WPML::switchLanguageApp();
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}
		$oToken->getUserID();

		$aData = $this->parsePost();
		if (!isset($aData['chatFriendID']) || empty($aData['chatFriendID'])) {
			return [
				'status' => 'error',
				'msg'    => 'authorIDIsRequired'
			];
		}

		$status = MessageModel::deleteChatRoom($_POST['chatFriendID']);
		if ($status) {
			return [
				'status' => 'error',
				'msg'    => 'weCouldNotDeleteAuthorMessage'
			];
		} else {
			return [
				'status' => 'success',
				'msg'    => 'messageHasBeenDelete'
			];
		}
	}

	public function deleteMessage()
	{
		WPML::switchLanguageApp();
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}
		$oToken->getUserID();

		$aData = $this->parsePost();
		if (!isset($aData['ID']) || empty($aData['ID'])) {
			return [
				'status' => 'error',
				'msg'    => 'msgIDIsRequired'
			];
		}

		$status = MessageModel::deleteMessageByCurrentID($_POST['ID']);
		if ($status) {
			return [
				'status' => 'error',
				'msg'    => 'weCouldNotDeleteMessage'
			];
		} else {
			return [
				'status' => 'success',
				'msg'    => 'messageHasBeenDelete'
			];
		}
	}

	protected static function aParseExcludeIDs($exclude)
	{
		if (empty($exclude)) {
			return [];
		}

		$aExcludes = !empty($exclude) ? explode(',', $exclude) : $exclude;
		if (empty($aExcludes)) {
			return [];
		}

		return array_map(function ($userID) {
			return abs($userID);
		}, $aExcludes);
	}

	public function buildChatResult($aData)
	{
		$diffInMinutes = Time::dateDiff(strtotime($aData['messageDateUTC']), current_time('timestamp', 1), 'minute');

		if ($diffInMinutes < 60) {
			if (empty($diffInMinutes)) {
				$at = 'aFewSecondAgo';
			} else {
				$at = str_replace('%s', $diffInMinutes, wilcityAppGetLanguageFiles('xMinutesAgo'));
			}
		} else {
			$diffInHours = Time::dateDiff(strtotime($aData['messageDateUTC']), current_time('timestamp', 1), 'hour');
			if ($diffInHours < 24) {
				$at = str_replace('%s', $diffInMinutes, wilcityAppGetLanguageFiles('xHoursAgo'));
			} elseif (Time::isDateInThisWeek(strtotime($aData['messageDate']))) {
				$at = date_i18n('l', strtotime($aData['messageDate']));
			} else {
				$at = date_i18n(get_option('date_format'), strtotime($aData['messageDate']));
			}
		}

		$aExcludes[] = $aData['ID'];

		return [
			'oProfile'  => $this->getUserProfile($aData['messageAuthorID'], false),
			'oMessage'  => [
				'at'      => $at,
				'content' => $aData['messageContent']
			],
			'aExcludes' => $aExcludes
		];
	}

	public function getAuthorMessages()
	{
		WPML::switchLanguageApp();
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}
		$oToken->getUserID();

		if (!isset($_GET['chatFriendID']) || empty($_GET['chatFriendID'])) {
			return [
				'status' => 'error',
				'msg'    => 'noMessage'
			];
		}

		$chatFiendID = $_GET['chatFriendID'];

		$aExcludes = [];
		if (isset($_GET['excludes'])) {
			$aExcludes = self::aParseExcludeIDs($_GET['excludes']);
		}
		if (isset($_GET['isFetchLatestChat'])) {
			$aRawResults = MessageModel::getNewestChat($oToken->userID, $chatFiendID, $aExcludes);
		} else {
			$aRawResults = MessageModel::getMyChat($oToken->userID, $chatFiendID, $aExcludes);
		}

		if (empty($aRawResults)) {
			return [
				'status' => 'error',
				'msg'    => 'noMessage'
			];
		}

		$aResults = [];
		foreach ($aRawResults as $aData) {
			$aResults[] = $this->buildChatResult($aData);
		}

		return [
			'status'   => 'success',
			'oResults' => $aResults
		];
	}

	public function getAuthorsChatted()
	{
		WPML::switchLanguageApp();
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}
		$oToken->getUserID();

		$postsPerPage = isset($_GET['postsPerPage']) ? $_GET['postsPerPage'] : 10;

		$aExcludes = isset($_GET['excludes']) ? self::aParseExcludeIDs($_GET['excludes']) : '';

		$aRawResults = MessageModel::getMessageAuthors($oToken->userID, $aExcludes, $postsPerPage);

		if (empty($aRawResults)) {
			if (empty($aExcludes)) {
				return [
					'status' => 'error',
					'msg'    => 'noMessage'
				];
			}

			return [
				'status' => 'error',
				'msg'    => 'fetchedAllMessages'
			];
		}

		$aResults = [];
		foreach ($aRawResults as $aData) {
			$aResults[] = $this->buildChatResult($aData);
		}

		return [
			'status'   => 'success',
			'oResults' => $aResults
		];
	}
}
