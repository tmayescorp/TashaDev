<?php

use WilokeListingTools\Framework\Helpers\GetSettings;

global $post, $wilcityArgs;
$mode = GetSettings::getProductMode($post->ID);
if ($mode === 'specify_products') {
    $aIds = GetSettings::getMyProducts($post->ID);
} else if ($mode == 'specify_product_cats') {
    $aIds = GetSettings::getMyProductCats($post->ID);
} else {
    $items = GetSettings::getListingProducts($post->ID, 'mode');

    if (isset($items[0]) && isset($items[0]['ID'])) {
        $aIds = [];
        foreach ($items as $aProduct) {
            $aIds[] = $aProduct['ID'];
        }
    }
}

if (empty($aIds)) {
    return '';
}

if (is_array($aIds) && isset($wilcityArgs['maximumItemsOnHome']) && !empty($wilcityArgs['maximumItemsOnHome'])) {
    $aIds = array_slice($aIds, 0, $wilcityArgs['maximumItemsOnHome']);
}

$columns = apply_filters('wilcity/event/my_tickets/columns', 2);

switch ($mode) {
    case 'specify_products':
        $productsContent = do_shortcode('[products columns="' . $columns . '" ids="' . implode(',', $aIds) . '"]');
        break;
    case 'specify_product_cats':
        $productsContent = do_shortcode('[products columns="' . $columns . '" category="' . implode(',', $aIds) . '"]');
        break;
    default:
        $productsContent = do_shortcode('[products columns="' . $columns . '" ids="' . implode(',', $aIds) . '"]');
        break;
}

if (empty($productsContent)) {
    return '';
}

if (function_exists('dokan')) {
    $oVendor = dokan()->vendor->get(get_post_field('post_author', $post->ID));
    $shopUrl = $oVendor->get_shop_url();
} else {
    $shopUrl = "";
}

?>
<div class="content-box_module__333d9">
    <?php get_template_part('single-listing/home-sections/section-heading'); ?>
    <div class="content-box_body__3tSRB">
        <div><?php echo $productsContent; ?></div>
    </div>
    <?php if (!empty($shopUrl)) : ?>
        <footer class="content-box_footer__kswf3">
            <a href="<?php echo esc_url($shopUrl) ?>" target="_blank" class="content-box_link__2K0Ib wil-text-center">
                <span><?php esc_html_e('Go to shop', 'wilcity'); ?></span>
            </a>
        </footer>
    <?php endif; ?>
</div>
<?php
wp_reset_postdata();

