<?php $authorID = get_query_var('author'); ?>
<div id="wil-author-results" class="wilcity-author-reviews container">
    <wil-review-items id="wilcity-review-wrapper"
                      :target-id="<?php echo abs($authorID); ?>"
                      wrapper-classes="row"
                      target-type="author">
        <template v-slot:default="{review}">
            <div class="wilcity-review-wrapper__content">
                <wil-review-item id="wil-review-on-home" :socials-sharing="reviewConfiguration.sharingOn"
                                 :review-id="review.ID"
                                 :can-do-anything="reviewConfiguration.isAdministrator==='yes'"
                                 :is-allow-reported="reviewConfiguration.isAllowReported === 'enable'"
                                 :is-discussion-allowed="reviewConfiguration.isDiscussionAllowed==='yes'"
                                 wrapper-classes="comment-review_module__-Z5tr col-md-6 col-xs-12"
                                 inner-wrapper-classes="bg-white"
                                 :review="review">
                </wil-review-item>
            </div>
        </template>
    </wil-review-items>
</div>
