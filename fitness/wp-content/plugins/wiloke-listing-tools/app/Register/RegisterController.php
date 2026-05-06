<?php

namespace WilokeListingTools\Register;

class RegisterController
{
    private $slug = 'wiloke-listing-settings';
    
    public function congratulationMsg()
    {
        return esc_html__('Congratulations! The settings have been updated successfully', 'wiloke-listing-tools');
    }
    
    protected function validateAjaxPermission()
    {
        if (!current_user_can('administrator')) {
            wp_send_json_error([
                'msg' => esc_html__('You do not have permission to access this page', 'wiloke-listing-tools')
            ]);
        }
        
        if (empty($_POST['postType'])) {
            wp_send_json_error([
                'msg' => esc_html__('The Post Type is required', 'wiloke-listing-tools')
            ]);
        }
    }
    
    protected function unSlashDeep($aVal)
    {
        if (!is_array($aVal)) {
            return stripslashes($aVal);
        }
        
        return array_map([__CLASS__, 'unSlashDeep'], $aVal);
    }
    
    protected function getPostType($hook)
    {
        if (strpos($hook, $this->slug) !== false || $hook == '') {
            return 'listing';
        } else {
            return str_replace(['wiloke-tools_page_', '_settings'], ['', ''], $hook);
        }
    }
}
