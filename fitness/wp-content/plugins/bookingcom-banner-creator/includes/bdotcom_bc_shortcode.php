<?php
/**
 * SHORTCODE SECTION
 * ----------------------------------------------------------------------------
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

add_shortcode( 'bdotcom_bm', 'bdotcom_bc_shortcode' );
function bdotcom_bc_shortcode( $attr )
{
    return bdotcom_bc_variables( basename( __FILE__ ), $attr[ 'bannerid' ] );
} //function bdotcom_bc_shortcode( $attr )