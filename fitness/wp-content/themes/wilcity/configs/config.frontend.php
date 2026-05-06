<?php
$aThemeOptions = Wiloke::getThemeOptions(true);

if (WilokeThemeOptions::isEnable('toggle_custom_login_page')) {
    $reCaptchaCallback = 'wilcityRunReCaptcha';
} else {
    $reCaptchaCallback = 'vueRecaptchaApiLoaded';
}

if (!empty($aThemeOptions['general_google_language'])) {
    $googleCaptchaUrl = 'https://www.google.com/recaptcha/api.js?onload=' . $reCaptchaCallback .
        '&render=explicit&hl=' .
        $aThemeOptions['general_google_language'];
} else {
    $googleCaptchaUrl = 'https://www.google.com/recaptcha/api.js?onload=' . $reCaptchaCallback . '&render=explicit';
}

return apply_filters('wilcity/config/frontend', [
    'scripts'           => [
        'js'  => [
            ['lazyload', 'lazyload/jquery.lazy.min.js', 'isVendor' => true, 'conditional' => 'wilcityIsLazyLoad'],
            [
                'lazyload.picture',
                'lazyload/jquery.lazy.picture.min.js',
                'isVendor'    => true,
                'conditional' => 'wilcityIsLazyLoad'
            ],
            'stripev3'             => [
                'stripev3',
                'https://js.stripe.com/v3/',
                'isExternal'  => true,
                'conditional' => 'wilCityAllowToEnqueueStripe,wilcityIsNeedPaymentScript',
                'isDefer'     => true
            ],
            'underscore'           => [
                'underscore',
                'isWPLIB' => true,
            ],
            'jquery-ui-slider'     => [
                'jquery-ui-slider',
                'isWPLIB'     => true,
                'isDefer'     => true,
                'conditional' => 'wilcityIsSearchPage,wilcityIsMapPage'
            ],
            ['jquery-ui-autocomplete', 'isWPLIB' => true, 'conditional' => 'wilcityIsSearchPage,wilcityIsMapPage'],
            [
                'jquery-ui-touch-punch',
                'touchpunch/jquery.ui.touch-punch.min.js',
                'isVendor'    => true,
                'conditional' => 'wp_is_mobile'
            ],
            'jquery-ui-datepicker' => ['jquery-ui-datepicker', 'isWPLIB' => true, 'isDefer' => false],
            ['spectrum', 'spectrum/spectrum.js', 'isVendor' => true, 'conditional' => 'wilcityIsAddListingPage'],
            ['jqueryeasing', 'jquery.easing/jquery.easing.js', 'isVendor' => true],
            'perfect-scrollbar'    => [
                'perfect-scrollbar',
                'perfect-scrollbar/perfect-scrollbar.min.js',
                'isVendor' => true,
                'isDefer'  => true
            ],
            'magnific-popup'       => [
                'magnific-popup',
                'magnific-popup/jquery.magnific-popup.js',
                'isVendor'    => true,
                'isDefer'     => true,
//                'conditional' => '!wilcityIsSearchV2'
            ],
            'swiper'               => [
                'swiper',
                'swiper/swiper.js',
                'isVendor' => true,
                'isDefer'  => true,
                //                'conditional' => 'is_front_page'
            ],
            'MagnificGalleryPopup' => [
                'MagnificGalleryPopup',
                'MagnificGalleryPopup.min.js',
                'isDefer'     => true,
                'conditional' => '!wilcityIsSearchV2'
            ],
            'theia-sticky-sidebar' => [
                'theia-sticky-sidebar',
                'theia-sticky-sidebar/theia-sticky-sidebar.js',
                'isVendor' => true,
                'isDefer'  => true
            ],
            ['wilcity-shortcodes', 'shortcodes.min.js'],
            ['waypoints-vendor', 'waypoints/jquery.waypoints.min.js', 'isVendor' => true],
            ['bundle', 'index.min.js'],
            ['Notification', 'Notification.min.js', 'conditional' => 'is_user_logged_in'],
            ['MessageNotifications', 'MessageNotifications.min.js', 'conditional' => 'is_user_logged_in'],
            ['Follow', 'Follow.min.js', 'conditional' => 'is_user_logged_in'],
            'SearchFormV2'         => [
                'SearchFormV2',
                'SearchFormV2.min.js',
                'conditional'         => 'wilcityIsSearchV2,wilcityIsTax',
                'conditionalRelation' => 'OR'
            ],
            [
                'HeroSearchForm',
                'HeroSearchForm.min.js',
                'conditional' => 'wilcityIsPageBuilder|elementor_header_footer',
                'mode'        => 'register'
            ],
            [
                'LoginRegister',
                'LoginRegister.min.js'
            ],
            ['WilcityFavoriteStatistics', 'FavoriteStatistics.min.js'],
            'quick-search'         => [
                'quick-search', 'quick-search.min.js'
            ],
            ['googleReCaptcha', $googleCaptchaUrl, 'isExternal' => true, 'conditional' => 'wilcityIsNotUserLoggedIn,wilcityIsLoginPage'],
            ['wp_enqueue_media', 'isWPLIB' => true, 'conditional' => 'wilcityIsAddListingDashboardSingleListingPage'],
            ['addlisting', 'addlisting.min.js', 'conditional' => 'wilcityIsAddListingPage'],
            ['dashboard', 'dashboard.min.js', 'conditional' => 'wilcityIsDashboardPage'],
            ['AddListingBtn', 'AddListingBtn.min.js'],
            ['single-listing-handle', 'single-listing.min.js', 'conditional' => 'wilcityIsSingleListingPage'],
            ['wp_enqueue_media', 'isWPLIB' => true, 'conditional' => 'wilcityIsLoginedSingleListingPage'],
            ['single-event', 'single-event.min.js', 'conditional' => 'wilcityIsSingleEventPage'],
            ['app', 'app.min.js'],
            ['custom-login', 'customLogin.min.js', 'conditional' => 'wilcityIsCustomLogin'],
            ['becomeAnAuthor', 'becomeAnAuthor.min.js', 'conditional' => 'wilcityIsBecomeAnAuthor'],
            ['reset-password', 'resetPassword.min.js', 'conditional' => 'wilcityIsResetPassword'],
            [
                'SearchFormV1',
                'SearchFormV1.min.js',
                'conditional'         => 'wilcityIsSearchWithoutMapPage,wilcityIsMapPage,wilcityIsTax,wilcityIsEventsTemplate',
                'conditionalRelation' => 'OR'
            ],
            'Follow'               => ['Follow', 'Follow.min.js', 'conditional' => 'is_author']
        ],
        'css' => [
            ['bootstrap', 'bootstrap/grid.css', 'isVendor' => true],
            ['spectrum', 'spectrum/spectrum.css', 'isVendor' => true, 'conditional' => 'wilcityIsAddListingPage'],
            ['perfect-scrollbar', 'perfect-scrollbar/perfect-scrollbar.min.css', 'isVendor' => true],
            ['font-awesome4', 'fontawesome/font-awesome.min.css', 'isFont' => true, 'mode' => 'enqueue'],
            [
                'Poppins',
                'Poppins:400,500,600,700,900|Roboto:300,400|Dancing+Script&display=swap',
                'isGoogleFont' => true
            ],
            ['line-awesome', 'line-awesome/line-awesome.css', 'isFont' => true, 'mode' => 'enqueue'],
            ['magnific-popup', 'magnific-popup/magnific-popup.css', 'isVendor' => true, 'mode' => 'enqueue'],
            //            ['magnific-select2', 'select2/select2.css', 'isVendor' => true],
            ['swiper', 'swiper/swiper.css', 'isVendor' => true],
            ['jquery-ui-custom-style', 'ui-custom-style/ui-custom-style.min.css', 'isVendor' => true],
            // ['snazzy-info-window', 'googlemap/snazzy-info-window.min.css', 'isVendor' => true],
            // 'mapbox-gl' => [
            //     'mapbox-gl',
            //     'https://api.tiles.mapbox.com/mapbox-gl-js/v0.53.1/mapbox-gl.css',
            //     'conditional' => '!wilcityIsNewNoMapTemplate,wilcityIsMapbox,wilcityIsMapPageOrSinglePage',
            //     'isExternal'  => true
            // ],
            ['additional-woocommerce', 'woocommerce.min.css', 'conditional' => 'wilcityIsUsingWooCommerce'],
            ['app', 'app.min.css'],
            ['app-fix', 'patch.min.css']
        ]
    ],
    'register_nav_menu' => [
        'menu'   => [
            [
                'key'  => 'wilcity_menu',
                'name' => esc_html__('WilCity Menu', 'wilcity'),
            ],
            [
                'key'  => 'wilcity_footer_login_menu',
                'name' => 'Footer Custom Login Page'
            ]
        ],
        'config' => [
            'wilcity_menu'              => [
                'theme_location'  => 'wilcity_menu',
                'name'            => esc_html__('WilCity Menu', 'wilcity'),
                'menu'            => '',
                'container'       => '',
                'container_class' => '',
                'container_id'    => '',
                'menu_class'      => 'nav-menu',
                'menu_id'         => apply_filters('wilcity/filter/id-prefix', 'wilcity-menu'),
                'echo'            => true,
                'before'          => '',
                'after'           => '',
                'link_before'     => '',
                'link_after'      => '',
                'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
                'depth'           => 0,
                'walker'          => ''
            ],
            'wilcity_footer_login_menu' => [
                'theme_location'  => 'wilcity_footer_login_menu',
                'name'            => 'Footer Custom Login Page',
                'menu'            => '',
                'container'       => '',
                'container_class' => '',
                'container_id'    => '',
                'menu_class'      => 'nav-menu',
                'menu_id'         => 'wilcity-footer-login-menu',
                'echo'            => true,
                'before'          => '',
                'after'           => '',
                'link_before'     => '',
                'link_after'      => '',
                'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
                'depth'           => 0,
                'walker'          => ''
            ]
        ]
    ],
]);

