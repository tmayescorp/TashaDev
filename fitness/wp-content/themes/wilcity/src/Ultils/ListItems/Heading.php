<?php

namespace Wilcity\Ultils\ListItems;

class Heading extends RenderableAbstract implements RenderableInterface
{
    public function render(): string
    {
        $icon    = $this->getAttribute('icon');
        $heading = $this->getAttribute('heading');
        if (empty($heading)) {
            $heading = $this->getAttribute('name');
        }
        $headingTag = $this->getAttribute('headingTag', 'h4');
        
        $btnNameOnRight = $this->getAttribute('btnNameOnRight');
        $linkOnRight    = $this->getAttribute('linkOnRight');
        
        ob_start();
        ?>
        <header class="<?php echo esc_attr($this->getAttribute('wrapperClasses', 'content-box_header__xPnGx
        clearfix')); ?>">
            <div class="wil-float-left">
                <<?php echo esc_attr($headingTag) ?> class="content-box_title__1gBHS">
                <?php if ($icon): ?>
                    <i class="<?php echo esc_attr($icon); ?>"></i>
                <?php endif; ?>
                <?php if ($heading): ?>
                    <span><?php echo esc_html($heading); ?></span>
                <?php endif; ?>
            </<?php echo esc_attr($headingTag) ?>>
            </div>
            
            <?php if (!empty($linkOnRight)): ?>
                <div class="wil-float-right">
                    <a class="fs-13 color-primary" href="<?php echo esc_url($linkOnRight); ?>">
                        <?php echo esc_html($btnNameOnRight); ?>
                    </a>
                </div>
            <?php endif; ?>
        </header>
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        
        return $content;
    }
}
