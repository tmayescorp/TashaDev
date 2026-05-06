<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Controllers\Retrieve\RestRetrieve;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Models\ReportModel;
use WilokeListingTools\Framework\Helpers\Validation as ValidationHelper;
use WilokeListingTools\Framework\Helpers\WPML;

class ReportController extends Controller
{
    public function __construct()
    {
        //		add_action('wp_ajax_wilcity_fetch_report_fields', array($this, 'fetchReportFields'));
        //		add_action('wp_ajax_nopriv_wilcity_fetch_report_fields', array($this, 'fetchReportFields'));
        add_action('wp_ajax_wilcity_submit_report', [$this, 'submitReport']);
        add_action('wp_ajax_nopriv_wilcity_submit_report', [$this, 'submitReport']);

        add_action('rest_api_init', function () {
            register_rest_route(WILOKE_PREFIX . '/v2', '/listings/fields/reports', [
                'methods'             => 'GET',
                'callback'            => [$this, 'getReportFields'],
                'permission_callback' => '__return_true'
            ]);
        });
    }

    public function submitReport($isApp = false)
    {
        if ($isApp) {
            $oChildRetrieve = new RestRetrieve();
        } else {
            $oChildRetrieve = new AjaxRetrieve();
        }
        $oRetrieve = new RetrieveController($oChildRetrieve);

        $this->middleware(['isPublishedPost'], [
            'postID' => $_POST['postID'],
            'isApp'  => $isApp ? 'yes' : 'no'
        ]);

        if (empty($_POST['data'])) {
            return $oRetrieve->error([
                'msg' => esc_html__('Please give us your reason', 'wiloke-listing-tools')
            ]);
        }

        if (is_array($_POST['data'])) {
            $aData = $_POST['data'];
        } else {
            if (!ValidationHelper::isValidJson($_POST['data'])) {
                return $oRetrieve->error([
                    'msg' => esc_html__('Invalid json format', 'wiloke-listing-tools')
                ]);
            }
            $aData = ValidationHelper::getJsonDecoded();
        }

        ReportModel::addReport(['postID' => $_POST['postID'], 'data' => $aData]);

        $aResponse = [
            'msg' => GetSettings::getOptions('report_thankyou', false, true)
        ];

        return $oRetrieve->success($aResponse);
    }

    public static function isAllowReport()
    {
        $toggle = GetSettings::getOptions('toggle_report', false, true);
        if (empty($toggle) || $toggle == 'disable') {
            return false;
        }

        return true;
    }

    public function getReportFields(\WP_REST_Request $oRequest)
    {
		WPML::cookieCurrentLanguage();
        $isWeb = $oRequest->get_param('isWeb');
        if ($isWeb) {
            $oRetrieve = new RetrieveController(new AjaxRetrieve());
        }

        $msg = esc_html__('Oops! You do not have permission to access this area.', 'wiloke-listing-tools');

        if (!self::isAllowReport()) {
            if ($isWeb) {
                $oRetrieve->error(['msg' => $msg]);
            } else {
                return [
                    'error' => [
                        'userMessage' => $msg,
                        'code'        => 404
                    ]
                ];
            }
        }

        $aRawFields = GetSettings::getOptions('report_fields', false, true);

        if (empty($aRawFields)) {
            $oRetrieve->error([
                'msg' => esc_html__('There are no report fields. Please go to Wiloke Listing Tools -> Reports to create one.',
                    'wiloke-listing-tools')
            ]);
        }

        $aFields = [];
        foreach ($aRawFields as $key => $aField) {
            switch ($aField['type']) {
                case 'text':
                case 'textarea':
                    $aFields[$key]['key'] = $aField['key'];
                    $aFields[$key]['label'] = $aField['label'];
                    $aFields[$key]['type'] = $aField['type'];
                    break;
                case 'select':
                    $aFields[$key]['type'] = $aField['type'];
                    if (empty($aField['options'])) {
                        break;
                    }

                    if ($oRequest->get_param('isWeb')) {
                        $parseOptions = General::parseSelectFieldOptions($aField['options'], 'wil-select-tree');
                        $aFields[$key]['key'] = $aField['key'];
                        $aFields[$key]['label'] = $aField['label'];

                        $aFields[$key]['options'] = array_merge(
                            [
                                'id'    => '',
                                'label' => '----'
                            ],
                            $parseOptions
                        );

                    } else {
                        $parseOptions = explode(',', $aField['options']);
                        $aFields[$key]['key'] = $aField['key'];
                        $aFields[$key]['label'] = $aField['label'];

                        $aFields[$key]['options'][] = '---';
                        foreach ($parseOptions as $option) {
                            $aParsedOption
                                = General::parseCustomSelectOption($option);
                            $aFields[$key]['options'][$aParsedOption['key']] = trim($aParsedOption['name']);
                        }
                    }
                    break;
            }
        }

        if ($isWeb) {
            $oRetrieve->success([
                'fields'      => $aFields,
                'description' => GetSettings::getOptions('report_description', false, true)
            ]);
        }

        return [
            'data' => [
                'fields'      => $aFields,
                'description' => GetSettings::getOptions('report_description', false, true)
            ]
        ];
    }
}
