<?php

namespace WilokeListingTools\MetaBoxes;

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Register\General;

class WooCommerce
{
    public function __construct()
    {
        add_action('cmb2_admin_init', [$this, 'registerMetaBoxes']);
        add_filter('cmb2_types_esc_term_ajax_search', [$this, 'modifyEscapeValue'], 10, 4);
    }
    
    public function modifyEscapeValue($output, $metaValue, $aArgs, $that)
    {
        if ($aArgs['id'] === 'wilcity_my_product_cats') {
            return $that->get_default();
        }
        
        return $output;
    }
    
    public static function getIsDokan()
    {
        $postID = isset($_GET['post']) && !empty($_GET['post']) ? $_GET['post'] : '';
        if (empty($postID)) {
            return '';
        }
        
        return GetSettings::getPostMeta($postID, 'is_dokan');
    }
    
    public static function getProductMode($postID = '')
    {
        if (empty($postID)) {
            $postID = isset($_GET['post']) && !empty($_GET['post']) ? $_GET['post'] : '';
        }
        
        if (empty($postID)) {
            return '';
        }
        
        $mode = GetSettings::getPostMeta($postID, 'my_product_mode');
        if (empty($mode)) {
            return 'specify_products';
        }
        
        return $mode;
    }
    
    public static function getIsSendQRCode()
    {
        $postID = isset($_GET['post']) && !empty($_GET['post']) ? $_GET['post'] : '';
        if (empty($postID)) {
            return '';
        }
        
        return GetSettings::getPostMeta($postID, 'is_send_qrcode');
    }
    
    public static function getQRCodeEmailContent()
    {
        $postID = isset($_GET['post']) && !empty($_GET['post']) ? $_GET['post'] : '';
        if (empty($postID)) {
            return '';
        }
        
        return GetSettings::getPostMeta($postID, 'qrcode_description');
    }
    
    public function registerMetaBoxes()
    {
        new_cmb2_box(wilokeListingToolsRepository()->get('woocommerce-metaboxes:metaBoxes'));
        new_cmb2_box(wilokeListingToolsRepository()->get('woocommerce-metaboxes:excludeFromShop'));
    }
}
