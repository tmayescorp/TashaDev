<?php

namespace WilokeListingTools\Register\RegisterMenu;

use WilokeListingTools\Controllers\Retrieve\RestRetrieve;
use WilokeListingTools\Controllers\RetrieveController;
use WilokeListingTools\Controllers\TraitAddListingSettings;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\Inc;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\Submission;
use WilokeListingTools\Framework\Helpers\TermSetting;
use WilokeListingTools\Framework\Helpers\Validation;
use WilokeListingTools\Register\ListingToolsGeneralConfig;
use WilokeListingTools\Register\ParseSection;

class RegisterListingScripts
{
    use ListingToolsGeneralConfig;
    use TraitAddListingSettings;
    use ParseSection;

    //    use GetAvailableSections;
    public static $slug                        = 'wiloke-listing-settings';
    protected     $aUsedSections               = [];
    protected     $aAvailableSections          = [];
    protected     $aReviewSettings             = [];
    protected     $isResetDefault              = false;
    protected     $aCustomPostTypes;
    protected     $aCustomPostTypesKey;
    protected     $aSearchUsedFields;
    protected     $aAvailableSearchFields;
    protected     $aListingCardDefaultBodyKeys = ['google_address', 'phone'];
    public static $aHighlightBoxes;
    protected     $aTaxonomies;
    protected     $postType;

    public function __construct()
    {
        add_action('admin_init', [$this, 'setDefaultReviewSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('wp_ajax_wilcity_design_fields_for_listing', [$this, 'saveUsedAddListingSections']);
        add_action('wp_ajax_save_review_settings', [$this, 'reviewSettings']);
        add_action('wp_ajax_wilcity_design_single_nav', [$this, 'saveDesignSingleNav']);
        add_action('wp_ajax_wilcity_reset_addlisting_settings', [$this, 'resetSettings']);
        add_action('wp_ajax_wilcity_save_highlight_boxes', [$this, 'saveHighlightBoxes']);
        add_action('wp_ajax_wilcity_search_fields', [$this, 'saveSearchFields']);
        add_action('wp_ajax_wilcity_reset_to_default_search_form', [$this, 'resetSearchForm']);
        add_action('wp_ajax_get_listing_post_types', [$this, 'getPostTypes']);

        add_action('wp_ajax_wilcity_hero_search_fields', [$this, 'saveHeroSearchFields']);
        add_action('wp_ajax_wilcity_reset_to_default_hero_search_form', [$this, 'resetHeroSearchFields']);
        add_action('wp_ajax_wilcity_reset_listing_card', [$this, 'resetListingCard']);
        add_action('wp_ajax_wilcity_save_listing_card', [$this, 'saveListingCard']);
        add_action('wp_ajax_wilcity_save_schema_markup', [$this, 'saveSchemaMarkupSettings']);
        add_action('wp_ajax_wilcity_save_schema_markup_reset', [$this, 'resetSchemaMarkupSettings']);
        add_action('wp_print_scripts', [$this, 'dequeueScripts'], 100);

        // Importing
        add_action('wilcity/wiloke-listing-tools/import-demo/setup-search-form', [$this, 'setDefaultSearchFields']);

        add_action('rest_api_init', [$this, 'registerRouter']);
        add_action('wp_ajax_wiloke_load_line_icon', [$this, 'loadLineIcons']);
        add_action('wilcity/wiloke-listing-tools/focus-flush-cache', [$this, 'focusFlushCache']);
        add_action('update_option_active_plugins', [$this, 'focusFlushCache']);
        add_action('upgrader_process_complete', [$this, 'focusFlushCache']);
    }

    /**
     * Fetching Line8 icon
     *
     * @return void
     */
    public function loadLineIcons()
    {
        wp_send_json_success(wilokeListingToolsRepository()->get('line-icon'));
    }

    public function focusFlushCache()
    {
        $aPostTypes = Submission::getListingPostTypes();
        foreach ($aPostTypes as $postType) {
            SetSettings::deleteOption(General::heroSearchFormSavedAt($postType), true);
            SetSettings::deleteOption(General::mainSearchFormSavedAtKey($postType),true);
        }
    }

    public function registerRouter()
    {
        register_rest_route('wiloke/v2', 'taxonomies/options', [
            'callback'            => [$this, 'fetchTaxonomyOptions'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route('wiloke/v2', 'post-types', [
            'callback'            => [$this, 'getPostTypes'],
            'permission_callback' => '__return_true'
        ]);
    }

    public function getPostTypes(\WP_REST_Request $request)
    {
        $aOptions = [];

        if ($request->get_param('action') == 'listing_post_types') {
            $aPostTypes = General::getPostTypes(false, true);
        } else {
            $aPostTypes = General::getPostTypes(true, false);
        }

        foreach ($aPostTypes as $postType => $aPostType) {
            $aOptions[] = [
                'value' => $postType,
                'name'  => $aPostType['name'],
                'text'  => $aPostType['name']
            ];
        }

        $oRetrieve = new RetrieveController(new RestRetrieve());

        return $oRetrieve->success([
            'results' => $aOptions
        ]);
    }

    public function fetchTaxonomyOptions(\WP_REST_Request $oRequest)
    {
        $oRetrieve = new RetrieveController(new RestRetrieve());
        $postType = $oRequest->get_param('post_type');
        $postType = empty($postType) ? 'all' : $postType;
        if (isset($this->aTaxonomies[$postType]) && !empty($this->aTaxonomies[$postType])) {
            return $oRetrieve->success($this->aTaxonomies[$postType]);
        }

        $aTaxonomies = TermSetting::getListingTaxonomies($postType);

        if (empty($aTaxonomies)) {
            return $oRetrieve->error(['msg' => 'There is no custom taxonomies']);
        }

        $aResponse = [];
        foreach ($aTaxonomies as $taxonomy => $aTaxonomy) {
            $aResponse['results'][] = [
                'name'  => isset($aTaxonomy['singular_label']) ? $aTaxonomy['singular_label'] :
                    $aTaxonomy['labels']['singular_name'],
                'value' => $taxonomy,
                'text'  => isset($aTaxonomy['singular_label']) ? $aTaxonomy['singular_name'] :
                    $aTaxonomy['labels']['singular_name']
            ];
        }

        $this->aTaxonomies[$postType] = $aResponse;

        return $oRetrieve->success($aResponse);
    }

    public function setupDefaultHeroSearchFormWhenImporting($postType)
    {
        $this->setDefaultHeroSearchFields($postType);
    }

    public function congratulationMsg()
    {
        return esc_html__('Congratulations! The settings have been updated successfully', 'wiloke-listing-tools');
    }

    public function reviewSettings()
    {
        if (!current_user_can('edit_theme_options')) {
            wp_send_json_error(
                [
                    'msg' => esc_html__('You do not have permission to access this page', 'wiloke-listing-tools')
                ]
            );
        }

        $aData = $_POST['value'];

        SetSettings::setOptions(General::getReviewKey('toggle', $_POST['postType']), $aData['toggle'], true);
        SetSettings::setOptions(General::getReviewKey('toggle_gallery', $_POST['postType']), $aData['toggle_gallery'], true);
        SetSettings::setOptions(General::getReviewKey('mode', $_POST['postType']), $aData['mode'], true);
        SetSettings::setOptions(
            General::getReviewKey('toggle_review_discussion', $_POST['postType']),
	        $aData['toggle_review_discussion'], true
        );
        SetSettings::setOptions(
            General::getReviewKey('is_immediately_approved', $_POST['postType']),
            $aData['is_immediately_approved'], true
        );

        if (isset($aData['details'])) {
            foreach ($aData['details'] as $key => $aDetail) {
                $aData['details'][$key]['isEditable'] = 'disable';
                $aData['details'][$key]['key'] = trim(strtolower($aDetail['key']));
            }

            SetSettings::setOptions(General::getReviewKey('details', $_POST['postType']), self::unSlashDeep($aData['details']), true);
        } else {
            SetSettings::setOptions(General::getReviewKey('details', $_POST['postType']), [], true);
        }

        do_action('wilcity/wiloke-listing-tools/app/Register/RegisterMenu/RegisterListingScripts/after/saved-review',
            $aData, $_POST['postType']);

        wp_send_json_success(
            [
                'msg' => esc_html__('Congratulations! This setting has been changed successfully.',
                    'wiloke-listing-tools')
            ]
        );
    }

    protected function getReviewSettings($postType)
    {
        $this->aReviewSettings['toggle']
            = GetSettings::getOptions(General::getReviewKey('toggle', $postType), false, true);
        $this->aReviewSettings['toggle_gallery']
            = GetSettings::getOptions(General::getReviewKey('toggle_gallery', $postType), false, true);
        $this->aReviewSettings['toggle_review_discussion']
            = GetSettings::getOptions(General::getReviewKey('toggle_review_discussion', $postType), false, true);
        $this->aReviewSettings['mode']
            = GetSettings::getOptions(General::getReviewKey('mode', $postType), false, true);
        $this->aReviewSettings['details']
            = GetSettings::getOptions(General::getReviewKey('details', $postType), false, true);
        $this->aReviewSettings['is_immediately_approved']
            = GetSettings::getOptions(General::getReviewKey('is_immediately_approved', $postType), false, true);

        $this->aReviewSettings['toggle']
            = empty($this->aReviewSettings['toggle']) ? 'disable' : $this->aReviewSettings['toggle'];
        $this->aReviewSettings['toggle_gallery']
            = empty($this->aReviewSettings['toggle_gallery']) ? 'disable' : $this->aReviewSettings['toggle_gallery'];
        $this->aReviewSettings['toggle_review_discussion']
            = empty($this->aReviewSettings['toggle_review_discussion']) ? 'disable' :
            $this->aReviewSettings['toggle_review_discussion'];

        $this->aReviewSettings['is_immediately_approved']
            = empty($this->aReviewSettings['is_immediately_approved']) ? 'yes' :
            $this->aReviewSettings['is_immediately_approved'];

        if (empty($this->aReviewSettings['mode'])) {
            $this->aReviewSettings['mode'] = 5;
            SetSettings::setOptions(General::getReviewKey('mode', $postType), 5, true);
        }

        if (!$this->aReviewSettings['details']) {
            $this->aReviewSettings['details'] = [
                [
                    'name'       => 'Overall',
                    'key'        => 'overall',
                    'isEditable' => 'disable'
                ]
            ];
        }
    }

    protected function getDefaultField($aField)
    {
        if ($aField['type'] != 'group') {
            $aValue['value'] = $aField['value'];
            $aValue['type'] = $aField['type'];

            return $aValue;
        } else {
            $aValues = array_map([$this, 'getDefaultFields'], [$aField['fields']]);

            return $aValues[0];
        }
    }

    public function getDefaultFields($aFields)
    {
        $aFieldValues = [];
        foreach ($aFields as $aField) {
            $aFieldValues[$aField['key']] = $this->getDefaultField($aField);
        }

        return $aFieldValues;
    }

    public function saveDesignSingleNav()
    {
        $this->validateAjaxPermission();

        if (isset($_POST['isReset']) && $_POST['isReset'] == 'yes') {
            $this->setSingleContentDefault();
            $msg = $this->congratulationMsg();
        } else {
            $aNavOrder = [];

            foreach ($_POST['data'] as $aItem) {
                $aNavOrder[$aItem['key']] = self::unSlashDeep($aItem);
            }
            SetSettings::setOptions(General::getSingleListingSettingKey('navigation', $_POST['postType']), $aNavOrder,true);
            $msg = $this->congratulationMsg();
        }

        wp_send_json_success([
            'msg' => $msg
        ]);
    }

    protected function setSingleContentDefault()
    {
        $aDefault = wilokeListingToolsRepository()->get('listing-settings:navigation', true)->sub('draggable');
        $postType = General::detectPostTypeSubmission();
        $postType = $this->getPostType($postType);
        SetSettings::setOptions(General::getSingleListingSettingKey('navigation', $postType), $aDefault,true);
    }

    public function setDefaultReviewSettings()
    {
        $postType = General::detectPostTypeSubmission();
        $postType = $this->getPostType($postType);

        if (!empty(GetSettings::getOptions(General::getReviewKey('details', $postType), false, true))) {
            return false;
        }

        SetSettings::setOptions(General::getReviewKey('details', $postType), [
            [
                'name'       => 'Overall',
                'key'        => 'overall',
                'isEditable' => 'disable'
            ]
        ], true);
    }

    public function setDefaultAddListingSettings()
    {
        $postType = General::detectPostTypeSubmission();
        $postType = $this->getPostType($postType);

	    if (empty(GetSettings::getOptions(General::getSingleListingSettingKey('navigation', $postType), false, true))) {
            $this->setSingleContentDefault();
        }

        if (!$this->isResetDefault) {
            $aAddListingSettings = GetSettings::getOptions(General::getUsedSectionKey($postType), false, true);

            if (empty($aAddListingSettings) || !is_array($aAddListingSettings)) {
                $this->setDefaultAddListing($postType);
            }
        }
    }

    public function setDefaultSearchFields($postType = '')
    {
        if (empty($postType)) {
            if (wp_doing_ajax()) {
                $postType = $_POST['postType'];
            } else {
                $postType = $this->getPostType($postType);
            }
        }

        $aDefaults = wilokeListingToolsRepository()->get('listing-settings:searchFields');
        $aUsedFields = [];
        foreach ($aDefaults as $key => $aField) {
            if (isset($aField['isDefault']) && !$this->isRemoveField($aField, $postType)) {
                $aUsedFields[$key] = $aField;
            }
        }
        SetSettings::setOptions(General::getSearchFieldsKey($postType), $aUsedFields,true);
    }

    protected function getAvailableSearchFields()
    {
        $this->postType = General::detectPostTypeSubmission();
        $this->getSearchFields($this->postType);
        $this->aAvailableSearchFields = wilokeListingToolsRepository()->get('listing-settings:searchFields');

        foreach ($this->aAvailableSearchFields as $key => $aDefaultField) {
            if ($this->isRemoveField($aDefaultField, $this->postType)) {
                unset($this->aAvailableSearchFields[$key]);
            }
        }

        if (is_array($this->aSearchUsedFields)) {
            foreach ($this->aSearchUsedFields as $aUsedField) {
                $originalKey = isset($aUsedField['originalKey']) ? $aUsedField['originalKey'] : $aUsedField['key'];
                if (isset($this->aAvailableSearchFields[$originalKey])) {
                    if (
                        !isset($this->aAvailableSearchFields[$originalKey]['isClone']) ||
                        !$this->aAvailableSearchFields[$originalKey]['isClone'] === true
                    ) {
                        unset($this->aAvailableSearchFields[$originalKey]);
                    }
                }
            }
        }

        $this->aAvailableSearchFields = !is_array($this->aAvailableSearchFields) ? [] : $this->aAvailableSearchFields;
    }

	/**
	 * @param string $postType
	 */
    protected function getSearchFields($postType = '')
    {
        if (empty($postType)) {
            $postType = General::detectPostTypeSubmission();
        }

        $this->aSearchUsedFields = GetSettings::getOptions(General::getSearchFieldsKey($postType),false,true);

        if (empty($this->aSearchUsedFields) || !is_array($this->aSearchUsedFields)) {
            $this->setDefaultSearchFields($postType);
        }
    }

    public function testResetDefault()
    {
        $this->setDefaultAddListing('listing');
    }

    protected function setDefaultAddListing($postType)
    {
        $settings = wilokeListingToolsRepository()->get('default-addlisting:' . $postType);
        $aSettings = json_decode($settings, true);


        SetSettings::setOptions(General::getUsedSectionKey($postType), $aSettings['settings'], false, true);
    }

    public function resetSettings()
    {
        $this->validateAjaxPermission();
        $this->isResetDefault = true;
        $this->setDefaultAddListing($_POST['postType']);

        SetSettings::setOptions(
            General::getUsedSectionSavedAt($_POST['postType']),
            current_time('timestamp', true), true
        );

        wp_send_json_success(
            [
                'msg' => esc_html__('Congrats! This setting has been reset successfully', 'wiloke-design-addlisting')
            ]
        );
    }

    private function cleanOptions(?array $aOptions): array {
        if (empty($aOptions)) {
            return [];
        }

        $aCleanOptions = [];
        foreach ($aOptions as $order => $aField) {
            if (!isset($aField['key'])) {
                unset($aOptions[$order]);
                continue;
            }


            // remove duplicate field
            $aCleanOptions[$aField['key']] = $aField;
        }

        return array_values($aCleanOptions);
    }

    public function saveUsedAddListingSections()
    {
        $this->validateAjaxPermission();

        if (!isset($_POST['results']) || empty($_POST['results'])) {
            SetSettings::deleteOption(General::getUsedSectionKey($_POST['postType']), true);
        } else {
            $aValues = $_POST['results'];
            SetSettings::setOptions(General::getUsedSectionKey($_POST['postType']), $this->cleanOptions($aValues), true);
        }

        SetSettings::setOptions(
            General::getUsedSectionSavedAt($_POST['postType']),
	        current_time('timestamp', true), true
        );
        wp_send_json_success(
            [
                'msg' => $this->congratulationMsg()
            ]
        );

        if (!current_user_can('administrator')) {
            wp_send_json_error(
                [
                    'msg' => esc_html__('You do not have permission to access this page', 'wiloke-design-addlisting')
                ]
            );
        }
    }

    public function saveHighlightBoxes()
    {
        $this->validateAjaxPermission();
        SetSettings::setOptions(
            General::getSingleListingSettingKey('highlightBoxes', $_POST['postType']),
           self::unSlashDeep( $_POST['data']), true
        );
        wp_send_json_success(
            [
                'msg' => $this->congratulationMsg()
            ]
        );
    }

    public function saveSearchFields()
    {
	    $this->validateAjaxPermission();

	    $savedAt = current_time('timestamp', 1);
	    SetSettings::setOptions(General::getSearchFieldsKey($_POST['postType']), self::unSlashDeep($_POST['data']), true);
	    SetSettings::setOptions(General::mainSearchFormSavedAtKey($_POST['postType']), $savedAt, true);

	    if (isset($_POST['toggle'])) {
		    SetSettings::setOptions(General::getSearchFieldToggleKey($_POST['postType']), $_POST['toggle'], true);
	    }

	    do_action(
		    'wilcity/wiloke-listing-tools/app/Register/RegisterListingSettings/savedSearchFields',
		    [
			    'fields'    => $_POST['data'],
			    'timestamp' => $savedAt,
			    'action'    => 'update_search_fields',
			    'postType'  => $_POST['postType']
		    ],
		    General::getSearchFieldsKey($_POST['postType'])
	    );

	    wp_send_json_success(
		    [
			    'msg' => 'Congratulations! Your search form has been designed successfully'
		    ]
	    );
    }

    public function resetSearchForm()
    {
        $this->validateAjaxPermission();
        $this->setDefaultSearchFields();
        wp_send_json_success(
            [
                'msg' => $this->congratulationMsg()
            ]
        );
    }

    public static function getHighlightBoxes($postType)
    {
        if (!empty(self::$aHighlightBoxes)) {
            return self::$aHighlightBoxes;
        }

        self::$aHighlightBoxes
            = GetSettings::getOptions(General::getSingleListingSettingKey('highlightBoxes', $postType), false, true);

        if (empty(self::$aHighlightBoxes) || !is_array(self::$aHighlightBoxes)) {
            self::$aHighlightBoxes = [];
            self::$aHighlightBoxes['aItems'] = [];
            self::$aHighlightBoxes['isEnable'] = 'no';
            self::$aHighlightBoxes['itemsPerRow'] = 'col-md-4 col-lg-4';
        } else {
            if (!isset(self::$aHighlightBoxes['aItems'])) {
                self::$aHighlightBoxes['aItems'] = [];
            }
        }

        self::$aHighlightBoxes['ajaxAction'] = 'wilcity_save_highlight_boxes';

        return self::$aHighlightBoxes;
    }

    private function setDefaultBodyListingCard($postType)
    {
        $aDefaultCard = wilokeListingToolsRepository()->get('listing-settings:listingCard', true)->sub('aBodyItems');
        $aDefault = array_filter($aDefaultCard, function ($aItem) {
            return in_array($aItem['key'], $this->aListingCardDefaultBodyKeys);
        });
        SetSettings::setOptions(General::getSingleListingSettingKey('card', $postType), self::unSlashDeep($aDefault), true);

        $aFooterSettings = wilokeListingToolsRepository()->get('listing-settings:listingCard', true)->sub('aFooter');
        SetSettings::setOptions(General::getSingleListingSettingKey('footer_card', $postType), self::unSlashDeep($aFooterSettings), true);

        return $aDefault;
    }

    public function resetListingCard()
    {
        $this->validateAjaxPermission();
        $aDefault = $this->setDefaultBodyListingCard($_POST['postType']);
        wp_send_json_success([
            'msg'   => 'Congratulations! The Listing Card settings have been reset successfully',
            'aData' => $aDefault
        ]);
    }

    public function saveListingCard()
    {
        $this->validateAjaxPermission();
        if (empty($_POST['value'])) {
            wp_send_json_error(['msg' => 'Please add 1 field at least']);
        }

        if (!empty($_POST['value']['body']) && is_array($_POST['value']['body'])) {
            SetSettings::setOptions(
                General::getSingleListingSettingKey('card', $_POST['postType']),
                Validation::deepValidation($_POST['value']['body']), true
            );
        }

        if (!empty($_POST['value']['footer']) && is_array($_POST['value']['footer'])) {
            SetSettings::setOptions(
                General::getSingleListingSettingKey('footer_card', $_POST['postType']),
                Validation::deepValidation($_POST['value']['footer']), true
            );
        }

        if (!empty($_POST['value']['header']) && is_array($_POST['value']['header'])) {
            SetSettings::setOptions(
                General::getSingleListingSettingKey('header_card', $_POST['postType']),
                Validation::deepValidation($_POST['value']['header']), true
            );
        }

        wp_send_json_success(['msg' => 'Congratulations! The Listing Card settings have been saved successfully']);
    }

    public function saveSchemaMarkupSettings()
    {
        $this->validateAjaxPermission();
        if (isJson($_POST['data'])) {
            SetSettings::setOptions(General::getSchemaMarkupKey($_POST['postType']), json_encode($_POST['data']));
        } else {
            SetSettings::setOptions(General::getSchemaMarkupKey($_POST['postType']), []);
        }

        SetSettings::setOptions(General::getSchemaMarkupSavedAtKey($_POST['postType']), current_time('timestamp', 1));
        wp_send_json_success(['msg' => 'Congratulations! The Schema Markup Setting has been saved successfully']);
    }

    public function resetSchemaMarkupSettings()
    {
        $settings = $this->setDefaultSchemaMarkup($_POST['postType']);

        wp_send_json_success([
            'msg'      => 'The Schema Markup has been reset successfully. Please re-fresh the browser to update the setting area.',
            'settings' => $settings
        ]);
    }

    protected function getUsedBodyListingCard()
    {
        $postType = General::detectPostTypeSubmission();
        $postType = $this->getPostType($postType);
        $aUsedListingCardBody = GetSettings::getOptions(General::getSingleListingSettingKey('card', $postType), false, true);

        if (!is_array($aUsedListingCardBody)) {
            $aUsedListingCardBody = $this->setDefaultBodyListingCard($postType);
        }

        return $aUsedListingCardBody;
    }

    public function enqueueScripts($hook)
    {
        $this->postType = General::detectPostTypeSubmission();
        if (empty($this->postType) || General::getPostTypeGroup($this->postType) !== 'listing') {
            return false;
        }

        $this->generalScripts();
        $this->requiredScripts();

        $this->getSearchFields($this->postType);
        $this->getAvailableSearchFields();
        $this->getAvailableHeroSearchFields();

        $this->getAddListingData();
        self::getHighlightBoxes($this->postType);
        $this->getReviewSettings($this->postType);

        $this->aSearchUsedFields
            = !empty($this->aSearchUsedFields) ? self::unSlashDeep($this->aSearchUsedFields) : [];
        $this->aUsedHeroSearchFields
            = !empty($this->aUsedHeroSearchFields) ? self::unSlashDeep($this->aUsedHeroSearchFields) : [];
        $this->aReviewSettings = !empty($this->aReviewSettings) ? self::unSlashDeep($this->aReviewSettings) : [];
        self::$aHighlightBoxes = !empty(self::$aHighlightBoxes) ? self::unSlashDeep(self::$aHighlightBoxes) : [];

        $aListingCard['body'] = $this->getUsedBodyListingCard();
        $aListingCard['footer']
            = GetSettings::getOptions(General::getSingleListingSettingKey('footer_card', $this->postType), false, true);

        if (!is_array($aListingCard['footer'])) {
            $aListingCard['footer'] = [
                'taxonomy' => 'listing_cat'
            ];
        }

        $aListingCard['header'] = GetSettings::getOptions(General::getSingleListingSettingKey('header_card',
	        $this->postType), false, true);

        if (!is_array($aListingCard['header'])) {
            $aListingCard['header'] = [
                'btnAction' => 'total_views'
            ];
        }

        $aListingCardTypes = array_keys(wilokeListingToolsRepository()
            ->get('listing-settings:listingCard', true)
            ->sub('bodyFields'));
        $aListingCardTypes = array_combine($aListingCardTypes, $aListingCardTypes);
        $aPromotionPlans = GetSettings::getOptions('promotion_plans', false, true);

        $this->designListingSettings();
        wp_localize_script(
            'listing-settings',
            'WILOKE_LISTING_TOOLS',
            apply_filters(
                'wilcity/filter/wiloke-listing-tools/listing-settings',
                [
                    'group'              => General::getPostTypeGroup($this->postType),
                    'PRODUCT_ASSETS_URL' => WILOKE_LISTING_TOOL_URL . 'admin/source/js/',
                    'postType'           => $this->postType,
                    'restURL'            => rest_url('wiloke/v2/'),
                    'addListing'         => [
                        'usedSections'      => $this->aUsedSections,
                        'availableSections' => array_values($this->aAvailableSections),
                        'value'             => !is_array($this->aAddListingSettings) ? [] :
                            array_values($this->aAddListingSettings),
                        'allSections'       => $this->aAllSections,
                        'ajaxAction'        => 'wilcity_design_fields_for_listing'
                    ],
                    'reviewSettings'     => $this->aReviewSettings,
                    'aSearchForm'        => [
                        'aUsedFields'      => $this->aSearchUsedFields,
                        //              'aUsedFields'      => $this->aSearchUsedFields,
                        'aAllFields'       => wilokeListingToolsRepository()->get('listing-settings:searchFields'),
                        'aAvailableFields' => !is_array($this->aAvailableSearchFields) ? [] :
                            array_values($this->aAvailableSearchFields),
                        'ajaxAction'       => 'wilcity_search_fields',
                        'toggle'           => GetSettings::getOptions(General::getSearchFieldToggleKey
                        ($this->postType), true, false, 'enable')
                    ],
                    'aHeroSearchForm'    => [
                        'aUsedFields'      => $this->aUsedHeroSearchFields,
                        'aAllFields'       => $this->getAllHeroSearchFields(),
                        'aAvailableFields' => !is_array($this->aAvailableHeroSearchFields) ? [] :
                            $this->aAvailableHeroSearchFields,
                        'ajaxAction'       => 'wilcity_hero_search_fields'
                    ],
                    'aBoxes'             => self::$aHighlightBoxes,
                    'listingCards'       => [
                        'value'      => $aListingCard,
                        'settings'   => [
                            'header' => [
                                'options' => wilokeListingToolsRepository()
                                    ->get('listing-settings:listingCard', true)
                                    ->sub('aButtonInfoOptions')
                            ],
                            'body'   => [
                                'fields'     => wilokeListingToolsRepository()
                                    ->get('listing-settings:listingCard', true)
                                    ->sub('bodyFields'),
                                'fieldTypes' => $aListingCardTypes
                            ]
                        ],
                        'ajaxAction' => 'wilcity_save_listing_card'
                    ],
                    'aSchemaMarkup'      => [
                        'aSettings'  => $this->getSchemaMarkup($this->postType),
                        'ajaxAction' => 'wilcity_save_schema_markup'
                    ],
                    'promotions'         => !is_array($aPromotionPlans) ? [] : $aPromotionPlans,
                    'orderOptions'       => [
                        [
                            'name'  => 'DESC',
                            'value' => 'DESC'
                        ],
                        [
                            'name'  => 'ASC',
                            'value' => 'ASC'
                        ]
                    ]
                ],
                $this
            )
        );
    }
}
