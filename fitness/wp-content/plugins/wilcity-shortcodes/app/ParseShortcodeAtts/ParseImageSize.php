<?php

namespace WILCITY_SC\ParseShortcodeAtts;

trait ParseImageSize
{
    private function parseImageSize()
    {
        if (!isset($this->aScAttributes['image_size'])) {
            return false;
        }
        
        if (strpos($this->aScAttributes['image_size'], ',') !== false) {
            $imgSize                           = explode(',', $this->aScAttributes['image_size']);
            $imgSize                           = array_map(function ($item) {
                return trim($item);
            }, $imgSize);
            $this->aScAttributes['image_size'] = $imgSize;
        }
        
        return true;
    }
}
