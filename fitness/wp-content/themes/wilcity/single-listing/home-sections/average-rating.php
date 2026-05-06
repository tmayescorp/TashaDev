<div v-if="reviewConfiguration.reviews.statistic.total > 0" class="wilcity-single-listing-average-review-box
content-box_module__333d9">
    <header class="content-box_header__xPnGx clearfix">
        <div class="wil-float-left">
            <h4 class="content-box_title__1gBHS">
                <i class="la la-star-o"></i>
                <span><?php esc_html_e('Average Reviews', 'wilcity'); ?></span>
            </h4>
        </div>
    </header>
    <div class="content-box_body__3tSRB">
        <div class="average-rating-info_module__TOHeu">
            <div class="average-rating-info_left__255Tl">
                <wil-review-average-rating :mode="reviewConfiguration.reviews.mode"
                                           :average-rating="reviewConfiguration.reviews.statistic.average"
                                           :quality="reviewConfiguration.reviews.statistic.quality"
                >
                </wil-review-average-rating>
            </div>
            <div class="average-rating-info_right__3xLnz">
                <div v-for="(review, order) in reviewConfiguration.reviews.statistic.aDetails"
                     :key="`review-item-${review.key}-${order}`"
                     class="average-rating-info_item__2yvNR">
                    <wil-review-detail :item="review" :mode="reviewConfiguration.reviews.mode"></wil-review-detail>
                </div>
            </div>
        </div><!-- End / average-rating-info_module__TOHeu -->
    </div>
    <footer v-if="reviewConfiguration.reviews.isReviewed==='no'" class="content-box_footer__kswf3">
        <wil-review-btn wrapper-classes="content-box_link__2K0Ib wil-text-center" :post-id="<?php echo abs($post->ID)
        ; ?>" :review="myReview" icon="la la-star-o"></wil-review-btn>
    </footer>
</div>
