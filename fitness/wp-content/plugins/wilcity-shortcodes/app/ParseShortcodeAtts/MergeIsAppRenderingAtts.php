<?php

namespace WILCITY_SC\ParseShortcodeAtts;

use WILCITY_SC\SCHelpers;

trait MergeIsAppRenderingAtts
{
    private function mergeIsAppRenderingAttr() {
        if (isset($_POST['post_ID'])) {
            $pageTemplate = get_page_template_slug($_POST['post_ID']);
            if ($pageTemplate == 'templates/mobile-app-homepage.php') {
                SCHelpers::$isApp = true;
            }
        }
        $this->aScAttributes['isApp'] = SCHelpers::$isApp;
    }
}
