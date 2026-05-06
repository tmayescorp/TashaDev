<?php
/*
 * Template Name: Wilcity Confirm Account
 */
use \WilokeListingTools\Framework\Helpers\SetSettings;
use \WilokeListingTools\Frontend\User as WilokeUser;

get_header();
global $wiloke;
?>
	<div class="wil-content">
		<section class="wil-section bg-color-gray-2 pt-30">
			<div class="container">
				<div id="wilcity-confirm-account" class="row">
					<?php
                        if ( have_posts() ){
                            while (have_posts()){
                                the_post();
                                the_content();

                                if (empty($post->post_content)) {
	                                ?>
                                    <p><?php esc_html_e('Congrats, your account has been confirmed successfully!', 'wilcity') ?></p>
	                                <?php
                                    if (current_user_can('administrator')) {
                                        ?>
                                        <p>You can customize this content yourself by going to Pages -> Your
                                            confirmation page -> Write something into the Editor</p>
                                        <?php
                                    }
                                }
                            }
                        }
					?>
				</div>
			</div>
		</section>
	</div>
<?php
do_action('wilcity/before-close-root');
get_footer();
