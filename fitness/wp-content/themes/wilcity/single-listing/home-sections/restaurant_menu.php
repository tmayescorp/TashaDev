<?php

use \WilokeListingTools\Frontend\SingleListing;
use \WilokeListingTools\Framework\Helpers\GetSettings;

global $post, $wilcityArgs;
if (!GetSettings::isPlanAvailableInListing($post->ID, 'toggle_restaurant_menu')) {
    return '';
}

$aMenus = SingleListing::getRestaurantMenu($post->ID);

if (empty($aMenus)) {
    return [];
}
?>
<wil-single-nav-wrapper v-if="currentTab === 'home'" :settings="getNavigation('restaurant_menu')"
                        id="single-home-restaurant-menu-section">
    <template v-slot:default="{settings}">
        <wil-single-nav-restaurant-menu id="wil-restaurant-home-section"
                                        :post-id="<?php echo abs($post->ID); ?>"
                                        :settings="settings" :menus="data.restaurant"></wil-single-nav-restaurant-menu>
    </template>
</wil-single-nav-wrapper>
