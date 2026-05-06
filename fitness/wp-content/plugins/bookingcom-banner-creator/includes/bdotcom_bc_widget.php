<?php
/**
 * WIDGET SECTION
 * ----------------------------------------------------------------------------
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

// use widgets_init action hook to execute custom function
add_action( 'widgets_init', 'bdotcom_register_widgets' );
//register our widget
function bdotcom_register_widgets( ) {
                register_widget( 'bdotcom_widget' );
}
                class bdotcom_widget extends WP_Widget {
                                //process the new widget
                                function __construct( ) {
                                                parent::__construct( 'bdotcom_widget', // Base ID
                                                                BDOTCOM_BC_PLUGIN_NAME, // Name
                                                                array(
                                                                 'description' => BDOTCOM_BC_PLUGIN_WIDGET_DESCR,
                                                                'classname' => 'bdotcom_widget' 
                                                ) // Args
                                                                );
                                }
                                function form( $instance ) {
                                                //here we need just the post/banner aid and we're done, we can create the shortcode       
                                                //Create da loop through the banner post type    
                                                $loop = new WP_Query( array(
                                                                 'post_type' => 'bdotcom_bm' 
                                                ) );
                                                if ( $loop->have_posts() ) {
?>
            
                                                                <select name="<?php
                                                                                echo esc_html( $this->get_field_name( 'bdotcom_bannerid' ) ); ?>">
                                                                                <option value="no_choice" <?php
                                                                                if ( isset( $instance[ 'bdotcom_bannerid' ] ) ) {
                                                                                                selected( $instance[ 'bdotcom_bannerid' ], 'no_choice' );
                                                                                } //isset( $instance[ 'bdotcom_bannerid' ] )
                                                                                ?>><?php esc_html_e("Select one banner...", 'bookingcom-banner-creator' ); ?></option>
            
                                                                <?php
                                                                                while ( $loop->have_posts() ) {
                                                                                                $loop->the_post();
                                                                ?>
                                                                                                <option value="<?php echo esc_attr( get_the_ID() ); ?>" <?php
                                                                                                if ( isset( $instance[ 'bdotcom_bannerid' ] ) ) {
                                                                                                                selected( $instance[ 'bdotcom_bannerid' ], get_the_ID() );
                                                                                                } //isset( $instance[ 'bdotcom_bannerid' ] )
                                                                                                ?>> <?php echo esc_html( get_the_title() ); ?> </option>
                
                                                                <?php
                                                                                } //while ( $loop->have_posts() )
                                                                ?>
            
                                                                </select>         
           
                                <?php
                                                } //if ( $loop->have_posts() )     
                                } //function form ( $instance )
                                function update( $new_instance, $old_instance ) {
                                                $instance                 = $old_instance;
                                                $instance[ 'bdotcom_bannerid' ] = $new_instance[ 'bdotcom_bannerid' ];
                                                return $instance;
                                }
                                //display the widget only if a banner was selected
                                function widget( $args, $instance ) {
                                                if ( $instance[ 'bdotcom_bannerid' ] != 'no_choice' ) {
                                                                extract( $args );
                                                                echo wp_kses_post( $before_widget );                                                           
                                                                // Use the shortcode to generate the banner               
                                                                echo do_shortcode( '[bdotcom_bm bannerid="' . esc_html( $instance[ 'bdotcom_bannerid' ] ) . '" ]' );
                                                                echo wp_kses_post( $after_widget );
                                                } //$instance[ 'bdotcom_bannerid' ] != 'no_choice'
                                }
                }
//}
?>