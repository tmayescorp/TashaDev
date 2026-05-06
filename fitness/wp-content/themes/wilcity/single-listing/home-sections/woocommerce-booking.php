<?php
global $post, $wilcityArgs, $wilcityTabKey;

use WilokeListingTools\Framework\Helpers\GetSettings;

if (!wp_is_mobile()) {
    return;
}

$aSidebarSetting = wilcityGetSidebarByBaseKey('woocommerceBooking');
$aRenderMachine = wilokeListingToolsRepository()->get('listing-settings:sidebar_settings', true)->sub('renderMachine');
$productID = GetSettings::getPostMeta($post->ID, 'my_room');
if (empty($productID)) {
    return;
}
?>
<!-- content-box_module__333d9 -->
<div class="content-box_module__333d9 wilcity-woocommerce-booking">
    <div class="content-box_body__3tSRB">
        <div class="row" data-col-xs-gap="10">
            <?php
            echo do_shortcode("[" . $aRenderMachine['woocommerceBooking'] . " atts='" .
                stripslashes(base64_encode(json_encode($aSidebarSetting, JSON_UNESCAPED_UNICODE))) . "'/]");
            ?>
        </div>
    </div>
</div><!-- End / content-box_module__333d9 -->
