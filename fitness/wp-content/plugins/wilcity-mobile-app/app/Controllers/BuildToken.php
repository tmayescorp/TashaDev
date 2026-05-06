<?php

namespace WILCITY_APP\Controllers;

use Exception;
use ReallySimpleJWT\TokenBuilder;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\SetSettings;

trait BuildToken
{
	private function buildToken($oUser, $expiration = ''): array
	{
		$builder = new TokenBuilder();

		if (empty($expiration)) {
			$expiration = $this->getOptionField('wilcity_token_expired_after');
			$expiration = !empty($expiration) ? '+' . $expiration . ' day' : '+30 day';
		}
		try {
			$token = $builder->addPayload(['key' => 'userID', 'value' => $oUser->ID])
				->setSecret($this->getSecurityAuthKey())
				->setExpiration(strtotime($expiration))
				->setIssuer(get_option('siteurl'))
				->build();
			do_action('wilcity/wilcity-mobile-app/app-signed-up', $oUser->ID, $token);

			return [
				'status' => 'success',
				'token'  => $token
			];
		}
		catch (Exception $oE) {
			return [
				'status' => 'error',
				'msg'    => $oE->getMessage() .
					'. Please go to Appearance -> Theme Options -> Mobile General Settings -> SECURE AUTH KEY to complete this setting',
				'file'   => $oE->getFile(),
				'line'   => $oE->getLine(),
				'code'   => $oE->getCode()
			];
		}
	}

	private function buildPermanentLoginToken($oUser): array
	{
		$aResponse = $this->buildToken($oUser);
		if ($aResponse['status'] == 'success') {
			SetSettings::setUserMeta($oUser->ID, 'app_token', $aResponse['token']);
		}

		return $aResponse;
	}

	/**
	 * Build temporary login token (It will be expired after 10 minutes)
	 * This is useful for WooCommerce login
	 */
	private function buildTemporaryLoginToken($oUser): array
	{
		$aResponse = $this->buildToken($oUser, '+10 minutes');
		if ($aResponse['status'] == 'success') {
			SetSettings::setUserMeta($oUser->ID, 'temporary_app_token', $aResponse['token']);
			SetSettings::setUserMeta($oUser->ID, 'temporary_user_ip', General::clientIP());
		}

		return $aResponse;
	}
}
