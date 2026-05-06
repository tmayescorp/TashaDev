<?php

namespace WilokeListingTools\Register;

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\Inc;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\Validation;
use WilokeListingTools\Framework\Helpers\WPML;

class AddCustomPostType
{
    use ListingToolsGeneralConfig;

    public $slug = 'add-post-type';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'register']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('wp_ajax_wiloke_save_custom_posttypes', [$this, 'saveCustomPostTypes']);
        add_action('init', [$this, 'init'], 1);
    }

    public function init()
    {
        $aCustomPostTypes
            = GetSettings::getOptions(wilokeListingToolsRepository()->get('addlisting:customPostTypesKey'));

        if (empty($aCustomPostTypes) || !is_array($aCustomPostTypes)) {
            $this->setDefault();
        }
    }

    public function setDefault()
    {
        $aCustomPostTypes = [
            [
                'key'               => 'listing',
                'slug'              => 'listing',
                'singular_name'     => 'Listing',
                'name'              => 'Listings',
                'addListingLabel'   => 'Add Listing',
                'addListingLabelBg' => '#f06292',
                'deleteAble'        => 'no',
                'keyEditAble'       => 'no',
                'icon'              => ''
            ],
            [
                'key'               => 'event',
                'slug'              => 'event',
                'name'              => 'Events',
                'singular_name'     => 'Event',
                'addListingLabelBg' => '#3ece7e',
                'addListingLabel'   => 'Add Event',
                'deleteAble'        => 'no',
                'keyEditAble'       => 'no',
                'icon'              => ''
            ]
        ];

        SetSettings::setOptions(wilokeListingToolsRepository()->get('addlisting:customPostTypesKey'),
            $aCustomPostTypes, true);
    }

    public function getValue()
    {
        $aCustomPostTypes = GetSettings::getOptions(
            wilokeListingToolsRepository()->get('addlisting:customPostTypesKey'),
            true,
            true
        );
        if (empty($aCustomPostTypes)) {
            $this->setDefault();
        } else {
            foreach ($aCustomPostTypes as $key => $aCustomPostType) {
                if (empty($key)) {
                    continue;
                }
                $aCustomPostTypes[$key]['keyEditAble'] = 'no';
            }
        }

        return $aCustomPostTypes;
    }

    public function register()
    {
        add_submenu_page($this->parentSlug, 'Add Listing Type', 'Add Listing Type', 'edit_theme_options',
            $this->slug, [$this, 'settings']);
    }

    public function settings()
    {
        Inc::file('add-custom-posttype:index');
    }

    public function enqueueScripts($hook)
    {
        if (strpos($hook, $this->slug) === false) {
            return false;
        }

        $aCustomPostTypes = $this->getValue();
        $this->generalScripts();
        $this->requiredScripts();
        //		$this->draggable();

        wp_enqueue_media();
        wp_enqueue_script('spectrum');
        wp_enqueue_style('spectrum');

        wp_enqueue_script('add-custom-posttype', WILOKE_LISTING_TOOL_URL . 'admin/source/js/add-custom-posttype.js',
            ['jquery'], WILOKE_LISTING_TOOL_VERSION, true);
        wp_localize_script('add-custom-posttype', 'WILOKE_CUSTOM_POSTTYPES', array_values($aCustomPostTypes));
    }

    public function saveCustomPostTypes()
    {
        if (!current_user_can('administrator')) {
            wp_send_json_error(
                [
                    'msg' => esc_html__('You do not permission to access this page', 'wiloke-listing-tools')
                ]
            );
        }

        if (empty($_POST['data'])) {
            wp_send_json_error(
                [
                    'msg' => esc_html__('There are no post types', 'wiloke-listing-tools')
                ]
            );
        }

        $aPostTypes = Validation::deepValidation($_POST['data']);
        foreach ($aPostTypes as $order => $aPostType) {
            if (empty($aPostType['key'])) {
                unset($aPostTypes[$order]);
            }

            $aPostTypes[$order]['key'] = preg_replace_callback('/\s+/', function () {
                return '_';
            }, strtolower($aPostType['key']));

            $aPostTypes[$order]['slug'] = preg_replace_callback('/\s+/', function () {
                return '-';
            }, strtolower($aPostType['slug']));

            if (strlen($aPostType['key']) > 20) {
                wp_send_json_error(
                    [
                        'msg' => sprintf(esc_html__('The key %s is longer than 20 character', 'wiloke-listing-tools')
                            , $aPostType['key'])
                    ]
                );
            }
        }

        SetSettings::setOptions(
            wilokeListingToolsRepository()->get('addlisting:customPostTypesKey'),
            $aPostTypes,
            true
        );

        wp_send_json_success([
            'msg' => 'Congratulations! The post type has been added to your site. Now, From the admin sidebar, click on Settings -> Permalinks -> Re-save Post name to update the re-write rule. To setup the plans for this post type, please click on Listing Plans -> Add new -> Create some plans -> Then click on Wiloke Submission -> Add the plans to this post type field.'
        ]);
    }
}
