<?php

namespace Wilcity\Ultils\ListItems;

class Lists extends RenderableAbstract implements RenderableInterface
{
    public function render(): string
    {
        $ul = '<'.$this->wrapperEl.' class="'.esc_attr($this->getAttribute('wrapperClasses', 'wil-lists-item')).'">';
        foreach ($this->aElements as $oElement) {
            $ul .= $oElement->render();
        }
        $ul .= '</'.$this->wrapperEl.'>';
        return $ul;
    }
}
