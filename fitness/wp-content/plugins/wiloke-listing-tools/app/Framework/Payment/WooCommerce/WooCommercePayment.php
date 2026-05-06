<?php
namespace WilokeListingTools\Framework\Payment\WooCommerce;

use WilokeListingTools\Framework\Helpers\Message;
use WilokeListingTools\Framework\Store\Session;

abstract class WooCommercePayment
{
    public $gateway = 'woocommerce';
    protected $userID;
    protected $paymentID;
    protected $oReceipt;
    protected $orderID;
    protected $token;
    
    protected function setup()
    {
        $this->userID = get_current_user_id();
    }
    
    protected function getPostID()
    {
        $this->postID = Session::getPaymentObjectID(false);
    }
    
    protected function setOrderID($orderID)
    {
        if (empty($orderID)) {
            Message::error(esc_html__('The order id is required', 'wiloke-listing-tools'));
        }
        $this->orderID = $orderID;
    }
    
    public function __get($name)
    {
        return $this->$name;
    }
    
    public function __isset($name)
    {
        return !empty($this->$name);
    }
}
