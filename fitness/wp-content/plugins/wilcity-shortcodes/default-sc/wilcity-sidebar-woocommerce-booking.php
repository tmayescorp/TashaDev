<?php

use WILCITY_SC\SCHelpers;
use \WilokeListingTools\Framework\Helpers\GetSettings;

add_shortcode('wilcity_sidebar_woocommerce_booking', 'wilcitySidebarWooCommerceBookingForm');

function wilcitySidebarWooCommerceBookingForm($aArgs)
{
    global $post;

    if (!class_exists('WC_Bookings')) {
        return '';
    }
    $aArgs['atts'] = isset($aArgs['atts']) ? SCHelpers::decodeAtts($aArgs['atts']) : [
      'name' => '',
      'icon' => ''
    ];

    $aArgs = shortcode_atts(
      [
	      'name'       => $aArgs['name'] ?? $aArgs['atts']['name'],
	      'atts'       => [
          'name'   => '',
          'icon'   => 'la la-shopping-cart',
          'postID' => ''
        ],
	      'product_id' => ''
      ],
      $aArgs
    );

    $aAtts = $aArgs['atts'];
    if (empty($aAtts['postID'])) {
        $aAtts['postID'] = $post->ID;
    }

    if (!empty($aArgs['product_id'])) {
        $productID = $aArgs['product_id'];
    } else {
        $productID = GetSettings::getPostMeta($post->ID, 'my_room');
    }

    if (empty($productID)) {
        return '';
    }

    if (isset($aAtts['isMobile'])) {
        return apply_filters('wilcity/mobile/woocommerce-booking', '', $productID, $aAtts);
    }

    if (!empty($aArgs['product_id'])) {
        $id      = apply_filters('wilcity/filter/booking/id-prefix', '', $aArgs);
        $classes = apply_filters('wilcity/filter/booking/classes-prefix', '', $aArgs);
        $content = apply_filters('wilcity/filter/booking/content-classes', 'content-box_body__3tSRB', $aArgs);;
    } else {
        $id      = apply_filters('wilcity/filter/id-prefix', 'wilcity-sidebar-woobooking');
        $classes = apply_filters('wilcity/filter/class-prefix', 'wilcity-sidebar-item-woobooking');
        $content = "content-box_body__3tSRB";
    }

    ob_start();
    ?>
    <div id="<?php echo esc_attr($id); ?>"
         class="<?php echo esc_attr($classes); ?> content-box_module__333d9">
        <?php wilcityRenderSidebarHeader($aArgs['name'], $aAtts['icon']); ?>
        <div class="<?php echo esc_attr($content); ?>">
            <?php echo do_shortcode('[product_page id='.$productID.']'); ?>
        </div>
    </div>
    <?php
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
}
