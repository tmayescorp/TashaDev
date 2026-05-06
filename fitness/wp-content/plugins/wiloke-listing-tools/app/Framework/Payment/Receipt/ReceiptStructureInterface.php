<?php

namespace WilokeListingTools\Framework\Payment\Receipt;

interface ReceiptStructureInterface
{
    /**
     * @return string
     */
    public function getPlanName();
    
    /**
     * @return integer
     */
    public function getPlanID();
    
    /**
     * @return string
     */
    public function getPlanSlug();
    
    /**
     * @return string
     */
    public function getPlanDescription();
    
    /**
     * @return float
     */
    public function getDiscount();
    
    /**
     * @return float
     */
    public function getTotal();
    
    /**
     * @return float
     */
    public function getSubTotal();
    
    /**
     * @return string
     */
    public function getCurrency();
    
    /**
     * @param array $aArgs
     *
     * @return mixed
     */
    public function getThankyouURL($aArgs = []);
    
    /**
     * @return integer
     */
    public function getUserID();
    
    /**
     * @param array $aArgs
     *
     * @return string
     */
    public function getCancelUrl($aArgs = []);
    
    /**
     * @return mixed
     */
    public function getTrialPeriod();
    
    /**
     * @return mixed
     */
    public function getRegularPeriod();
    
    /**
     * @return mixed
     */
    public function getPlanFeaturedImg();
    
    /**
     * @return mixed
     */
    public function setupPlan();
    
    /**
     * @return mixed
     */
    public function getPackageType();
    
    /**
     * @param array $aAdditionalData
     *
     * @return mixed
     */
    public function getPaymentData($aAdditionalData = []);
    
    /**
     * @return mixed
     */
    public function getProductID();
    
    public function getOrderID();
    
    public function getGateway();
    
    public function getTax();
    
    public function getTaxRate();
    
    public function getTotalWithoutDiscount();
}
