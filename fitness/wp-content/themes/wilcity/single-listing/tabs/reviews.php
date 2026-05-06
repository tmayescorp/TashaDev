<?php global $post; ?>
<wil-single-nav-wrapper v-if="currentTab === 'reviews'" :settings="getNavigation('reviews')" id="single-nav-reviews">
    <template v-slot:default="{settings}">
        <div class="listing-detail_row__2UU6R clearfix">
            <div class="wil-colLarge js-sticky pos-r">
                <wil-review-items id="wilcity-review-wrapper"
                                  :target-id="<?php echo abs($post->ID); ?>"
                                  target-type="postParent"
                                  :is-show-empty="true"
                                  always-return-review="yes"
                                  :is-loadmore="true">
                    <template v-slot:default="{review}">
                        <wil-review-item id="wil-review-on-home" :socials-sharing="reviewConfiguration.sharingOn"
                                         :review-id="review.ID"
                                         :can-do-anything="reviewConfiguration.isAdministrator==='yes'"
                                         :is-allow-reported="reviewConfiguration.isAllowReported === 'enable'"
                                         :is-discussion-allowed="reviewConfiguration.isDiscussionAllowed==='yes'"
                                         :review="review">
                            <template v-slot:wil-review-item-after-footer="{item, canDoAnything, isDiscussionAllowed}">
                                <wil-lazy-load-component :id="`wil-listing-review-discussion-${item.ID}`">
                                    <template v-slot:default="{isInView}">
                                        <wil-review-discussion-items
                                            v-if="isInView"
                                            :parent-id="item.ID"
                                            :parent="item"
                                            :is-user-logged-in="reviewConfiguration.isUserLoggedIn === 'yes'"
                                            :my-info="reviewConfiguration.myInfo"
                                            uquid="<?php echo uniqid('wil-discussion-item'); ?>"
                                            :can-do-anything="canDoAnything"
                                            :is-discussion-allowed="isDiscussionAllowed"></wil-review-discussion-items>
                                    </template>
                                </wil-lazy-load-component>
                            </template>
                        </wil-review-item>
                    </template>
                </wil-review-items>
            </div>
            <div class="wil-colSmall js-sticky">
                <wil-review-details-statistic wrapper-classes="content-box_module__333d9"
                                              :my-user-id="<?php echo abs(get_current_user_id()); ?>"
                                              :post-id="<?php echo abs($post->ID); ?>"
                                              post-title="<?php echo esc_attr($post->post_title);
                                              ?>"></wil-review-details-statistic>
            </div>
        </div>
    </template>
</wil-single-nav-wrapper>
