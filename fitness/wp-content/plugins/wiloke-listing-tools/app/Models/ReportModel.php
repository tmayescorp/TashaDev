<?php

namespace WilokeListingTools\Models;

use WilokeListingTools\Framework\Helpers\Collection\ArrayCollection;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Validation;

class ReportModel
{
    public static function isEnable()
    {
        return GetSettings::getOptions('toggle_report', false, true);
    }
    
    public static function addReport($aData)
    {
        $oArray = new ArrayCollection($aData);
        $title  = $oArray->deepPluck('data->post_title')->output(
            sprintf(
                esc_html__('Report an issue of %s', 'wiloke-listing-tools'),
                get_the_title($aData['postID'])
            )
        )
        ;
        
        $aArgs = [
            'post_type'   => 'report',
            'post_status' => 'pending',
            'post_title'  => $title
        ];
        
        if (isset($aData['data']['content'])) {
            $aArgs['post_content'] = Validation::deepValidation($aData['data']['content']);
            unset($aData['data']['content']);
        }
        
        $reportID = wp_insert_post($aArgs);
        
        SetSettings::setPostMeta($reportID, 'listing_name', $aData['postID']);
        
        $aFields = GetSettings::getOptions('report_fields', false, true);
        if (empty($aFields)) {
            return true;
        }
        
        foreach ($aFields as $aField) {
            if (isset($aData['data'][$aField['key']]) && !empty($aData['data'][$aField['key']])) {
                SetSettings::setPostMeta($reportID, $aField['key'],
                    Validation::deepValidation($aData['data'][$aField['key']]));
            }
        }
        
        do_action('wilcity/submitted-report', $aData['postID'], $reportID);
        
        return true;
    }
}
