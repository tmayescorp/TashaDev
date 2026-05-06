<?php

namespace WilokeListingTools\Framework\Payment\Receipt;

use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Store\Session;

abstract class ReceiptAbstract
{
    protected $gateway;
    protected $couponCode;
    protected $regularPrice;
    protected $userID;
    public    $planID;
    protected $orderID;
    public    $productID;
    protected $aInfo;
    protected $aPlanSettings;
    protected $aCouponInfo;
    protected $discountPrice = 0;
    protected $subTotal = 0;
    protected $total = 0;
    protected $tax = 0;
    protected $isWooCommerce = false;
    protected $category;
    /**
     * @var $oProduct \WooCommerce
     */
    protected $oProduct;

    public function getCurrency()
    {
        return $this->isWooCommerce ? get_woocommerce_currency() : GetWilokeSubmission::getField('currency_code');
    }

    public function getTaxRate()
    {
        return GetWilokeSubmission::getTaxRate();
    }

    public function getThankyouURL($aArgs = [])
    {
        return GetWilokeSubmission::getThankyouPageURL($aArgs);
    }

    public function getCancelUrl($aArgs = [])
    {
        return GetWilokeSubmission::getCancelPageURL($aArgs);
    }

    public function getProductID()
    {
        return $this->productID;
    }

    public function getOrderID()
    {
        return $this->orderID;
    }

    public function roundPrice($price)
    {
        return empty($price) ? 0 : round($price, 2);
    }

    public function getPlanID()
    {
        return $this->planID;
    }

    protected function setUserID($userID)
    {
        $this->userID = $userID;
    }

    public function getUserID()
    {
        return $this->userID;
    }

    public function getDiscount()
    {
        return $this->discountPrice;
    }

    public function getPackageType()
    {
        return $this->category;
    }

    public function getPaymentData($aAdditionalData = [])
    {
        // TODO: Implement getPaymentData() method.
    }

    public function getSubTotal()
    {
        return $this->subTotal;
    }

    public function getTotal()
    {
        return $this->total;
    }

    protected function setupCategorySession()
    {
        $category = Session::getPaymentCategory();
        if (empty($category)) {
            Session::setPaymentCategory($this->category);
        }
    }

    public function getGateway()
    {
        return $this->gateway;
    }

    public function getTax()
    {
        return $this->tax;
    }

    /**
     * @return $this
     */
    protected function setTax()
    {
        $this->tax = $this->roundPrice(($this->regularPrice - $this->discountPrice) * $this->getTaxRate() / 100);

        if ($this->tax <= 0) {
            $this->tax = 0;
        }

        return $this;
    }

    protected function setTotal()
    {
        if (!empty($this->total)) {
            return $this->total;
        }

        $this->total = $this->roundPrice($this->subTotal + $this->tax - $this->discountPrice);

        if ($this->total < 0) {
            $this->total = 0;
        }

        return $this;
    }

    public function focusSetGateway($gateway)
    {
        $this->gateway = $gateway;

        return $this;
    }

    /**
     * It's needed for PayPal.
     * @return int
     */
    public function getTotalWithoutDiscount()
    {
        return $this->roundPrice($this->total + $this->discountPrice);
    }
}
