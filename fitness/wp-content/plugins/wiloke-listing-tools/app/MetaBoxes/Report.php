<?php

namespace WilokeListingTools\MetaBoxes;

use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;

class Report
{
    public function __construct()
    {
        add_action('cmb2_admin_init', [$this, 'renderMetaboxFields']);
    }
    
    public function renderMetaboxFields()
    {
        $aReports = wilokeListingToolsRepository()->get('report');
        
        $aAdditionalFields = GetSettings::getOptions('report_fields', false, true);
        
        if (!empty($aAdditionalFields)) {
            foreach ($aAdditionalFields as $aField) {
                if ($aField['key'] === 'content') {
                    continue;
                }
                
                switch ($aField['type']) {
                    case 'text':
                        $aReports['report_information']['fields'][] = [
                            'type' => 'text',
                            'id'   => 'wilcity_'.$aField['key'],
                            'name' => $aField['label']
                        ];
                        break;
                    case 'textarea':
                        $aReports['report_information']['fields'][] = [
                            'type' => 'textarea',
                            'id'   => 'wilcity_'.$aField['key'],
                            'name' => $aField['label']
                        ];
                        break;
                    case 'select':
                        if (!empty($aField['options'])) {
                            $aSelectField                               = [
                                'type' => 'select',
                                'id'   => 'wilcity_'.$aField['key'],
                                'name' => $aField['label']
                            ];
                            $aSelectField['options']                    =
                                General::parseSelectFieldOptions($aField['options']);
                            $aReports['report_information']['fields'][] = $aSelectField;
                        }
                        
                        break;
                }
            }
        }
        
        new_cmb2_box($aReports['report_information']);
        new_cmb2_box($aReports['report_my_note']);
    }
}
