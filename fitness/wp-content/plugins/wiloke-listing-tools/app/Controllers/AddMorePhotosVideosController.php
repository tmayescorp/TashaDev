<?php

namespace WilokeListingTools\Controllers;


use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Routing\Controller;

class AddMorePhotosVideosController extends Controller
{
    public static $aPlanSettings = null;
    protected     $aRawGallery;
    protected     $listingID;
    protected     $aGallery;
    protected     $aVideos;

    use InsertGallery;
    use SetVideo;

    public function __construct()
    {
//		add_action('wilcity/footer/vue-popup-wrapper', array($this, 'printFooter'));
        add_action('wp_ajax_fetch_photos_of_listing', [$this, 'fetchPhotos']);
        add_action('wp_ajax_nopriv_fetch_photos_of_listing', [$this, 'fetchPhotos']);
//		add_action('wp_ajax_fetch_videos_of_listing', array($this, 'fetchVideos'));
//		add_action('wp_ajax_nopriv_fetch_videos_of_listing', array($this, 'fetchVideos'));

        add_action('rest_api_init', function () {
            register_rest_route(WILOKE_PREFIX . '/v2', '/listings/(?P<postID>\d+)/add-more-videos', [
                'methods'             => 'GET',
                'callback'            => [$this, 'getVideos'],
                'permission_callback' => '__return_true'
            ]);

            register_rest_route(WILOKE_PREFIX . '/v2', '/listings/(?P<postID>\d+)/add-more-photos', [
                'methods'  => 'GET',
                'callback' => [$this, 'getPhotos'],
                'permission_callback' => '__return_true'
            ]);
        });

        add_action('wp_ajax_update_gallery_and_videos', [$this, 'updateGalleryAndVideos']);
    }

    protected function getPlanSettings()
    {
        $planID = GetSettings::getPostMeta($this->listingID, 'belongs_to');
        if (isset(self::$aPlanSettings[$planID])) {
            return self::$aPlanSettings[$planID];
        }

        if (empty($planID)) {
            self::$aPlanSettings[$planID] = [];
        } else {
            self::$aPlanSettings[$planID] = GetSettings::getPlanSettings($planID);
        }

        return self::$aPlanSettings[$planID];
    }

    protected function parseVideo()
    {
        $this->aVideos = $_POST['videos'];
        if (!current_user_can('edit_theme_options')) {
            $aPlanSettings = $this->getPlanSettings();
            if ($aPlanSettings && $aPlanSettings['toggle_videos'] == 'disable') {
                return true;
            }

            if (!empty($this->aVideos)) {
                if (!empty($aPlanSettings['maximumVideos'])) {
                    $this->aVideos = array_splice($this->aVideos, 0, $aPlanSettings['maximumVideos']);
                }
            }
        }

        $this->setVideos();

        return true;
    }

    protected function parseGallery()
    {
        $this->aRawGallery = $_POST['gallery'];
        if (!current_user_can('edit_theme_options')) {
            $aPlanSettings = $this->getPlanSettings();
            if (!empty($aPlanSettings) && $aPlanSettings['toggle_gallery'] == 'disable') {
                return true;
            }

            if (!empty($this->aRawGallery)) {
                if (!empty($aPlanSettings['maximumGalleryImages'])) {
                    $this->aRawGallery = array_splice($this->aRawGallery, 0, $aPlanSettings['maximumGalleryImages']);
                }
            }
        }
        $this->insertGallery();

        return true;
    }

    public function updateGalleryAndVideos()
    {
        $this->listingID = $_POST['listingID'];

        $this->middleware(['isListingBeingReviewed', 'isPostAuthor'], [
            'postID'        => $this->listingID,
            'passedIfAdmin' => true
        ]);

        $this->parseGallery();
        $this->parseVideo();

        wp_send_json_success([
            'msg' => esc_html__('Congratulations! Your update has been successfully', 'wiloke-listing-tools')
        ]);
    }

    public function getVideos($oData)
    {
        $this->listingID = $oData->get_param('postID');
        $aPlanSettings = $this->getPlanSettings();

        if (!empty($aPlanSettings) && $aPlanSettings['toggle_videos'] == 'disable') {
            return [
                'error' => [
                    'userMessage' => esc_html__('The Add Video is not supported by this plan.', 'wiloke-listing-tools'),
                    'code'        => 404
                ]
            ];
        }

        $aRawVideos = GetSettings::getPostMeta($this->listingID, 'video_srcs');

        if (!empty($aRawVideos)) {
            $aReturn = ['videos' => $aRawVideos];
            $aReturn = $aReturn + ['oPlanSettings' => $aPlanSettings];
        }

        if (!isset($aPlanSettings['maximumVideos']) || empty($aPlanSettings['maximumVideos'])) {
            $aReturn['msg'] = '';
            $aReturn['videos'] = [];
        } else {
            $aReturn['msg'] = sprintf(esc_html__('You can upload maximum %s video urls to this listing',
                'wiloke-listing-tools'), $aPlanSettings['maximumVideos']);
        }
        return ['data' => $aReturn];
    }

    public function getPhotos($oData)
    {
        $this->listingID = $oData->get_param('postID');

        $aPlanSettings = $this->getPlanSettings();

        if ($aPlanSettings && $aPlanSettings['toggle_gallery'] == 'disable') {
            return [
                'error' => [
                    'userMessage' => esc_html__('The add photos feature is not supported by this plan.',
                        'wiloke-listing-tools'),
                    'code'        => 404
                ]
            ];
        }

        $aRawPhotos = GetSettings::getPostMeta($this->listingID, 'gallery');

        $aReturn = [];
        $aPhotos = [];
        if (!empty($aRawPhotos)) {
            foreach ($aRawPhotos as $id => $src) {
                $aPhoto['imgID'] = $id;
                $aPhoto['src'] = wp_get_attachment_image_url($id, 'medium');
                $aPhotos['images'][] = $aPhoto;
            }

            $aReturn = $aPhotos;
        }

        if (!current_user_can('edit_theme_options')) {
            $aReturn = $aReturn + ['oPlanSettings' => $aPlanSettings];
        }

        if (!isset($aPlanSettings['maximumGalleryImages']) || empty($aPlanSettings['maximumGalleryImages'])) {
            $aReturn['msg'] = '';
        } else {
            $aReturn['msg'] = sprintf(esc_html__('You can upload maximum %s images to this listing',
                'wiloke-listing-tools'), $aPlanSettings['maximumGalleryImages']);
        }

        return ['data' => $aReturn];
    }
}
