<?php

namespace WILCITY_APP\Controllers\WooCommerce;

use Exception;
use WC_Cart;
use WC_Product;
use WC_Product_Factory;
use WILCITY_APP\Controllers\WooCommerce\Cart\VariationValidation;
use WILCITY_APP\Controllers\WooCommerceController;
use WILCITY_APP\Helpers\App;
use function GuzzleHttp\Psr7\str;

class WooCommerceCartController extends WooCommerceController
{
	private        $aVariations;
	private        $aAttributes;
	private        $quantity;
	private        $isDeduct    = false;
	private        $variationID = 0;
	private static $userId;

	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'wc/my-cart', [
				'methods'             => 'GET',
				'callback'            => [$this, 'getMyCart'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'wc/add-to-cart', [
				'methods'             => 'POST',
				'callback'            => [$this, 'addProductToCart'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'wc/deduct-to-cart', [
				'methods'             => 'POST',
				'callback'            => [$this, 'deductProductsToCart'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/' . WILOKE_MOBILE_REST_VERSION, 'wc/remove-cart', [
				'methods'             => 'POST',
				'callback'            => [$this, 'removeCartItem'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'wc/my-cart', [
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'getMyCart']
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'wc/add-to-cart', [
				'methods'             => 'POST',
				'callback'            => [$this, 'addProductToCart'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'wc/deduct-to-cart', [
				'methods'             => 'POST',
				'callback'            => [$this, 'deductProductsToCart'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route(WILOKE_PREFIX . '/v2', 'wc/remove-cart', [
				'methods'             => 'POST',
				'callback'            => [$this, 'removeCartItem'],
				'permission_callback' => '__return_true'
			]);
		});

		add_filter('woocommerce_cart_contents_changed', [$this, 'addCartKeyToUser']);
		add_action('woocommerce_cart_item_removed', [$this, 'removeCartKeyToUser']);
		add_action('woocommerce_cart_emptied', [$this, 'emptyCart']);
		add_action('woocommerce_after_calculate_totals', [$this, 'addCartTotal']);
//		add_action('init', function () {
//			if (isset($_GET['test1'])) {
//				echo '<pre>';
//				var_export($this->getMyCartSkeleton(60));
//				die;
//			}
//		});
//		add_filter('woocommerce_persistent_cart_enabled', [$this, 'enablePersistentCart'], 999);
	}

	public function enablePersistentCart(): bool
	{
		return true;
	}

	public function emptyCart()
	{
		if (empty(self::$userId) || !class_exists('\WilokeListingTools\Framework\Helpers\GetSettings')) {
			return;
		}
		delete_user_meta(self::$userId, $this->cartItemsKey);
		delete_user_meta(self::$userId, $this->cartTotalKey);
		delete_user_meta(self::$userId, $this->cartSubTotalKey);
		delete_user_meta(self::$userId, $this->cartQuantity);
	}

	public function addCartTotal(WC_Cart $that)
	{
		if (empty(self::$userId) || !class_exists('\WilokeListingTools\Framework\Helpers\GetSettings')) {
			return;
		}

		update_user_meta(self::$userId, $this->cartTotalKey, $that->get_totals());
		update_user_meta(self::$userId, $this->cartSubTotalKey, $that->get_subtotal());
		update_user_meta(self::$userId, $this->cartQuantity, count($that->get_cart_item_quantities()));
	}

	public function removeCartKeyToUser($cartItemKey)
	{
		if (empty(self::$userId) || !class_exists('\WilokeListingTools\Framework\Helpers\GetSettings')) {
			return;
		}

		$aCartItems = get_user_meta(self::$userId, $this->cartItemsKey, true);
		if (!is_array($aCartItems)) {
			return;
		}

		unset($aCartItems[$cartItemKey]);

		update_user_meta(self::$userId, $this->cartItemsKey, $aCartItems);
	}

	public function addCartKeyToUser($aCartItems)
	{
		if (empty(self::$userId) || !class_exists('\WilokeListingTools\Framework\Helpers\GetSettings')) {
			return $aCartItems;
		}

		update_user_meta(self::$userId, $this->cartItemsKey, $aCartItems);
		return $aCartItems;
	}

	/**
	 * @param \WP_REST_Request $oRequest
	 *
	 * @return array|bool
	 */
	private function verifyAddToCart(\WP_REST_Request $oRequest)
	{
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}

		$oToken->getUserID();
		$this->userID = $oToken->userID;
		self::$userId = $this->userID;
		wp_set_current_user($this->userID);
		$this->productID = $oRequest->get_param('id');
		$this->quantity = $oRequest->get_param('quantity');
		$this->oProduct = new WC_Product($this->productID);

		if (get_post_type($this->productID) !== 'product') {
			return [
				'status' => 'error',
				'msg'    => esc_html__('This product does not exists', 'wilcity-mobile-app')
			];
		}

		return true;
	}

	public function removeCartItem(\WP_REST_Request $oRequest)
	{
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}

		self::$userId = $oToken->userID;

		return App::get('WooCart')->reset()
			->setCartKey($oRequest->get_param('key'))
			->removeCart();
	}

	public function deductProductsToCart(\WP_REST_Request $oRequest)
	{
		$aStatus = $this->verifyAddToCart($oRequest);
		if (isset($aStatus['status']) && $aStatus['status'] === 'error') {
			return $aStatus;
		}

		/**
		 * @var App::get('WooCart') WILCITY_APP\Controllers\WooCommerce\Cart\WooCart
		 */
		return App::get('WooCart')->reset()
			->setProduct($this->oProduct)
			->setUserId($this->userID)
			->setMode('deduceOne')
			->addToCart();
	}

	public function addProductToCart(\WP_REST_Request $oRequest)
	{
		$aResponse = $this->verifyAddToCart($oRequest);
		if (isset($aResponse['status']) && $aResponse['status'] === 'error') {
			return $aResponse;
		}

		$mode = $oRequest->get_param('mode');
		$mode = empty($mode) ? 'specifyQuantity' : $mode;
		$type = WC_Product_Factory::get_product_type($this->oProduct->get_id());

		switch ($type) {
			case 'variation':
			case 'variable':
			case 'variable-subscription':
				$oVariationValidation = new VariationValidation();
				try {
					$aResponse = $oVariationValidation->setProduct($this->oProduct)
						->setVariationId($oRequest->get_param('variationID'))
						->setAttributes($oRequest->get_param('attributes'))
						->setQuantity($this->quantity)
						->validate();

					if ($aResponse['status'] == 'error') {
						return $aResponse;
					}

					$aResponse = App::get('WooCart')->reset()
						->setProduct($this->oProduct)
						->setUserId($this->userID)
						->setVariationId($oRequest->get_param('variationID'))
						->setMode($mode)
						->setQuantity($this->quantity)
						->setVariations($aResponse['variations'])
						->addToCart();
				}
				catch (Exception $e) {
					return [
						'status' => 'error',
						'msg'    => $e->getMessage()
					];
				}
				break;
			case 'simple':
				$aResponse = App::get('WooCart')->reset()
					->setProduct($this->oProduct)
					->setUserId($this->userID)
					->setMode($mode)
					->setQuantity($this->quantity)
					->addToCart();

				break;
			default:
				$aResponse = [
					'status' => 'error',
					'msg'    => sprintf(
						wilcityAppGetLanguageFiles('noSupportedProductType'),
						$type
					)
				];
				break;
		}

		return $aResponse;
	}

	public function getMyCart()
	{
		$oToken = $this->verifyPermanentToken();
		if (!$oToken) {
			return $this->tokenExpiration();
		}
		$oToken->getUserID();
		$aCartItems = $this->getMyCartSkeleton($oToken->userID);

		if (empty($aCartItems)) {
			return [
				'status'     => 'success',
				'msg'        => 'emptyCart',
				'oCartItems' => []
			];
		}

		return [
			'status'     => 'success',
			'oCartItems' => $aCartItems
		];
	}
}
