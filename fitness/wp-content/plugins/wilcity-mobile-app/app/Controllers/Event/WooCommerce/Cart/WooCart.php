<?php

namespace WILCITY_APP\Controllers\WooCommerce\Cart;

use Exception;

/**
 * Class WooCart
 * @package WILCITY_APP\Controllers\WooCommerce\Cart
 */
class WooCart implements AddToCartInterface
{
	protected $oUser;
	/**
	 * @var $oProduct \WC_Product
	 */
	public    $oProduct;
	protected $userId;
	public    $cartKey;
	protected $quantity     = 1;
	public    $variationId  = 0;
	public    $aVariations  = [];
	public    $aCartItems   = [];
	public    $oSessionHandler;
	public    $aProductsCart;
	public    $hasCartKey;
	protected $mode         = 'specifyQuantity';
	protected $err          = '';
	private   $cartItemsKey = 'wilcity_my_cart_items';

	public function setProduct(\WC_Product $product)
	{
		$this->oProduct = $product;

		return $this;
	}

	public function setUserId($userId)
	{
		$this->userId = $userId;
		$this->oUser = new \WP_User($userId);

		return $this;
	}

	public function setQuantity($quantity)
	{
		$this->quantity = abs($quantity);

		return $this;
	}

	public function getQuantity()
	{
		return $this->quantity;
	}

	public function setVariationId($variationId)
	{
		$this->variationId = $variationId;

		return $this;
	}

	public function setVariations($aVariations)
	{
		$this->aVariations = (array)$aVariations;

		return $this;
	}

	public function setCartKey($cartKey)
	{
		$this->cartKey = $cartKey;

		return $this;
	}

	public function getError()
	{
		return $this->err;
	}

	public function hasError()
	{
		return !empty($this->err);
	}

	public function setMode($mode)
	{
		$this->mode = $mode;

		return $this;
	}

	public function isDeduce(): bool
	{
		return $this->mode === 'deduceOne';
	}

	public function isAddOne(): bool
	{
		return $this->mode === 'addOne';
	}

	public function getCartItems()
	{
		global $current_user;
		$current_user = $this->oUser;
//        $this->oSessionHandler = new \WC_Session_Handler();
//        $this->oSessionHandler->init_session_cookie();
//
//        // Adding product to cart through home page or shop page
//        $aSessions        = $this->oSessionHandler->get_session($this->oUser->ID);
		$aCartItems = get_user_meta($current_user->ID, $this->cartItemsKey, true);
		$this->aCartItems = is_array($aCartItems) ? $aCartItems : [];

		return $this;
	}

	public function getCartKey()
	{
		if (!empty($this->cartKey)) {
			return $this;
		}

		$this->getCartItems();

		if (empty($this->aCartItems)) {
			return false;
		}

		$this->aProductsCart = array_filter($this->aCartItems, function ($aItem) {
			if ($aItem['product_id'] == $this->oProduct->get_id()) {
				$this->cartKey = $aItem['key'];

				return true;
			}

			return false;
		});

		if (empty($this->aProductsCart)) {
			return false;
		}

		if ($this->isDeduce()) {
			$this->quantity = absint(absint($this->aProductsCart[0]['quantity']) - 1);
		} else if ($this->isAddOne()) {
			$this->quantity = absint(absint($this->aProductsCart[0]['quantity']) + 1);
		}

		return $this;
	}

	/**
	 * @throws Exception
	 */
	protected function addNewCart(): bool
	{
		try {
			$this->cartKey = WC()->cart->add_to_cart(
				$this->oProduct->get_id(),
				$this->quantity,
				$this->variationId,
				$this->aVariations
			);
		}
		catch (Exception $e) {
			$this->err = $e->getMessage();

			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	protected function updateCart(): bool
	{
		return WC()->cart->set_quantity($this->cartKey, $this->quantity, true);
	}

	/**
	 * Add a product to the cart.
	 *
	 * @param int $product_id contains the id of the product to add to the cart.
	 * @param int $quantity contains the quantity of the item to add.
	 * @param int $variation_id ID of the variation being added to the cart.
	 * @param array $variation attribute values.
	 * @param array $cart_item_data extra cart item data we want to pass into the item.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function addToCart()
	{
		if (empty($this->oUser) || is_wp_error($this->oUser)) {
			return [
				'status' => 'error',
				'msg'    => 'The user id is required'
			];
		}

		$this->hasCartKey = $this->getCartKey();

		if (empty($this->cartKey)) {
			$status = $this->addNewCart();
		} else {
			$status = $this->updateCart();
		}

		if (!$status) {
			return [
				'status' => 'error',
				'msg'    => $this->hasError() ? $this->getError() :
					wilcityAppGetLanguageFiles('couldNotAddProductToCart')
			];
		}

		return [
			'status'  => 'success',
			'msg'     => wilcityAppGetLanguageFiles('itemHasBeenAddedToCart'),
			'cartKey' => $this->cartKey
		];
	}

	public function removeCart()
	{
		if (empty($this->cartKey)) {
			return [
				'status' => 'success',
				'msg'    => 'The cart key is required'
			];
		}

		if (!is_array($this->cartKey)) {
			$this->cartKey = explode(',', $this->cartKey);
		}

		foreach ($this->cartKey as $key) {
			WC()->cart->remove_cart_item($key);
		}

		//Check if invalid coupon so remove it
		do_action('woocommerce_check_cart_items');

		return [
			'status' => 'success',
			'msg'    => wilcityAppGetLanguageFiles('itemHasBeenRemovedFromCart')
		];
	}

	public function reset()
	{
		$this->oProduct = null;
		$this->userId = null;
		$this->cartKey = null;
		$this->quantity = 1;
		$this->variationId = 0;
		$this->aVariations = [];
		$this->aCartItems = [];
		$this->oSessionHandler = null;

		return $this;
	}
}
