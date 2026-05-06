<?php

namespace WilokeListingTools\Framework\Payment\Receipt;

use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Controllers\RetrieveController;
use WilokeListingTools\Framework\Helpers\GetSettings;

final class WilokePromotionReceipt extends ReceiptAbstract implements ReceiptStructureInterface
{
    protected $aInfo;
    protected $category = 'promotion';
    protected $aPromotionPlans;
    protected $aSelectedPlanKeys;
    protected $aSelectedPlans;

    /**
     * @var \WooCommerce
     */

    public function __construct($aInfo)
    {
        $this->aInfo = $aInfo;
    }

    public function getPlanName()
    {
        return sanitize_text_field($this->aInfo['planName']);
    }

    public function getPlanSlug()
    {
        // TODO: Implement getPlanSlug() method.
    }

    public function getPlanDescription()
    {

    }

    protected function setSubTotal()
    {
        if (!empty($this->subTotal)) {
            return $this->subTotal;
        }

        $this->subTotal = 0;
        foreach ($this->aPromotionPlans as $aPlan) {
            if (in_array(GetSettings::generateSavingPromotionDurationKey($aPlan), $this->aSelectedPlanKeys)) {
                $this->subTotal         += floatval($aPlan['price']);
                $this->aSelectedPlans[] = $aPlan;
            }
        }

        $this->regularPrice = $this->subTotal;

        return $this;
    }

    public function getSubTotal()
    {
        return $this->subTotal;
    }

    public function getTrialPeriod()
    {
        return 0;
    }

    public function getRegularPeriod()
    {
        return 0;
    }

    public function getPlanFeaturedImg()
    {
        return '';
    }

    public function getProductID()
    {
        if (isset($this->aInfo['aProductIDs']) && !empty($this->aInfo['aProductIDs'])) {
            $this->productID = $this->aInfo['aProductIDs'];
        } else {
            $aProductIDs = array_map(function ($aPlan) {
                return $aPlan['productAssociation'];
            }, $this->aSelectedPlans);

            foreach ($aProductIDs as $key => $productID) {
                if (get_post_type($productID) != 'product' || get_post_status($productID) !== 'publish') {
                    unset($aProductIDs[$key]);
                } else {
                    $aProductIDs[$key] = trim(absint($productID));
                }
            }

            if (!empty($aProductIDs)) {
                $this->isWooCommerce = true;
                $this->productID     = implode(',', $aProductIDs);
            }
        }
    }

    public function setupPlan()
    {
        $oRetrieve = new RetrieveController(new NormalRetrieve());

        if (!isset($this->aInfo['planName']) || empty($this->aInfo['planName'])) {
            return $oRetrieve->error([
                'msg' => esc_html__('The planName is required', 'wiloke-listing-tools')
            ]);
        }

        if (!isset($this->aInfo['aSelectedPlanKeys']) || empty($this->aInfo['aSelectedPlanKeys'])) {
            return $oRetrieve->error([
                'msg' => esc_html__('The aSelectedPlanKeys is required', 'wiloke-listing-tools')
            ]);
        }

        if (!isset($this->aInfo['userID']) || empty($this->aInfo['userID'])) {
            return $oRetrieve->error([
                'msg' => esc_html__('The userID is required', 'wiloke-listing-tools')
            ]);
        }

        $this->setUserID($this->aInfo['userID']);
        $this->aPromotionPlans   = GetSettings::getPromotionPlans();
        $this->aSelectedPlanKeys = $this->aInfo['aSelectedPlanKeys'];

        $this->setSubTotal();
        $this->setTax();
        $this->setTotal();
        $this->setupCategorySession();

        if ($this->aInfo['gateway'] == 'woocommerce') {
            $this->getProductID();
            $this->orderID = $this->aInfo['orderID'];

            if (empty($this->productID)) {
                return $oRetrieve->success([
                    'msg' => esc_html__('The product id is required', 'wiloke-listing-tools')
                ]);
            }
        }

        // Find out what promotions are using

        return $oRetrieve->success([
            'msg' => esc_html__('The plan has been setup successfully', 'wiloke-listing-tools')
        ]);
    }

    public function getPaymentData($aAdditionalData = [])
    {
        return array_merge(
            [
                'planID'            => $this->planID,
                'userID'            => $this->getUserID(),
                'currency'          => $this->getCurrency(),
                'subTotal'          => $this->getSubTotal(),
                'total'             => $this->getTotal(),
                'tax'               => $this->getTax(),
                'taxRate'           => $this->getTaxRate(),
                'discount'          => $this->getDiscount(),
                'category'          => $this->category,
                'planName'          => $this->getPlanName(),
                'aSelectedPlans'    => $this->aSelectedPlans,
                'gateway'           => $this->gateway,
                'aSelectedPlanKeys' => $this->aSelectedPlanKeys
            ],
            $aAdditionalData
        );
    }
}
