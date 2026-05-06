<?php
/*
 * Plugin Name: Wiloke Listing Tools
 * Plugin URI: https://wiloke.com
 * Author: Wiloke
 * Author URI: https://wiloke.com
 * Version: 1.7.48
 * Description: This tool allows customizing your Add Listing page
 * Text Domain: wiloke-listing-tools
 * Domain Path: /languages/
 */

define('WILOKE_LISTING_TOOL_DB_VERSION', '2.1');
define('WILOKE_LISTING_TOOL_VERSION', '1.7.48');
define('WILOKE_LISTING_DOMAIN', 'wiloke-listing-tools');
define('WILOKE_LISTING_PREFIX', 'wilcity_');
define('WILOKE_LISTING_TOOL_URL', plugin_dir_url(__FILE__));
define('WILOKE_LISTING_TOOL_DIR', plugin_dir_path(__FILE__));

if (!defined('WILOKE_PREFIX')) {
    define('WILOKE_PREFIX', 'wiloke');
}

add_action('plugins_loaded', 'wiloke_listing_tools_load_textdomain');
function wiloke_listing_tools_load_textdomain()
{
    load_plugin_textdomain('wiloke-listing-tools', false, basename(dirname(__FILE__)) . '/languages');
}

require plugin_dir_path(__FILE__) . 'vendor/autoload.php';

use WilokeListingTools\Controllers\AddListing\WooCommerceAddListingController;
use WilokeListingTools\Controllers\AdminNoticeController;
use WilokeListingTools\Controllers\AjaxUploadFileController;
use WilokeListingTools\Controllers\GoogleAuthenticator;
use WilokeListingTools\Controllers\GuardController;
use WilokeListingTools\Controllers\DirectBankTransferPaymentScheduleController;

new GuardController;

use WilokeListingTools\Controllers\HaveBeenThereController;
use WilokeListingTools\Controllers\TranslationController;
use WilokeListingTools\Framework\Helpers\ListingProduct\InterfaceListingProduct;
use WilokeListingTools\Framework\Helpers\ListingProduct\ListingProduct;
use WilokeListingTools\Framework\Helpers\ProductSkeleton;
use \WilokeListingTools\Framework\Helpers\QRCodeGenerator;

// MetaBox
//use WilokeListingTools\MetaBoxes\AdvancedProduct;
use WilokeListingTools\Framework\Helpers\SearchFieldSkeleton;
use WilokeListingTools\Framework\Helpers\VendorSkeleton;
use WilokeListingTools\Framework\Helpers\WPML;
use WilokeListingTools\MetaBoxes\CustomCMB2Fields as CustomCMB2Fields;
use WilokeListingTools\MetaBoxes\Listing as MetaBoxesListing;
use WilokeListingTools\MetaBoxes\EventPlan as WilokeMetaboxEventPlan;
use WilokeListingTools\MetaBoxes\ListingPlan as MetaBoxesListingPlan;
use WilokeListingTools\MetaBoxes\ListingCategory as MetaboxesListingCategory;
use WilokeListingTools\MetaBoxes\ListingLocation as MetaboxesListingLocation;
use WilokeListingTools\MetaBoxes\UserMeta as MetaboxesUserMeta;
use WilokeListingTools\MetaBoxes\ListingTag as MetaboxesListingTag;
use WilokeListingTools\MetaBoxes\Discount as WilokeDiscount;
use WilokeListingTools\MetaBoxes\Event as WilokeMetaboxEvent;
use WilokeListingTools\MetaBoxes\ClaimListing as WilokeClaimListing;
use WilokeListingTools\MetaBoxes\Review as MetaboxReview;
use WilokeListingTools\MetaBoxes\EventComment as MetaboxEventComment;
use WilokeListingTools\MetaBoxes\CustomFieldsForPostType as MetaboxCustomFieldsForPostType;
use WilokeListingTools\MetaBoxes\Report as MetaboxReport;
use WilokeListingTools\MetaBoxes\Promotion as MetaboxPromotion;
use WilokeListingTools\MetaBoxes\BookingComBannerCreator;
use WilokeListingTools\MetaBoxes\WooCommerce as MetaBoxWooCommerce;
use WilokeListingTools\MetaBoxes\Post as PostMetaBox;
use WilokeListingTools\MetaBoxes\Coupon as CouponMetaBox;
use WilokeListingTools\MetaBoxes\ListingCustomTaxonomy as ListingCustomTaxonomyMetaBox;
use WilokeListingTools\Controllers\WebhookController;
use WilokeListingTools\Controllers\TermController;
use WilokeListingTools\Framework\Helpers\EventSkeleton;
use WilokeListingTools\Framework\Helpers\App;

new CustomCMB2Fields;
new MetaBoxesListing;
new MetaboxesListingTag;
new WilokeMetaboxEventPlan;
new MetaBoxesListingPlan;
new MetaboxesListingCategory;
new MetaboxesListingLocation;
//new AdvancedProduct;
new MetaboxesUserMeta;
new WilokeDiscount;
new WilokeMetaboxEvent;
new WilokeClaimListing;
new MetaboxReview;
new MetaboxPromotion;
new MetaboxEventComment;
new MetaboxCustomFieldsForPostType;
new MetaboxReport;
new BookingComBannerCreator;
new MetaBoxWooCommerce;
new PostMetaBox;
new CouponMetaBox;

use WilokeListingTools\Register\RegisterMenu\RegisterAddNewPayment;
use WilokeListingTools\Register\RegisterReportSubmenu;
use WilokeListingTools\Register\RegisterPromotionPlans;
use WilokeListingTools\Register\RegisterClaimSubMenu;

//use WilokeListingTools\Register\RegisterEventSettings;
//use WilokeListingTools\Register\RegisterListingSetting;
use WilokeListingTools\Register\RegisterMenu\RegisterScripts;
use WilokeListingTools\Register\RegisterMenu\RegisterMenu;
use WilokeListingTools\Register\RegisterSettings;
use WilokeListingTools\Register\RegisterPostTypes;
use WilokeListingTools\Register\RegisterTables;
use WilokeListingTools\Register\WilokeSubmission;
use WilokeListingTools\Register\RegisterInvoiceSubMenu;
use WilokeListingTools\Register\RegisterSaleSubMenu;
use WilokeListingTools\Register\RegisterSaleDetailSubMenu;
use WilokeListingTools\Register\RegisterSubscriptions;
use WilokeListingTools\Register\ManageListingColumns;
use WilokeListingTools\Register\AddCustomPostType;
use WilokeListingTools\Register\RegisterSubmenuQuickSearchForm;
use WilokeListingTools\Register\RegisterMobileMenu;
use WilokeListingTools\Register\RegisterFirebaseNotification;
use WilokeListingTools\Register\RegisterImportExportWilokeTools;
use WilokeListingTools\Register\RegisterSingleNavigationSettings;
use WilokeListingTools\Register\RegisterSingleSidebarSettings;
use WilokeListingTools\Controllers\ModalController;
use WilokeListingTools\Controllers\TagsBelongsToCatController;
use WilokeListingTools\Controllers\WPMLTranslationController;

// Front-end
use WilokeListingTools\Frontend\GenerateURL as GenerateURL;

//use WilokeListingTools\Frontend\EnqueueScripts as EnqueueScripts;
use WilokeListingTools\Controllers\BookingComController;

new GenerateURL;
//new EnqueueScripts;

require WILOKE_LISTING_TOOL_DIR . 'functions.php';
new RegisterTables;
new RegisterSettings;
new RegisterClaimSubMenu;
new RegisterMenu;
new RegisterScripts;
new RegisterPostTypes;
new RegisterPromotionPlans;
new WilokeSubmission;
new RegisterSubmenuQuickSearchForm;
new RegisterMobileMenu;
new RegisterFirebaseNotification;

// Alter Table
use WilokeListingTools\Controllers\ModifyQueryController;
use WilokeListingTools\Controllers\AppleLoginController;

if (is_admin()) {
    new AdminNoticeController;
    new RegisterInvoiceSubMenu;
    new RegisterSaleSubMenu;
    new RegisterSubscriptions;
    new RegisterAddNewPayment;
    new RegisterSaleDetailSubMenu;
    new RegisterReportSubmenu;
    new RegisterImportExportWilokeTools;
    new AddCustomPostType;

    new WilokeListingTools\Controllers\ChangePlanStatusController;
    new WilokeListingTools\Register\General;
    new WilokeListingTools\Controllers\TaxonomiesControllers;
    new WilokeListingTools\Controllers\RunUpdateDBToLatestVersionController;

    new ListingCustomTaxonomyMetaBox;
    new ManageListingColumns;
    new RegisterSingleNavigationSettings;

    if (!class_exists('CMB2_Field_Ajax_Search')) {
        require_once WILOKE_LISTING_TOOL_DIR . 'app/MetaBoxes/cmb2-field-ajax-search/cmb2-field-ajax-search.php';
    }

    new RegisterSingleSidebarSettings;
}

App::bind('WooCommerceAddListingController', new WooCommerceAddListingController);
App::bind('SingleListingWooCommerce', new WilokeListingTools\Controllers\SingleListing\WooCommerce);
use WilokeListingTools\Frontend\SingleListing;

new SingleListing;

// Payment
use WilokeListingTools\Controllers\AjaxUploadImgController;
use WilokeListingTools\Controllers\StripeController;
use WilokeListingTools\Controllers\PaymentController;
use WilokeListingTools\Controllers\UserPlanController;
use WilokeListingTools\Controllers\PlanRelationshipController;
use WilokeListingTools\Controllers\PostController;
use WilokeListingTools\Controllers\ReviewController;
use WilokeListingTools\Controllers\WooCommerceController;
use WilokeListingTools\Controllers\EventController;
use WilokeListingTools\Controllers\AddListingController;
use WilokeListingTools\Controllers\ListingController;
use WilokeListingTools\Controllers\ClaimController;
use WilokeListingTools\Controllers\CouponController;
use WilokeListingTools\Controllers\AddListingButtonController;
use WilokeListingTools\Controllers\AddMorePhotosVideosController;
use WilokeListingTools\Controllers\PromotionController;
use WilokeListingTools\Controllers\MessageController;
use WilokeListingTools\Controllers\AuthorPageController;
use WilokeListingTools\Controllers\FollowController;
use WilokeListingTools\Controllers\SearchFormController;
use WilokeListingTools\Controllers\FavoriteStatisticController;
use WilokeListingTools\Controllers\ViewStatisticController;
use WilokeListingTools\Controllers\SharesStatisticController;
use WilokeListingTools\Controllers\NotificationsController;
use WilokeListingTools\Controllers\ReportController;
use WilokeListingTools\Controllers\RegisterLoginController;
use WilokeListingTools\Controllers\SessionController;
use WilokeListingTools\Controllers\ProfileController;
use WilokeListingTools\Controllers\BillingControllers;
use WilokeListingTools\Controllers\EmailController;
use WilokeListingTools\Controllers\ContactFormController;
use WilokeListingTools\Controllers\InvoiceController;
use WilokeListingTools\Controllers\DashboardController;
use WilokeListingTools\Controllers\UserController;
use WilokeListingTools\Controllers\TermsAndPolicyController;
use WilokeListingTools\Controllers\IconController;
use WilokeListingTools\Controllers\GridItemController;
use WilokeListingTools\Controllers\SchemaController;
use WilokeListingTools\Controllers\PermalinksController;
use WilokeListingTools\Controllers\DokanController;
use WilokeListingTools\Controllers\WooCommerceBookingController;
use WilokeListingTools\Controllers\NoticeController;
use WilokeListingTools\Controllers\FreePlanController;
use WilokeListingTools\Controllers\GoogleReCaptchaController;
use WilokeListingTools\Controllers\OptimizeScripts;
use WilokeListingTools\Controllers\RestaurantMenuController;
use WilokeListingTools\Controllers\FacebookLoginController;
use WilokeListingTools\Controllers\AddListingPaymentController;
use WilokeListingTools\Controllers\GalleryController;

new ModifyQueryController;
new IconController;
new AuthorPageController;
App::bind('PromotionController', new PromotionController);
new MessageController;
new AddMorePhotosVideosController;
new AddListingButtonController;
new CouponController;
//new PayPalController;
new AddListingPaymentController;
new StripeController; // removed StripeController
//new DirectBankTransferController;
new PaymentController;
new UserPlanController;
new DirectBankTransferPaymentScheduleController;
App::bind('PostController', new PostController);
new PlanRelationshipController;
new WooCommerceController;
new ViewStatisticController;
new ReviewController;
new EventController;
App::bind('AddListingController', new AddListingController);
new ListingController;
new ClaimController;
new FollowController;
new SearchFormController;
new FavoriteStatisticController;
new SharesStatisticController;
new NotificationsController;
new ReportController;
new RegisterLoginController;
new SessionController;
new BillingControllers;
App::bind('EmailController', new EmailController);
new ContactFormController;
new InvoiceController;
new DashboardController;
new UserController;
new TermsAndPolicyController;
new GridItemController;
new SchemaController;
new PermalinksController;
new BookingComController;
new AjaxUploadImgController;
new NoticeController;
new FreePlanController;
new GoogleReCaptchaController;
new OptimizeScripts;
new RestaurantMenuController;
new FacebookLoginController;
new DokanController;
new WooCommerceBookingController;
new WebhookController;
new GalleryController;
new ModalController;
new TagsBelongsToCatController;
new TermController;
new AppleLoginController;
new HaveBeenThereController;
new TranslationController;
new AjaxUploadFileController;
if(WPML::isActive()){
	new WPMLTranslationController();
}

App::bind('EventSkeleton', new EventSkeleton);
App::bind('ProductSkeleton', new ProductSkeleton);
App::bind('ListingProduct', new ListingProduct);
App::bind('VendorSkeleton', new VendorSkeleton);
App::bind('SearchFieldSkeleton', new SearchFieldSkeleton);
// Schedule Registration
function wilokeListingToolsScheduleRegistration()
{
    if (!wp_next_scheduled('wilcity_check_event_status')) {
        wp_schedule_event(time(), 'hourly', 'wilcity_check_event_status');
    }

    if (!wp_next_scheduled('wilcity_daily_events')) {
        wp_schedule_event(time(), 'daily', 'wilcity_daily_events');
    }

    do_action('wilcity/wiloke-listing-tools/after-plugin-activated');
}

register_activation_hook(__FILE__, 'wilokeListingToolsScheduleRegistration');

// Single Widgets
//use WilokeListingTools\Shortcodes\SinglePriceRange;

add_action('plugins_loaded', function () {
    do_action('wiloke-listing-tools/run-extension');
});
/*
 * @params: $aData: listingID, isNewListing, planID
 */

// Flush all search Cache
register_deactivation_hook(__FILE__, ['WilokeListingTools\Controllers\SearchFormController', 'flushSearchCache']);
