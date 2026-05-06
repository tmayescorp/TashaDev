<?php

namespace WILCITY_APP\Database;

use Exception;
use Kreait\Firebase\Exception\DatabaseException;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Frontend\User;

class FirebaseUser
{
	private static $firebaseIDKey = 'firebase_id';

	private static function buildKey($userID)
	{
		return 'connections/___' . $userID . '___';
	}

	public static function isUserOnlineOnApp($userID)
	{
		$status = FirebaseDB::getDB()->getReference(self::buildKey($userID))->getSnapshot()->getValue();

		return (bool)$status;
	}

	public static function updateConnectionStatus($userID, $isOnline)
	{
		try {
			FirebaseDB::getDB()->getReference('connections')
				->update([
					'___' . $userID . '___' => $isOnline ? true : null
				]);
		}
		catch (Exception $oException) {
			if (current_user_can('administrator')) {
				echo "Invalid Firebase Configuration: " . $oException->getMessage();
			} else {
				echo "Something went error";
			}
		}
		catch (DatabaseException $oException) {
			if (current_user_can('administrator')) {
				echo "Invalid Firebase Configuration: " . $oException->getMessage();
			} else {
				echo "Something went error";
			}
		}
	}

	public static function getFirebaseID($userID = null)
	{
		$userID = empty($userID) ? User::getCurrentUserID() : $userID;

		return GetSettings::getUserMeta($userID, self::$firebaseIDKey);
	}
}
