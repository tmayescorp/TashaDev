<?php

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\Message;
use WilokeListingTools\Framework\Helpers\Submission;
use WilokeListingTools\Frontend\SingleListing;
use WilokeListingTools\Framework\Helpers\General;

global $post;

$aRenderMachine = wilokeListingToolsRepository()->get('listing-settings:sidebar_settings', true)->sub('renderMachine');

$aSidebarSettings = SingleListing::getSidebarOrder();

if (empty($aSidebarSettings)) {
    return '';
}

do_action('wilcity/single-listing/sidebar-top', $post);

foreach ($aSidebarSettings as $aSidebarSetting) {
    if (!isset($aSidebarSetting['key']) || (isset($aSidebarSetting['status']) && $aSidebarSetting['status'] == 'no')) {
        continue;
    }

    $baseKey = $aSidebarSetting['baseKey'] ?? $aSidebarSetting['key'];
    $belongsTo = GetSettings::getListingBelongsToPlan($post->ID);

    if ($belongsTo && !Submission::isPlanSupported($belongsTo,
            'toggle_' . $aSidebarSetting['key'])) {
        continue;
    }

    if (wp_is_mobile()) {
        if ($baseKey == 'woocommerceBooking') {
            continue;
        }
    }

    if ($baseKey == 'google_adsense') {
        $content = do_shortcode("[wilcity_google_adsense]");
        if (!empty($content)):
            ?>
            <div class="content-box_module__333d9">
                <div class="content-box_body__3tSRB">
                    <?php echo $content; ?>
                </div>
            </div>
        <?php
        endif;
    } else {
        if (empty($baseKey)) {
            if (isset($aSidebarSetting['taxonomy'])) {
                $baseKey = 'taxonomy';
            }
        }

        if (!isset($aRenderMachine[$baseKey])) {
            if ($baseKey == 'promotion') {
                do_action('wilcity/single-listing/sidebar-promotion', $post, $aSidebarSetting);
            } else {
                $scKey = str_replace('wilcity_single_sidebar_', '', $aSidebarSetting['key']);
                if (is_array($aSidebarSetting)) {
                    $aSidebarSetting = General::unSlashDeep($aSidebarSetting);
                }

                $buildSC = SingleListing::parseCustomFieldSC($aSidebarSetting['content'], '', $post->ID);
                $content = do_shortcode(stripslashes($buildSC));
                if (!empty($content)) :
                    ?>
                    <div class="content-box_module__333d9 <?php echo esc_attr('wilcity-sidebar-item-' .
                        $aSidebarSetting['key']); ?>">
                        <?php
                        wilcityRenderSidebarHeader($aSidebarSetting['name'], $aSidebarSetting['icon']);
                        ?>
                        <div class="content-box_body__3tSRB">
                            <?php echo $content; ?>
                        </div>
                    </div>
                <?php
                endif;
            }
        } else {
            echo do_shortcode("[" . $aRenderMachine[$baseKey] . " atts='" .
                stripslashes(base64_encode(json_encode($aSidebarSetting, JSON_UNESCAPED_UNICODE))) . "'/]");
        }
    }
}

do_action('wilcity/single-listing/sidebar-bottom', $post);
