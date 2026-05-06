<?php

namespace Wilcity\Ultils\ListItems;

class Li extends RenderableAbstract implements RenderableInterface
{
    public function render(): string
    {
        try {
            return '<li class="'.esc_attr($this->getAttribute('wrapperClasses', 'wil-li-item')).'">'
                   .$this->beforeRenderElement()->content.'</li>';
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
