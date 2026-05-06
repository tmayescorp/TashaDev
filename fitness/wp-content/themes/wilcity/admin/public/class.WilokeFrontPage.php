<?php
/**
 * WO_FrontEnd Class
 *
 * @category Front end
 * @package  Wiloke Framework
 * @author   Wiloke Team
 * @version  1.0
 */

use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;

if (!defined('ABSPATH')) {

    exit;
}

use WilokeListingTools\Frontend\User as WilokeUser;
use WilokeListingTools\Framework\Store\Session;
use \WilokeListingTools\Framework\Helpers\FileSystem as WilcityFileSystem;
use WilokeListingTools\Register\WilokeSubmission;
use \WilokeListingTools\Framework\Helpers\SetSettings;

class WilokeFrontPage
{
    public  $mainStyle   = '';
    public  $minifyStyle = 'wiloke_minify_theme_css';
    private $aDeferJSs;

    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'registerAnchorJS'], 1);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts'], 9999);
        add_action('wp_head', [$this, 'addFavicon']);
        add_action('wp_head', [$this, 'fbTags']);
        add_action('wp_head', [$this, 'googleReCaptcha']);
        add_action('wp_enqueue_scripts', [$this, 'loadFBSDK']);
        add_filter('script_loader_tag', [$this, 'addAttributeToScriptTag'], 10, 2);
        add_action('wp_print_styles', [$this, 'removeDokanFrontAwesome'], 99);
        add_action('wp_enqueue_scripts', [$this, 'enqueueFontAwesomeToDokanDashboard']);
    }

    public function enqueueFontAwesome4()
    {
        if (!defined('ELEMENTOR_VERSION')) {
            wp_enqueue_style('font-awesome4');
        }
    }

    public function printStylesToFooter()
    {
        wp_enqueue_style('font-awesome4');
    }

    public function registerAnchorJS()
    {
        $jsURL = WILOKE_THEME_URI . 'assets/production/js/';
        wp_register_script(
            'wilcity-empty',
            $jsURL . 'activeListItem.min.js',
            [],
            WILOKE_THEMEVERSION,
            false
        );
    }

    public function removeDokanFrontAwesome()
    {
        wp_dequeue_style('dokan-fontawesome');

        if (wilcityIsLoginPage()) {
            wp_dequeue_script('contact-form-7');
            wp_dequeue_script('wpcf7-recaptcha');
        }
    }

    public function enqueueFontAwesomeToDokanDashboard() {
        if ( function_exists('dokan_is_seller_dashboard') && dokan_is_seller_dashboard()) {
            wp_enqueue_style( 'font-awesome5', 'https://use.fontawesome.com/releases/v5.5.0/css/all.css' );
        }
    }

    private function isDeferJSs($jsID)
    {
        if (empty($this->aDeferJSs)) {
            global $wiloke;
            $aScripts = $wiloke->aConfigs['frontend']['scripts']['js'];

            $this->aDeferJSs = array_filter($aScripts, function ($aJS) {
                return isset($aJS['isDefer']) && $aJS['isDefer'];
            });
        }

        return isset($this->aDeferJSs[$jsID]) && $this->aDeferJSs[$jsID];
    }

    public function addAttributeToScriptTag($link, $handle)
    {
        if ($this->isDeferJSs($handle)) {
            $link = str_replace(" src", " defer src", $link);
        }

        return $link;
    }

    private function getProductJSURL($file)
    {
        return esc_url(WILOKE_THEME_URI . 'assets/production/js/' . $file);
    }

    public function googleReCaptcha()
    {
        if (is_user_logged_in() || !wilcityIsLoginPage() || !WilokeThemeOptions::isEnable('toggle_google_recaptcha')) {
            return false;
        }

        $mode = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'login';

        if ($mode == 'rp') {
            return false;
        }

        if ($mode == 'login') {
            if (WilokeThemeOptions::getOptionDetail('using_google_recaptcha_on') != 'both') {
                return false;
            }
        }

        ?>
        <script type="text/javascript">
            var wilcityRunReCaptcha = function () {
                grecaptcha.render('wilcity-render-google-repcatcha', {
                    'sitekey': '<?php echo esc_attr(WilokeThemeOptions::getOptionDetail('recaptcha_site_key')); ?>'
                });
            };
        </script>
        <?php
    }

    public function loadFBSDK()
    {
        global $wiloke;
        $alwaysIncludeFb = false;
        if (is_user_logged_in()) {
            if (!is_singular()) {
                return false;
            }
        }

        if (!$alwaysIncludeFb) {
            $isIncludeFB = class_exists('\WilokeListingTools\Framework\Helpers\General') && (is_singular
                (General::getPostTypeKeys(false, false)) ||
                (isset($wiloke->aThemeOptions['fb_toggle_login']) && $wiloke->aThemeOptions['fb_toggle_login'] == 'enable'));

            $isIncludeFB = apply_filters('wilcity/is-include-fb-skd', $isIncludeFB);
            if (!$isIncludeFB) {
                return false;
            }
        }

        $language
            = isset($wiloke->aThemeOptions['fb_api_language']) ? esc_js($wiloke->aThemeOptions['fb_api_language']) : '';

        $sdkURL = "https://connect.facebook.net/" . $language . "/sdk.js";
        ob_start();
        ?>
        window.fbAsyncInit = function() {
        FB.init({
        appId      : '<?php echo esc_js($wiloke->aThemeOptions['fb_api_id']); ?>',
        cookie     : true,  // enable cookies to allow the server to access
        xfbml      : true,  // parse social plugins on this page
        version    : 'v5.0' // use version 2.2
        });
        };
        // Load the SDK asynchronously
        (function(d, s, id) {
        let js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "<?php echo esc_url($sdkURL); ?>";
        fjs.parentNode.insertBefore(js, fjs);
        }(document, 'script', 'facebook-jssdk'));
        <?php
        $script = ob_get_clean();
        wp_add_inline_script('wilcity-empty', $script);
    }

    public function fbTags()
    {
        $aThemeOptions = Wiloke::getThemeOptions();
        if (!class_exists('\WilokeListingTools\Framework\Helpers\General')) {
            return '';
        }
        global $post;

        $aListingTypes = General::getPostTypeKeys(false, false);

        if (!is_singular($aListingTypes)) {
            return '';
        }

        if (isset($aThemeOptions['toggle_fb_ogg_tag_to_listing']) &&
            $aThemeOptions['toggle_fb_ogg_tag_to_listing'] == 'enable') {
            ?>
            <meta property="og:title" content="<?php echo get_the_title($post->ID); ?>"/>
            <meta property="og:url" content="<?php echo get_permalink($post->ID); ?>"/>
            <meta property="og:image" content="<?php echo esc_url(GetSettings::getFeaturedImg($post->ID, 'full')); ?>"/>
            <meta property="og:description"
                  content="<?php echo esc_html(Wiloke::contentLimit($aThemeOptions['listing_excerpt_length'], $post,
                      false, $post->post_content, true, '...')); ?>"/>
            <?php
        }
    }

    public function addFavicon()
    {
        global $wiloke;
        if (isset($wiloke->aThemeOptions['general_favicon']) && !empty($wiloke->aThemeOptions['general_favicon'])) {
            ?>
            <link rel="shortcut icon" type="image/png"
                  href="<?php echo esc_url($wiloke->aThemeOptions['general_favicon']['url']); ?>"/>
            <?php
        }
    }

    public function addAsyncAttributes($tag, $handle)
    {
        if (strpos($tag, 'async') === false) {
            return $tag;
        }

        return str_replace(' src', ' async defer src', $tag);
    }

    public function dequeueScripts()
    {
        wp_dequeue_script('isotope');
        wp_dequeue_script('isotope-css');
    }

    public static function fontUrl($fonts)
    {
        $font_url = '';

        /*
        Translators: If there are characters in your language that are not supported
        by chosen font(s), translate this to 'off'. Do not translate into your own language.
         */
        if ('off' !== _x('on', 'Google font: on or off', 'wilcity')) {
            $font_url = add_query_arg(
                'family',
                urlencode($fonts),
                "//fonts.googleapis.com/css"
            );
        }

        return $font_url;
    }

    private function checkConditional($conditions, $scriptName, $conditionalRelation = 'AND')
    {
        $aParseConditions = explode(',', $conditions);
        if (!is_array($aParseConditions)) {
            return true;
        }

        $isGoodConditional = true;
        foreach ($aParseConditions as $condition) {
            $condition = trim($condition);
            $matched = true;
            if (strpos($condition, '!') === 0) {
                $matched = false;
                $condition = ltrim($condition, '!');
            }

            if (function_exists($condition)) {
                $status = call_user_func($condition, $scriptName);
                if ($matched === $status) {
                    $isGoodConditional = true;
                    if ($conditionalRelation === 'OR') {
                        return $isGoodConditional;
                    }
                } else {
                    $isGoodConditional = false;
                    if ($conditionalRelation === 'AND') {
                        return false;
                    }
                }
            } else {
                $isGoodConditional = true;
            }
        }

        return $isGoodConditional;
    }

    protected function isMode($aSetting, $mode = 'register')
    {
        if (isset($aSetting['mode'])) {
            return $aSetting['mode'] === $mode;
        }

        return true;
    }

    /**
     * Enqueue scripts into front end
     */
    public function enqueueScripts()
    {
        global $wiloke, $post;
        $vendorURL = WILOKE_THEME_URI . 'assets/vendors/';
        $cssURL = WILOKE_THEME_URI . 'assets/production/css/';
        $cssDir = WILOKE_THEME_DIR . 'assets/production/css/';
        $jsURL = WILOKE_THEME_URI . 'assets/production/js/';
        $fontURL = WILOKE_THEME_URI . 'assets/fonts/';

        $aScripts = $wiloke->aConfigs['frontend']['scripts'];

        // Enqueue Scripts
        if (isset($aScripts['js'])) {
            foreach ($aScripts['js'] as $name => $aJs) {
                if (isset($aJs['conditional'])) {
                    $conditionalRelation = isset($aJs['conditionalRelation']) ? $aJs['conditionalRelation'] : 'AND';
                    if (!$this->checkConditional($aJs['conditional'], $aJs, $conditionalRelation)) {
                        continue;
                    }
                }

                if (isset($aJs['isExternal']) && $aJs['isExternal']) {
                    if ($this->isMode($aJs, 'register')) {
                        wp_register_script($aJs[0], $aJs[1], ['jquery'], WILOKE_THEMEVERSION, true);
                    }

                    if ($this->isMode($aJs, 'enqueue')) {
                        wp_enqueue_script($aJs[0]);
                    }
                } else {
                    if (isset($aJs['isVendor'])) {
                        if ($this->isMode($aJs, 'register')) {
                            wp_register_script($aJs[0], $vendorURL . $aJs[1], ['jquery'], WILOKE_THEMEVERSION, true);
                        }

                        if ($this->isMode($aJs, 'enqueue')) {
                            wp_enqueue_script($aJs[0]);
                        }
                    } else if (isset($aJs['isWPLIB'])) {
                        if (function_exists($aJs[0])) {
                            call_user_func($aJs[0]);
                        } else {
                            wp_enqueue_script($aJs[0]);
                        }
                    } else if (isset($aJs['isGoogleAPI'])) {
                        $googleAPI = isset($wiloke->aThemeOptions['general_google_api']) &&
                        !empty($wiloke->aThemeOptions['general_google_api']) ?
                            $wiloke->aThemeOptions['general_google_api'] : '';
                        $url = isset($aJs[1]) ? $aJs[1] : 'https://maps.googleapis.com/maps/api/js?key=';
                        $url = apply_filters('wilcity/filter/scripts/google-map', $url);
                        $url = $url . $googleAPI;

                        if (isset($wiloke->aThemeOptions['general_google_language']) &&
                            !empty($wiloke->aThemeOptions['general_google_language'])) {
                            $url .= '&language=' . esc_js(trim($wiloke->aThemeOptions['general_google_language']));
                        }

                        wp_enqueue_script($aJs[0], $url);
                    } else {
                        if ($this->isMode($aJs, 'register')) {
                            wp_register_script($aJs[0], $jsURL . $aJs[1], ['jquery'], WILOKE_THEMEVERSION, true);
                        }

                        if ($this->isMode($aJs, 'enqueue')) {
                            wp_enqueue_script($aJs[0]);
                        }
                    }
                }
            }
        }

        if (is_user_logged_in()) {
            if ((wilcityIsAddListingPage() && isset($_GET['listing_type'])) ||
                (wilcityOnMyListingPage() && Session::getPaymentObjectID() == $post->ID)) {
                wp_enqueue_script(
                    'payandpublish',
                    $jsURL . 'PayAndPublish.min.js',
                    ['jquery'],
                    WILOKE_THEMEVERSION,
                    true
                );
            }
        }

        if (isset($aScripts['css'])) {
            foreach ($aScripts['css'] as $aCSS) {
                if (isset($aCSS['conditional'])) {
                    $conditionalRelation = isset($aJs['conditionalRelation']) ? $aJs['conditionalRelation'] : 'AND';
                    if (!$this->checkConditional($aCSS['conditional'], $aCSS, $conditionalRelation)) {
                        continue;
                    }
                }

                if (isset($aCSS['isExternal']) && $aCSS['isExternal']) {
                    wp_register_style($aCSS[0], $aCSS[1], [], WILOKE_THEMEVERSION, false);
                    wp_enqueue_style($aCSS[0]);
                } else {
                    if (isset($aCSS['isVendor'])) {
                        wp_register_style($aCSS[0], $vendorURL . $aCSS[1], [], WILOKE_THEMEVERSION);
                        wp_enqueue_style($aCSS[0]);
                    } else if (isset($aCSS['isWPLIB'])) {
                        wp_enqueue_style($aCSS[0]);
                    } elseif (isset($aCSS['isGoogleFont'])) {
                        wp_enqueue_style($aCSS[0], self::fontUrl($aCSS[1]));
                    } else if (isset($aCSS['isFont'])) {
                        if ($this->isMode($aCSS, 'register')) {
                            wp_register_style($aCSS[0], $fontURL . $aCSS[1], [], WILOKE_THEMEVERSION);
                        } else {
                            wp_enqueue_style($aCSS[0], $fontURL . $aCSS[1], [], WILOKE_THEMEVERSION);
                        }
                    } else {
                        wp_register_style($aCSS[0], $cssURL . $aCSS[1], [], WILOKE_THEMEVERSION);
                        wp_enqueue_style($aCSS[0]);
                    }
                }
            }
        }

        if (isset($wiloke->aThemeOptions['advanced_google_fonts']) &&
            $wiloke->aThemeOptions['advanced_google_fonts'] == 'general' &&
            class_exists('WilokeListingTools\Framework\Helpers\GetSettings')) {
            if (isset($wiloke->aThemeOptions['advanced_general_google_fonts']) &&
                !empty($wiloke->aThemeOptions['advanced_general_google_fonts'])) {
                wp_enqueue_style('wilcity-custom-google-font',
                    esc_url($wiloke->aThemeOptions['advanced_general_google_fonts']));

                $cssRules = $wiloke->aThemeOptions['advanced_general_google_fonts_css_rules'];

                if (!empty($cssRules)) {
                    $googleFont = GetSettings::getOptions('custom_google_font', true, true);
                    $fontTextFileName = 'fontText.css';
                    $fontTitleFileName = 'fontTitle.css';
                    if ($googleFont == urlencode($cssRules) && WilcityFileSystem::isFileExists($fontTextFileName)) {
                        wp_enqueue_style('wilcity-google-font-text-rules',
                            WilcityFileSystem::getFileURI($fontTextFileName));
                        wp_enqueue_style('wilcity-google-font-title-rules',
                            WilcityFileSystem::getFileURI($fontTitleFileName));
                    } else {
                        ob_start();
                        include get_template_directory() . '/assets/production/css/fonts/fontText.css';
                        $fontText = ob_get_clean();
                        $fontText = str_replace('#googlefont', $cssRules, $fontText);

                        ob_start();
                        include get_template_directory() . '/assets/production/css/fonts/fontTitle.css';
                        $fontTitle = ob_get_clean();
                        $fontTitle = str_replace('#googlefont', $cssRules . ' !important;', $fontTitle);

                        if (WilcityFileSystem::filePutContents($fontTextFileName, $fontText)) {
                            WilcityFileSystem::filePutContents($fontTitleFileName, $fontTitle);

                            wp_enqueue_style('wilcity-custom-fontText',
                                WilcityFileSystem::getFileURI($fontTextFileName));
                            wp_enqueue_style('wilcity-custom-fontTitle',
                                WilcityFileSystem::getFileURI($fontTitleFileName));
                            SetSettings::setOptions('custom_google_font',
                                urlencode($cssRules));
                        } else {
                            wp_add_inline_style('wilcity-custom-fontText', $fontText);
                            wp_add_inline_style('wilcity-custom-fontTitle', $fontTitle);
                        }
                    }
                }
            }
        }

        wp_enqueue_script('comment-reply');
        wp_enqueue_style(WILOKE_THEMESLUG, get_stylesheet_uri(), [], WILOKE_THEMEVERSION);

        if (isset($wiloke->aThemeOptions['advanced_main_color']) &&
            !empty($wiloke->aThemeOptions['advanced_main_color'])) {

            if ($wiloke->aThemeOptions['advanced_main_color'] != 'custom') {
                wp_enqueue_style(WILCITY_WHITE_LABEL . '-custom-color',
                    $cssURL . 'colors/' . $wiloke->aThemeOptions['advanced_main_color'] .
                    '.css', [], WILOKE_THEMEVERSION);
            } else {
                if (class_exists('\WilokeListingTools\Framework\Helpers\FileSystem')) {
                    $currentCustomColor = get_option('wilcity_current_custom_color');
                    $version = get_option('wilcity_current_custom_color_version');
                    if (!isset($_GET['reset_custom_color']) &&
                        WilcityFileSystem::isFileExists('custom-main-color.css') &&
                        $currentCustomColor == $wiloke->aThemeOptions['advanced_custom_main_color']['rgba']) {
                        wp_enqueue_style(WILCITY_WHITE_LABEL . '-custom-color',
                            WilcityFileSystem::getFileURI('custom-main-color.css'), [], $version);
                    } else {
                        if (isset($wiloke->aThemeOptions['advanced_custom_main_color']) &&
                            isset($wiloke->aThemeOptions['advanced_custom_main_color']['rgba'])) {
                            if (!function_exists('WP_Filesystem')) {
                                require_once(ABSPATH . 'wp-admin/includes/file.php');
                            }
                            WP_Filesystem();
                            global $wp_filesystem;
                            $defaultCSS = $wp_filesystem->get_contents($cssDir . 'colors/default.css');
                            if (empty($wiloke->aThemeOptions['advanced_custom_main_color']['alpha'])) {
                                $customColor = $wiloke->aThemeOptions['advanced_custom_main_color']['color'];
                            } else {
                                $customColor = $wiloke->aThemeOptions['advanced_custom_main_color']['rgba'];
                            }
                            $parseCSS = str_replace(
                                '#f06292',
                                $customColor,
                                $defaultCSS
                            );

                            $status = WilcityFileSystem::filePutContents('custom-main-color.css', $parseCSS);
                            if ($status) {
                                update_option('wilcity_current_custom_color',
                                    $wiloke->aThemeOptions['advanced_custom_main_color']['rgba']);
                                update_option('wilcity_current_custom_color_version', time());
                                wp_enqueue_style(WILCITY_WHITE_LABEL . '-custom-color',
                                    WilcityFileSystem::getFileURI('custom-main-color.css'), [], $version);
                            } else {
                                wp_add_inline_style(WILOKE_THEMESLUG, $parseCSS);
                            }
                        }
                    }
                }
            }
        }

        if (isset($wiloke->aThemeOptions['advanced_css_code']) && !empty($wiloke->aThemeOptions['advanced_css_code'])) {
            wp_add_inline_style(WILOKE_THEMESLUG, $wiloke->aThemeOptions['advanced_css_code']);
        }

        if (isset($wiloke->aThemeOptions['advanced_js_code']) && !empty($wiloke->aThemeOptions['advanced_js_code'])) {
            wp_add_inline_script('wilcity-empty', $wiloke->aThemeOptions['advanced_js_code']);
        }

        wp_enqueue_script('wilcity-empty');
    }
}
