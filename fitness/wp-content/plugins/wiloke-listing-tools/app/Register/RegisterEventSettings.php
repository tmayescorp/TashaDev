<?php

namespace WilokeListingTools\Register;

use WilokeListingTools\Controllers\TraitAddListingSettings;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\Inc;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\General;

class RegisterEventSettings
{
    use ListingToolsGeneralConfig;
    use ParseSection;
    //    use GetAvailableSections;
    use TraitAddListingSettings;
    public static $slug = 'wiloke-event-settings';
    public static $forPostType = 'event';
    protected $aGeneralSettings;
    protected $aEventContent;
    protected $aSearchUsedFields;
    protected $aAvailableSearchFields;
    protected $postType;
    protected $listingType = 'event';
    public $aExcludeSearchFields = ['price_range', 'best_rated'];

    public function __construct()
    {
        add_action('wp_ajax_wiloke_save_event_general_settings', [$this, 'saveGeneralSettings']);
        add_action('wp_ajax_wilcity_design_fields_for_event', [$this, 'saveUsedAddListingSections']);
        add_action('wp_ajax_wilcity_reset_event_settings', [$this, 'resetDefaultFields']);
        add_action('admin_init', [$this, 'setDefault']);
        add_action('wp_ajax_wlt_search_event_key', [$this, 'searchKey']);
        add_action('wp_ajax_wlt_save_event_content', [$this, 'saveContent']);
    }

    public function congratulationMsg()
    {
        return esc_html__('Congratulations! The settings have been updated successfully', 'wiloke-listing-tools');
    }

    public function setDefaultSearchFields()
    {
        $data  = wilokeListingToolsRepository()->get('event-settings:default-fields');
        $aData = json_decode(stripslashes($data), true);
        SetSettings::setOptions(wilokeListingToolsRepository()
            ->get('event-settings:designFields', true)
            ->sub('usedSectionKey'), $aData['settings'], true);
    }

    protected function getSearchFields($postType = '')
    {
        $this->aSearchUsedFields = GetSettings::getOptions(General::getSearchFieldsKey(self::$forPostType),false,true);
        if (empty($this->aSearchUsedFields) || !is_array($this->aSearchUsedFields)) {
            $this->setDefaultSearchFields();
        }
    }

    protected function getAvailableSearchFields()
    {
        $this->getSearchFields(self::$forPostType);
        $aDefault = wilokeListingToolsRepository()->get('listing-settings:searchFields');

        foreach ($aDefault as $key => $aDefaultField) {
            if ($this->isRemoveField($aDefaultField, self::$forPostType)) {
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
        SetSettings::setOptions('event_content_fields', $_POST['fields'], true);

        SetSettings::setOptions(General::getUsedSectionSavedAt('event'), current_time('timestamp', true), true);

        wp_send_json_success([
            'msg' => 'Congratulations! Your settings have been updated'
        ]);
    }

    public function searchKey()
    {
        $aFields = GetSettings::getOptions(wilokeListingToolsRepository()
            ->get('event-settings:designFields', true)
            ->sub('usedSectionKey'), false, true);
        $aData   = [];

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
                $value = $aContent['key'].'|'.$aContent['type'];
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
        $this->aEventContent = GetSettings::getOptions('event_content_fields', false, true);
        if (empty($this->aEventContent) || !is_array($this->aEventContent)) {
            SetSettings::setOptions('event_content_fields', [
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
        $this->aEventContent = GetSettings::getOptions('event_content_fields', false, true);
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
        SetSettings::setOptions(wilokeListingToolsRepository()
            ->get('event-settings:designFields', true)
	        ->sub('usedSectionKey'), $aValues, true);
        SetSettings::setOptions(General::getUsedSectionSavedAt('event'), current_time('timestamp', true), true);

        wp_send_json_success(
            [
                'msg' => 'Congrats! Your settings have been updated'
            ]
        );
    }

    public function resetDefaultFields()
    {
        $this->setFieldDefaults(true);
	    SetSettings::setOptions(General::getUsedSectionSavedAt($_POST['postType']), current_time('timestamp',
		    true), true);

        wp_send_json_success(
            [
                'msg' => 'Congrats! The settings have been reset successfully.'
            ]
        );
    }

    private function setFieldDefaults($isFocus = false)
    {
        if (!$isFocus) {
            if (!isset($_GET['page'])) {
                return false;
            }

            $postType = str_replace('_settings', '',  $_GET['page']);
            if (!in_array($postType, General::getPostTypesGroup('event'))) {
                return false;
            }

            $aUsedSections = GetSettings::getOptions(wilokeListingToolsRepository()
                ->get('event-settings:designFields', true)
	            ->sub('usedSectionKey'), true, true);

            if (!empty($aUsedSections) && is_array($aUsedSections)) {
                return $aUsedSections;
            }
        }

        $this->getAllSections();

        $aListingTitle                      = $this->aAllSections['listing_title']['fields'][0];
        $aRawDefaultFields['listing_title'] = $this->aAllSections['listing_title'];
        unset($aRawDefaultFields['listing_title']['fields'][0]);
        $aRawDefaultFields['listing_title']['fields']['listing_title'] = $aListingTitle;

        $aImage                              = $this->aAllSections['featured_image']['fields'][0];
        $aRawDefaultFields['featured_image'] = $this->aAllSections['featured_image'];
        unset($aRawDefaultFields['featured_image']['fields'][0]);
        $aRawDefaultFields['featured_image']['fields']['featured_image'] = $aImage;

        $aEventCalendar                      = $this->aAllSections['event_calendar']['fields'][0];
        $aRawDefaultFields['event_calendar'] = $this->aAllSections['event_calendar'];
        unset($aRawDefaultFields['event_calendar']['fields'][0]);
        $aRawDefaultFields['event_calendar']['fields']['event_calendar'] = $aEventCalendar;

        $aDefaultFields[] = $aRawDefaultFields['listing_title'];
        $aDefaultFields[] = $aRawDefaultFields['featured_image'];
        $aDefaultFields[] = $aRawDefaultFields['event_calendar'];

        unset($aRawDefaultFields['listing_title']);
        unset($aRawDefaultFields['featured_image']);
        unset($aRawDefaultFields['event_calendar']);

        SetSettings::setOptions(wilokeListingToolsRepository()
            ->get('event-settings:designFields', true)
            ->sub('usedSectionKey'), $aDefaultFields, true);

        return $aDefaultFields;
    }

    public function saveGeneralSettings()
    {
        if (!current_user_can('edit_theme_options')) {
            wp_send_json_error();
        }

        $aOptions = [];
        foreach ($_POST['settings'] as $key => $val) {
            $aOptions[sanitize_text_field($key)] = sanitize_text_field($val);
        }

        SetSettings::setOptions(
            wilokeListingToolsRepository()->get('event-settings:keys', true)->sub('general'),
            $aOptions,
            true
        );
        wp_send_json_success();
    }

    public function setupValue()
    {
        $this->aGeneralSettings = GetSettings::getOptions('event_general_settings', true, true);

        $isSetDef = empty($this->aGeneralSettings);

        $this->aGeneralSettings = wp_parse_args(
            $this->aGeneralSettings,
            wilokeListingToolsRepository()->get('event-settings', true)->sub('general')
        );

        if ($isSetDef) {
            SetSettings::setOptions(
                wilokeListingToolsRepository()->get('event-settings:keys', true)->sub('general'),
                $this->aGeneralSettings
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
        if (strpos($hook, self::$slug) === false) {
            return false;
        }

        $this->postType = 'event';
        $this->requiredScripts();
        $this->getAvailableSearchFields();
        $this->getAvailableHeroSearchFields();
        $this->setupValue();

        $this->getAddListingData();
        $this->addRequiredSections();
        $this->getAvailableSections();
        $this->getEventContent();
        wp_enqueue_script('wiloke-event-script', WILOKE_LISTING_TOOL_URL.'admin/source/js/event-script.js', ['jquery'],
            WILOKE_LISTING_TOOL_VERSION, true);
        wp_localize_script('wiloke-event-script', 'WILOKE_EVENT_GENERAL_SETTINGS', $this->aGeneralSettings);
        wp_localize_script('wiloke-event-script', 'WILOKE_EVENT_CONTENT', $this->aEventContent);

        wp_localize_script(
            'wiloke-event-script',
            'WILOKE_LISTING_TOOLS',
            [
                'postType'        => self::$forPostType,
                'addListing'      => [
                    'usedSections'      => $this->aUsedSections,
                    'allSections'       => $this->aAllSections,
                    'value'             => empty($this->aAddListingSettings) ? [] : $this->aAddListingSettings,
                    'availableSections' => array_values($this->aAvailableSections),
                    'postType'          => $this->postType,
                    'ajaxAction'        => 'wilcity_design_fields_for_event'
                ],
                'settings'        => wilokeListingToolsRepository()->get('event-settings:settings'),
                'aHeroSearchForm' => [
                    'aUsedFields'      => $this->aUsedHeroSearchFields,
                    'aAllFields'       => $this->getAllHeroSearchFields(),
                    'aAvailableFields' => empty($this->aAvailableHeroSearchFields) ? [] :
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
                    'aSettings'  => $this->getSchemaMarkup('event'),
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

    public function settings()
    {
        Inc::file('event-settings:index');
    }

    public function register()
    {
        add_submenu_page(
            $this->parentSlug,
            'Event Settings',
            'Event Settings',
            'edit_theme_options',
            self::$slug,
            [$this, 'settings']
        );
    }
}
