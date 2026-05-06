<?php

use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;

$aPostTypes = General::getPostTypeKeys(false, false);

if (in_array($postType = get_queried_object()->post_type, $aPostTypes)) {
    $searchPage = GetSettings::getSearchPage();

    wp_redirect(add_query_arg(['postType' => $postType], $searchPage));
    exit();
}

get_template_part('index');
