<?php

namespace WILCITY_APP\Database;

use Exception;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Database;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use WilokeListingTools\Framework\Helpers\Firebase;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Upload\Upload;
use WilokeListingTools\Frontend\User;

class FirebaseDB
{
	private static $db = null;
	/**
	 * @var Auth $auth
	 */
	private static $auth;

	private static $factory;
	private static $firebaseIDKey = 'firebase_id';

	/**
	 * @return Database
	 */
	public static function getDB(): ?Database
	{
		if (self::$db) {
			return self::$db;
		}

		self::_connect();

		return self::$db;
	}

	private static function _auth()
	{
		$serviceAccount = ServiceAccount::fromValue(Upload::getFolderDir('wilcity') . 'firebaseConfig.json');

		self::$auth = (new Factory)
			->withServiceAccount($serviceAccount)
			->createAuth();
	}

	private static function _connect(): void
	{
		try {
			$serviceAccount = ServiceAccount::fromValue(Upload::getFolderDir('wilcity') . 'firebaseConfig.json');
			self::$factory = (new Factory)
				->withServiceAccount($serviceAccount)
				// The following line is optional if the project id in your credentials file
				// is identical to the subdomain of your Firebase project. If you need it,
				// make sure to replace the URL with the URL of your project.
				->withDatabaseUri(Firebase::getFirebaseField('databaseURL'));
			self::$db = self::$factory->createDatabase();
		}
		catch (Exception $exception) {
			wp_mail(
				get_option("admin_email"),
				esc_html__("Invalid Firebase Configuration", "wilcity-mobile-app"),
				__(
					"<p>Your firebase configuration is not valid, please go to Wiloke Tools -> Notification and recheck your configuration.</p> 
					<p>We recommend reading <a href='https://documentation.wilcity.com/knowledgebase/notification-settings/'>Notification Documentation</a> to learn how to set up this feature.</p>",
					"wilcity-mobile-app"
				)
			);
		}
	}

	public static function getFirebaseID($userID = null)
	{
		$userID = empty($userID) ? User::getCurrentUserID() : $userID;

		return GetSettings::getUserMeta($userID, self::$firebaseIDKey);
	}

	public static function setFirebaseID($firebaseID, $userID = null)
	{
		$userID = empty($userID) ? User::getCurrentUserID() : $userID;
		SetSettings::setUserMeta($userID, self::$firebaseIDKey, $firebaseID);
	}

	/**
	 * @return Auth
	 */
	public static function getAuth()
	{
		if (self::$auth) {
			return self::$auth;
		}

		self::_auth();

		return self::$auth;
	}
}

