<?php

namespace Wilcity\Ultils\ListItems;

use WilokeListingTools\Frontend\User;

class WilMessageBtn extends RenderableAbstract implements RenderableInterface
{
    public function render(): string
    {
        $receiverId = $this->getAttribute('receiverId', '');
        if (empty($postID)) {
            global $post;
            if (empty($post)) {
                return '';
            }
            
            $receiverId = $post->post_author;
        }
        
        $classes = $this->getAttribute('wrapperClasses', 'list_link__2rDA1 text-ellipsis color-primary--hover');
        ob_start();
        ?>
        <wil-message-btn :receiver-id="<?php echo abs($receiverId); ?>"
                         receiver-name="<?php echo User::getField('display_name', $receiverId); ?>"
                         btn-name="<?php echo esc_attr($this->getAttribute('btnName')); ?>"
                         wrapper-classes="<?php echo esc_attr($classes); ?>"></wil-message-btn>
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        
        return $content;
    }
}
