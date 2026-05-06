<?php

namespace Wilcity\Ultils\ListItems;

class WilSwitchTabBtn extends RenderableAbstract implements RenderableInterface
{
    public function render(): string
    {
        global $post;
        $classes = $this->getAttribute('wrapperClasses', 'list_link__2rDA1 text-ellipsis color-primary--hover');
        $tabKey  = $this->getAttribute('tabKey', uniqid('tab-key-'));
        $tabHref = $this->getAttribute('href');
        if (empty($tabHref)) {
            $tabHref = $tabKey;
        }
        ob_start();
        ?>
        <wil-switch-tab-btn icon="<?php echo esc_attr($this->getAttribute('icon')); ?>"
                            :post-id="<?php echo abs($post->ID); ?>"
                            btn-name="<?php echo esc_attr($this->getAttribute('btnName')); ?>"
                            page-url="<?php echo esc_url($this->getAttribute('pageUrl', get_permalink($post->ID))); ?>"
                            tab-key="<?php echo esc_attr($tabKey); ?>"
                            tab-href="<?php echo esc_attr($tabHref); ?>"
                            btn-classes="<?php echo esc_attr($this->getAttribute('btnClasses',
                                'list_link__2rDA1 text-ellipsis color-primary--hover')); ?>"
                            has-wrapper-for-icon="<?php echo $this->getAttribute('hasWrapperForIcon', 'yes'); ?>"
                            wrapper-classes="<?php echo esc_attr($classes); ?>"></wil-switch-tab-btn>
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        
        return $content;
    }
}
