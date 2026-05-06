<?php

namespace WILCITY_APP\Controllers\User;

use WilokeListingTools\Controllers\DashboardController;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\UserSkeleton;
use WilokeListingTools\Frontend\User;
use WP_User;

/**
 * Class AuthorInfo
 * @package WILCITY_APP\Controllers\Listing
 */
class UserInfo
{
	/**
	 * @param       $userID
	 * @param array $aPluck
	 *
	 * @return array
	 */
	public function getData($userID, $aPluck = [])
	{
		if (empty($aPluck)) {
			$aPluck = [
				'userID',
				'avatar',
				'displayName',
				'position',
				'phone',
				'address',
				'oSocialNetworks',
				'coverImage',
				'website',
				'email'
			];
		}

		$oUserSkeleton = new UserSkeleton($userID);

		return $oUserSkeleton->pluck($aPluck);
	}

	public function buildUserInfo($oUser, $token): array
	{
		$oUser = new WP_User($oUser->ID);
		return [
			'status'    => 'success',
			'msg'       => 'loggedIn',
			'token'     => $token,
			'oUserInfo' => [
				'userID'        => $oUser->ID,
				'displayName'   => GetSettings::getUserMeta($oUser->ID, 'display_name'),
				'userName'      => $oUser->user_login,
				'avatar'        => User::getAvatar($oUser->ID),
				'position'      => User::getPosition($oUser->ID),
				'coverImg'      => User::getCoverImage($oUser->ID),
				'isContributor' => !empty(array_intersect($oUser->roles, ['administrator', 'contributor']))
			],
			'oUserNav'  => array_values(DashboardController::getNavigation($oUser->ID))
		];
	}
}
