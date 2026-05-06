<?php global $post; ?>

<wil-single-nav-wrapper v-if="currentTab === 'restaurant_menu'" :settings="getNavigation('restaurant_menu')"
                        id="single-restaurant-menu">
    <template v-slot:default="{settings}">
        <wil-single-nav-restaurant-menu
            id="wil-restaurant-menu-tab"
            :post-id="<?php echo abs($post->ID); ?>"
            :menus="data.restaurant"
            :column-classes="data.restaurant.length > 1 ? 'col-md-6 col-xs-12' : 'col-md-12 col-xs-12'"
            :settings="getNavigation('restaurant_menu')"></wil-single-nav-restaurant-menu>
    </template>
</wil-single-nav-wrapper>
