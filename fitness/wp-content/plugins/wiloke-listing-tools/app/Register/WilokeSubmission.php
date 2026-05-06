<?php

namespace WilokeListingTools\Register;

use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\SemanticUi;
use WilokeListingTools\Framework\Helpers\WPML;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\SetSettings;

class WilokeSubmission
{
    use WilokeSubmissionConfiguration;

    public static  $optionKey = 'wiloke_submission_configuration';
    private static $aDefault  = [];
//	private $aWPMLPageBuilders =[
//	        'become_an_author_page',
//            'listing_plans',
//            'free_claim_listing_plan',
//            'event_plans',
//            'free_claim_event_plan',
//            'renthouse_plans',
//            'free_claim_renthouse_plan',
//            'dashboard_page',
//            'package',
//            'addlisting',
//            'checkout',
//            'thankyou',
//            'cancel'
//    ];

    public function __construct()
    {
        add_action('admin_menu', [$this, 'register']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('wilcity/wiloke-listing-tools/after-plugin-activated', [$this, 'setupDefault']);
    }

    public static function isAddListingMode()
    {
        return !empty(Session::getPaymentObjectID());
    }

    public static function getDefault(): array
    {
        if (!empty(self::$aDefault)) {
            return self::$aDefault;
        }

        $aFieldSettings = apply_filters(
            'wilcity/wiloke-listing-tools/wiloke-submission-fields',
            wilokeListingToolsRepository()->get('wiloke-submission:configuration', true
            )->sub('fields')
        );

        foreach ($aFieldSettings as $aField) {
            if (isset($aField['default'])) {
                $key = str_replace(['wilcity_submission', '[', ']'], ['', '', ''], $aField['name']);
                self::$aDefault[$key] = $aField['default'];
            }
        }

        return self::$aDefault;
    }

    public function setupDefault()
    {
        $aOptions = GetSettings::getOptions(WilokeSubmission::$optionKey, false, true);

        if (empty($aOptions)) {
            SetSettings::setOptions(WilokeSubmission::$optionKey, self::getDefault(), true);
        }
    }

    public function register()
    {
        add_menu_page(esc_html__('Wiloke Submission', 'wiloke-listing-tools'),
            esc_html__('Wiloke Submission', 'wiloke'), 'administrator', 'wiloke-submission', [$this, 'submissionArea'],
            'dashicons-hammer', 29);
    }

    public function enqueueScripts($hook)
    {
        if (strpos($hook, $this->parentSlug) !== false) {
            wp_dequeue_script('semantic-selection-ui');
            wp_register_style('semantic-ui', WILOKE_LISTING_TOOL_URL . 'admin/assets/semantic-ui/form.min.css');
            wp_enqueue_style('semantic-ui');
            wp_register_script('semantic-ui', WILOKE_LISTING_TOOL_URL . 'admin/assets/semantic-ui/semantic.min.js',
                ['jquery'], null, true);
            wp_enqueue_script('semantic-ui');
            wp_enqueue_script('wiloke-submission-general',
                WILOKE_LISTING_TOOL_URL . 'admin/source/js/wiloke-submission-general.js', ['jquery'],
                WILOKE_LISTING_TOOL_VERSION, true);
            wp_register_style('jquery-ui', 'http://code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css');
        }
    }

    public static function isDashboard(): bool
    {
        global $post;
        if (!isset($post->ID)) {
            return false;
        }

        $dashboardID = GetWilokeSubmission::getField('dashboard_page');
        if ($post->ID == $dashboardID) {
            return true;
        }

        if (function_exists('wpml_object_id')) {
            $originalID = wpml_object_id($post->ID, $post->post_type);
            return $originalID == $dashboardID;
        }

        return false;
    }

    public function saveConfiguration()
    {
        if (!current_user_can('administrator')) {
            return false;
        }

        if ((isset($_POST['wilcity_submission']) && !empty($_POST['wilcity_submission'])) &&
            isset($_POST['wiloke_nonce_field']) && !empty($_POST['wiloke_nonce_field']) &&
            wp_verify_nonce($_POST['wiloke_nonce_field'], 'wiloke_nonce_action')) {
            $options = $_POST['wilcity_submission'];

            SetSettings::setOptions(self::$optionKey, maybe_serialize($options), true);

            //Setting some fields same
            if (WPML::isActive()) {
                $aActiveLanguages = WPML::getActiveLanguages();
                $currentLanguage = WPML::getCurrentLanguage();
                if (isset($aActiveLanguages[$currentLanguage])) {
                    unset($aActiveLanguages[$currentLanguage]);
                }
                $aFieldSettings = apply_filters('wilcity/wiloke-listing-tools/wiloke-submission-fields',
                    wilokeListingToolsRepository()->get('wiloke-submission:configuration', true)->sub('fields'));
                $aPermanentFields = [];
                foreach ($aFieldSettings as $aField) {
                    if ($aField['type'] !== 'select_post' && isset($aField['name'])) {
                        $aOptionsKey = str_replace(['wilcity_submission', '[', ']'], ['', '', ''], $aField['name']);
                        $aPermanentFields[$aOptionsKey] = '';

                    }
                }
                if (!empty($aPermanentFields)) {
                    $aPermanentFields = array_intersect_key($options, $aPermanentFields);
                }
                foreach ($aActiveLanguages as $langCode => $params) {
                    $aFields = maybe_unserialize(GetSettings::getOptions(self::$optionKey . '_' . $langCode));
                    $aFields = array_merge($aFields, $aPermanentFields);
                    SetSettings::setOptions(self::$optionKey . '_' . $langCode, maybe_serialize($aFields));
                }
            }
            do_action('wilcity/wiloke-listing-tools/app/updated-wiloke-submission', $_POST);
        }
    }

    public function submissionArea()
    {
        $this->saveConfiguration();
        $aOptions = GetSettings::getOptions(self::$optionKey, true, true);
        $aOptions = maybe_unserialize($aOptions);
        ?>
        <div id="wiloke-submission-wrapper" class="wrap">
            <form class="form ui" action="<?php echo esc_url(admin_url('admin.php?page=' . $this->parentSlug)); ?>"
                  method="POST">
                <?php wp_nonce_field('wiloke_nonce_action', 'wiloke_nonce_field'); ?>
                <?php
                $aCustomPostTypes = \WilokeListingTools\Framework\Helpers\GetSettings::getOptions(
                    wilokeListingToolsRepository()->get('addlisting:customPostTypesKey'), false, true
                );
                $aCustomPostTypes = array_filter($aCustomPostTypes, function ($aInfo) {
                    return ($aInfo['key'] !== 'listing' && $aInfo['key'] !== 'event');
                });
                $isAddedCustomPostType = false;

                $aFieldSettings = apply_filters('wilcity/wiloke-listing-tools/wiloke-submission-fields',
                    wilokeListingToolsRepository()->get('wiloke-submission:configuration', true)->sub('fields'));

                foreach ($aFieldSettings as $aField) {
                    if ($aField['type'] == 'password' || $aField['type'] == 'text' ||
                        $aField['type'] == 'select_post' || $aField['type'] == 'select_ui' ||
                        $aField['type'] == 'select' || $aField['type'] == 'textarea') {
                        $name = str_replace(['wilcity_submission', '[', ']'], ['', '', ''], $aField['name']);
                        $aField['value'] = isset($aOptions[$name]) ? $aOptions[$name] : $aField['default'];
                    }

                    switch ($aField['type']) {
                        case 'open_segment';
                            SemanticUi::renderOpenSegment($aField);
                            break;
                        case 'open_accordion';
                            SemanticUi::renderOpenAccordion($aField);
                            break;
                        case 'open_fields_group';
                            SemanticUi::renderOpenFieldGroup($aField);
                            break;
                        case 'close';
                            SemanticUi::renderClose();
                            break;
                        case 'close_segment';
                            SemanticUi::renderCloseSegment();
                            break;
                        case 'password':
                            SemanticUi::renderPasswordField($aField);
                            break;
                        case 'text';
                            SemanticUi::renderTextField($aField);
                            break;
                        case 'select_post';
                        case 'select_ui';
                            SemanticUi::renderSelectUiField($aField);
                            break;
                        case 'select':
                            SemanticUi::renderSelectField($aField);
                            break;
                        case 'textarea':
                            SemanticUi::renderTextareaField($aField);
                            break;
                        case 'submit':
                            SemanticUi::renderSubmitBtn($aField);
                            break;
                        case 'header':
                            SemanticUi::renderHeader($aField);
                            break;
                        case 'desc';
                            SemanticUi::renderDescField($aField);
                            break;
                    }

                    if (!$isAddedCustomPostType) {
                        if (isset($aField['id']) && $aField['id'] == 'default_event_plans' &&
                            !empty($aCustomPostTypes)) {
                            $isAddedCustomPostType = true;
                            foreach ($aCustomPostTypes as $aCustomPostType) {
                                $planKey = $aCustomPostType['key'] . '_plans';
                                SemanticUi::renderSelectUiField([
                                    'type'      => 'select_post',
                                    'heading'   => sprintf('Plans for %s', $aCustomPostType['name']),
                                    'name'      => 'wilcity_submission[' . $planKey . ']',
                                    'id'        => $planKey,
                                    'post_type' => 'listing_plan',
                                    'multiple'  => true,
                                    'value'     => isset($aOptions[$planKey]) ? $aOptions[$planKey] : '',
                                    'default'   => ''
                                ]);

                                SemanticUi::renderSelectUiField([
                                    'type'      => 'select_post',
                                    'heading'   => sprintf('Default plan for %s', $aCustomPostType['name']),
                                    'desc'      => 'If you are using Free Claim / Downgrade to Default Plan, this setting is required. Once a listing claim is approved, this plan will be assigned to this listing.',
                                    'name'      => 'wilcity_submission[free_claim_' . $aCustomPostType['key'] .
                                        '_plan]',
                                    'id'        => 'free_claim_' . $aCustomPostType['key'] . '_plan',
                                    'post_type' => 'listing_plan',
                                    'multiple'  => true,
                                    'value'     => isset($aOptions['free_claim_' . $aCustomPostType['key'] . '_plan']) ?
                                        $aOptions['free_claim_' . $aCustomPostType['key'] . '_plan'] : '',
                                    'default'   => ''
                                ]);
                            }
                        }
                    }

                }
                ?>
            </form>
        </div>
        <?php
    }
}
