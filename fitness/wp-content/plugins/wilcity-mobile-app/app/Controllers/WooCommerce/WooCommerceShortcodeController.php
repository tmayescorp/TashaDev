<?php

namespace WILCITY_APP\Controllers\WooCommerce;

use WilokeListingTools\Framework\Helpers\App;
use WilokeListingTools\Framework\Helpers\WooCommerce;
use WilokeListingTools\Frontend\User;

class WooCommerceShortcodeController
{
	public function __construct()
	{
		add_filter('wilcity/wilcity-mobile-app/wilcity_app_products', [$this, 'getProductJson'], 10, 3);
		add_filter('wilcity/wilcity-mobile-app/wilcity_app_product_blocks', [$this, 'getProductJson'], 10, 3);
		add_filter('wilcity/wilcity-mobile-app/filter/wilcity_bookings_on_mobile', [$this, 'getProductJson'], 10, 3);
	}

	public function fixOldVersion($aProduct, $post)
	{
		$aProduct['id'] = $post->ID;
		$aProduct['name'] = $post->post_title;
		$aProduct['salePriceHtml'] = $aProduct['salePriceHTML'];
		$aProduct['regularPriceHtml'] = $aProduct['regularPriceHTML'];
		$aProduct['priceHtml'] = $aProduct['priceHTML'];

		return $aProduct;
	}

	public function getProductJson(\WC_Product $product, \WP_Post $post, $aAtts)
	{
		$aProduct = App::get('ProductSkeleton')->getSkeleton(
			$post->ID,
			[
				'ID',
				'type',
				'title',
				'productName',
				'oFeaturedImg',
				'oCategories',
				'salePriceHTML',
				'salePrice',
				'regularPriceHTML',
				'regularPrice',
				'saleOff',
				'priceHTML',
				'price',
				'averageRating',
				'ratingCounts',
				'cartKey',
				'quantity',
				'link',
				'isAddedToCart',
				'isAddedToWishlist',
				'stockStatus',
				'oAuthor'
			]
		);

		$aProduct['style'] = 'classic';

		return $this->fixOldVersion($aProduct, $post);
	}
}
