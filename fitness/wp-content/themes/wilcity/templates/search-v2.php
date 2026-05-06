<?php
/*
 * Template Name: Wilcity Search V2
 */

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\General;
use Wilcity\Map\FactoryMap;
use WilokeListingTools\Framework\Helpers\TermSetting;
use \WilokeListingTools\Framework\Helpers\Validation;

get_header();

if (!class_exists('WilokeListingTools\Framework\Helpers\Validation')) {
    return false;
}

$markerSvg = get_template_directory_uri() . '/assets/img/marker.svg';
$postType = isset($_GET['postType']) ? trim($_GET['postType']) : General::getDefaultPostTypeKey(false);
$oMap = new FactoryMap;
$aMapSettings = $oMap->set()->getAllConfig();
$searchPageWithType = \WilokeThemeOptions::getOptionDetail('search_page_layout');
$searchPageWrapperClasses = 'container mt-30';
$searchPageID = abs(\WilokeThemeOptions::getOptionDetail('search_page'));
$isSidebar = GetSettings::getPostMeta($searchPageID, 'toggle_sidebar') === 'enable';
if (!$isSidebar) {
    $isSidebar = is_tax() && is_active_sidebar('wilcity-listing-taxonomy');
}

$page = isset($_GET['page']) ? trim($_GET['page']) : 1;
if ($searchPageWithType !== 'container-default') {
    $searchPageWrapperClasses = 'container-fluid';
}

$defaultSearch = "";
if (isset($_GET['keyword'])) {
    $defaultSearch = stripslashes($_GET['keyword']);
} else if (isset($_GET['oAddress'])) {
    if (Validation::isValidJson(urldecode($_GET['oAddress']))) {
        $aAddress = Validation::getJsonDecoded();
        $defaultSearch = $aAddress['address'] ?? '';
    }
} else if (is_tax()) {
    $defaultSearch = get_queried_object()->name;
    if (!isset($_GET['postType'])) {
        $postType = TermSetting::getDefaultPostType(get_queried_object()->term_id, get_queried_object()->taxonomy);
    }
}

$aSearchTarget = \WilokeThemeOptions::getOptionDetail('complex_search_target');
if (empty($aSearchTarget) || empty($aSearchTarget['enabled'])) {
    $aSearchTarget = ['geocoder', 'listing_location', 'listing'];
} else {
    unset($aSearchTarget['enabled']['placebo']);
    $aSearchTarget = array_keys($aSearchTarget['enabled']);
}
global $post;
$position = GetSettings::getPostMeta($post->ID, 'show_content_at');
$position = empty($position) ? 'hidden' : $position;

?>
    <div id="wil-search-v2" class="wil-content container-fullwidth">
        <?php do_action('wilcity/search-without-map/before-section'); ?>
        <notifications position="right"></notifications>

        <div class="searchbox_module__3ZYKm">
            <div class="container">
                <div class="searchbox_wrap__37JXq">
                    <div class="searchbox_searchInput__2p4ds">
                        <div class="field_module__1H6kT field_style5__3OR3T mb-0 js-field">
                            <div class="field_wrap__Gv92k wil-autocomplete-field-wrapper">
                                <wil-auto-complete @change="handleAutoCompleteChange"
                                                   v-on:keyword-change="handleUpdateKeyword"
                                                   :search-target='<?php echo json_encode($aSearchTarget); ?>'
                                                   placeholder="<?php esc_html_e('Search for address, title, location ...',
                                                       'wilcity'); ?>"
                                                   default-search="<?php echo esc_attr($defaultSearch); ?>"
                                                   module="complex"
                                                   :postType="postType" :external-params="parseAutoCompleteParams">
                                </wil-auto-complete>
                            </div>
                        </div>
                    </div>
                    <div class="searchbox_searchButton__1c9iK">
                        <a class="wil-btn wil-btn--primary wil-btn--round wil-btn--md" href="#"
                           @click.prevent="handleTopSearchFormClick">
                            <i class="fa fa-search"></i> <?php esc_html_e('Search', 'wilcity'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <section class="wil-section bg-color-gray-2 pt-0">
            <div class="listing-bar_module__2BCsi js-listing-bar-sticky">
                <div class="container">
                    <div class="listing-bar_resuilt__R8pwY">
                        <?php esc_html_e('We found', 'wilcity'); ?>
                        <span class="color-primary" v-text="totalListingText"></span>
                        <a @click.prevent="reset" class="wil-btn wil-btn--border wil-btn--round wil-btn--xs"
                           href="#">
                            <i class="color-primary la la-share"></i>
                            <?php esc_html_e('Reset', 'wilcity'); ?>
                        </a>
                    </div>
                    <?php if (apply_filters('wilcity/filter/templates/search-v2/is-using-post-type-navbar',
                        true)) : ?>
                        <wil-search-form-post-types :active="postType" :post-types="postTypes"
                                                    @change="handlePostTypeChange"></wil-search-form-post-types>
                    <?php endif; ?>
                    <?php do_action('wilcity/templates/search-v2/after-post-type-nav-bar'); ?>
                </div>
            </div>

            <div :class="searchFieldWrapper" style="min-height: 59px;">
                <div class="container">
                    <wil-search-form-v2 v-if="!isMobile"
                                        :search-fields="searchFields"
                                        :cache-timestamp="cacheTimestamp"
                                        :post-type="postType"
                                        @change="handleFormChange"
                                        @back="showPreviousTerms"
                                        :deep-terms="deepTerms"
                                        @field-change="handleFieldChange"
                                        :value="query"
                                        v-on:fetch-term-child="fetchSubTerms"
                                        v-on:dropdown-click="handleDropdownClick"></wil-search-form-v2>
                    <wil-search-form-v2-mobile v-else :search-fields="searchFields"
                                               :cache-timestamp="cacheTimestamp"
                                               :is-mobile="isMobile" :post-type="postType"
                                               @change="handleFormChange"
                                               @clear="handleAfterClearValue"
                                               @field-change="handleFieldChange"
                                               :value="query"
                                               v-on:dropdown-click="handleDropdownClick"></wil-search-form-v2-mobile>
                    <wil-switch @change="isShowMap=!isShowMap" type="style2" :value="isShowMap" :true-value="true"
                                :false-value="false" wrapper-classes="wil-map-btn"
                                v-show="!isHideMapBtn"
                                @clear="handleAfterClearValue"
                                label="<?php esc_html_e('Map', 'wilcity'); ?>">
                    </wil-switch>
                </div>
            </div>

            <?php do_action('wilcity/templates/search-v2/before/result'); ?>

            <?php
            if (!is_tax() && $position == 'before') {
                if (have_posts()) {
                    ?>
                    <?php
                    while (have_posts()) {
                        the_post();
                        the_content();
                    }
                    ?>
                    <?php
                }
                wp_reset_postdata();
            }
            ?>

            <div id="wil-results-wrapper" class="container mt-30">
                <div class="row flex-sm">
                    <?php if ($isSidebar) : ?>
                        <div class="wil-page-sidebar" style="padding-left: 0;">
                            <?php
                            do_action('wilcity/search-without-map-sidebar');
                            if (is_active_sidebar('wilcity-listing-taxonomy')) {
                                dynamic_sidebar('wilcity-listing-taxonomy');
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                    <div class="w-100 flex-lg wil-page-content" style="order: 0">
                        <wil-async-grid endpoint="<?php echo esc_url(rest_url(WILOKE_PREFIX . '/v2/listings')); ?>"
                                        v-on:max-posts="handleUpdateMaxPosts"
                                        :is-random-premium=<?php echo apply_filters('wilcity/filter/templates/search-v2/is-random-premium',
                                            'true'); ?>
                                        :wrapper-classes="contentClasses"
                                        column-classes="<?php echo esc_attr(GetSettings::getColumnClasses($searchPageID)); ?>"
                                        @loaded="scrollToTop"
                                        page-now="search"
                                        :type="parseItemType"
                                        @change="handleUpdatePosts"
                                        v-on:click="handleMouseOnListing"
                                        :query-args="query">
                            <template v-slot:after-grid="{page, maxPages, postsPerPage, isLoading}">
                                <wil-pagination v-if="maxPages > 1 && !isLoading" name="after-grid"
                                                @change="handlePaginationChange" :max-pages="maxPages"
                                                :current-page="page"></wil-pagination>
                            </template>
                        </wil-async-grid>

                        <keep-alive>
                            <div v-cloak v-if="isShowMap" :class="mapClasses">
                                <component marker-svg="<?php echo esc_url($markerSvg); ?>"
                                           @dragend="handleSearchAfterMapDragend" :raw-items="posts"
                                           :is-open-map="isShowMap" :mouse-on-item="mouseOnItem" :is-multiple="true"
                                           access-token="<?php echo esc_attr($aMapSettings['accessToken']); ?>"
                                           map-style="<?php echo esc_attr($aMapSettings['style']); ?>"
                                           :max-zoom="<?php echo abs($aMapSettings['maxZoom']); ?>"
                                           :min-zoom="<?php echo abs($aMapSettings['minZoom']); ?>"
                                           :default-zoom="<?php echo abs($aMapSettings['defaultZoom']); ?>"
                                           wrapper-classes="wil-map-show" style="height: 100%"
                                           :lat-lng="latLng"
                                           language="<?php echo esc_attr(WilokeThemeOptions::getOptionDetail('general_google_language',
                                               'en')); ?>"
                                           is="<?php echo $aMapSettings['vueComponent']; ?>">
                                </component>
                            </div>
                        </keep-alive>
                    </div>
                </div>
            </div>
            <?php
            if (!is_tax() && $position == 'after') {
                if (have_posts()) {
                    while (have_posts()) {
                        the_post();
                        the_content();
                    }
                }
                wp_reset_postdata();
            }
            ?>
            <?php do_action('wilcity/templates/search-v2/after/result'); ?>
        </section>
        <?php do_action('wilcity/search-without-map/after-section'); ?>
    </div>
<?php
do_action('wilcity/before-close-root');
get_footer();
