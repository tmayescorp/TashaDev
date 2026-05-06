<?php

namespace Wilcity\Ultils\ListItems;

use WilokeListingTools\Framework\Helpers\PostSkeleton;

class WilGalleryBtn extends RenderableAbstract implements RenderableInterface
{
    public function render(): string
    {
        global $post;
        $hasWrapperForIcon = $this->getAttribute('hasWrapperForIcon', 'no');
        $aGalleryItems     = $this->getAttribute('items');
        $postID            = $this->getAttribute('postId', $post->ID);
        
        if (empty($aGalleryItems)) {
            $oPostSkeleton = new PostSkeleton();
            $aRawGallery   = $oPostSkeleton->getSkeleton($postID, ['gallery']);
            
            if (empty($aRawGallery['gallery'])) {
                return '';
            }
            
            $aGalleryItems = $aRawGallery['gallery'];
        }
        
        $classes = $this->getAttribute(
            'wrapperClasses',
            'list_link__2rDA1 text-ellipsis color-primary--hover'
        );
        ob_start();
        ?>
        <wil-gallery-btn icon="<?php echo esc_attr($this->getAttribute('icon', 'la la-photo')); ?>"
                         has-wrapper-for-icon="<?php echo esc_attr($hasWrapperForIcon); ?>"
                         :post-id="<?php echo abs($postID); ?>"
                         btn-name="<?php echo esc_attr($this->getAttribute('btnName')); ?>"
                         items="<?php echo base64_encode(json_encode($aGalleryItems)); ?>"
                         wrapper-classes="<?php echo esc_attr($classes); ?>"></wil-gallery-btn>
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        
        return $content;
    }
}
