<?php

namespace WILCITY_SC\ParseShortcodeAtts;

trait PrepareHeading
{
    private function prepareHeading()
    {
        if (!empty($this->aScAttributes['description']) && empty($this->aScAttributes['desc'])) {
            if (isset($this->aScAttributes['_title'])) {  // sign of elementor
                $this->aScAttributes['desc'] = $this->aScAttributes['description'];
            } else {
                $parseDesc = base64_decode($this->aScAttributes['description'], true);
                if ($parseDesc) {
                    $this->aScAttributes['desc'] = $parseDesc;
                } else {
                    $this->aScAttributes['desc'] = $this->aScAttributes['description'];
                }
            }
            $this->aScAttributes['desc_color'] = $this->aScAttributes['description_color'];
        }
        
        if (is_tax()) {
            $termName                       = get_queried_object()->name;
            $this->aScAttributes['heading'] = str_replace('%termName%', $termName, $this->aScAttributes['heading']);
            $this->aScAttributes['desc']    = str_replace('%termName%', $termName, $this->aScAttributes['desc']);
        }
        
        return $this->aScAttributes;
    }
}
