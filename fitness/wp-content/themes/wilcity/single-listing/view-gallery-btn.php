<?php

use WilokeListingTools\Framework\Helpers\General;
use Wilcity\Ultils\ListItems\WilGalleryBtn;
use WilokeListingTools\Framework\Helpers\PostSkeleton;

$oPostSkeleton                     = new PostSkeleton();
$aRawGallery                       = $oPostSkeleton->getSkeleton($post->ID, ['gallery']);

if (!empty($aRawGallery['gallery'])) :
    $aPostTypeInfo = General::getPostTypeSettings($post->post_type);
    $aButtons                      = wilcityGetConfig('btn');
    $aGalleryBtn                   = $aButtons['wilGalleryBtn'];
    $aGalleryBtn['btnName']        = sprintf(esc_html__('Tour this %s', 'wilcity'), $aPostTypeInfo['singular_name']);
    $aGalleryBtn['items']          = $aRawGallery['gallery'];
    $aGalleryBtn['wrapperClasses'] = 'wil-btn wil-btn--overlay-dark wil-btn--sm wil-btn--round';
    $oGalleryBtn                   = new WilGalleryBtn();
    ?>
    <div class="wil-header-btn-wrapper wilcity-tour-wrapper" style="z-index: 1">
        <?php
        $oSwitchTabBtn = $oGalleryBtn->setConfiguration($aGalleryBtn);
        echo $oSwitchTabBtn->render();
        ?>
    </div>
<?php endif; ?>
