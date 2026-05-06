<?php
namespace WilokeListingTools\Controllers;


use WilokeListingTools\Framework\Routing\Controller;

class PayPalController extends Controller {
	public $gateway = 'paypal';
	protected $planID;

	public function __construct() {
	}
}
