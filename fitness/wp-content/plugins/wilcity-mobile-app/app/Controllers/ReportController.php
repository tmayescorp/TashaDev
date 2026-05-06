<?php

namespace WILCITY_APP\Controllers;

use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Models\ReportModel;
use WilokeListingTools\Framework\Helpers\WPML;

class ReportController
{
	use VerifyToken;
	use JsonSkeleton;
	use ParsePost;

	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'get-report-fields', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getReportField'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'post-report', [
				'methods'             => 'POST',
				'callback'            => [$this, 'postReport'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'get-report-fields', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getReportField'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'post-report', [
				'methods'             => 'POST',
				'callback'            => [$this, 'postReport'],
				'permission_callback' => '__return_true'
			]);
		});
	}

	public function getReportField()
	{
		WPML::switchLanguageApp();
		$toggleReport = GetSettings::getOptions('toggle_report');

		if ($toggleReport != 'enable') {
			wp_send_json_error(
				[
					'msg' => 'Report Disabled'
				]
			);
		}
		$aFields = GetSettings::getOptions('report_fields');
		if (empty($aFields)) {
			wp_send_json_error(
				[
					'msg' => 'Report Disabled'
				]
			);
		}

		$description = GetSettings::getOptions('report_description');
		foreach ($aFields as $key => $aField) {
			if ($aField['type'] == 'select') {
				$aRawOptions = explode(',', $aField['options']);
				$aOptions = array_map(function ($val) {
					$aParsedOptions = General::parseCustomSelectOption($val);

					return [
						'id'       => $aParsedOptions['key'],
						'name'     => $aParsedOptions['name'],
						'selected' => false
					];
				}, $aRawOptions);
				$aFields[$key]['options'] = $aOptions;
			} else {
				unset($aFields[$key]['options']);
			}
		}

		$aResults = [
			'aFields' => $aFields
		];

		if (!empty($description)) {
			$aResults['description'] = $description;
		}

		return [
			'status'   => 'success',
			'oResults' => $aResults
		];
	}

	public function postReport()
	{
		WPML::switchLanguageApp();
		$aData = $this->parsePost();
		if (!isset($aData['postID']) || empty($aData['postID']) || get_post_status($aData['postID']) !== 'publish') {
			return [
				'status' => 'error',
				'msg'    => 403
			];
		}

		if (!isset($aData['data']) || empty($aData['data'])) {
			return [
				'status' => 'error',
				'msg'    => 'weNeedYourReportMsg'
			];
		}

		ReportModel::addReport($aData);

		return [
			'status' => 'success',
			'msg'    => GetSettings::getOptions('report_thankyou')
		];
	}
}
