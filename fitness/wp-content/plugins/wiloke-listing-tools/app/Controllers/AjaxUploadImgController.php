<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Framework\Upload\Upload;
use WilokeListingTools\Frontend\User;

class AjaxUploadImgController extends Controller
{
    private $aValidFileTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    private $maxMSize;
    private $maxBSize;

    public function __construct()
    {
        add_action('wp_ajax_wilcity_ajax_upload_imgs', [$this, 'uploadImgsViaAjax']);
        add_action('wp_ajax_wilcity_delete_attachment', [$this, 'deleteAttachment']);
    }

    public function deleteAttachment()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $aStatus = $this->middleware(['verifyNonce']);

        if ($aStatus['status'] === 'error') {
            $oRetrieve->error($aStatus);
        }

        if (!isset($_POST['id']) || empty($_POST['id'])) {
            $oRetrieve->error([]);
        }

        $id = absint($_POST['id']);

        if (get_post_field('post_author', $id) != User::getCurrentUserID()) {
            $oRetrieve->error([]);
        }

        wp_delete_attachment($id);
        $oRetrieve->success([]);
    }

    private function deletePreviousImg()
    {
        if (isset($_GET['previous']) && !empty($_GET['previous']) && $_GET['previous'] !== 'undefined') {
            if (get_post_field('post_author', $_GET['previous']) == User::getCurrentUserID()) {
                wp_delete_attachment($_GET['previous']);
            }
        }
    }

    // Returns a file size limit in bytes based on the PHP upload_max_filesize
    // and post_max_size
    private function fileUploadMaxSize()
    {
        if (!empty($this->maxBSize)) {
            return $this->maxBSize;
        }

        $this->maxMSize = min(ini_get('post_max_size'), ini_get('upload_max_filesize'));
        $this->maxBSize = str_replace('M', '', $this->maxMSize);
        $this->maxBSize = $this->maxBSize * 1048576;

        return $this->maxBSize;
    }

    function isValidFileSize($fileSize)
    {
        $this->fileUploadMaxSize();

        return $fileSize <= $this->maxBSize;
    }

    public function uploadImgsViaAjax()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $aStatus = $this->middleware(['verifyNonce']);

        if ($aStatus['status'] === 'error') {
            $oRetrieve->error($aStatus);
        }

        $this->deletePreviousImg();
        if (!is_array($_FILES)) {
            $oRetrieve->error([
                'msg' => esc_html__('You need to upload 1 image at least', 'wiloke-listing-tools')
            ]);
        }

        $aGalleries = [];
        $aErrors    = [];

        foreach ($_FILES as $aFile) {
            if (!in_array($aFile['type'], $this->aValidFileTypes)) {
                $aErrors[] = [
                    'name' => $aFile['name'],
                    'msg'  => esc_html__('Invalid File Type', 'wiloke-listing-tools')
                ];

                continue;
            }

            if (!$this->isValidFileSize($aFile['size'])) {
                $aErrors[] = [
                    'name' => $aFile['name'],
                    'msg'  => sprintf(
                        esc_html__('You can upload an image smaller or equal to %s', 'wiloke-listing-tools'),
                        $this->maxMSize
                    )
                ];

                continue;
            }

            $instUploadImg                 = new Upload();
            $instUploadImg->userID         = get_current_user_id();
            $instUploadImg->aData['aFile'] = $aFile;
            $imgID                         = $instUploadImg->uploadFakeFile();

            if (is_numeric($imgID)) {
                $aGalleries[] = [
                    'src' => wp_get_attachment_image_url($imgID, 'thumbnail'),
                    'id'  => $imgID
                ];
            } else {
                $aErrors[] = [
                    'name' => $aFile['name'],
                    'msg'  => $imgID
                ];
            }
        }

        if (empty($aGalleries)) {
            if (!empty($aErrors)) {
                $msg = $aErrors[0]['msg'];
            } else {
                $msg = esc_html__(
                    'Unfortunately, We could not upload your images. Possible reason: Wrong Image Format or Your image is exceeded the allowable file size.',
                    'wiloke-listing-tools'
                );
            }
            $oRetrieve->error([
                'msg' => $msg
            ]);
        } else {
            $oRetrieve->success([
                'aImgs'   => $aGalleries,
                'aErrors' => $aErrors
            ]);
        }
    }
}
