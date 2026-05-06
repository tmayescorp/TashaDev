<?php
/*
 * Template Name: Wilcity Search With Map
 */

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\General;
use Wilcity\Map\FactoryMap;

get_header();
$markerSvg          = get_template_directory_uri().'/assets/img/marker.svg';
$postType           = isset($_GET['postType']) ? trim($_GET['postType']) : General::getDefaultPostTypeKey(false);
$oMap               = new FactoryMap;
$aMapSettings       = $oMap->set()->getAllConfig();
$searchPageWithType = \WilokeThemeOptions::getOptionDetail('search_page_layout');
$searchPageWrapperClasses = 'container mt-30';
if ($searchPageWithType !== 'container-default') {
    $searchPageWrapperClasses = 'container-fluid';
}


if (is_tax()) {
    $postID = WilokeThemeOptions::getOptionDetail('search_page');
} else {
    $postID = $post->ID;
}

$position = GetSettings::getPostMeta($postID, 'show_content_at');
$position = empty($position) ? 'hidden' : $position;

?>
    <div id="<?php echo esc_attr(apply_filters('wilcity/filter/id-prefix', 'wilcity-map-wrapper')); ?>"
         class="wil-content">
        <div id="wil-search-v1">
            <div class="listing-map_left__1d9nh js-listing-map-content">
                <?php do_action('wilcity/search-without-map/before-section'); ?>
                <section class="wil-section bg-color-gray-2 pt-0">
                    <div class="listing-bar_module__2BCsi js-listing-bar-sticky">
                        <div class="container">
                            <div class="listing-bar_resuilt__R8pwY">
                                <?php esc_html_e('We found ', 'wilcity'); ?>
                                <span class="color-primary" v-text="totalListingText"></span>
                                <a @click.prevent="reset" class="wil-btn wil-btn--border wil-btn--round wil-btn--xs"
                                   href="#">
                                    <i class="color-primary la la-share"></i>
                                    <?php esc_html_e('Reset', 'wilcity'); ?>
                                </a>
                            </div>
                            <wil-layout-switch wrapper-classes="listing-bar_layout__TK3vH"
                                               :layout="parseItemType"
                                               @change="handleUpdateListingLayout"></wil-layout-switch>
                            <div v-if="!isDesktop" class="listing-bar_layout__TK3vH">
                                <wil-link
                                    btn-name="<?php esc_html_e('Search', 'wilcity'); ?>"
                                    icon="la la-search"
                                    @click.prevent="toggleSearchPopupBtn"
                                    wrapper-classes="wil-btn js-listing-search-button-mobile wil-btn--primary wil-btn--round wil-btn--xs">
                                </wil-link>
                                <portal to="wil-modal" v-if="isOpenSearchPopup">
                                    <wil-popup
                                        id="search-form-popup"
                                        :is-open="true"
                                        has-submit-btn="no"
                                        @close="isOpenSearchPopup = false"
                                        title="<?php esc_html_e('Search', 'wilcity'); ?>"
                                    >
                                        <div slot="body">
                                            <wil-search-form-v1 :is-loading="isFetchingSearchFields"
                                                                :search-fields="searchFields"
                                                                :cache-timestamp="cacheTimestamp"
                                                                @field-change="handleFieldChange"
                                                                :post-type="postType" @change="handleFormChange"
                                                                :value="query">
                                                <template v-slot:beforeformfields="{isLoading}">
                                                    <content-placeholders v-if="isLoading">
                                                        <content-placeholders-text :lines="4"></content-placeholders-text>
                                                    </content-placeholders>
                                                </template>
                                            </wil-search-form-v1>
                                        </div>
                                        <div slot="footer">
                                            <footer class="popup_footer__2pUrl">
                                                <wil-link
                                                    wrapper-classes="wil-btn wil-btn--primary wil-btn--md wil-btn--block"
                                                    icon="la la-search"
                                                    @click="handleMobileSearchQuery"
                                                    btn-name="<?php esc_html_e('Search', 'wilcity'); ?>"></wil-link>
                                            </footer>
                                        </div>
                                    </wil-popup>
                                </portal>
                            </div>
                        </div>
                    </div>
                    <div
                        class="content-box_module__333d9 content-box_lg__3v3a- listing-map_box__3QnVm mb-0 js-listing-search">
                        <div v-if="isDesktop" class="content-box_module__333d9">
                            <wil-alert v-if="searchFieldErrMsg.length" :msg="searchFieldErrMsg"
                                       type="danger"></wil-alert>
                            <wil-search-form-v1 v-else
                                                wrapper-classes="content-box_module__333d9"
                                                inner-classes="content-box_body__3tSRB"
                                                field-wrapper-classes="col-md-6 col-lg-6"
                                                :is-loading="isFetchingSearchFields"
                                                :search-fields="searchFields"
                                                :cache-timestamp="cacheTimestamp"
                                                @field-change="handleFieldChange"
                                                :post-type="postType" @change="handleFormChange" :value="query">
                                <template v-slot:beforeformfields="{isLoading}">
                                    <content-placeholders v-if="isLoading">
                                        <content-placeholders-text :lines="4"></content-placeholders-text>
                                    </content-placeholders>
                                </template>
                            </wil-search-form-v1>
                        </div>
                        <?php
                        if (!is_tax() && $position == 'before') {
                            if (have_posts()) {
                                while (have_posts()) {
                                    the_post();
                                    the_content();
                                }
                            }
                            wp_reset_postdata();
                        }
                        ?>
                        <?php do_action('wilcity/templates/search-v2/before/result'); ?>
                        <div
                            class="content-box_module__333d9 content-box_lg__3v3a- listing-map_box__3QnVm bg-color-gray-2">
                            <div class="content-box_body__3tSRB">
                                <wil-async-grid
                                    endpoint="<?php echo esc_url(rest_url(WILOKE_PREFIX.'/v2/listings')); ?>"
                                    v-on:max-posts="handleUpdateMaxPosts" :is-random-premium="true"
                                    wrapper-classes="w-100"
                                    column-classes="<?php echo esc_attr(GetSettings::getColumnClasses($postID)); ?>"
                                    :type="parseItemType" @change="handleUpdatePosts"
                                    v-on:mouse-on="handleMouseOnListing"
                                    v-on:mouse-leave="handleMouseLeaveListing" :query-args="query">
                                    <template v-slot:after-grid="{page, maxPages, postsPerPage, isLoading}">
                                        <wil-pagination v-if="maxPages > 1 && !isLoading" name="after-grid"
                                                        @change="handlePaginationChange" :max-pages="maxPages"
                                                        :current-page="page"></wil-pagination>
                                    </template>
                                </wil-async-grid>
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
                    </div>
                </section>
            </div>
            <div v-if="isDesktop" id="wilcity-map" class="listing-map_right__2Euc- js-listing-map">
                <keep-alive>
                    <component marker-svg="<?php echo esc_url($markerSvg); ?>"
                               @dragend="handleSearchAfterMapDragend" :raw-items="posts"
                               :mouse-on-item="mouseOnItem" :is-multiple="true"
                               access-token="<?php echo esc_attr($aMapSettings['accessToken']); ?>"
                               map-style="<?php echo esc_attr($aMapSettings['style']); ?>"
                               :max-zoom="<?php echo abs($aMapSettings['maxZoom']); ?>"
                               :min-zoom="<?php echo abs($aMapSettings['minZoom']); ?>"
                               :default-zoom="<?php echo abs($aMapSettings['defaultZoom']); ?>"
                               wrapper-classes="wil-map-show"
                               style="height: 100%"
                               is="<?php echo $aMapSettings['vueComponent']; ?>"
                    >
                    </component>
                </keep-alive>
            </div>
        </div>
    </div>
<?php
do_action('wilcity/before-close-root');
get_footer();
