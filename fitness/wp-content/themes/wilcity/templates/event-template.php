<?php
/*
 * Template Name: Wilcity Events Template
 */

use WilokeListingTools\Framework\Helpers\GetSettings;

get_header();
global $wiloke;
$pageID = get_queried_object()->ID;

if (have_posts()) : while (have_posts()) : the_post();
    $postsPerPage = GetSettings::getPostMeta($post->ID, 'events_per_page');
    ?>
    <div id="<?php echo esc_attr(apply_filters('wilcity/filter/id-prefix', 'wilcity-no-map')); ?>" class="wil-content">
        <div id="wil-search-v1">
            <div v-if="!isDesktop" class="listing-bar_module__2BCsi js-listing-bar-sticky js-sticky-for-md">
                <div class="container">
                    <div class="listing-bar_layout__TK3vH">
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

            <section class="wil-section bg-color-gray-2 pt-30">
                <div class="container">
                    <div class="row">
                        <div class="col-md-8 js-sticky">
                            <h1 class="text-center wil-event-template-title"><?php the_title(); ?></h1>
                            <?php the_content(); ?>
                            <wil-async-grid
                                endpoint="<?php echo esc_url(rest_url(WILOKE_PREFIX.'/v2/listings')); ?>"
                                v-on:max-posts="handleUpdateMaxPosts" :is-random-premium="true"
                                column-classes="<?php echo esc_attr(GetSettings::getColumnClasses($pageID)); ?>"
                                :type="parseItemType" @change="handleUpdatePosts"
                                v-on:mouse-on="handleMouseOnListing"
                                v-on:mouse-leave="handleMouseLeaveListing" :query-args="query">
                                <template v-slot:after-grid="{page, maxPages, postsPerPage, isLoading}">
                                    <wil-pagination v-if="maxPages > 1 && !isLoading" name="after-grid"
                                                    @change="handlePaginationChange"
                                                    :max-pages="maxPages"
                                                    :current-page="page"></wil-pagination>
                                </template>
                            </wil-async-grid>
                        </div>
                        <div class="wilcity-events-sidebar col-md-4 js-sticky">
                            <wil-search-form-v1
                                v-if="isDesktop"
                                wrapper-classes="content-box_module__333d9"
                                inner-classes="content-box_body__3tSRB"
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
                            <?php
                            if (is_active_sidebar('wilcity-sidebar-events')) {
                                dynamic_sidebar('wilcity-sidebar-events');
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
<?php
endwhile; endif;
wp_reset_postdata();
do_action('wilcity/before-close-root');
get_footer();
