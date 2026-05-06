<?php

namespace Wilcity\Ultils\ListItems;

class WilSocialSharingBtn extends RenderableAbstract implements RenderableInterface
{
    public function render(): string
    {
        $postID = $this->getAttribute('postId', '');
        if (empty($postID)) {
            global $post;
            if (empty($post)) {
                return '';
            }
            $postID    = $post->ID;
            $postTitle = $post->post_title;
        } else {
            $postTitle = get_the_title($postID);
        }
        
        $socials = \WilokeThemeOptions::getOptionDetail('sharing_on');
        if (!$socials) {
            return '';
        }
        
        $featuredImg = get_the_post_thumbnail_url($postID, 'full');
        $permalink   = get_permalink($postID);
        
        $aSharingInfo = [
            'title'  => $postTitle,
            'img'    => $featuredImg,
            'link'   => $permalink,
            'postID' => abs($postID)
        ];
        
        ob_start();
        ?>
        <wil-social-sharing-btn settings="<?php echo base64_encode(json_encode($aSharingInfo)); ?>"
                                :socials='<?php echo json_encode($socials); ?>'></wil-social-sharing-btn>
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        
        return $content;
    }
}
