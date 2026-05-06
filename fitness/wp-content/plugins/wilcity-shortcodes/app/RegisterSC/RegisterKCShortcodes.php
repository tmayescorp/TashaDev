<?php

namespace WILCITY_SC\RegisterSC;

class RegisterKCShortcodes extends AbstractRegisterShortcodes
{
    public function __construct()
    {
        add_action('init', [$this, 'registerShortcodes'], 99);
    }

    public function registerShortcodes()
    {
        if (function_exists('kc_add_map')) {
            global $kc;

            $aConfiguration = $this->getConfigurations();
            foreach ($aConfiguration as $key => $aScItem) {
                $aParams = $this->prepareShortcodeItem($aScItem['params']);
                if (isset($aParams['general'])) {
                    $aParams['general'][] = [
                        'name' => 'extra_class',
                        'label' => 'Extra Class',
                        'type' => 'text',
                        'admin_label' => true
                    ];

                    $aParams['general'][] = [
                        'name' => 'wrapper_id',
                        'label' => 'Wrapper ID',
                        'description' => 'It must be unique id. It should not contains special character like &,$, space, uppercase',
                        'type' => 'text',
                        'admin_label' => true
                    ];
                }

                if (!isset($aParams['styling'])) {
                    $aParams['styling'] = [
                        [
                            'name' => 'css_custom',
                            'type' => 'css'
                        ]
                    ];
                }

                $aConfiguration[$key]['params'] = $aParams;
            }
            $kc->add_map($aConfiguration);
        }
    }
}
