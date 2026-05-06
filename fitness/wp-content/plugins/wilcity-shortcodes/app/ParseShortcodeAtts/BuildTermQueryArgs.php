<?php

namespace WILCITY_SC\ParseShortcodeAtts;

use WILCITY_SC\SCHelpers;

trait BuildTermQueryArgs
{
    public function buildTermQueryArgs()
    {
        if (!isset($this->aScAttributes['taxonomy'])) {
            return false;
        }

        $aArgs = [
	        'orderby'             => $this->aScAttributes['orderby'],
	        'order'               => $this->aScAttributes['order'],
	        'taxonomy'            => $this->aScAttributes['taxonomy'],
	        'number'              => $this->aScAttributes['number'] ?? 6,
	        'hide_empty'          => $this->aScAttributes['is_hide_empty'] == 'yes'
        ];

        if( $this->aScAttributes['is_show_parent_only'] == 'yes' ) {
            $aArgs['parent'] = 0;
        }

	    if (isset($this->aScAttributes['post_type'])) {
		    $aArgs['post_type'] = $this->aScAttributes['post_type'];
	    }

        if (!isset($this->aScAttributes[$this->aScAttributes['taxonomy'].'s']) ||
            empty($this->aScAttributes[$this->aScAttributes['taxonomy'].'s'])) {
            return false;
        }

        $aArgs['include'] = SCHelpers::getAutoCompleteVal($this->aScAttributes[$this->aScAttributes['taxonomy'].'s']);
        return $aArgs;
    }
}
