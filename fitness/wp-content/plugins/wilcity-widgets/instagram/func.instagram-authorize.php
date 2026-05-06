<?php

use WilokeListingTools\Framework\Store\Session;

$shortTokenRequestUrl = 'https://api.instagram.com/oauth/access_token';
$longLivedTokenRequestUrl = 'https://graph.instagram.com/access_token';
$refreshTokenRequestUrl = 'https://graph.instagram.com/refresh_access_token';
$userRequestUrl = 'https://graph.instagram.com/me';
$instagramKey = 'wiloke_instagram_settings';
$aInstagram = get_option($instagramKey);

if (isset($_GET['state']) && $_GET['state'] == Session::getSession('request-instagram-token', true)) {
	$aShortTokenResponse = wp_remote_post(
		$shortTokenRequestUrl,
		[
			'body' => [
				'client_id'     => $aInstagram['app_id'],
				'client_secret' => $aInstagram['app_secret'],
				'code'          => $_GET['code'],
				'grant_type'    => 'authorization_code',
				'redirect_uri'  => $aInstagram['redirect_uri']
			]
		]
	);

	if (is_wp_error($aShortTokenResponse) || wp_remote_retrieve_response_code($aShortTokenResponse) !== 200) {
		\WilokeListingTools\Framework\Store\Session::addTopNotifications('error-instagram-token', [
			'msg'              => 'We could not get your Instagram Token, please re-check your configuration',
			'type'             => 'error',
			'numberOfDisplays' => 1
		]);

		return false;
	}

	$sShortAccessToken = json_decode(wp_remote_retrieve_body($aShortTokenResponse))->access_token;
	$aLongLivedTokenResponse = wp_remote_get(
		add_query_arg(
			[
				'grant_type'    => 'ig_exchange_token',
				'client_secret' => $aInstagram['app_secret'],
				'access_token'  => $sShortAccessToken
			],
			$longLivedTokenRequestUrl
		)
	);

	if (is_wp_error($aLongLivedTokenResponse)
		|| wp_remote_retrieve_response_code($aLongLivedTokenResponse) !== 200
	) {
		return false;
	}
	$aInstagram['access_token'] = json_decode(wp_remote_retrieve_body($aLongLivedTokenResponse))->access_token;
	$aUserDataResponse = wp_remote_get(
		add_query_arg(
			[
				'fields'       => 'id,username',
				'access_token' => $aInstagram['access_token']
			],
			$userRequestUrl
		)
	);
	$oUserData = json_decode(wp_remote_retrieve_body($aUserDataResponse));

	if (!empty($oUserData)) {
		$aInstagram['userid'] = $oUserData->id;
		$aInstagram['username'] = $oUserData->username;
	}
	$aRefreshTokenResponse = wp_remote_get(
		add_query_arg(
			[
				'grant_type'   => 'ig_refresh_token',
				'access_token' => $aInstagram['access_token']
			],
			$refreshTokenRequestUrl
		)
	);

	if (is_wp_error($aRefreshTokenResponse) || wp_remote_retrieve_response_code($aRefreshTokenResponse) !== 200) {
		return false;
	}
	$aInstagram['refresh_token'] = json_decode(wp_remote_retrieve_body($aLongLivedTokenResponse))->access_token;
	update_option($instagramKey, $aInstagram);
	\WilokeListingTools\Framework\Store\Session::addTopNotifications('saved-instagram-token', [
		'msg'              => esc_html__('Congrats! The Instagram token has been saved', 'wilcity-widgets'),
		'type'             => 'success',
		'numberOfDisplays' => 1
	]);
}
