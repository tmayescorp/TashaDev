<?php


namespace WilokeListingTools\Controllers;


use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Framework\Upload\Upload;

class AjaxUploadFileController extends Controller
{
    private $aValidFileTypes = ['application/pdf', 'application/doc', 'application/docx', 'application/dotx', 'application/csv', 'application/xlsx'];
    private $maxMSize;
    private $maxBSize;

    public function __construct()
    {
        add_action('wp_ajax_wilcity_upload_files', [$this, 'uploadFiles']);
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


    /**
     * @throws \Exception
     */
    public function uploadFiles()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $aStatus = $this->middleware(['verifyNonce']);

        if ($aStatus['status'] === 'error') {
            $oRetrieve->error($aStatus);
        }

        if (!is_array($_FILES)) {
            $oRetrieve->error([
                'msg' => esc_html__('You need to upload 1 file at least', 'wiloke-listing-tools')
            ]);
        }

        $aFile = $_FILES['file'];
        if (!in_array($aFile['type'], $this->aValidFileTypes)) {
            return $oRetrieve->error(
                [
                    'name' => $aFile['name'],
                    'msg'  => esc_html__('Invalid File Type', 'wiloke-listing-tools')
                ]
            );
        }

        if (!$this->isValidFileSize($aFile['size'])) {
            return $oRetrieve->error(
                [
                    'name' => $aFile['name'],
                    'msg'  => sprintf(
                        esc_html__('You can upload an image smaller or equal to %s', 'wiloke-listing-tools'),
                        $this->maxMSize
                    )
                ]
            );
        }

        $instUploadImg = new Upload();
        $instUploadImg->userID = get_current_user_id();
        $instUploadImg->aData['aFile'] = $aFile;
        $attachmentID = $instUploadImg->uploadFakeFile();

        if (is_numeric($attachmentID)) {
            $aUploadedFile = [
                'src' => wp_get_attachment_url($attachmentID),
                'id'  => $attachmentID
            ];
        } else {
            return $oRetrieve->error(
                [
                    'name' => $aFile['name'],
                    'msg'  => sprintf(
                        esc_html__('Something went error. We could upload this file %s', 'wiloke-listing-tools'),
                        $aFile['name']
                    )
                ]
            );
        }

        $oRetrieve->success([
            'item' => $aUploadedFile
        ]);
    }
}
