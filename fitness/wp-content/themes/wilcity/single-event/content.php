<?php

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\General;

global $wilcityArgs;

$aEventContent = GetSettings::getOptions(General::getEventContentFieldKey($post->post_type), true, true);

foreach ($aEventContent as $aField) {
    $fileName = str_replace(array('listing_', 'event_'), array('', ''), $aField['key']);
    $wilcityArgs = $aField;
    if (is_file(get_template_directory() . '/single-event/content/' . $fileName . '.php')) {
        get_template_part('single-event/content/' . $fileName);
    } else {
        get_template_part('single-event/content/custom');
    }
}
