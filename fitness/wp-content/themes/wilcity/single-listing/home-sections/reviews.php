<?php global $post; ?>
<wil-lazy-load-component v-if="currentTab==='home'" id="wil-listing-reviews" height="1px;">
    <template v-slot:default="{isInView}">
        <wil-review-total-statistic v-if="isInView" :post-id="<?php echo abs($post->ID); ?>"
                                    :user-id="parseInt(data.userID, 10)"
                                    heading="<?php echo esc_attr($post->post_title); ?>"
                                    :is-reviewed="reviewConfiguration.reviews.isReviewed==='yes'"></wil-review-total-statistic>
        <wil-review-items v-if="isInView" id="wilcity-review-wrapper" :target-id="<?php echo abs($post->ID); ?>"
                          target-type="postParent">
            <template v-slot:default="{review}">
                <wil-review-item id="wil-review-on-home" :socials-sharing="reviewConfiguration.sharingOn" :review-id="review.ID"
                                 :can-do-anything="reviewConfiguration.isAdministrator==='yes'"
                                 :is-allow-reported="reviewConfiguration.isAllowReported === 'enable'"
                                 :is-discussion-allowed="reviewConfiguration.isDiscussionAllowed==='yes'"
                                 :review="review">
                    <template v-slot:wil-review-item-after-footer="{item, canDoAnything, isDiscussionAllowed}">
                        <wil-lazy-load-component :id="`wil-listing-review-discussion-${item.ID}`">
                            <template v-slot:default="{isInView}">
                                <wil-review-discussion-items v-if="isInView" :parent-id="item.ID"
                                                             :parent="item"
                                                             :is-user-logged-in="reviewConfiguration.isUserLoggedIn === 'yes'"
                                                             uquid="<?php echo uniqid('wil-discussion-item'); ?>"
                                                             :my-info="reviewConfiguration.myInfo"
                                                             :can-do-anything="canDoAnything"
                                                             :is-discussion-allowed="isDiscussionAllowed"></wil-review-discussion-items>
                            </template>
                        </wil-lazy-load-component>
                    </template>
                </wil-review-item>
            </template>
        </wil-review-items>
    </template>
</wil-lazy-load-component>
