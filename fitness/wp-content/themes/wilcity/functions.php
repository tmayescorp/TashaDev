<?php

use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use \WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\Submission;
use WilokeListingTools\Framework\Helpers\TermSetting;
use WilokeListingTools\Frontend\SingleListing;
use \WilokeListingTools\Frontend\User as WilcityUser;
use WilokeListingTools\Framework\Store\Session;

require_once get_template_directory() . '/vendor/autoload.php';
function wilcityIsSinglePage()
{
    return is_single();
}


// Disables the block editor from managing widgets in the Gutenberg plugin.
add_filter('gutenberg_use_widgets_block_editor', '__return_false', 100);
// Disables the block editor from managing widgets.
add_filter('use_widgets_block_editor', '__return_false', 999);
function wilcityAddMetaDescriptionToCustomCategoryPage()
{
    if (is_tax()) {
        $rankMathDesc = get_term_meta(get_queried_object()->term_id, 'rank_math_description', true);
        ?>
        <meta name="description" value="<?php echo esc_attr($rankMathDesc); ?>">
        <?php
    }
}

add_action('wp_head', 'wilcityAddMetaDescriptionToCustomCategoryPage');

function wilcityIsBecomeAnAuthor()
{
    return is_page_template('wiloke-submission/become-an-author.php');
}

function wilcityIsGoogleMap()
{
    $mapType = WilokeThemeOptions::getOptionDetail('map_type');

    return $mapType != 'mapbox';
}

function wilcityIsMapbox()
{
    return !wilcityIsGoogleMap();
}

function wilcityIsTax()
{
    return is_tax(); // conditional on config.frontend.php
}

function wilcityIsDefaultTermPage(): bool
{
    $taxonomies = TermSetting::getListingTaxonomyKeys();

    if (!is_tax($taxonomies)) {
        return false;
    }

    $taxonomyType = \WilokeThemeOptions::getOptionDetail('listing_taxonomy_page_type');
    $taxonomy = get_query_var('taxonomy');

    return $taxonomyType === 'default' || empty(\WilokeThemeOptions::getOptionDetail($taxonomy . '_page'));
}

function wilcityIsNotUserLoggedIn()
{
    return !is_user_logged_in() && wilcityIsUsingGoogleReCaptcha();
}

function wilcityIsUsingGoogleReCaptcha()
{
    return WilokeThemeOptions::isEnable('toggle_google_recaptcha', false);
}

function wilcityIsMapPageOrSinglePage()
{
    return wilcityIsMapPage() || wilcityIsSingleEventPage() || wilcityIsSingleListingPage() ||
        wilcityIsSearchWithoutMapPage() ||
        wilcityIsAddListingPage();
}

function wilcityIsLazyLoad()
{
    return WilokeThemeOptions::isEnable('general_toggle_lazyload');
}

function wilcityIsLoginPage()
{
    if (WilokeThemeOptions::isEnable('toggle_custom_login_page')) {
        return wilcityIsCustomLogin();
    } else {
        return wilcityIsNotUserLoggedIn();
    }
}

function wilcityIsCustomLogin()
{
    return is_page_template('templates/custom-login.php');
}

function wilcityIsPageBuilder()
{
    return is_page_template('templates/page-builder.php');
}

function wilcityIsSearchWithoutMapPage($template = null): bool
{
    return $template == 'templates/search-without-map.php' || is_page_template('templates/search-without-map.php');
}


function wilcityIsSearchV2($template = null)
{
    return apply_filters(
        'wilcity/filter/wilcityIsSearchV2',
        $template == 'templates/search-v2.php' || is_page_template('templates/search-v2.php')
    );
}

function wilcityGetSidebarByBaseKey($key)
{
    global $post;
    $aSidebarSettings = SingleListing::getSidebarOrder();
    foreach ($aSidebarSettings as $aSidebarSetting) {
        $baseKey = $aSidebarSetting['baseKey'] ?? $aSidebarSetting['key'];
        if ($baseKey == $key) {
            $belongsTo = GetSettings::getListingBelongsToPlan($post->ID);

            if ($belongsTo && !Submission::isPlanSupported($belongsTo,
                    'toggle_' . $aSidebarSetting['key'])) {
                return null;
            }

            return $aSidebarSetting;
        }
    }

    return null;
}

function wilcityIsMapPage($template = null)
{
    return $template == 'templates/map.php' || is_page_template('templates/map.php');
}


function wilcityIsSearchPage($postId = null)
{
    $template = null;
    if (!empty($postId)) {
        $template = get_post_meta($postId, '_wp_page_template', true);
    }

    return apply_filters(
        'wilcity/filter/is-search-page',
        wilcityIsSearchWithoutMapPage($template) || wilcityIsSearchV2($template) || wilcityIsMapPage($template)
    );
}

function wilcityIsWebview()
{
    return ((isset($_REQUEST['iswebview']) && $_REQUEST['iswebview'] == 'yes')) || Session::getSession('isWebview');
}

add_filter('body_class', 'wilcityAddBodyClasses');

function wilcityAddBodyClasses($aClasses)
{

    if (is_user_logged_in()) {
        $oUser = new WP_User(get_current_user_id());
        $aClasses[] = "user-loggedin-" . $oUser->user_login;

        foreach ($oUser->roles as $role) {
            $aClasses[] = "user-permission-" . $role;
        }
    }

    if (is_tax('listing_location') || is_tax('listing_cat') || is_tax('listing_tag')) {
        $aClasses[] = "page-template-search-v2";
    }

    return $aClasses;
}

if (defined('WILCITY_CUSTOM_ADS')) {
    add_action('widgets_init', 'wilcityCustomAdsOnSidebar');
//   https://wordpress.org/plugins/ad-widget/
    if (!function_exists('wilcityCustomAdsOnSidebar')) {
        function wilcityCustomAdsOnSidebar()
        {
            register_sidebar([
                'name'          => __('Custom Ads', 'wilcity'),
                'id'            => 'wilcity-custom-ads',
                'description'   => __('Widgets in this area will be shown on ads on Listing sidebar.', 'wilcity'),
                'before_widget' => '<div class="wilcity-custom-ads">',
                'after_widget'  => '</div>',
                'before_title'  => '<h2 class="widgettitle">',
                'after_title'   => '</h2>',
            ]);
        }

        add_action('wilcity/single-listing/sidebar-top', 'wilcityRenderCustomAdsSidebar');
        function wilcityRenderCustomAdsSidebar($post)
        {
            if (is_active_sidebar('wilcity-custom-ads')) :
                ?>
                <div class="content-box_module__333d9">
                    <div class="content-box_body__3tSRB">
                        <?php dynamic_sidebar('wilcity-custom-ads'); ?>
                    </div>
                </div>
            <?php
            endif;
        }
    }
}

add_shortcode('wilcity_before_footer_shortcode', 'wilcityBeforeFooterShortcode');
if (!function_exists('wilcityBeforeFooterShortcode')) {
    function wilcityBeforeFooterShortcode()
    {
        get_template_part('before-footer');
    }
}

function wilcityIncludeBeforeFooterFile()
{
    if (is_page_template('templates/custom-login.php')) {
        if (wilcityIsWebview()) {
            Session::setSession('isWebview', true);
        }

        return;
    }

    if (function_exists('is_woocommerce')) {
        if (is_cart() || is_checkout() || is_woocommerce()) {
            return;
        }
    }
    get_template_part('before-footer');
}

//add_action('wp_footer', 'wilcityIncludeAfterBodyToWooCommercePage');
function wilcityIncludeAfterBodyToWooCommercePage()
{
    if (function_exists('is_woocommerce')) {
        if (is_product() || is_shop() || is_woocommerce()) {
            get_template_part('before-footer');
        }
    }
}

function wilcityIncludeAfterBodyFile()
{
    if (is_page_template('templates/custom-login.php')) {
        if (wilcityIsWebview()) {
            Session::setSession('isWebview', true);
        }

        return '';
    }
    get_template_part('after-body');
}

function wilcityOnMyListingPage()
{
    if (!is_singular() || !class_exists('\WilokeListingTools\Frontend\User') || is_front_page() || is_home()) {
        return false;
    }

    global $post;

    if (WilcityUser::isUserLoggedIn() &&
        ($post->post_author == WilcityUser::getCurrentUserID() || current_user_can('administrator'))) {
        return true;
    }

    return false;
}

function wilcityIsUsingWooCommerce()
{
    return function_exists('is_woocommerce');
}

function wilcityIsAddListingDashboardSingleListingPage()
{
    return wilcityIsDashboard() || wilcityIsAddListingPage() || wilcityOnMyListingPage();
}

add_action('wilcity/before-close-root', 'wilcityIncludeBeforeFooterFile');
add_action('wilcity/after-open-body', 'wilcityIncludeAfterBodyFile');
add_action('elementor/theme/before_do_footer', 'wilcityIncludeBeforeFooterFile');

add_action('after_switch_theme', 'wilcityHasNewUpdate');
function wilcityHasNewUpdate()
{
    update_option('wilcity_has_new_update', 'yes');
}

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_script(
        'wilcity-notice-after-updating',
        get_template_directory_uri() . '/admin/source/js/noticeafterupdating.js',
        ['jquery'],
        '1.0',
        true
    );
});

add_action('wp_ajax_wilcity_read_notice_after_updating', function () {
    delete_option('wilcity_has_new_update');
});

function wilcityNoticeAfterUpdatingNewVersion()
{
    if (!get_option('wilcity_has_new_update')) {
        return '';
    } ?>
    <div id="wilcity-notice-after-updating" class="notice notice-error is-dismissible">
        <p>After updating to the new version of Wilcity, you may need re-install Wilcity plugin. We recommend reading <a
                href="https://wilcityservice.com/" target="_blank">Changelog</a> to know how to do it
            .</p>
    </div>
    <?php
}

add_action('admin_notices', 'wilcityNoticeAfterUpdatingNewVersion');

if (!defined('WILCITY_NUMBER_OF_DISCUSSIONS')) {
    define('WILCITY_NUMBER_OF_DISCUSSIONS', apply_filters('wilcity/number_of_discussions', 2));
}

if (!function_exists('isJson')) {
    function isJson($string)
    {
        json_decode($string);

        return (json_last_error() == JSON_ERROR_NONE);
    }
}

function wilcityIsAddListingPage()
{
    if (is_page_template('wiloke-submission/addlisting.php') && GetWilokeSubmission::isSystemEnable()) {
        return true;
    }

    return false;
}

function wilcityDequeueScripts()
{
    wp_dequeue_script('waypoints');
}

add_action('wp_print_scripts', 'wilcityDequeueScripts');

function wilcityIsDashboardPage()
{
    if (is_page_template('dashboard/index.php')) {
        return true;
    }

    return false;
}

require_once(get_template_directory() . '/admin/run.php');

/*
 |--------------------------------------------------------------------------
 | After theme setup
 |--------------------------------------------------------------------------
 |
 | Run needed functions after the theme is setup
 |
 */

function wilcityAfterSetupTheme()
{
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
    add_theme_support('title-tag');
    add_theme_support('widgets');
    add_theme_support('woocommerce');
    add_post_type_support('post_type', 'woosidebars');
    add_theme_support('automatic-feed-links');
    add_theme_support('post-thumbnails');
    add_theme_support('title-tag');
    add_theme_support('editor-style');
    add_theme_support('custom-logo');

    // Woocommerce
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');

    //    add_image_size('wilcity_530x290', 530, 290, false);
    //    add_image_size('wilcity_380x215', 380, 215, false);
    //    add_image_size('wilcity_500x275', 500, 275, false);
    //    add_image_size('wilcity_560x300', 560, 300, false);
    //    add_image_size('wilcity_290x165', 290, 165, false);
    //    add_image_size('wilcity_360x200', 360, 200, false);
    //    add_image_size('wilcity_360x300', 360, 300, false);

    $GLOBALS['content_width'] = apply_filters('wiloke_filter_content_width', 1200);
    load_theme_textdomain('wilcity', get_template_directory() . '/languages');
}

add_action('after_setup_theme', 'wilcityAfterSetupTheme');

function wilcityIsNeedPaymentScript()
{
    global $post;
    if (!class_exists('\WilokeListingTools\Framework\Helpers\GetWilokeSubmission')) {
        return false;
    }

    if (is_home() || !is_user_logged_in()) {
        return false;
    }

    $aPostTypes = General::getPostTypeKeys(false, false);

    if (is_singular($aPostTypes)) {
        if ($post->post_author != get_current_user_id()) {
            return false;
        }
    }

    return true;
}

function wilcityAllowToEnqueueStripe()
{
    if (!class_exists('\WilokeListingTools\Framework\Helpers\GetWilokeSubmission')) {
        return false;
    }

    if (!GetWilokeSubmission::isGatewaySupported('stripe') || is_home()) {
        return false;
    }

    if (!function_exists('is_woocommerce')) {
        $promotion = GetSettings::getOptions('toggle_promotion');
        $postTypes = General::getPostTypeKeys(false, false);

        return (wilcityIsDashboard() || is_page_template('wiloke-submission/checkout.php') ||
            ($promotion == 'enable' && is_singular($postTypes)));
    }

    return !is_checkout();
}

function wilcityIsPostAuthor()
{
    global $post;
    if (!wilcityIsSingleListingPage() || !\WilokeListingTools\Frontend\User::isPostAuthor($post)) {
        return false;
    }

    return true;
}

function wilcityIsSingleListingOrEventPage()
{
    return wilcityIsSingleListingPage() || wilcityIsSingleEventPage();
}

function wilcityIsSingleListingPage()
{
    global $post;

    if (!class_exists('WilokeListingTools\Framework\Helpers\Submission')) {
        return false;
    }

    if (!is_single()) {
        return false;
    }

    return General::isPostTypeInGroup([$post->post_type], 'listing');
}

function wilcityIsLoginedSingleListingPage()
{
    $status = wilcityIsSingleListingPage();

    if (is_user_logged_in()) {
        $status = true;
    }

    return $status;
}

function wilcityIsSingleEventPage()
{
    global $post;
    if (!class_exists('WilokeListingTools\Framework\Helpers\Submission') || !is_singular()) {
        return false;
    }

    return General::isPostTypeInGroup([$post->post_type], 'event');
}

function wilcityIsResetPassword()
{
    return is_page_template('templates/reset-password.php');
}

function wilcityIsNoMapTemplate()
{
    return is_page_template('templates/search-without-map.php')
        || is_tax()
        || is_page_template('templates/event-template.php');
}

function wilcityIsEventsTemplate()
{
    return is_page_template('templates/event-template.php');
}

function wilcityIsDashboard()
{
    return is_page_template('dashboard/index.php');
}

function wilcityIsFileExists($file)
{
    $file = get_stylesheet_directory() . '/' . $file . '.php';

    if (!is_file($file)) {
        $file = get_template_directory() . '/' . $file . '.php';
    }

    return is_file($file);
}

function wilcityFilterBodyClass($classes)
{
    $aPostTypes = class_exists('\WilokeListingTools\Framework\Helpers\General') ?
        General::getPostTypeKeys(
            false,
            false
        ) : '';

    global $post;

    if (is_page_template('templates/custom-login.php')) {
        return array_merge($classes, ['log-reg-action']);
    }

    if (is_page_template('templates/search-without-map.php')) {
        return array_merge($classes, [WilokeThemeOptions::getOptionDetail('search_page_layout')]);
    }

    if (is_author() || (!empty($aPostTypes) && is_singular($aPostTypes)) || wilcityIsDashboard()) {
        $classes = array_merge($classes, ['header-no-sticky']);
    }

    if (is_tax()) {
        return array_merge($classes, [WilokeThemeOptions::getOptionDetail('search_page_layout')]);
    }

    if (is_page_template('wiloke-submission/addlisting.php')) {
        if (isset($_GET['listing_type']) &&
            General::isPostTypeInGroup($_GET['listing_type'],
                'event')) {
            $classes = array_merge($classes, ['event-group']);
        } else {
            $classes = array_merge($classes, ['listing-group']);
        }
    }

    global $wiloke;

    if (
        isset($wiloke->aThemeOptions['general_toggle_show_full_text']) &&
        $wiloke->aThemeOptions['general_toggle_show_full_text'] == 'enable'
    ) {
        $classes = array_merge($classes, ['text-ellipsis-mode-none']);
    }

    $stickyMenu = "";
    if (is_singular()) {
        $stickyMenu = GetSettings::getOptions('toggle_menu_sticky');
    } else if (is_home() && is_front_page()) {
        $homepageId = get_option('page_on_front');
        $stickyMenu = GetSettings::getPostMeta($homepageId, 'toggle_menu_sticky');
    }

    if (empty($stickyMenu) || $stickyMenu == 'inherit') {
        $stickyMenu = WilokeThemeOptions::getOptionDetail('general_sticky_menu');
    }

    if ($stickyMenu == 'enable') {
        $classes = array_merge($classes, ['wil-sticky-menu']);
    } else if ($stickyMenu == 'disable') {
        $classes = array_merge($classes, ['wil-disable-sticky-menu']);
    }

    return $classes;
}

add_filter('body_class', 'wilcityFilterBodyClass');

add_action('widgets_init', 'wilcityRegisterSidebars');
function wilcityRegisterSidebars()
{
    register_sidebar(
        [
            'name'          => esc_html__('Blog Sidebar', 'wilcity'),
            'description'   => esc_html__('Displaying widget items on the Sidebar area', 'wilcity'),
            'id'            => 'wilcity-blog-sidebar',
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ]
    );

    register_sidebar(
        [
            'name'          => esc_html__('Single Post Sidebar', 'wilcity'),
            'description'   => esc_html__('Displaying widget items on the Single Post area', 'wilcity'),
            'id'            => 'wilcity-single-post-sidebar',
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ]
    );

    register_sidebar(
        [
            'name'          => esc_html__('Single Page Sidebar', 'wilcity'),
            'description'   => esc_html__('Displaying widget items on the Single Page area', 'wilcity'),
            'id'            => 'wilcity-single-page-sidebar',
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ]
    );

    register_sidebar(
        [
            'name'          => 'Events Page Sidebar',
            'id'            => 'wilcity-sidebar-events',
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ]
    );

    register_sidebar(
        [
            'name'          => esc_html__('Single Event Sidebar', 'wilcity'),
            'description'   => esc_html__('Displaying widget items on the Single Event area', 'wilcity'),
            'id'            => 'wilcity-single-event-sidebar',
            'before_widget' => '<div id="%1$s" class="content-box_module__333d9 widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<header class="content-box_header__xPnGx clearfix"><div class="wil-float-left"><h4 class="content-box_title__1gBHS">',
            'after_title'   => '</h4></div></header>',
        ]
    );

    register_sidebar(
        [
            'name'          => esc_html__('Listing Taxonomy Sidebar', 'wilcity'),
            'description'   => esc_html__(
                'Displaying widget items on the Listing Tag page, Listing Location page and Listing Category page',
                'wilcity'
            ),
            'id'            => 'wilcity-listing-taxonomy',
            'before_widget' => '<div id="%1$s" class="content-box_module__333d9 widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<header class="content-box_header__xPnGx clearfix"><div class="wil-float-left"><h4 class="content-box_title__1gBHS">',
            'after_title'   => '</h4></div></header>',
        ]
    );

    register_sidebar(
        [
            'name'          => 'Shop Sidebar',
            'description'   => 'Showing Sidebar on the WooCommerce page',
            'id'            => 'wilcity-woocommerce-sidebar',
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ]
    );

    register_sidebar(
        [
            'name'          => esc_html__('Footer 1', 'wilcity'),
            'description'   => esc_html__('Displaying widget items on the Footer 1 area', 'wilcity'),
            'id'            => 'wilcity-first-footer',
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ]
    );

    register_sidebar(
        [
            'name'          => esc_html__('Footer 2', 'wilcity'),
            'description'   => esc_html__('Displaying widget items on the Footer 2 area', 'wilcity'),
            'id'            => 'wilcity-second-footer',
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ]
    );

    register_sidebar(
        [
            'name'          => esc_html__('Footer 3', 'wilcity'),
            'description'   => esc_html__('Displaying widget items on the Footer 3 area', 'wilcity'),
            'id'            => 'wilcity-third-footer',
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ]
    );

    register_sidebar(
        [
            'name'          => esc_html__('Footer 4', 'wilcity'),
            'description'   => esc_html__('Displaying widget items on the Footer 4 area', 'wilcity'),
            'id'            => 'wilcity-four-footer',
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>'
        ]
    );

    if (class_exists('Wiloke')) {
        $aThemeOptions = Wiloke::getThemeOptions(true);
        if (isset($aThemeOptions['sidebar_additional']) && !empty($aThemeOptions['sidebar_additional'])) {
            $aParse = explode(',', $aThemeOptions['sidebar_additional']);

            foreach ($aParse as $sidebar) {
                $sidebar = trim($sidebar);
                register_sidebar([
                    'name'          => $sidebar,
                    'id'            => $sidebar,
                    'description'   => 'This is a custom sidebar, which has been created in the Appearance -> Theme Options -> Advanced Settings.',
                    'before_widget' => '<section id="%1$s" class="widget %2$s">',
                    'after_widget'  => '</section>',
                    'before_title'  => '<h2 class="widget-title">',
                    'after_title'   => '</h2>'
                ]);
            }
        }
    }
}

// Comment
add_action('comment_form_top', 'wilcityAddWrapperBeforeFormField');
function wilcityAddWrapperBeforeFormField()
{
    echo '<div class="row">';
}

add_action('comment_form', 'wilcityAddWrapperAfterFormField', 10);
function wilcityAddWrapperAfterFormField()
{
    echo '</div>';
}

add_filter('wilcity/header/header-style', 'wilcityMenuBackground', 10, 1);
function wilcityMenuBackground($color)
{
    global $wiloke, $post;

    if ((is_singular('page') || (is_home() && is_front_page())) && class_exists
        ('\WilokeListingTools\Framework\Helpers\GetSettings')) {
        $menuBg = GetSettings::getPostMeta($post->ID, 'menu_background');
        if (!empty($menuBg) && $menuBg != 'inherit') {
            if ($menuBg == 'custom') {
                return GetSettings::getPostMeta($post->ID, 'custom_menu_background');
            }

            return $menuBg;
        }
    } elseif (is_author()) {
        $option = WilokeThemeOptions::getOptionDetail('general_author_menu_background');
        if ($option != 'custom') {
            return $option;
        }

        return WilokeThemeOptions::getColor('general_author_custom_menu_background');
    } else {
        if (is_tax() && WilokeThemeOptions::getOptionDetail('listing_taxonomy_page_type') == 'custom') {
            $taxonomyKey = get_queried_object()->taxonomy . '_page';
            $customTaxPageID = WilokeThemeOptions::getOptionDetail($taxonomyKey);
            if ($customTaxPageID) {
                $menuBg = GetSettings::getPostMeta($customTaxPageID, 'menu_background');
                if (!empty($menuBg) && $menuBg != 'inherit') {
                    return $menuBg;
                }
            }
        }

        $aListings = class_exists('\WilokeListingTools\Framework\Helpers\General') ?
            General::getPostTypeKeys(
                false,
                true
            ) : ['listing'];
        if (is_singular($aListings)) {
            $option = WilokeThemeOptions::getOptionDetail('general_listing_menu_background');
            if ($option != 'custom') {
                return $option;
            }

            return WilokeThemeOptions::getColor('general_custom_listing_menu_background');
        }
    }

    $option = WilokeThemeOptions::getOptionDetail('general_menu_background');

    if ($option != 'custom') {
        return empty($option) ? 'dark' : $option;
    }

    return WilokeThemeOptions::getColor('general_custom_menu_background');
}

function wilcityIsHasFooterWidget()
{
    global $wiloke;
    if (!isset($wiloke->aThemeOptions['footer_items']) || empty($wiloke->aThemeOptions['footer_items'])) {
        return false;
    }

    $aFooterIDs = ['wilcity-first-footer', 'wilcity-second-footer', 'wilcity-third-footer', 'wilcity-four-footer'];

    for ($i = 0; $i < abs($wiloke->aThemeOptions['footer_items']); $i++) {
        if (is_active_sidebar($aFooterIDs[$i])) {
            return true;
        }
    }
}

function wilcityHasCopyright()
{
    global $wiloke;

    return isset($wiloke->aThemeOptions['copyright']) && !empty($wiloke->aThemeOptions['copyright']);
}

function wilcityGetConfig($fileName)
{
    $fileName = preg_replace_callback('/\.|\//', function ($aMatches) {
        return '';
    }, $fileName);

    $dir = get_template_directory() . '/configs/config.' . $fileName . '.php';
    if (is_file($dir)) {
        $config = include get_template_directory() . '/configs/config.' . $fileName . '.php';

        return $config;
    }

    return false;
}

add_action('init', 'wilcityDisableEmojis');
function wilcityDisableEmojis()
{
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
}
