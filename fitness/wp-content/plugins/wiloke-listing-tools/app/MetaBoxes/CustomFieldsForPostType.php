<?php

namespace WilokeListingTools\MetaBoxes;

use WilokeListingTools\Framework\Helpers\AddListingFieldSkeleton;
use WilokeListingTools\Framework\Helpers\Collection\ArrayCollection;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Validation;
use WilokeListingTools\Framework\Routing\Controller;

class CustomFieldsForPostType extends Controller
{
    private $aUsedSections;
    private $aGroups;
    private $postType;
    private $aGroupKeys;
    private $groupPrefix  = 'wilcity_group_';
    private $aGroupValues = [];
    private $aGroupItems  = ['items', 'group_title', 'group_description', 'group_icon'];

    public function __construct()
    {
        add_action('cmb2_admin_init', [$this, 'registerCustomBoxes']);
        add_action('cmb2_admin_init', [$this, 'registerGroups']);

        //        add_action('update_post_meta', [$this, 'saveGroup']);
        add_action('init', [$this, 'saveGroup'], 1);
        add_action('init', [$this, 'getGroupFields'], 1);
        add_action('init', function () {
            if (!empty($this->aGroups)) {
                foreach ($this->aGroups as $groupKey => $aGroup) {
                    foreach ($this->aGroupItems as $groupItem) {
                        add_filter(
                            'cmb2_override_' . $this->groupPrefix . $groupKey . '[' . $groupItem . ']_meta_value',
                            [$this, 'getGroupValues'],
                            10,
                            4
                        );
                    }
                }
            }
        });
    }

    public function getGroupValues($value, $objectID, $args, $field)
    {
        preg_match('/\[([a-zA-Z0-9_]+)\]/', $args['field_id'], $aMatches);
        if (!isset($aMatches[1])) {
            if (WP_DEBUG) {
                throw new \Exception('The group field is is invalid, it should not contain special characters');
            }
        }
        $fieldKey = $aMatches[1];
        $groupKey = str_replace([$aMatches[0]], [''], $args['field_id']);
        $this->aGroupValues[$groupKey] = GetSettings::getPostMeta($objectID, $groupKey);
        if (empty($this->aGroupValues[$groupKey])) {
            return '';
        }

        return isset($this->aGroupValues[$groupKey][$fieldKey]) ? $this->aGroupValues[$groupKey][$fieldKey] : '';
    }

    public function getGroupFields()
    {
        if (!isset($_GET['post'])) {
            return [];
        }

        $oAddListingFieldSkeleton = new AddListingFieldSkeleton(get_post_type($_GET['post']));
        $aFields = $oAddListingFieldSkeleton->getFields();

        if (!is_array($aFields)) {
            return [];
        }

        $this->aGroups = array_filter($aFields, function ($aField) {
            return isset($aField['type']) && $aField['type'] === 'group';
        });
    }

    private function getUsedSections()
    {
        if ($this->aUsedSections) {
            return $this->aUsedSections;
        }

        if (empty($this->postType)) {
            $this->postType = General::detectPostTypeSubmission();
        }

        if (empty($this->postType)) {
            return false;
        }

        $this->aUsedSections = GetSettings::getOptions(General::getUsedSectionKey($this->postType), false, true);
        if (empty($this->aUsedSections)) {
            return false;
        }

        return $this->aUsedSections;
    }

    protected function determineCmbType($aField)
    {
        if ($aField['key'] === 'listing_type_relationships') {
            return 'listing_type_relationships';
        }

        switch ($aField['type']) {
            case 'date-time':
                $cmbType = 'wilcity_date_time';
                break;
            case 'wil-uploader':
            case 'wil-upload-file':
                $cmbType = $aField['maximum'] > 1 ? 'file_list' : 'file';
                break;
            case 'wil-select-tree':
                $cmbType = $aField['maximum'] > 1 ? 'multicheck' : 'select';
                break;
            case 'wil-multiple-checkbox':
                $cmbType = 'multicheck_inline';
                break;
            case 'wil-input':
                $cmbType = 'text';
                break;
            default:
                $cmbType = str_replace('wil-', '', $aField['type']);
                break;
        }

        return $cmbType;
    }

    private function getFileTypes($fileTypes)
    {
        $aFileTypes = explode(",", $fileTypes);
        return array_map(function ($fileType) {
            $fileType = trim($fileType);
            if (in_array($fileType, ['png', 'jpg', 'jpeg', 'gif', 'svg'])) {
                return "image/" . $fileType;
            }

            return "application/" . $fileType;
        }, $aFileTypes);
    }

    /**
     * Auto generate custom cmb 2 field
     *
     * @param $fieldInfo
     * @param $aCustomSection
     *
     * @return array
     */
    protected function generateField($fieldInfo, $aCustomSection): array
    {
        $prefix = wilokeListingToolsRepository()->get('addlisting:customMetaBoxPrefix');
        switch ($cmbType = $this->determineCmbType($fieldInfo)) {
            case 'select':
            case 'multicheck':
            case 'multicheck_inline':
                $aOptions = General::parseSelectFieldOptions($fieldInfo['options']);

                $aField = [
                    'type'    => $cmbType,
                    'id'      => $prefix . $aCustomSection['key'],
                    'name'    => $fieldInfo['label'],
                    'options' => in_array($cmbType, ['multicheck', 'multicheck_inline']) ? $aOptions :
                        array_merge(['' => '----'], $aOptions)
                ];
                break;
            case 'listing_type_relationships':
                $isMultiple = isset($fieldInfo['maximum']) && $fieldInfo['maximum'] > 1;
                $aField = [
                    'name'        => $fieldInfo['label'],
                    'type'        => 'select2_posts',
                    'description' => $fieldInfo['desc'] ?? '',
                    'attributes'  => [
                        'ajax_action' => 'wilcity_fetch_listing_type',
                        'post_types'  => $fieldInfo['post_types'],
                        'post_status' => 'publish'
                    ],
                    'id'          => $prefix . $aCustomSection['key'],
                    'multiple'    => $isMultiple
                ];
                break;
            case 'file':
            case 'file_list':
                $aField = [
                    'id'   => $prefix . $aCustomSection['key'] . '_' . $fieldInfo['key'],
                    'name' => $fieldInfo['label'] ?? '',
                    'type' => $cmbType
                ];

                if (isset($fieldInfo['allowed_extension'])) {
                    $aField['query_args'] = [
                        'type' => $this->getFileTypes($fieldInfo['allowed_extension'])
                    ];
                }
                break;
            case 'wil-datepicker':
            case 'datepicker':
                if (isset($fieldInfo['showTimePanel']) && $fieldInfo['showTimePanel'] == 'yes') {
                    $dateType = 'text_datetime_timestamp';
                } else {
                    $dateType = 'text_date_timestamp';
                }

                $aField = [
                    'type' => $dateType,
                    'id'   => $prefix . $aCustomSection['key'],
                    'name' => $fieldInfo['label'] ?? ''
                ];
                break;
            default:
                $aField = [
                    'id'   => $prefix . $aCustomSection['key'],
                    'name' => $fieldInfo['label'] ?? '',
                    'type' => $cmbType
                ];
                break;
        }

        return $aField;
    }

    private function getGroups()
    {
        if (empty($this->aUsedSections)) {
            $this->getUsedSections();
        }

        if (empty($this->aUsedSections)) {
            return false;
        }

        if (!empty($this->aGroups)) {
            return $this->aGroups;
        }

        $this->aGroups = array_filter(
            $this->aUsedSections,
            function ($aSection) {
                if (isset($aSection['type']) && $aSection['type'] == 'group') {
                    return true;
                }

                return false;
            }
        );
    }

    private function getGroupKeys()
    {
        if (!empty($this->aGroupKeys)) {
            return $this->aGroupKeys;
        }

        $this->aGroupKeys = array_map(function ($aGroup) {
            return $aGroup['key'];
        }, $this->aGroups);
    }

    public function saveGroup()
    {
        if (!$this->isAdminEditing()) {
            return false;
        }

        $this->getGroups();
        if (empty($this->aGroups)) {
            return false;
        }

        $this->getGroupKeys();
        foreach ($this->aGroupKeys as $groupKey) {
            $groupKey = $this->groupPrefix . $groupKey;

            if (isset($_POST[$groupKey]) && !empty($_POST[$groupKey])) {
                $val = Validation::deepValidation($_POST[$groupKey]);
                SetSettings::setPostMeta($_POST['post_ID'], $groupKey, $val);
            } else {
                SetSettings::deletePostMeta($_POST['post_ID'], $groupKey);
            }
        }
    }

    public function registerGroups()
    {
        if (empty($this->aGroups)) {
            return false;
        }

        $aCommon = [
            'id'           => 'wilcity_custom_settings',
            'title'        => 'My Group',
            'object_types' => [$this->postType], // Post type
            'context'      => 'normal',
            'save_fields'  => false,
            'priority'     => 'low',
            'show_names'   => true, // Show field names on the left
        ];

        foreach ($this->aGroups as $aGroup) {
            if (!isset($aGroup['fieldGroups']) || empty($aGroup['fieldGroups'])) {
                continue;
            }
            $aCommon['id'] = $this->groupPrefix . $aGroup['key'];
            $aCommon['title'] = $aGroup['heading'];

            $instCMB2 = new_cmb2_box($aCommon);
            $fieldGroupOrder = 0;
            foreach ($aGroup['fieldGroups'] as $aFieldInfo) {
                if (!isset($aFieldInfo['fieldsSkeleton']) || !is_array($aFieldInfo['fieldsSkeleton'])) {
                    continue;
                }

                $idPrefix = $this->groupPrefix . $aGroup['key'];
                $instCMB2->add_field(
                    [
                        'name' => $aFieldInfo['groupTitleLabel'],
                        'type' => 'text',
                        'id'   => $idPrefix . '[group_title]',
                        //                                                'default' => isset($aValue['group_title']) ? $aValue['group_title'] : ''
                    ]
                );

                $instCMB2->add_field(
                    [
                        'name' => $aFieldInfo['groupDescriptionLabel'],
                        'type' => 'text',
                        'id'   => $idPrefix . '[group_description]',
                        //                        'default' => isset($aValue['group_description']) ? $aValue['group_description'] : ''
                    ]
                );

                $instCMB2->add_field(
                    [
                        'name' => $aFieldInfo['groupIconLabel'],
                        'type' => 'text',
                        'id'   => $idPrefix . '[group_icon]',
                        //                        'default' => isset($aValue['group_icon']) ? $aValue['group_icon'] : ''
                    ]
                );

                $groupFieldID = $instCMB2->add_field(
                    [
                        'id'         => $idPrefix . '[items]',
                        'type'       => 'group',
                        'repeatable' => true, // use false if you want non-repeatable group
                        'options'    => [
                            'group_title'   => __('Entry {#}', 'wiloke-listing-tools'),
                            // since version 1.1.4, {#} gets replaced by row number
                            'add_button'    => __('Add Another Entry', 'wiloke-listing-tools'),
                            'remove_button' => __('Remove Entry', 'wiloke-listing-tools'),
                            'sortable'      => true
                        ],
                        //                        'default'    => $aValue['items'][$fieldGroupOrder]
                    ]
                );

                foreach ($aFieldInfo['fieldsSkeleton'] as $order => $aFieldSkeleton) {
                    $aField = $this->generateField($aFieldSkeleton, $aGroup);
                    $aField['id'] = $aFieldSkeleton['key'];
                    $instCMB2->add_group_field($groupFieldID, $aField);
                }
                $fieldGroupOrder++;
            }
        }
    }

    public function registerCustomBoxes()
    {
        $aUsedSections = $this->getUsedSections();

        if (empty($aUsedSections) || !is_array($aUsedSections)) {
            return false;
        }

        $aCustomSections = array_filter(
            $aUsedSections,
            function ($aSection) {
                if (
                    isset($aSection['isCustomSection']) &&
                    ($aSection['isCustomSection'] === true || $aSection['isCustomSection'] == 'yes')
                ) {
                    return true;
                }

                return false;
            }
        );

        if (!empty($aCustomSections)) {
            foreach ($aCustomSections as $aCustomSection) {
                if (isset($aCustomSection['key']) && empty($aCustomSection['key'])) {
                    continue;
                }

                if (isset($aCustomSection['isGroup']) && $aCustomSection['isGroup']) {
                    continue;
                }

                $instCMB2 = new_cmb2_box(
                    [
                        'id'           => $aCustomSection['key'],
                        'title'        => $aCustomSection['heading'],
                        'object_types' => [$this->postType], // Post type
                        'context'      => 'normal',
                        'priority'     => 'low',
                        'show_names'   => true, // Show field names on the left
                    ]
                );

                if (isset($aCustomSection['fieldGroups']) && !empty($aCustomSection['fieldGroups'])) {
                    foreach ($aCustomSection['fieldGroups'] as $fieldInfo) {
                        $field = $this->generateField($fieldInfo, $aCustomSection);

                        if ($aCustomSection['type'] == 'image' && $fieldInfo['key'] === 'link_to') {
                            $field['id'] = $field['id'] . '_' . $fieldInfo['key'];
                        }

                        $instCMB2->add_field($field);
                    }
                }
            }
        }
    }
}
