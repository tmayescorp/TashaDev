<?php

use Wilcity\Map\FactoryMap;
use WilokeListingTools\Framework\Helpers\GetSettings;

function wilcityRenderMapSC($aAtts)
{
	global $wiloke;
	$mapType = WilokeThemeOptions::getOptionDetail('map_type');
	if ($mapType == 'mapbox') {
		wp_enqueue_style('mapbox-gl', 'https://api.tiles.mapbox.com/mapbox-gl-js/v0.53.1/mapbox-gl.css');
		wp_enqueue_script('jquery-ui-autocomplete');
		wp_enqueue_script('mapbox', get_template_directory_uri() . '/assets/production/js/mapbox.min.js', ['jquery'],
			WILCITY_SC_VERSION, true);
	} else {
		wp_enqueue_script('markerclusterer',
			get_template_directory_uri() . '/assets/vendors/googlemap/markerclusterer.js', ['jquery'],
			WILCITY_SC_VERSION,
			true);
		wp_enqueue_script('snazzy-info-window',
			get_template_directory_uri() . '/assets/vendors/googlemap/snazzy-info-window.min.js', ['jquery'],
			WILCITY_SC_VERSION, true);
		wp_enqueue_script(WILCITY_WHITE_LABEL . '-map',
			get_template_directory_uri() . '/assets/production/js/SearchFormV1.min.js',
			['jquery'], WILCITY_SC_VERSION, true);
	}

	$oMap = new FactoryMap;
	$aMapSettings = $oMap->set()->getAllConfig();
	$postID = WilokeThemeOptions::getOptionDetail('search_page');
	?>
    <section id="<?php echo esc_attr(apply_filters('wilcity/filter/id-prefix', 'wilcity-map-wrapper')); ?>"
             style="min-height: 500px;" class="wilcity-map-shortcode wil-section bg-color-gray-1 pd-0">
        <div id="wil-search-v1">
            <!--        <div v-show="!isInitialized" class="full-load">-->
            <!--            <div class="pill-loading_module__3LZ6v pos-a-center">-->
            <!--                <div class="pill-loading_loader__3LOnT"></div>-->
            <!--            </div>-->
            <!--        </div>-->
            <div class="listing-map_left__1d9nh js-listing-map-content">
                <!--            <div class="listing-bar_module__2BCsi js-listing-bar-sticky">-->
                <!--                <div class="container">-->
                <!--                    <div class="listing-bar_resuilt__R8pwY">-->
                <!--                        <span v-show="foundPosts!=0">-->
				<?php //esc_html_e('Showing', 'wilcity-shortcodes');
				?><!-- <span-->
                <!--                                    v-html="showingListingDesc"></span></span>-->
                <!--                        <a class="wil-btn wil-btn--border wil-btn--round wil-btn--xs" @click.prevent="resetSearchForm"-->
                <!--                           href="#"><i class="color-primary la la-share"></i> --><?php //esc_html_e('Reset',
				//								'wilcity-shortcodes');
				?>
                <!--                        </a>-->
                <!--                    </div>-->
                <!--                    <div class="listing-bar_layout__TK3vH">-->
                <!--                        <a class="listing-bar_item__266Xo js-grid-button color-primary" href="#"-->
                <!--                           data-tooltip="-->
				<?php //echo esc_attr__('Grid Layout', 'wilcity-shortcodes');
				?><!--"-->
                <!--                           @click.prevent="switchLayoutTo('grid')" data-tooltip-placement="bottom"><i-->
                <!--                                    class="la la-th-large"></i></a>-->
                <!--                        <a class="listing-bar_item__266Xo js-list-button" href="#"-->
                <!--                           @click.prevent="switchLayoutTo('list')"-->
                <!--                           data-tooltip="-->
				<?php //echo esc_attr__('List Layout', 'wilcity-shortcodes');
				?><!--"-->
                <!--                           data-tooltip-placement="bottom"><i class="la la-list"></i></a><a-->
                <!--                                class="listing-bar_item__266Xo js-map-button" href="#"><i-->
                <!--                                    class="la la-map-marker"></i><i class="la la-close"></i></a>-->
                <!--                        <a class="wil-btn js-listing-search-button wil-btn--primary wil-btn--round wil-btn--xs "-->
                <!--                           href="#"><i class="la la-search"></i> --><?php //esc_html_e('Search', 'wilcity-shortcodes');
				?>
                <!--                        </a>-->
                <!--                        <a class="wil-btn js-listing-search-button-mobile wil-btn--primary wil-btn--round wil-btn--xs "-->
                <!--                           href="#" @click.prevent="toggleSearchFormPopup"><i-->
                <!--                                    class="la la-search"></i> --><?php //esc_html_e('Search', 'wilcity-shortcodes');
				?>
                <!--                        </a>-->
                <!--                    </div>-->
                <!--                </div>-->
                <!--            </div>-->
                <!-- End / listing-bar_module__2BCsi -->

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
                <div v-if="isDesktop"
                     class="content-box_module__333d9 content-box_lg__3v3a- listing-map_box__3QnVm mb-0 js-listing-search">
                    <!--                <search-form v-on:searching="togglePreloader" type="-->
					<?php //echo esc_attr($aAtts['type']);
					?><!--"-->
                    <!--                             raw-taxonomies="" raw-taxonomies-options is-map="yes"-->
                    <!--                             posts-per-page="-->
					<?php //echo esc_attr($wiloke->aThemeOptions['listing_posts_per_page']);
					?><!--"-->
                    <!--                             lat-lng="" form-item-class="col-md-6 col-lg-6" is-popup="no" is-mobile="no"-->
                    <!--                             v-on:fetch-listings="triggerFetchListing"-->
                    <!--                             image-size="--><?php //echo esc_attr($aAtts['img_size']);
					?><!--"-->
                    <!--                             order-by="--><?php //echo esc_attr($aAtts['orderby']);
					?><!--"-->
                    <!--                             order="--><?php //echo esc_attr($aAtts['order']);
					?><!--"-->
                    <!--                             lat-lng="--><?php //echo esc_attr(trim($aAtts['latlng']));
					?><!--"-->
                    <!--                             template-style="--><?php //echo esc_attr($aAtts['style']);
					?><!--"></search-form>-->

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

                <div class="content-box_module__333d9 content-box_lg__3v3a- listing-map_box__3QnVm bg-color-gray-2">
                    <div class="content-box_body__3tSRB">
                        <!--                    <listings posts-per-page="-->
						<?php //echo abs($wiloke->aThemeOptions['listing_posts_per_page']);
						?><!--"-->
                        <!--                              img-size="--><?php //echo esc_attr($aAtts['img_size']);
						?><!--"></listings>-->
                        <wil-async-grid
                                endpoint="<?php echo esc_url(rest_url(WILOKE_PREFIX . '/v2/listings')); ?>"
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
            </div>

            <div v-if="isDesktop" id="wilcity-map" class="listing-map_right__2Euc- js-listing-map">
                <keep-alive>
                    <component
                            marker-svg="<?php echo esc_url(get_template_directory_uri() . "/assets/img/marker.svg"); ?>"
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

            <!--        <map default-zoom="--><?php //echo esc_attr($aAtts['default_zoom']);
			?><!--"-->
            <!--             max-zoom="--><?php //echo esc_attr($aAtts['max_zoom']);
			?><!--"-->
            <!--             min-zoom="--><?php //echo esc_attr($aAtts['min_zoom']);
			?><!--" mode="multiple"-->
            <!--             map-id="--><?php //echo esc_attr(apply_filters('wilcity/filter/id-prefix', 'wilcity-map'));
			?><!--"-->
            <!--             is-using-mapcluster="yes" grid-size="--><?php //echo esc_attr($aAtts['image_size']);
			?><!--"-->
            <!--             marker-svg="--><?php //echo esc_url(get_template_directory_uri()."/assets/img/marker.svg");
			?><!--"></map>-->
        </div>
    </section>
	<?php
}
