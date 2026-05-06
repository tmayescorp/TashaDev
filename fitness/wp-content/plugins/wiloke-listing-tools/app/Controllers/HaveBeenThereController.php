<?php


namespace WilokeListingTools\Controllers;


use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Models\HaveBeenThereModel;
use WilokeListingTools\Models\ReviewModel;

class HaveBeenThereController extends Controller
{
    public function __construct()
    {
        add_action('wp_ajax_wilcity_update_have_been_there', [$this, 'toggleHaveBeenThere']);
        add_action('wp_ajax_nopriv_wilcity_update_have_been_there', [$this, 'toggleHaveBeenThere']);
        add_action('wp_ajax_wilcity_count_have_been_there', [$this, 'countHaveBeenThere']);
        add_action('wp_ajax_nopriv_wilcity_count_have_been_there', [$this, 'countHaveBeenThere']);
    }

    public function countHaveBeenThere()
    {
        if (!isset($_GET['postId']) || empty($_GET['postId'])) {
            wp_send_json_error();
        } else {
            $total = HaveBeenThereModel::count($_GET['postId']);
            wp_send_json_success(['count' => $total]);
        }
    }

    public function toggleHaveBeenThere()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        $this->middleware(['verifyNonce']);

        if (!isset($_POST['postId']) || empty($_POST['postId']) || get_post_status($_POST['postId']) !== 'publish' ||
            !General::isPostTypeInGroup(get_post_type($_POST['postId']), 'listing')) {
            $oRetrieve->error(['msg' => esc_html__('Invalid post', 'wiloke-listing-tools')]);
        }

        $postId = absint($_POST['postId']);

        $popupMsg = !is_user_logged_in() ?
            esc_html__('Do you want to log into the site and leave a review for this listing', 'wiloke-listing-tools') :
            esc_html__('Do you want to leave a review for this listing', 'wiloke-listing-tools');

        if ($id = HaveBeenThereModel::isChecked($postId)) {
            HaveBeenThereModel::delete($id);
            $oRetrieve->success(
                [
                    'status'          => 'unchecked',
                    'popupMsg'        => $popupMsg,
                    'isReviewAllowed' => ReviewModel::isEnabledReview(get_post_type($_POST['postId'])) ? 'yes' :
                        'no',
                    'count'           => HaveBeenThereModel::count($postId)
                ]
            );
        }

        HaveBeenThereModel::add($postId);
        $oRetrieve->success(
            [
                'status'          => 'checked',
                'popupMsg'        => $popupMsg,
                'count'           => HaveBeenThereModel::count($postId),
                'isReviewAllowed' => ReviewModel::isEnabledReview(get_post_type($_POST['postId'])) ? 'yes' : 'no'
            ]
        );
    }
}
