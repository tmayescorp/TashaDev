<?php
/*
 * Plugin Name: Wilcity Mobile App
 * Plugin URI: https://wilcity.com
 * Author: Wilcity
 * Author URI: https://wilcity.com
 * Version: 1.8.5
 * Description: Wilcity Mobile App
 */

add_action('admin_notices', function () {
	if (version_compare(PHP_VERSION, '7.4', '<')) {
		?>
        <div class="notice notice-error" style="padding: 20px; border-left:  4px solid #dc3232; color: red;">
            In order to use Wilcity App, you need to upgrade PHP version to 7.4. Please read
            <a href="https://documentation.wilcity.com/knowledgebase/wordpress-and-wilcity-server-requirements/"
               target="_blank" style="color: red;">WordPress and Wilcity Server Requirements</a> to know more.
        </div>
		<?php
		return false;
	}

    $firebaseIssue = $_COOKIE['wilcity_firebase_issue'] ?? "";
    if (!empty($firebaseIssue)) {
        ?>
        <div class="notice notice-error" style="padding: 20px; border-left:  4px solid #dc3232; color: red;">
            There is an issue with your Firebase Configuration. <br />
            Suggestion: You should re-check your Google File Key permission (Wiloke Tools -> Notifications). <br />
            Specific reason: <?php echo esc_html($firebaseIssue); ?>
        </div>
        <?php
    }
});

use WILCITY_APP\Controllers\AdmobController;
use WILCITY_APP\Controllers\AppleLoginController;
use WILCITY_APP\Controllers\DashboardController;
use WILCITY_APP\Controllers\Event\EventController;
use WILCITY_APP\Controllers\Event\EventGeneralData;
use WILCITY_APP\Controllers\Event\EventsController;
use WILCITY_APP\Controllers\Event\EventShortcodeController;
use WILCITY_APP\Controllers\FavoritesController;
use WILCITY_APP\Controllers\Firebase\LoginRegister;
use WILCITY_APP\Controllers\Firebase\MessageController;
use WILCITY_APP\Controllers\Firebase\PushNotificationController;
use WILCITY_APP\Controllers\GeneralSettings;
use WILCITY_APP\Controllers\GlobalSettingController;
use WILCITY_APP\Controllers\HomeController;
use WILCITY_APP\Controllers\ImageController;
use WILCITY_APP\Controllers\Listing\ListingExternalButton;
use WILCITY_APP\Controllers\Listing\ListingGeneralData;
use WILCITY_APP\Controllers\Listing\ListingHomeController;
use WILCITY_APP\Controllers\Listing\ListingMeta;
use WILCITY_APP\Controllers\Listing\ListingNavigation;
use WILCITY_APP\Controllers\Listing\ListingSidebar;
use WILCITY_APP\Controllers\Listing\ListingReview;
use WILCITY_APP\Controllers\NotificationController;
use WILCITY_APP\Controllers\ReportController;
use WILCITY_APP\Controllers\ReviewController;
use WILCITY_APP\Controllers\SMSMessageController;
use WILCITY_APP\Controllers\TermController;
use WILCITY_APP\Controllers\Taxonomies as WilcityMobileAppTaxonomies;
use WILCITY_APP\Controllers\PostTypes as PostTypes;

//use WILCITY_APP\Controllers\Listings;
//use WILCITY_APP\Controllers\Listing;
use WILCITY_APP\Controllers\OrderBy;
use WILCITY_APP\Controllers\Filter;
use WILCITY_APP\Controllers\Translations;
use WILCITY_APP\Controllers\Review;
use WILCITY_APP\Controllers\Blog;
use WILCITY_APP\Controllers\MenuController;
use WILCITY_APP\Controllers\SearchField;
use WILCITY_APP\Controllers\User\UserInfo;
use WILCITY_APP\Controllers\UserController;
use WILCITY_APP\Controllers\WooCommerce\Cart\WooCart;
use WILCITY_APP\Controllers\WooCommerce\DokanGlobalController;
use WILCITY_APP\Controllers\WooCommerce\DokanOrderController;
use WILCITY_APP\Controllers\WooCommerce\DokanProductController;
use WILCITY_APP\Controllers\WooCommerce\DokanStatisticController;
use WILCITY_APP\Controllers\WooCommerce\DokanWithdrawnController;
use WILCITY_APP\Controllers\WooCommerce\WooCommerceBookingController;
use WILCITY_APP\Controllers\WooCommerce\WooCommerceCartController;
use WILCITY_APP\Controllers\WooCommerce\WooCommerceCheckoutController;
use WILCITY_APP\Controllers\WooCommerce\WooCommerceGatewayController;
use WILCITY_APP\Controllers\WooCommerce\WooCommerceOrderController;
use WILCITY_APP\Controllers\WooCommerce\WooCommerceProductController;
use WILCITY_APP\Controllers\WooCommerce\WooCommerceRatingController;
use WILCITY_APP\Controllers\WooCommerce\WooCommerceCouponController;
use WILCITY_APP\Controllers\Listing\ListingsController;
use WILCITY_APP\Controllers\Listing\ListingController;
use WILCITY_APP\Controllers\WooCommerce\WooCommerceShortcodeController;
use WILCITY_APP\Controllers\WooCommerce\WooCommerceWishlistController;
use WILCITY_APP\SidebarOnApp\BusinessHours;
use WILCITY_APP\SidebarOnApp\BusinessInfo;
use WILCITY_APP\SidebarOnApp\Categories;
use WILCITY_APP\SidebarOnApp\Claim;
use WILCITY_APP\SidebarOnApp\CustomContent;
use WILCITY_APP\SidebarOnApp\PriceRange;
use WILCITY_APP\SidebarOnApp\Statistic;
use WILCITY_APP\SidebarOnApp\Tags;
use WILCITY_APP\SidebarOnApp\Taxonomy;
use WILCITY_APP\SidebarOnApp\TermBox;
use WilokeListingTools\Framework\Helpers\EventSkeleton;
use WilokeListingTools\Framework\Helpers\Firebase;
use WILCITY_APP\Helpers\App;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\PostSkeleton;

if (!function_exists('apache_request_headers')) {
	function apache_request_headers()
	{
		$headers = [];
		foreach ($_SERVER as $name => $value) {
			if (substr($name, 0, 5) == 'HTTP_') {
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
			}
		}

		return $headers;
	}
}

function wilcityAppStripTags($text)
{
	return is_array($text) ? $text : strip_tags($text);
}

function wilcityAppGetLanguageFiles($field = '', $lang = '')
{
	if (!class_exists('\WilokeListingTools\Framework\Helpers\GetSettings')) {
		return '';
	}

	$aFinalTranslation = GetSettings::getTranslation();

	if (!empty($field)) {
		if (isset($aFinalTranslation[$field])) {
			return $aFinalTranslation[$field];
		}

		return '';
	}

	return $aFinalTranslation;
}

add_filter('wiloke-listing-tools/config/middleware', function ($aMiddleware) {
	$aMiddleware['isLoggedInToFirebase'] = 'WILCITY_APP\Middleware\IsLoggedInFirebase';
	$aMiddleware['verifyFirebaseChat'] = 'WILCITY_APP\Middleware\VerifyFirebaseChat';
	$aMiddleware['isOwnerOfReview'] = 'WILCITY_APP\Middleware\IsOwnerOfReview';

	return $aMiddleware;
});

if (!function_exists('wilcityModifyProductCatsQuery')) {
	add_filter('kc_autocomplete_product_cats', 'wilcityModifyProductCatsQuery');

	function wilcityModifyProductCatsQuery($data)
	{
		$aTerms = wilcitySCSearchTerms($_POST['s'], 'product_cat');
		if (!$aTerms) {
			return false;
		}

		return ['Select Terms' => $aTerms];
	}
}

if (!function_exists('wilcityModifyProductIDsQuery')) {
	add_filter('kc_autocomplete_product_ids', 'wilcityModifyProductIDsQuery');

	function wilcityModifyProductIDsQuery($aData)
	{
		$query = new WP_Query(
			[
				'post_type'      => 'product',
				'posts_per_page' => 20,
				's'              => $aData['s'],
				'post_status'    => 'publish'
			]
		);
		$aListings = [];
		if ($query->have_posts()) {
			while ($query->have_posts()) {
				$query->the_post();
				$aListings[] = $query->post->ID . ':' . $query->post->post_title;
			}
		}

		return ['Select Listings' => $aListings];
	}
}

add_filter( 'cron_schedules', function ( $aSchedules ) {
	$aSchedules['per_five_minutes'] = array(
		'interval' => 300,
		'display' => __( '5 Minutes', 'wilcity-mobile-app' )
	);
	return $aSchedules;
} );

register_activation_hook(__FILE__, 'wilcityMobileAppRegisterSchedule');

// Schedule Cron Job Event
function wilcityMobileAppRegisterSchedule() {
	if ( ! wp_next_scheduled( 'wilcity_mobile_app_schedule' ) ) {
		wp_schedule_event( time(), 'per_five_minutes', 'wilcity_mobile_app_schedule_each_five_minutes' );
		wp_schedule_event( time(), 'daily', 'wilcity_mobile_app_schedule_everyday' );
	}
}

register_deactivation_hook(__FILE__, 'wilcityMobileAppDeregisterSchedule');

function wilcityMobileAppDeregisterSchedule() {
	wp_clear_scheduled_hook('wilcity_mobile_app_schedule_each_five_minutes');
}

// Fix for WooCommerce 3.7
add_filter('woocommerce_is_rest_api_request', 'loadWooCommerceForWilcityRestAPI');

function loadWooCommerceForWilcityRestAPI($status)
{
	$request = $_SERVER['REQUEST_URI'];
	if (strpos($request, 'wilcity') !== false
		|| strpos($request, 'wiloke') !== false) {
		return false;
	}

	return $status;
}

if (version_compare(PHP_VERSION, '7.4', '>=')) {
	add_action('wiloke-listing-tools/run-extension', function () {
		if (!defined('WILCITY_SC_VERSION')) {
			return false;
		}

		if (!defined('WILOKE_PREFIX')) {
			define('WILOKE_PREFIX', 'wiloke');
		}

		define('WILOKE_MOBILE_REST_VERSION', 'v3');
		define('WILCITY_MOBILE_APP', 'wiloke');
		define('WILCITY_HSBLOG_NAMESPACE', 'wp-json/hsblog/v1');
		define('WILCITY_HSBLOG_WILCITY_ENDPOINT', 'wilcity');
		define('WILCITY_MOBILE_CAT', 'Wilcity Mobile App');
		define('WILCITY_APP_PATH', plugin_dir_path(__FILE__));
		define('WILCITY_APP_URL', plugin_dir_url(__FILE__));
		define('WILCITY_APP_IMG_PLACEHOLDER', WILCITY_APP_URL . 'assets/img/app-img-placeholder.jpg');

		require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

		App::bind('PostSkeleton', (new PostSkeleton));
		App::bind('ListingGeneralData', (new ListingGeneralData));
		App::bind('ListingExternalButton', (new ListingExternalButton));
		App::bind('ListingMeta', (new ListingMeta));
		App::bind('ListingReview', (new ListingReview));
		App::bind('ListingNavigation', (new ListingNavigation));
		App::bind('ListingHomeController', (new ListingHomeController));
		App::bind('ListingsController', (new ListingsController));
		App::bind('ListingController', (new ListingController));
		App::bind('EventSkeleton', (new EventSkeleton));
		App::bind('EventGeneralData', (new EventGeneralData));
		App::bind('EventController', (new EventController));
		App::bind('EventsController', (new EventsController));
		App::bind('ListingSidebar', (new ListingSidebar));
		App::bind('EventShortcodeController', (new EventShortcodeController));
//    App::bind('EventShortcodeController', (new EventShortcodeController));
		App::bind('WooCommerceShortcodeController', (new WooCommerceShortcodeController));
		App::bind('NearByMeController', (new \WILCITY_APP\Controllers\User\NearByMeController()));
		App::bind('WooCart', (new WooCart));
		App::bind('UserInfo', (new UserInfo));

		new \WILCITY_APP\Controllers\Deprecated\ListingController;
		new \WILCITY_APP\Controllers\Deprecated\ListingsController;
		new \WILCITY_APP\Controllers\Deprecated\EventsController;
		new \WILCITY_APP\Controllers\Deprecated\EventController;

		new HomeController;
		new TermController;
		new WilcityMobileAppTaxonomies;
		new PostTypes;
		new OrderBy;
		new Filter;
		new WILCITY_APP\Controllers\Deprecated\NearByMe;
		new Translations;
		new Review;
		new Blog;
		new MenuController;
		new SearchField;

		// Sidebar Items
		new TermBox;
		new Tags;
		new Taxonomy;
		new Statistic;
		new PriceRange;
		new CustomContent;
		new Claim;
		new Categories;
		App::bind('SidebarBusinessHours', (new BusinessHours));
		new BusinessInfo;
		new GeneralSettings;
		new \WILCITY_APP\Controllers\LoginRegister;
		new FavoritesController;
		new WILCITY_APP\Controllers\Deprecated\MyDirectoryController;
		new UserController;
		new ReportController;
		new DashboardController;
		new NotificationController;
		new \WILCITY_APP\Controllers\MessageController;
		new ReviewController;
		new ImageController;
		new AdmobController;
		new GlobalSettingController;
		new SMSMessageController;
		new AppleLoginController;
		//	new \WILCITY_APP\Controllers\WooCommerceController;

		// firebase
		if (Firebase::isFirebaseEnable()) {
			new LoginRegister;
			new MessageController;
			new PushNotificationController;
		}

		if (class_exists('WooCommerce')) {
			new WooCommerceRatingController();
			new WooCommerceCartController();
			new WooCommerceCheckoutController();
			new WooCommerceGatewayController();
			new WooCommerceProductController();
			new WooCommerceWishlistController();
			new WooCommerceOrderController();
			new WooCommerceBookingController();
			new WooCommerceCouponController();
		};

		if (class_exists('WeDevs_Dokan')) {
			new DokanGlobalController();
			new DokanProductController();
			new DokanOrderController();
			new DokanStatisticController();
			new DokanWithdrawnController();
		}
		require_once WILCITY_APP_PATH . 'mobile-shortcodes.php';

		do_action('wilcity/wilcity-mobile-app/app-signed-up/loaded');
	}, 99);
}

require_once plugin_dir_path(__FILE__) . 'guzzle-new.php';
require_once plugin_dir_path(__FILE__) . 'guzzle-old.php';
