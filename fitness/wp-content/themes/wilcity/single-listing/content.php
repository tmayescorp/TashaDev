<?php

use WilokeListingTools\Frontend\SingleListing;

global $post, $wilcityArgs, $wilcityTabKey;

get_template_part('single-listing/home');

$aTabs = SingleListing::getNavOrder();
do_action('wilcity/single-listing/before-render-section', $post);

$isIncludedTermTab = false;
if (!empty($aTabs)) {
    foreach ($aTabs as $aWilcityTab) {
        if ($aWilcityTab['status'] == 'no') {
            continue;
        }

        if (isset($aWilcityTab['isCustomSection']) && $aWilcityTab['isCustomSection'] == 'yes') {
            $fileName = 'custom';
        } else {
            $fileName = $aWilcityTab['key'];
        }

        $aWilcityTab['key'] = str_replace('wilcity_single_navigation_', '', $aWilcityTab['key']);
        $wilcityArgs = $aWilcityTab;
        $wilcityTabKey = $aWilcityTab['key'];

        switch ($aWilcityTab['key']) {
            case 'tags':
            case 'taxonomy':
                if ($aWilcityTab['key'] === 'tags') {
                    $wilcityArgs['taxonomy'] = 'listing_tag';
                }
                get_template_part('single-listing/tabs/taxonomy');
                break;
            case 'posts':
            case 'events':
            case 'my_products':
                if (has_action('wilcity/single-listing/content/tab/' . $aWilcityTab['key'])) {
                    do_action('wilcity/single-listing/content/tab/' . $aWilcityTab['key'], $aWilcityTab);
                } else {
                    $wilcityArgs['ajaxAction'] = 'wilcity_fetch_' . $aWilcityTab['key'];
                    get_template_part('single-listing/tabs/'.$aWilcityTab['key']);
                }
                break;
            default:
                if (isset($aWilcityTab['isCustomSection']) && $aWilcityTab['isCustomSection'] === 'yes') {
                    get_template_part('single-listing/tabs/custom');
                } elseif (isset($aWilcityTab['taxonomy']) && taxonomy_exists($aWilcityTab['taxonomy'])) {
                    get_template_part('single-listing/tabs/taxonomy');
                } else {
                    get_template_part('single-listing/tabs/' . $fileName);
                }
                break;
        }
    }
}

do_action('wilcity/single-listing/after-render-section', $post);
