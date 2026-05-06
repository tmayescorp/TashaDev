<?php

namespace WilokeListingTools\Register;


use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\Inc;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\WPML;

class RegisterReportSubmenu
{
    use ListingToolsGeneralConfig;

    public $slug = 'report';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'register']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('wp_ajax_wiloke_save_report_settings', [$this, 'saveReportSettings']);
    }

    public function saveReportSettings()
    {
        if (!current_user_can('edit_theme_options')) {
            wp_send_json_error();
        }

        SetSettings::setOptions('toggle_report', $_POST['toggle'], true);
        SetSettings::setOptions('report_description', $_POST['description'], true);
        SetSettings::setOptions('report_fields', $_POST['fields'], true);
        SetSettings::setOptions('report_thankyou', $_POST['thankyou'], true);

        wp_send_json_success();
    }

    public function enqueueScripts($hook)
    {
        if (strpos($hook, $this->slug) === false) {
            return false;
        }
        $this->requiredScripts();
        $this->generalScripts();

        wp_enqueue_script('wiloke-report-script', WILOKE_LISTING_TOOL_URL . 'admin/source/js/report-script.js',
            ['jquery'], WILOKE_LISTING_TOOL_VERSION, true);

        $aFields = GetSettings::getOptions('report_fields', false, true);

        $toggle = GetSettings::getOptions('toggle_report', false, true);
        $desc = GetSettings::getOptions('report_description', false, true);
        $thank = GetSettings::getOptions('report_thankyou', false, true);

        wp_localize_script('wiloke-report-script', 'WILOKE_REPORT',
            [
                'fields'      => empty($aFields) || !is_array($aFields) ? [] : $aFields,
                'toggle'      => empty($toggle) ? 'disable' : $toggle,
                'description' => empty($desc) ?
                    'If you think this content inappropriate and should be removed from our website, please let us know by submitting your reason at the form below.' :
                    $desc,
                'thankyou'    => empty($thank) ?
                    'Thank for reporting the issue. We value your feedback. We will try to deal with your issue as quickly as possible' :
                    $thank
            ]
        );
    }

    public function showReports()
    {
        Inc::file('report-settings:index');
    }

    public function register()
    {
        add_submenu_page($this->parentSlug, 'Reports', 'Reports', 'administrator', $this->slug, [$this, 'showReports']);
    }
}
