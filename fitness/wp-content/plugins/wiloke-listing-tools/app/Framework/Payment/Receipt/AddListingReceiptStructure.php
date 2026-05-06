<?php

namespace WilokeListingTools\Framework\Payment\Receipt;

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\Message;
use WilokeListingTools\Framework\Payment\Coupon;

final class AddListingReceiptStructure extends ReceiptAbstract implements ReceiptStructureInterface
{
    protected $category = 'addlisting';

    public function __construct($aInfo)
    {
        $this->aInfo = $aInfo;
    }

    /**
     * @return string
     */
    public function getPlanSlug()
    {
        return get_post_field('post_name', $this->planID);
    }

    private function setCouponCode()
    {
        $this->couponCode = isset($this->aInfo['couponCode']) ? $this->aInfo['couponCode'] : '';
    }

    public function getPlanName()
    {
        return get_the_title($this->planID);
    }

    public function getPlanFeaturedImg()
    {
        return get_the_post_thumbnail_url($this->planID, 'large');
    }

    public function getPlanDescription()
    {
        $desc = get_post_field('post_excerpt', $this->planID);
        if (empty($desc)) {
            $desc = $this->getPlanName();
        }

        return $desc;
    }

    protected function setRegularPrice()
    {
        $this->regularPrice = $this->roundPrice($this->aPlanSettings['regular_price']);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRegularPrice()
    {
        return $this->regularPrice;
    }

    protected function setSubTotal()
    {
        $this->subTotal = $this->regularPrice;

        return $this;
    }

    public function getSubTotal()
    {
        return $this->subTotal;
    }

    public function getTrialPeriod()
    {
        return isset($this->aPlanSettings['trial_period']) ? absint($this->aPlanSettings['trial_period']) : 0;
    }

    public function getRegularPeriod()
    {
        return isset($this->aPlanSettings['regular_period']) ? absint($this->aPlanSettings['regular_period']) : 0;
    }

    public function getPackageType()
    {
        return get_post_type($this->planID);
    }

    private function verifyCoupon()
    {
        if (!empty($this->couponCode)) {
            $instCoupon = new Coupon();
            $instCoupon->getCouponID($this->couponCode);
            $instCoupon->getCouponSlug();
            $instCoupon->getCouponInfo();

            if (!$instCoupon->isCouponExpired() && $instCoupon->isPostTypeSupported(get_post_type($this->planID))) {
                $this->aCouponInfo = $instCoupon->aSettings;
                $this->aCouponInfo['amount'] = $this->roundPrice($this->aCouponInfo['amount']);

                if ($this->aCouponInfo['type'] == 'percentage') {
                    $this->discountPrice = $this->roundPrice($this->regularPrice * $this->aCouponInfo['amount'] / 100);
                } else {
                    $this->discountPrice = $this->aCouponInfo['amount'];
                }
            }
        }
    }

    private function setupWooCommercePlan()
    {

    }

    /**
     * @return $this|int
     */
    protected function setTotal()
    {
        if (!empty($this->total)) {
            return $this->total;
        }

        if ($this->isWooCommerce) {
            $this->total = $this->oProduct->get_price();
        } else {
            $this->total = $this->roundPrice($this->subTotal + $this->tax - $this->discountPrice);
        }

        if ($this->total < 0) {
            $this->total = 0;
        }

        return $this;
    }

    public function setupPlan()
    {
        // Set up plan
        if (!isset($this->aInfo['planID'])) {
            try {
                Message::error(esc_html__('The plan is required', 'wiloke-listing-tools'));
            }
            catch (\Exception $e) {
            }

            return false;
        }

        if (isset($this->aInfo['productID']) && !empty($this->aInfo['productID'])) {
            $this->productID = absint($this->aInfo['productID']);
            $this->isWooCommerce = true;
            $this->orderID = $this->aInfo['orderID'];
            $this->oProduct = wc_get_product($this->productID);
        }

        $this->planID = trim($this->aInfo['planID']);
        $this->aPlanSettings = GetSettings::getPlanSettings($this->planID);

        // Coupon
        $this->setCouponCode();
        $this->setRegularPrice();
        $this->verifyCoupon();
        $this->setTax();
        $this->setSubTotal();
        $this->setTotal();

        // Set UserID
        $this->setUserID($this->aInfo['userID']);
        $this->setupCategorySession();
    }

    public function getPaymentData($aAdditionalData = [])
    {
        return array_merge(
            [
                'planID'   => $this->planID,
                'userID'   => $this->getUserID(),
                'currency' => $this->getCurrency(),
                'total'    => $this->getTotal(),
                'subTotal' => $this->getSubTotal(),
                'discount' => $this->getDiscount(),
                'category' => $this->category,
                'gateway'  => $this->gateway,
                'planName' => $this->getPlanName(),
                'tax'      => $this->getTax(),
                'taxRate'  => $this->getTaxRate()
            ],
            $aAdditionalData
        );
    }
}
