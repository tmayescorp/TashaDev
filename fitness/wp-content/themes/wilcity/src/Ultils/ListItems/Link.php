<?php

namespace Wilcity\Ultils\ListItems;

class Link extends RenderableAbstract implements RenderableInterface
{
    public function render(): string
    {
        $classes            = $this->getAttribute(
            'wrapperClasses',
            'list_link__2rDA1 text-ellipsis color-primary--hover'
        );
        $href               = $this->getAttribute('link', '#');
        $icon               = $this->getAttribute('icon');
        $btnName            = $this->getAttribute('btnName');
        $btnTarget          = $this->getAttribute('btnTarget', '_self');
        $iconWrapperClasses = $this->getAttribute('iconWrapperClasses', 'list_icon__2YpTp');
        $btnWrapperClasses  = $this->getAttribute('btnWrapperClasses', 'list_text__35R07');
        
        switch ($href) {
            case 'editListing':
                $postID = $this->getAttribute('postId', '');
                if (empty($postID)) {
                    global $post;
                    if (empty($post)) {
                        return '';
                    }
                } else {
                    $post = get_post($postID);
                }
                
                $href = apply_filters('wilcity/single-listing/edit-listing', null, $post);
                break;
        }
        
        ob_start();
        ?>
        <a class="<?php echo esc_attr($classes); ?>"
           href="<?php echo esc_url($href); ?>"
           target="<?php echo esc_attr($btnTarget); ?>">
            <?php if (!empty($icon)) : ?>
                <span class="<?php echo esc_attr($iconWrapperClasses); ?>"><i
                        class="<?php echo esc_attr($icon); ?>"></i></span>
            <?php endif; ?>
            <span class="<?php echo esc_attr($btnWrapperClasses); ?>"><?php echo esc_html($btnName); ?></span>
        </a>
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        
        return $content;
    }
}
