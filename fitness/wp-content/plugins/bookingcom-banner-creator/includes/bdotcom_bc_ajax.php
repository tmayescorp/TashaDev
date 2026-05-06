<?php
/**
 * AJAX CALLS
 * ----------------------------------------------------------------------------
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/* Theme  Preview */
add_action( 'wp_ajax_bdotcom_bc_theme_preview', 'bdotcom_bc_ajax_theme_preview' );
function bdotcom_bc_ajax_theme_preview()
{
    if ( isset( $_REQUEST[ 'bdotcom_bc_ajax_nonce' ] ) && wp_verify_nonce( wp_unslash( sanitize_key( $_POST[ 'bdotcom_bc_ajax_nonce' ] ) ), 'bdotcom_bc_ajax_nonce' ) ) {
        $bdotcom_bc_mbe_themes = sanitize_text_field( $_REQUEST[ 'bdotcom_bc_mbe_themes' ] );
        $bdotcom_bc_mbe_img_id = sanitize_text_field( $_REQUEST[ 'bdotcom_bc_mbe_img_id' ] );
        $output = '';
        if ( !empty( $bdotcom_bc_mbe_themes ) && $bdotcom_bc_mbe_themes === 'custom_theme' ) {
            if ( wp_get_attachment_image( $bdotcom_bc_mbe_img_id ) ) {
                $bdotcom_bc_mbe_img_path = wp_get_attachment_image( $bdotcom_bc_mbe_img_id, 'thumbnail', false, array(
                    'class' => 'bdotcom_bc_thumbnail_displayed',
                    'alt' => 'Custom Theme' 
                ) );
                $output .= $bdotcom_bc_mbe_img_path;
            }
        } else {
            //Default theme
            $output .= '<img src="' . $bdotcom_bc_mbe_themes . '" class="bdotcom_bc_thumbnail_displayed">';
        }
        die( wp_kses_post( $output ) );
    } //if ( isset( $_REQUEST[ 'bdotcom_bc_ajax_nonce' ] ) && wp_verify_nonce( $_POST[ 'bdotcom_bc_ajax_nonce' ], 'bdotcom_bc_ajax_nonce' ) ) {
    else {
        die( esc_html__( 'Issues with theme preview generation', 'bookingcom-banner-creator' ) );
    }
}
/* Banner Preview */
add_action( 'wp_ajax_bdotcom_bc_preview', 'bdotcom_bc_ajax_preview' );
function bdotcom_bc_ajax_preview()
{
    if ( isset( $_REQUEST[ 'bdotcom_bc_ajax_nonce' ] ) && wp_verify_nonce( sanitize_key( $_POST[ 'bdotcom_bc_ajax_nonce' ] ), 'bdotcom_bc_ajax_nonce' ) ) {
        //retrieve all values from form fields using the function
        $output = bdotcom_bc_variables( basename( __FILE__ ), sanitize_text_field( $_REQUEST[ 'bdotcom_bc_mbe_post_id' ] ) );
        die( wp_kses_post( $output ) );
    } //if ( isset( $_REQUEST[ 'bdotcom_bc_ajax_nonce' ] ) && wp_verify_nonce( $_POST[ 'bdotcom_bc_ajax_nonce' ], 'bdotcom_bc_ajax_nonce' ) ) {
    else {
        die( esc_html__( 'Issues with banner preview generation', 'bookingcom-banner-creator' ) );
    }
}