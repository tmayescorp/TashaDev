<?php

namespace WilokeListingTools\Register\RegisterMenu;

use WilokeListingTools\Controllers\TraitAddListingSettings;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Register\ListingToolsGeneralConfig;
use WilokeListingTools\Register\ParseSection;

class RegisterEventScripts
{
    use ListingToolsGeneralConfig;
    use ParseSection;
    use TraitAddListingSettings;

    protected $aGeneralSettings;
    protected $aEventContent;
    protected $aSearchUsedFields;
    protected $aAvailableSearchFields;
    protected $postType;

    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('admin_init', [$this, 'setDefault']);

        add_action('wp_ajax_wilcity_reset_event_settings', [$this, 'resetDefaultFields']);
        add_action('wp_ajax_wiloke_save_event_general_settings', [$this, 'saveGeneralSettings']);
        add_action('wp_ajax_wilcity_design_fields_for_event', [$this, 'saveUsedAddListingSections']);
        add_action('wp_ajax_wlt_search_event_key', [$this, 'searchKey']);
        add_action('wp_ajax_wlt_save_event_content', [$this, 'saveContent']);
        $this->getEventContent();
    }

    public function congratulationMsg()
    {
        return esc_html__('Congratulations! The settings have been updated successfully', 'wiloke-listing-tools');
    }

    public function setDefaultSearchFields()
    {
        $data = wilokeListingToolsRepository()->get('event-settings:default-fields');
        $aData = json_decode(stripslashes($data), true);
        SetSettings::setOptions(wilokeListingToolsRepository()
            ->get('event-settings:designFields', true)
	        ->sub('usedSectionKey'), $aData['settings'], true);
    }

    protected function getSearchFields($postType = '')
    {
        $this->aSearchUsedFields = GetSettings::getOptions(General::getSearchFieldsKey($this->postType),false,true);
        if (empty($this->aSearchUsedFields) || !is_array($this->aSearchUsedFields)) {
            $this->setDefaultSearchFields();
        }
    }

    protected function getAvailableSearchFields()
    {
        $this->getSearchFields($this->postType);
        $aDefault = wilokeListingToolsRepository()->get('listing-settings:searchFields');

        foreach ($aDefault as $key => $aDefaultField) {
            if ($this->isRemoveField($aDefaultField, $this->postType)) {
                unset($aDefault[$key]);
            }
        }

        if (empty($this->aSearchUsedFields)) {
            $this->aAvailableSearchFields = $aDefault;
        } else {
            $aUsedSearchFieldKeys = array_map(function ($aField) {
                return $aField['key'];
            }, $this->aSearchUsedFields);

            foreach ($aDefault as $aField) {
                if (isset($aField['isCustomField']) || (!in_array($aField['key'], $aUsedSearchFieldKeys))) {
                    $this->aAvailableSearchFields[] = $aField;
                }
            }
        }

        $this->aAvailableSearchFields = empty($this->aAvailableSearchFields) ? [] : $this->aAvailableSearchFields;
    }

    public function saveContent()
    {
        $this->validateAjaxPermission();

        SetSettings::setOptions(General::getEventContentFieldKey($_POST['postType']), $_POST['fields'], true);
        SetSettings::setOptions(General::getUsedSectionSavedAt($_POST['postType'], true),
            current_time('timestamp', true));


        wp_send_json_success([
            'msg' => 'Congratulations! Your settings have been updated'
        ]);
    }

    public function searchKey()
    {
        $aFields = GetSettings::getOptions(General::getEventFieldKey($_GET['postType']), false, true);
        $aData = [];

        foreach ($aFields as $aContent) {
            if (in_array($aContent['key'], [
                'event_calendar',
                'listing_address',
                'event_belongs_to_listing',
                'contact_info',
                'single_price',
                'price_range',
                'listing_title'
            ])) {
                continue;
            }

            if ((isset($aContent['isCustomSection']) && $aContent['isCustomSection'] == 'yes') ||
                ($aContent['type'] == 'group')) {
                $value = $aContent['key'] . '|' . $aContent['type'];
            } else {
                $value = $aContent['key'];
            }

            $aData[] = [
                'name'            => $aContent['heading'],
                'value'           => $value,
                'text'            => $aContent['heading'],
                'isCustomSection' => isset($aContent['isCustomSection']) ? $aContent['isCustomSection'] : 'no'
            ];
        }
        echo json_encode([
            'success' => true,
            'results' => $aData
        ]);
        die();
    }

    public function setDefault()
    {
        if (!isset($_GET['page']) || !General::isPostTypeInGroup($this->getPostType($_GET['page']), 'event')) {
            return false;
        }

        $this->postType = $this->getPostType($_GET['page']);

        $this->aEventContent = GetSettings::getOptions(
            General::getEventContentFieldKey($this->postType), false, true
        );

        if (empty($this->aEventContent) || !is_array($this->aEventContent)) {
	        SetSettings::setOptions(General::getEventContentFieldKey($this->postType), [
		        [
			        'name' => 'Description',
			        'key'  => 'listing_content',
			        'icon' => 'la la-file-text'
		        ]
	        ], true);
        }
    }

    public function getEventContent()
    {
        $this->aEventContent = GetSettings::getOptions(General::getEventContentFieldKey($this->postType), false, true);

        if (empty($this->aEventContent) || !is_array($this->aEventContent)) {
            $this->aEventContent = [
                [
                    'name' => 'Description',
                    'key'  => 'listing_content',
                    'icon' => 'la la-file-text'
                ]
            ];
        }
    }

    public function saveUsedAddListingSections()
    {
        if (!current_user_can('administrator')) {
            wp_send_json_error(
                [
                    'msg' => esc_html__('You do not have permission to access this page', 'wiloke-design-addlisting')
                ]
            );
        }

        $aValues = $_POST['results'];

        SetSettings::setOptions(
	        General::getEventFieldKey($_POST['postType']),
	        $aValues, true
        );

        SetSettings::setOptions(
            General::getUsedSectionSavedAt($this->postType),
            current_time('timestamp', true), true);
        wp_send_json_success(
            [
                'msg' => 'Congrats! Your settings have been updated'
            ]
        );
    }

    public function resetDefaultFields()
    {
        $this->postType = $_POST['postType'];
        $this->setFieldDefaults(true);
        SetSettings::setOptions(
            General::getUsedSectionSavedAt($_POST['postType']),
            current_time('timestamp', true), true
        );

        wp_send_json_success(
            [
                'msg' => 'Congrats! The settings have been reset successfully.'
            ]
        );
    }

    private function setFieldDefaults($isFocus = false)
    {
        if (empty($this->postType) || !General::isPostTypeInGroup($this->postType, 'event')) {
            return false;
        }

        if (!$isFocus) {
            $aUsedSections = GetSettings::getOptions($this->postType, true);

            if (!empty($aUsedSections) && is_array($aUsedSections)) {
                return $aUsedSections;
            }
        }

        $this->getAllSections();

        $aListingTitle = $this->aAllSections['listing_title']['fields'][0];
        $aRawDefaultFields['listing_title'] = $this->aAllSections['listing_title'];
        unset($aRawDefaultFields['listing_title']['fields'][0]);
        $aRawDefaultFields['listing_title']['fields']['listing_title'] = $aListingTitle;

        $aImage = $this->aAllSections['featured_image']['fields'][0];
        $aRawDefaultFields['featured_image'] = $this->aAllSections['featured_image'];
        unset($aRawDefaultFields['featured_image']['fields'][0]);
        $aRawDefaultFields['featured_image']['fields']['featured_image'] = $aImage;

        $aEventCalendar = $this->aAllSections['event_calendar']['fields'][0];
        $aRawDefaultFields['event_calendar'] = $this->aAllSections['event_calendar'];
        unset($aRawDefaultFields['event_calendar']['fields'][0]);
        $aRawDefaultFields['event_calendar']['fields']['event_calendar'] = $aEventCalendar;

        $aDefaultFields[] = $aRawDefaultFields['listing_title'];
        $aDefaultFields[] = $aRawDefaultFields['featured_image'];
        $aDefaultFields[] = $aRawDefaultFields['event_calendar'];

        unset($aRawDefaultFields['listing_title']);
        unset($aRawDefaultFields['featured_image']);
        unset($aRawDefaultFields['event_calendar']);

        SetSettings::setOptions(General::getEventFieldKey($this->postType), $aDefaultFields, true);

        return $aDefaultFields;
    }

    public function saveGeneralSettings()
    {
        if (!current_user_can('administrator')) {
            wp_send_json_error();
        }

        $aOptions = [];
        foreach ($_POST['settings'] as $key => $val) {
            $aOptions[sanitize_text_field($key)] = sanitize_text_field($val);
        }

        SetSettings::setOptions(
	        General::getEventGeneralKey($_POST['postType']),
	        $aOptions, true
        );
        wp_send_json_success();
    }

    public function setupValue()
    {
        $this->aGeneralSettings = GetSettings::getOptions(General::getEventGeneralKey($this->postType),false, true);
        $isSetDef = empty($this->aGeneralSettings);

        $this->aGeneralSettings = wp_parse_args(
            $this->aGeneralSettings,
            General::getEventGeneralKey($this->postType)
        );

        if ($isSetDef) {
	        SetSettings::setOptions(
		        General::getEventGeneralKey($this->postType),
		        $this->aGeneralSettings, true
	        );
        }
    }

    protected function addRequiredSections()
    {
        foreach ($this->aUsedSections as $key => $aSection) {
            if ($aSection['type'] == 'event_calendar' || $aSection['type'] == 'listing_address') {
                $this->aUsedSections[$key]['isNotDeleteAble'] = true;
            }
        }
    }

    public function enqueueScripts($hook)
    {
        $this->postType = $this->getPostType($hook);
        if (General::getPostTypeGroup($this->postType) !== 'event') {
            return false;
        }

        $this->requiredScripts();
        $this->getAvailableSearchFields();
        $this->getAvailableHeroSearchFields();
        $this->setupValue();

        $this->getAddListingData();
        $this->addRequiredSections();
        $this->getAvailableSections();
        $this->getEventContent();

        wp_enqueue_script('wiloke-event-script', WILOKE_LISTING_TOOL_URL .
            'admin/source/js/event-script.js', ['jquery'],
            WILOKE_LISTING_TOOL_VERSION, true);
        wp_localize_script('wiloke-event-script', 'WILOKE_EVENT_GENERAL_SETTINGS', $this->aGeneralSettings);
        wp_localize_script('wiloke-event-script', 'WILOKE_EVENT_CONTENT', $this->aEventContent);

        wp_localize_script(
            'wiloke-event-script',
            'WILOKE_LISTING_TOOLS',
            [
                'group'           => General::getPostTypeGroup($this->postType),
                'postType'        => $this->postType,
                'addListing'      => [
                    'usedSections'      => $this->aUsedSections,
                    'allSections'       => $this->aAllSections,
                    'value'             => !is_array($this->aAddListingSettings) ? [] : $this->aAddListingSettings,
                    'availableSections' => array_values($this->aAvailableSections),
                    'postType'          => $this->postType,
                    'ajaxAction'        => 'wilcity_design_fields_for_event'
                ],
                'settings'        => wilokeListingToolsRepository()->get('event-settings:settings'),
                'aHeroSearchForm' => [
                    'aUsedFields'      => $this->aUsedHeroSearchFields,
                    'aAllFields'       => $this->getAllHeroSearchFields(),
                    'aAvailableFields' => !is_array($this->aAvailableHeroSearchFields) ? [] :
                        $this->aAvailableHeroSearchFields,
                    'ajaxAction'       => 'wilcity_hero_search_fields'
                ],

                'aSearchForm'   => [
                    'aUsedFields'      => $this->aSearchUsedFields,
                    'aAllFields'       => wilokeListingToolsRepository()->get('listing-settings:searchFields'),
                    'aAvailableFields' => !is_array($this->aAvailableSearchFields) ? [] :
                        array_values($this->aAvailableSearchFields),
                    'ajaxAction'       => 'wilcity_search_fields'
                ],
                'aSchemaMarkup' => [
                    'aSettings'  => $this->getSchemaMarkup($this->postType),
                    'ajaxAction' => 'wilcity_save_schema_markup'
                ]
            ]
        );

        $this->generalScripts();
        $this->schemaMarkup();
        $this->designFieldsScript();
        $this->designHeroSearchForm();
        $this->designSearchForm();
    }
}
