<?php
namespace WilokeListingTools\Register;

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\Inc;
use WilokeListingTools\Framework\Helpers\SetSettings;

class RegisterClaimSubMenu
{
    protected $aClaimFields = [];
    use ListingToolsGeneralConfig;
    use ParseSection;
    use GetAvailableSections;
    public $slug = 'wiloke-claim-settings';
    protected $aGeneralSettings;
    protected $aUsedSections;
    protected $aAllSections;
    protected $aAvailableSections;
    public static $claimKey = 'claim_settings';
    
    public function __construct()
    {
        add_action('admin_menu', [$this, 'register']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('wp_ajax_wiloke_save_claim_fields_setting', [$this, 'saveClaimFieldsSettings']);
        add_action('admin_init', [$this, 'setDefault']);
        add_action('wilcity/wiloke-listing-tools/app/updated-paid-claim', [$this, 'autoToggleClaimPackageField']);
    }
    
    public function autoToggleClaimPackageField($aData)
    {
        $aClaimFields = GetSettings::getOptions('claim_settings', false, true);
        
        if ($aData['toggle'] === 'disable') {
            if (!empty($aClaimFields)) {
                $aClaimFields = array_filter($aClaimFields, function ($aField) {
                    return $aField['type'] !== 'claimPackage';
                });
                SetSettings::setOptions('claim_settings', $aClaimFields, true);
            }
        } else {
            $hasClaimPackageField = array_filter($aClaimFields, function ($aField) {
                return $aField['type'] == 'claimPackage';
            });
            
            if (empty($hasClaimPackageField)) {
                $aClaimFields[] = [
                    'name'       => 'Label',
                    'key'        => 'claimPackage',
                    'isRequired' => 'yes',
                    'type'       => 'claimPackage',
                    'label'      => 'Package',
                    'options'    => ''
                ];
                SetSettings::setOptions('claim_settings', $aClaimFields, true);
            }
        }
    }
    
    public function setDefault()
    {
        $default  =
            '[{"label":"First Name","key":"first_name","isRequired":"yes","type":"text"},{"label":"Last Name","key":"last_name","isRequired":"yes","type":"text"},{"label":"Phone Number","key":"phone_number","isRequired":"yes","type":"text"},{"label":"Phone Number","key":"phone_number","isRequired":"yes","type":"text"},{"label":"Introducing your self","key":"introduce_your_self","isRequired":"yes","type":"textarea"}]';
        $aOptions = GetSettings::getOptions('claim_settings', false, true);
        if (empty($aOptions)) {
            SetSettings::setOptions('claim_settings', json_decode($default, true), true);
        }
    }
    
    protected function getClaimFields()
    {
        $this->aClaimFields = GetSettings::getOptions(self::$claimKey);
        if (empty($this->aClaimFields) || !is_array($this->aClaimFields)) {
            $this->aClaimFields = wilokeListingToolsRepository()->get('claimfields');
            SetSettings::setOptions(self::$claimKey, $this->aClaimFields);
        }
    }
    
    public function saveClaimFieldsSettings()
    {
        if (!current_user_can('edit_theme_options')) {
            wp_send_json_error([
                'msg' => esc_html__('You do not have permission to access this page', 'wiloke-listing-tools')
            ]);
        }
        
        $aData                = $_POST['data'];
        $hasClaimPackageField = false;
        foreach ($aData as $key => $aSettings) {
            if ($aSettings['type'] == 'claimPackage') {
                if (!$hasClaimPackageField) {
                    $aData[$key]['options'] = '';
                    $hasClaimPackageField   = true;
                } else {
                    unset($aData[$key]);
                }
            }
        }
        
        SetSettings::setOptions('claim_settings', $aData, true);
        
        wp_send_json_success(
            [
                'msg' => esc_html__('Congratulations! This setting has been changed successfully.',
                    'wiloke-listing-tools')
            ]
        );
    }
    
    public function enqueueScripts($hook)
    {
        if (strpos($hook, $this->slug) === false) {
            return false;
        }
        
        $this->requiredScripts();
        $this->generalScripts();
        $this->getClaimFields();
        
        wp_enqueue_script('wiloke-claim-script', WILOKE_LISTING_TOOL_URL.'admin/source/js/claim-script.js', ['jquery'],
            WILOKE_LISTING_TOOL_VERSION, true);
        wp_localize_script('wiloke-claim-script', 'WILOKE_EVENT_GENERAL_SETTINGS', $this->aGeneralSettings);
        
        wp_localize_script('wiloke-claim-script', 'WILOKE_CLAIM_SETTINGS',
            [
                'aFields' => array_values($this->aClaimFields)
            ]
        );
    }
    
    public function settings()
    {
        Inc::file('claim-settings:index');
    }
    
    public function register()
    {
        add_submenu_page($this->parentSlug, 'Claim Settings', 'Claim Settings', 'edit_theme_options', $this->slug,
            [$this, 'settings']);
    }
}
