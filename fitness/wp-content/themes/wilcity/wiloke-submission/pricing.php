<?php
/*
 * Template Name: Wilcity Package Page
 */

get_header();

use WilokeListingTools\Frontend\User;
use WilokeListingTools\Frontend\User as WilokeUser;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;

global $wiloke;
?>
    <div class="wil-content">
        <section class="wil-section bg-color-gray-2">
            <div class="container">
                <div class="row" data-col-xs-gap="20">
                    <?php
                    if (is_user_logged_in()) {
                        if (!WilokeUser::canSubmitListing(get_current_user_id())) {
                            if (!User::isAccountConfirmed()) {
                                echo Wiloke::ksesHTML(WilokeThemeOptions::getOptionDetail('addlisting_confirm_account_warning'));
                            } else {
                                if (GetWilokeSubmission::getField('toggle_become_an_author') == 'enable' &&
                                    $becomeAnAuthorUrl = GetWilokeSubmission::getField('become_an_author_page', true)) {
                                    wp_safe_redirect($becomeAnAuthorUrl);
                                    exit();
                                }
                            }
                        } else {
                            if (have_posts()) {
                                while (have_posts()) {
                                    the_post();
                                    the_content();
                                }
                            }
                        }
                    } else {
                        do_action('wilcity/can-not-submit-listing');
                    }
                    ?>
                </div>
            </div>
        </section>
    </div>
<?php
do_action('wilcity/before-close-root');
get_footer();
