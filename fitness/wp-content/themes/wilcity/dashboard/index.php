<?php
/*
 * Template Name: Wilcity Dashboard
 */

if ( !is_user_logged_in() ){
	wp_redirect(home_url('/'));
	die();
}
get_header();

global $wiloke;
use WilokeListingTools\Frontend\User as WilokeUser;
use WilokeListingTools\Controllers\FollowController;
use WilokeListingTools\Framework\Helpers\HTML;

$oUser = WilokeUser::getUserData();
$avatar = WilokeUser::getAvatar();
?>
<div id="wilcity-dashboard" class="wil-dashboard wil-content"></div>
<?php

do_action('wilcity/before-close-root');
get_footer();

