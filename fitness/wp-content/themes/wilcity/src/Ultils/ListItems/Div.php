<?php

namespace Wilcity\Ultils\ListItems;

class Div extends RenderableAbstract implements RenderableInterface
{
    public function render(): string
    {
        return '<div class="'.esc_attr($this->getAttribute('wrapperClasses', 'wil-li-item')).'">'
               .$this->beforeRenderElement()->content.'</div>';
    }
}
