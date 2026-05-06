<?php
global $post, $wilcityReviewConfiguration;
if ( !isset($wilcityReviewConfiguration['enableReview']) || !$wilcityReviewConfiguration['enableReview'] ){
    return '';
}
?>
<wil-comment-form heading="<?php echo esc_attr__('Discussion', 'wilcity'); ?>"
                  post-comment-text="<?php echo esc_attr__('Post Discussion', 'wilcity'); ?>"
                  @submitted="handleSubmittedDiscussion"
                  content-label="<?php echo esc_attr__('Discussion', 'wilcity'); ?>"
                  heading-icon="la la-comments"
                  :post-id="<?php echo abs($post->ID); ?>"
                  :is-user-logged-in="reviewConfiguration.isUserLoggedIn === 'yes'"></wil-comment-form>

