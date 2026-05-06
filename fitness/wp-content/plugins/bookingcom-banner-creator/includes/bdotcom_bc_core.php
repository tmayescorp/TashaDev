<?php
/**
 * CORE SCRIPT
 * ----------------------------------------------------------------------------
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/* Localization and internazionalization */
add_action( 'plugins_loaded', 'bdotcom_bc_load_plugin_textdomain' );
function bdotcom_bc_load_plugin_textdomain()
{
    load_plugin_textdomain( 'bookingcom-banner-creator', false, dirname( BDOTCOM_BC_PLUGIN_FILE ) . '/languages/' );
}
/* Create custom post type */
add_action( 'init', 'bdotcom_bc_post_type' );
function bdotcom_bc_post_type()
{
    // set dashicons class for RVM post icon
    $menu_icon = BDOTCOM_BC_DASHICON_CLASS;
    register_post_type( 'bdotcom_bm', array(
        'labels' => array(
            'name' => __( BDOTCOM_BC_PLUGIN_NAME ),
            'singular_name' => __( BDOTCOM_BC_PLUGIN_NAME . ' Singular Name', 'bookingcom-banner-creator' ),
            'add_new' => __( 'Add New Banner', 'bookingcom-banner-creator' ),
            'add_new_item' => __( 'Add a Responsive Banner', 'bookingcom-banner-creator' ),
            'edit_item' => __( 'Edit Banner', 'bookingcom-banner-creator' ),
            'new_item' => __( 'New Banner', 'bookingcom-banner-creator' ),
            'view_item' => __( 'View This Banner', 'bookingcom-banner-creator' ),
            'search_items' => __( 'Search Banner', 'bookingcom-banner-creator' ),
            'not_found' => __( 'No Banner Found', 'bookingcom-banner-creator' ),
            'not_found_in_trash' => __( 'No Banner Found in Trash', 'bookingcom-banner-creator' ),
            'parent_item_colon' => __( 'Parent Banner Colon', 'bookingcom-banner-creator' ),
            'menu_name' => __( 'B.com Banner', 'bookingcom-banner-creator' ) 
        ),
        'description' => __( '{plugin_custompost_descr}', 'bookingcom-banner-creator' ),
        'public' => true,
        'has_archive' => true,
        'menu_position' => 65, //After plugin menu 
        'menu_icon' => $menu_icon,
        'supports' => array(
        'title' 
        ) 
    ) );
}
add_action( 'add_meta_boxes', 'bdotcom_bc_meta_boxes_create' );
function bdotcom_bc_meta_boxes_create()
{
    add_meta_box( 'bdotcom_bc_meta', __( 'Settings For ' . get_the_title(), 'bookingcom-banner-creator' ), 'bdotcom_bc_mb_function', 'bdotcom_bm', 'normal', 'high' );
}
// Save data into DB
add_action( 'save_post', 'bdotcom_bc_save_meta' );
function bdotcom_bc_save_meta( $post_id )
{
    if ( isset( $_POST[ 'bdotcom_bc_mbe_post_id' ] ) && isset( $_POST[ 'bdotcom_bc_ajax_nonce' ] ) && wp_verify_nonce( sanitize_key( $_POST[ 'bdotcom_bc_ajax_nonce' ] ), 'bdotcom_bc_ajax_nonce' ) ) {
        $array_fields = bdotcom_bc_fields_array();
        foreach ( $array_fields as $field ) {
            if ( $field[ 0 ] == 'bdotcom_bc_mbe_button' ) { // if we have a checkbox and is not isset means is unchecked
                if ( !isset( $_POST[ $field[ 0 ] ] ) ) {
                    $field_value = 'no';
                } else {
                    $field_value = 'yes';
                }
            } elseif ( ( $field[ 0 ] == 'bdotcom_bc_mbe_button_border_width' && !is_numeric( $_POST[ $field[ 0 ] ] ) ) ) {
                $field_value = '';
            } elseif ( $field[ 0 ] == 'bdotcom_bc_mbe_aid' && !is_numeric( $_POST[ $field[ 0 ] ] ) ) {
                $field_value = '';
            } elseif ( $field[ 0 ] == 'bdotcom_bc_mbe_label' ) {
                $field_value = sanitize_title( $_POST[ $field[ 0 ] ] );
            } elseif ( $field[ 0 ] == 'bdotcom_bc_mbe_copy' ) {
                $field_value = sanitize_textarea_field( $_POST[ $field[ 0 ] ] );
            } elseif ( $field[ 0 ] == 'bdotcom_bc_mbe_add_css' ) {
                $field_value = bdotcom_bc_force_class_format( sanitize_key( $_POST[ $field[ 0 ] ] ) );
            } elseif ( $field[ 0 ] == 'bdotcom_bc_mbe_banner_link' ) {
                $field_value = esc_url_raw( $_POST[ $field[ 0 ] ] );
            } elseif ( $field[ 0 ] == 'bdotcom_bc_mbe_img_path' && absint( $_POST[ $field[ 0 ] ] ) && realpath( get_attached_file( $_POST[ $field[ 0 ] ] ) ) ) {
                $field_value = $_POST[ $field[ 0 ] ];//Validated, no need for sanitizing
            } else {
                $field_value = sanitize_text_field( $_POST[ $field[ 0 ] ] );
            }
            update_post_meta( $post_id, '_' . $field[ 0 ], $field_value );
        } //$array_fields as $field
    }
}
?>