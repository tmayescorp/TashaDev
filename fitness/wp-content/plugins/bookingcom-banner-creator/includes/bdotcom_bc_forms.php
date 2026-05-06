<?php
/**
 * CORE SCRIPT
 * ----------------------------------------------------------------------------
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

// Fields input arrays 
function bdotcom_bc_fields_array()
{
    $fields = array();
    /*0  'field name', '
     *  1 input type',  
     * 2 'field label', 
     * 3 'field bonus expl.', '
     *  4 input maxlenght', 
     *  5 'input size', 
     *  6 'required', 
     * 7 'which section belongs to?', '
     * 8 'class' 
     * 9 'default value / placeholder' 
     * */
    $fields[ 'bdotcom_bc_mbe_aid' ] = array(
         'bdotcom_bc_mbe_aid',
        'text',
        __( 'Your affiliate ID', 'bookingcom-banner-creator' ),
        __( 'Your affiliate ID is a unique number that allows Booking.com to track commission. If you are not an affiliate yet, <a href="https://www.booking.com/affiliate-program/v2/index.html" target="_blank">check our affiliate programme</a> and get an affiliate ID. It\'s easy and fast. Start earning money, <a href="https://www.booking.com/affiliate-program/v2/index.html" target="_blank">sign up now!</a>', 'bookingcom-banner-creator' ),
        10,
        10,
        0,
        'main',
        '',
        BDOTCOM_BC_DEFAULT_AID 
    );
    $fields[ 'bdotcom_bc_mbe_button' ] = array(
         'bdotcom_bc_mbe_button',
        'checkbox',
        __( 'Need For a Button?', 'bookingcom-banner-creator' ),
        '',
        '',
        '',
        1,
        'main',
        '',
        BDOTCOM_BC_DEFAULT_BUTTON 
    );
    $fields[ 'bdotcom_bc_mbe_button_copy' ] = array(
         'bdotcom_bc_mbe_button_copy',
        'text',
        __( 'Button Copy', 'bookingcom-banner-creator' ),
        '',
        '',
        '',
        1,
        'main',
        '',
        __( "Book now", 'bookingcom-banner-creator' ) 
    );
    $fields[ 'bdotcom_bc_mbe_themes' ] = array(
         'bdotcom_bc_mbe_themes',
        'hidden',
        __( 'Theme', 'bookingcom-banner-creator' ),
        '',
        '',
        "",
        1,
        'main',
        '',
        BDOTCOM_BC_DEFAULT_THEME 
    );
    $fields[ 'bdotcom_bc_mbe_img_path' ] = array(
         'bdotcom_bc_mbe_img_path',
        'text',
        __( 'Banner Image ( 1920px wide suggested )', 'bookingcom-banner-creator' ),
        __( 'Choose Your Image', 'bookingcom-banner-creator' ),
        '',
        '',
        1,
        'main',
        'bdotcom_bc_mbe_hide_field',
        '' 
    );
    $fields[ 'bdotcom_bc_mbe_copy' ] = array(
         'bdotcom_bc_mbe_copy',
        'textarea',
        __( 'Banner Copy', 'bookingcom-banner-creator' ),
        '',
        '',
        "",
        1,
        'main',
        'bdotcom_bc_textarea',
        __( "Search hotels and more...", 'bookingcom-banner-creator' ) 
    );
    $fields[ 'bdotcom_bc_mbe_add_css' ] = array(
         'bdotcom_bc_mbe_add_css',
        'text',
        __( 'Add Your Class', 'bookingcom-banner-creator' ),
        __( 'Use this area to add your custom CSS class to the banner.', 'bookingcom-banner-creator' ),
        '',
        "",
        1,
        'main',
        '',
        BDOTCOM_BC_DEFAULT_EDIT_CSS 
    );
    $fields[ 'bdotcom_bc_mbe_banner_link' ] = array(
         'bdotcom_bc_mbe_banner_link',
        'text',
        __( 'Banner Link', 'bookingcom-banner-creator' ),
        __( 'Leave blank to link to your landing page on Booking.com. Your affiliate ID will be automatically added to the link - make sure you add it in the &ldquo;affiliate ID&rdquo; field at the top of this page.', 'bookingcom-banner-creator' ),
        "",
        "",
        1,
        'main',
        '',
        __( 'i.e.: https://www.booking.com/city/nl/amsterdam.en-gb.html', 'bookingcom-banner-creator' ) 
    );
    $fields[ 'bdotcom_bc_mbe_label' ] = array(
         'bdotcom_bc_mbe_label',
        'text',
        __( 'Banner label', 'bookingcom-banner-creator' ),
        __( 'Customise your label for reservation tracking. When blank, the label will be set as the page title by default.', 'bookingcom-banner-creator' ),
        '',
        '',
        1,
        'main',
        '',
        'my-page-to-track' 
    );
    return $fields;
}
function bdotcom_bc_mb_function( $post )
{
    $output = ''; //initialize output
    // Get form fields
    $array_fields = bdotcom_bc_fields_array();
    $output .= '<div id="bdotcom_bc_main_settings">';
    $display_field = '';
    $bdotcom_bc_mbe_button = get_post_meta( $post->ID, '_bdotcom_bc_mbe_button', true ); //to display or not the button preferencies
    $bdotcom_bc_mbe_themes = get_post_meta( $post->ID, '_bdotcom_bc_mbe_themes', true ); //image path theme
    $bdotcom_bc_mbe_img_path = get_post_meta( $post->ID, '_bdotcom_bc_mbe_img_path', true ); //custom image path
    //$output .= '$bdotcom_bc_mbe_themes :' . $bdotcom_bc_mbe_themes . '<br>';
    foreach ( $array_fields as $field ) {
        $field_value = get_post_meta( $post->ID, '_' . $field[ 0 ], true );
        //$output .= '$field_value :' . $field_value . '<br>';
        if ( empty( $field_value ) ) {
            $field_value = '';
        }
        if ( empty( $field_value ) && ( $field[ 0 ] != 'bdotcom_bc_mbe_aid' && $field[ 0 ] != 'bdotcom_bc_mbe_banner_link' && $field[ 0 ] != 'bdotcom_bc_mbe_copy' && $field[ 0 ] != 'bdotcom_bc_mbe_add_css' && $field[ 0 ] != 'bdotcom_bc_mbe_label' ) ) {
            $field_value = $field[ 9 ];
        } // default values   ))
        if ( !empty( $field[ 4 ] ) ) {
            $bdotcom_bc_maxlength = 'maxlength="' . $field[ 4 ] . '"';
        } else {
            $bdotcom_bc_maxlength = '';
        }
        if ( !empty( $field[ 5 ] ) ) {
            $bdotcom_bc_mbe_size = 'size="' . $field[ 5 ] . '"';
        } else {
            $bdotcom_bc_mbe_size = '';
        }
        if ( !empty( $field[ 9 ] ) ) {
            $bdotcom_bc_mbe_placeholder = 'placeholder="' . esc_attr( $field[ 9 ] ) . '"';
        } else {
            $bdotcom_bc_mbe_placeholder = '';
        }
        if ( $field[ 7 ] == 'main' ) {                                     
            if ( $field[ 1 ] == 'textarea' ) {
                if ( $field[ 0 ] == 'bdotcom_bc_mbe_copy' ) {
                    $output .= '<p class="' . $field[ 0 ] . '">';
                    $output .= '<label for="' . $field[ 0 ] . '">' . $field[ 2 ] . '</label>';
                    $output .= '<textarea ' . $bdotcom_bc_mbe_placeholder . '  name="' . $field[ 0 ] . '" id="' . $field[ 0 ] . '" class="' . $field[ 8 ] . '" cols="20" rows="5">';
                    $output .= esc_textarea( $field_value );
                    $output .= '</textarea> <span class="bdotcom_bc_mbe_bonus_text">' . $field[ 3 ] . '</span>';
                    $output .= '</p>';
                }
            } // if( $field[ 1 ] == 'textarea' )                     
            if ( $field[ 1 ] == 'text' ) {
                switch ( $field[ 0 ] ) {
                    case 'bdotcom_bc_mbe_add_css':
                        $output .= '<p class="' . $field[ 0 ] . '">';
                        $output .= '<label for="' . $field[ 0 ] . '">' . $field[ 2 ] . '</label>';
                        $output .= '<input  ' . $bdotcom_bc_maxlength . $bdotcom_bc_mbe_size . $bdotcom_bc_mbe_placeholder . '    id="' . $field[ 0 ] . '" class="' . $field[ 8 ] . '" type="' . $field[ 1 ] . '" name="' . $field[ 0 ] . '"  value="' . esc_html( $field_value ) . '" ' . $bdotcom_bc_mbe_size . ' /> <span class="bdotcom_bc_mbe_bonus_text">' . $field[ 3 ] . '</span>';
                        $output .= '</p>';
                        break;
                    case 'bdotcom_bc_mbe_aid':
                        $output .= '<p class="' . $field[ 0 ] . '">';
                        $output .= '<label for="' . $field[ 0 ] . '">' . $field[ 2 ] . '</label>';
                        $output .= '<input ' . $bdotcom_bc_maxlength . $bdotcom_bc_mbe_size . $bdotcom_bc_mbe_placeholder . '  id="' . $field[ 0 ] . '" class="' . $field[ 8 ] . '" type="' . $field[ 1 ] . '" name="' . $field[ 0 ] . '"  value="' . esc_html( $field_value ) . '" /> <span class="bdotcom_bc_mbe_bonus_text">' . $field[ 3 ] . '</span>';
                        $output .= '</p>';
                        break;
                    case 'bdotcom_bc_mbe_button_copy':
                        //Open bdotcom_bc_mbe_button_block
                        if ( empty( $bdotcom_bc_mbe_button ) || $bdotcom_bc_mbe_button === 'yes' ) {
                            $display_field = 'bdotcom_bc_mbe_display_field';
                        } else {
                            $display_field = 'bdotcom_bc_mbe_hide_field';
                        }
                        $output .= '<div id="bdotcom_bc_mbe_button_block" class="' . $display_field . '">';
                        $output .= '<p class="' . $field[ 0 ] . '">';
                        $output .= '<label for="' . $field[ 0 ] . '">' . $field[ 2 ] . '</label>';
                        $output .= '<input  ' . $bdotcom_bc_maxlength . $bdotcom_bc_mbe_size . $bdotcom_bc_mbe_placeholder . '  id="' . $field[ 0 ] . '" class="' . $field[ 8 ] . '" type="' . $field[ 1 ] . '" name="' . $field[ 0 ] . '"  value="' . esc_html( $field_value ) . '" /> <span class="bdotcom_bc_mbe_bonus_text">' . $field[ 3 ] . '</span>';
                        $output .= '</p>';
                        $output .= '</div>';
                        break;
                    case 'bdotcom_bc_mbe_button_border_width':
                        $output .= '<p class="' . $field[ 0 ] . '">';
                        $output .= '<label for="' . $field[ 0 ] . '">' . $field[ 2 ] . '</label>';
                        $output .= '<input ' . $bdotcom_bc_mbe_placeholder . '  size="' . $field[ 5 ] . '" id="' . $field[ 0 ] . '" class="' . $field[ 8 ] . '" type="' . $field[ 1 ] . '" name="' . $field[ 0 ] . '"  value="' . esc_html( $field_value ) . '" /> ' . $field[ 3 ];
                        $output .= '</p>';
                        $output .= '</div>'; //close bdotcom_bc_mbe_button_block as bdotcom_bc_mbe_button_border_width is last field
                        break;
                    case 'bdotcom_bc_mbe_banner_link':
                        $output .= '<p class="' . $field[ 0 ] . '">';
                        $output .= '<label for="' . $field[ 0 ] . '">' . $field[ 2 ] . '</label>';
                        $output .= '<input  ' . $bdotcom_bc_maxlength . $bdotcom_bc_mbe_size . $bdotcom_bc_mbe_placeholder . '   id="' . $field[ 0 ] . '" class="' . $field[ 8 ] . '" type="' . $field[ 1 ] . '" name="' . $field[ 0 ] . '"  value="' . esc_url( $field_value ) . '" /> <span class="bdotcom_bc_mbe_bonus_text">' . $field[ 3 ] . '</span>';
                        $output .= '</p>';
                        break;
                    case 'bdotcom_bc_mbe_img_path':
                        $output .= '<p id="' . $field[ 0 ] . '_wrapper" class="' . $field[ 8 ] . '">';
                        $output .= '<span class="' . $field[ 0 ] . '_uploader"><label for="' . $field[ 0 ] . '">' . $field[ 2 ] . '</label><input  ' . $bdotcom_bc_maxlength . $bdotcom_bc_mbe_size . $bdotcom_bc_mbe_placeholder . '  id="' . $field[ 0 ] . '" name="' . $field[ 0 ] . '"  value="' . $field_value . '"  type="hidden" />
                                                        <input id="bdotcom_bc_mbe_img_uploader_button" class="bdotcom_bc_mbe_img_uploader_button button-secondary" name="bdotcom_bc_mbe_img_uploader_button" type="submit" value="' . esc_html( $field[ 3 ] ) . '" /></span>';
                        $output .= '</p>';
                        break;
                    case 'bdotcom_bc_mbe_label':
                        $output .= '<p class="' . $field[ 0 ] . '">';
                        $output .= '<label for="' . $field[ 0 ] . '">' . $field[ 2 ] . '</label>';
                        $output .= BDOTCOM_BC_DEFAULT_LABEL . '<input ' . $bdotcom_bc_mbe_placeholder . '  size="' . $field[ 5 ] . '" id="' . $field[ 0 ] . '" class="' . $field[ 8 ] . '" type="' . $field[ 1 ] . '" name="' . $field[ 0 ] . '"  value="' . esc_html( $field_value ) . '" /> <span class="bdotcom_bc_mbe_bonus_text">' . $field[ 3 ] . '</span>';
                        $output .= '</p>';
                        break;
                    default:
                        $output .= '<p class="' . $field[ 0 ] . '">';
                        $output .= '<label for="' . $field[ 0 ] . '">' . $field[ 2 ] . '</label>';
                        $output .= '<input  ' . $bdotcom_bc_maxlength . $bdotcom_bc_mbe_size . $bdotcom_bc_mbe_placeholder . '    id="' . $field[ 0 ] . '" class="' . $field[ 8 ] . '" type="' . $field[ 1 ] . '" name="' . $field[ 0 ] . '"  value="' . esc_html( $field_value ) . '" ' . $bdotcom_bc_mbe_size . ' /> <span class="bdotcom_bc_mbe_bonus_text">' . $field[ 3 ] . '</span>';
                        $output .= '</p>';
                }
            } // if( $field[ 1 ] == 'text' )
            if ( $field[ 1 ] == 'hidden' ) {
                if ( $field[ 0 ] == 'bdotcom_bc_mbe_themes' ) {
                    $output .= '<p class="' . $field[ 0 ] . '">';
                    $output .= '<label for="' . $field[ 0 ] . '">' . $field[ 2 ] . '</label>';
                    $output .= '<input ' . $bdotcom_bc_mbe_placeholder . '  size="' . $field[ 5 ] . '" id="' . $field[ 0 ] . '" class="' . $field[ 8 ] . '" type="' . $field[ 1 ] . '" name="' . $field[ 0 ] . '"  value="' . esc_html( $field_value ) . '" /></span>';
                    $output .= '<input type="button" id="bdotcom_bc_show_defaults_themes" class="button-primary"  value="' . __( 'Default Themes', 'bookingcom-banner-creator' ) . '" />';
                    /*Display the Your Theme button only if user have min editor previleges */
                    if( current_user_can( 'edit_posts' ) ) {
                        $output .= ' <input type="button" id="bdotcom_bc_custom_theme" class="button-primary"  value="' . __( 'Your Theme', 'bookingcom-banner-creator' ) . '" />';
                    }                    
                    $output .= '</p>';
                    $output .= '<span id="bdotcom_bc_theme_preview">';
                    if ( !empty( $bdotcom_bc_mbe_themes ) && $bdotcom_bc_mbe_themes === 'custom_theme' ) {
                        if ( wp_get_attachment_image( $bdotcom_bc_mbe_img_path ) ) {
                            $bdotcom_bc_mbe_img_path = wp_get_attachment_image( $bdotcom_bc_mbe_img_path, 'thumbnail', false, array(
                                 'class' => 'bdotcom_bc_thumbnail_displayed',
                                'alt' => 'Search for accommodations'
                            ) );
                            $output .= $bdotcom_bc_mbe_img_path;
                        } else {
                            //For legacy banners
                            $output .= '<img src="' . $bdotcom_bc_mbe_img_path . '" class="bdotcom_bc_thumbnail_displayed" alt="Search for accommodations">';
                        }
                    } else {
                        $bdotcom_bc_default_image_paths_array = bdotcom_bc_default_image_paths();
                        foreach ( $bdotcom_bc_default_image_paths_array as $bdotcom_bc_default_image_item ) {
                            if ( $bdotcom_bc_mbe_themes == $bdotcom_bc_default_image_item[ 0 ] ) {
                                $bdotcom_bc_banner_image = $bdotcom_bc_default_image_item[ 3 ];
                                break;
                            } else {
                                $bdotcom_bc_banner_image = "https://r.bstatic.com/data/sp_aff/" . BDOTCOM_BC_DEFAULT_AID . "/bdotcom_hotel_theme_1" . BDOTCOM_BC_DEFAULT_THUMBNAIL . ".jpg";
                            }
                        }
                        $output .= '<img src="' . $bdotcom_bc_banner_image . '" class="bdotcom_bc_thumbnail_displayed">';
                    }
                    $output .= '</span>';
                    $output .= '<div id="bdotcom_bc_default_themes_box" class="bdotcom_bc_mbe_hide_field">';
                    $output .= '<div id="bdotcom_bc_default_themes_box_black_overlay"></div>';
                    $output .= '<div id="bdotcom_bc_default_themes_box_images">';
                    $bdotcom_bc_default_image_paths_array = bdotcom_bc_default_image_paths();
                    foreach ( $bdotcom_bc_default_image_paths_array as $bdotcom_bc_default_image_item ) {
                        /*$bdotcom_bc_default_image_selected = ( empty( $bdotcom_bc_mbe_themes ) && ( $bdotcom_bc_default_image_item[0] == 'hotel_theme_1' )  ) ? "selected='selected'" : '';*/
                        $output .= '<img data-theme="' . $bdotcom_bc_default_image_item[ 0 ] . '" class="bdotcom_bc_thumbnail" id="' . $bdotcom_bc_default_image_item[ 0 ] . '" src="' . $bdotcom_bc_default_image_item[ 3 ] . '" alt="Deafult theme images">';
                    }
                    $output .= '</div>';
                    $output .= '</div>';
                }
            } // $if( $field[ 1 ] == 'hidden')                                    
            if ( $field[ 1 ] == 'checkbox' ) {
                if ( $field[ 0 ] == 'bdotcom_bc_mbe_button' ) {
                    $output .= '<p>';
                    $output .= '<label for="' . $field[ 0 ] . '">' . $field[ 2 ] . '</label>';
                    $output .= '<input id="' . $field[ 0 ] . '" type="' . $field[ 1 ] . '" name="' . $field[ 0 ] . '"   ' . checked( 'yes', $field_value, false ) . ' /> <span class="bdotcom_bc_mbe_bonus_text">' . $field[ 3 ] . '</span>';
                    $output .= '</p>';
                }
            } //$field[1] == 'checkbox' 
            //$output .= $field[ 3 ];  
        } //if( $field[ 7 ] == 'main' )                  
    } //foreach( $array_fields as $field )
    $output .= '<input type="hidden"  id="bdotcom_bc_mbe_post_id" name="bdotcom_bc_mbe_post_id" value="' . $post->ID . '" />';
    $output .= '<input type="button" id="bdotcom_bc_mbe_preview_button" class="button-primary"  value="' . __( 'Preview', 'bookingcom-banner-creator' ) . '" />';
    $output .= '<div id="bdotcom_bc_mbe_preview_wrapper"></div>'; //banner will be loaded here via ajax 
    $output .= '<div id="bdotcom_bc_shortcode" class="updated"><p>' . __( 'Use following shortcode to display the banner in posts/pages:', 'bookingcom-banner-creator' ) . ' <strong><span id="bdotcom_bc_shortcode_to_copy">[bdotcom_bm bannerid="' . $post->ID . '"]</span></strong> .</p></div>';
    $output .= '</div>';
    // close id="bdotcom_bc_main_settings" ;
    /**************** End: Main settings *****************/
    //create nonce for ajax call    
    //$output .= '<span id="bdotcom_bc_ajax_nonce" class="hidden" style="visibility: hidden;">' . wp_create_nonce( 'bdotcom_bc_ajax_nonce' ) . '</span>';            
    $output .= '<input id="bdotcom_bc_ajax_nonce" name="bdotcom_bc_ajax_nonce" type="hidden" value="' . wp_create_nonce( 'bdotcom_bc_ajax_nonce' ) . '">';
    echo wp_kses( $output, bdotcom_bc_allowed_tags() ); // echo the fields
} // function bdotcom_bc_mb_function( $post )
// Adding custom columns to display maps list
//this works before 3.1
add_filter( 'manage_bdotcom_bm_posts_columns', 'add_new_bdotcom_bm_posts_columns' );
function add_new_bdotcom_bm_posts_columns( $columns )
{
    $columns[ 'cb' ] = '<input type="checkbox" />';
    $columns[ 'title' ] = esc_html__( 'Banner', 'bookingcom-banner-creator' );
    $columns[ 'shortcode' ] = esc_html__( 'Shortcode', 'bookingcom-banner-creator' );
    $columns[ 'date' ] = esc_html__( 'Date', 'bookingcom-banner-creator' );
    return $columns;
}
//Populate shortcodes column
add_action( 'manage_bdotcom_bm_posts_custom_column', 'populate_bdotcom_bm_posts_custom_columns', 10, 2 );
function populate_bdotcom_bm_posts_custom_columns( $columns, $post_id )
{
    if( is_numeric( $post_id ) ) {
        switch ( $columns ) {
            case 'shortcode':
                echo '[bdotcom_bm bannerid="' . esc_html( $post_id ) . '"]';
                break;
        } //$columns
    }

}

?>