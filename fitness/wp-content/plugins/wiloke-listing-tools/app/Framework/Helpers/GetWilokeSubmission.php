<?php

namespace WilokeListingTools\Framework\Helpers;

use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Register\WilokeSubmission;

class GetWilokeSubmission
{
    public static $aConfiguration = [];
    public static $isFocusNonRecurring;
    public static $aGateways;

    public static function isPage($page)
    {
        global $post;
        if (!isset($post->ID)) {
            return false;
        }

        $pageId = GetWilokeSubmission::getField($page);

        return $post->ID == $pageId;
    }

    public static function getThankyouPageURL($aArgs = [], $isFocus = false)
    {
        $thankyouURL = GetWilokeSubmission::getField('thankyou', true, $isFocus);

        if (!empty($aArgs)) {
            return add_query_arg($aArgs, $thankyouURL);
        }

        return $thankyouURL;
    }

    public static function getDefaultPlanID($postID)
    {
        return GetWilokeSubmission::getField('free_claim_' . get_post_type($postID) . '_plan');
    }

    public static function getCancelPageURL($aArgs = [], $isFocus = false)
    {
        $cancelURL = GetWilokeSubmission::getField('cancel', true, $isFocus);

        if (!empty($aArgs)) {
            return add_query_arg($aArgs, $cancelURL);
        }

        return $cancelURL;
    }

    public static function getField($field, $isUrl = false, $isFocus = false)
    {
        self::getAll($isFocus);
        if (isset(self::$aConfiguration[$field])) {
            return $isUrl ? get_permalink(self::$aConfiguration[$field]) : self::$aConfiguration[$field];
        }

        return '';
    }

    public static function getDashboardUrl($field, $router)
    {
        $dashboardURL = self::getField($field, true);

        return $dashboardURL . '#' . $router;
    }

    private static function verifyPlan($planID): bool
    {
        return !empty($planID) && get_post_type($planID) == 'listing_plan' &&
            get_post_status($planID) == 'publish';
    }

    public static function getFreePlan($postType)
    {
        $freePlanID = self::getField('free_claim_' . $postType . '_plan');
        if (self::verifyPlan($freePlanID)) {
            return $freePlanID;
        }

        $aPlans = self::getAddListingPlans($postType . '_plans');

        foreach ($aPlans as $planID) {
            if (self::verifyPlan($planID)) {
                return $planID;
            }
        }

        return 0;
    }

    public static function getFreeClaimPlanID($listingID)
    {
        $key = 'free_claim_' . get_post_type($listingID) . '_plan';

        $plans = self::getField($key);
        $aPlans = explode(',', $plans);
        foreach ($aPlans as $plan) {
            if (get_post_type($plan) === 'listing_plan' && get_post_status($plan) === 'publish') {
                return absint($plan);
            }
        }

        return false;
    }

    public static function isEnable($field)
    {
        $toggle = self::getField($field);

        return $toggle == 'enable';
    }

    public static function isFreePlan($planID)
    {
        $aPlanSettings = GetSettings::getPlanSettings($planID);

        return empty($aPlanSettings['regular_price']);
    }

    public static function isSystemEnable()
    {
        return self::isEnable('toggle');
    }

    public static function getAll($isFocus = false)
    {
        if ($isFocus || empty(self::$aConfiguration)) {
            self::$aConfiguration = GetSettings::getOptions(WilokeSubmission::$optionKey, $isFocus, true);
            self::$aConfiguration = maybe_unserialize(self::$aConfiguration);
        }

        return self::$aConfiguration;
    }

    public static function getBillingType()
    {
        return self::getField('billing_type');
    }

    public static function getGatewaysWithName($excludeDirectBank = false)
    {
        $aTranslations = [
            'stripe'       => esc_html__('Stripe', 'wiloke-listing-tools'),
            'banktransfer' => esc_html__('Bank Transfer', 'wiloke-listing-tools'),
            'paypal'       => esc_html__('PayPal', 'wiloke-listing-tools'),
            'woocommerce'  => esc_html__('WooCommerce', 'wiloke-listing-tools')
        ];

        $aGateways = self::getAllGateways($excludeDirectBank);
        if (empty($aGateways)) {
            return false;
        }

        $aWithName = [];
        foreach ($aGateways as $gateway) {
            $aWithName[$gateway] = $aTranslations[$gateway];
        }

        return $aWithName;
    }

    public static function getDefaultGateway() {
        $aGateways = self::getAllGateways();
        if (empty($aGateways)) {
            return null;
        }

        return $aGateways[0];
    }

    public static function getAllGateways($excludeDirectBank = false)
    {
        if (!empty(self::$aGateways)) {
            return self::$aGateways;
        }

        $gateways = self::getField('payment_gateways');
        if (empty($gateways)) {
            self::$aGateways = false;

            return self::$aGateways;
        }

        self::$aGateways = explode(',', $gateways);
        if ($excludeDirectBank) {
            $key = array_search('banktransfer', self::$aGateways);
            $aGateways = self::$aGateways;
            if (!empty($key)) {
                unset($aGateways[$key]);
            }

            return $aGateways;
        }

        return self::$aGateways;
    }

    /**
     * @param bool $excludeDirectBank
     *
     * @return array
     */
    public static function getGatewaysWithConfiguration($excludeDirectBank = false)
    {
        $aRawGatewayWithNames = self::getGatewaysWithName($excludeDirectBank);
        if (empty($aRawGatewayWithNames)) {
            return [];
        }

        foreach ($aRawGatewayWithNames as $gateway => $name) {
            $aGatewayWithNames[$gateway] = [];
            if ($gateway == 'direct_bank') {
                $aGatewayWithNames[$gateway]['icon'] = 'la la-money';
                $aGatewayWithNames[$gateway]['bgColor'] = '';
            } else {
                $aGatewayWithNames[$gateway]['icon'] = 'la la-cc-' . $gateway;
                $aGatewayWithNames[$gateway]['bgColor'] = 'bg-color-' . $gateway;
            }

            $aGatewayWithNames[$gateway]['key'] = $gateway;
            $aGatewayWithNames[$gateway]['heading'] = $name;
            $aGatewayWithNames[$gateway]['link'] = '#';
        }

        return $aGatewayWithNames;
    }

    public static function getPlanKeyByListingID($listingID)
    {
        $postType = get_post_type($listingID);
        if (empty($listingID)) {
            return false;
        }

        return $postType . '_plans';
    }

    /**
     * @param $listingTypeOrPlanKey
     * @return array
     */
    public static function getPlanOptions($listingTypeOrPlanKey)
    {
        if (strpos($listingTypeOrPlanKey, '_plans') === false) {
            $listingTypeOrPlanKey .= '_plans';
        }

        $aPlanIds = self::getAddListingPlans($listingTypeOrPlanKey);

        $aPlanOptions = [];
        foreach ($aPlanIds as $id) {
            $aPlanOptions[$id] = get_the_title($id);
        }

        return $aPlanOptions;
    }

    public static function getAddListingPlans($planKey = '')
    {
        $planKey = empty($planKey) ? 'listing_plans' : $planKey;
        if (strpos($planKey, '_plans') === false) {
            $planKey = $planKey . '_plans';
        }

        $planIDs = self::getField($planKey);
        if (empty($planIDs)) {
            return false;
        }

        $aPlanIDs = explode(',', $planIDs);
        $aPlanIDs = array_map('trim', $aPlanIDs);

        return $aPlanIDs;
    }

    public static function isGatewaySupported($gateway = '')
    {
        self::getAllGateways();
        if (!self::$aGateways) {
            return false;
        }

        return in_array($gateway, self::$aGateways);
    }

    public static function getPermalink($field)
    {
        $val = self::getField($field);

        return get_permalink($val);
    }

    public static function getAddToCardUrl($productID)
    {
        $aArgs = [
            'add-to-cart' => $productID,
            'quantity'    => 1
        ];

        return add_query_arg(
            $aArgs,
            get_permalink(get_option('woocommerce_checkout_page_id'))
        );
    }

    public static function isNonRecurringPayment($billingType = null)
    {
        if (self::$isFocusNonRecurring) {
            return true;
        }
        $billingType = empty($billingType) ? self::getBillingType() : $billingType;

        if (empty($billingType)) {
            if (empty($planID)) {
                try {
                    Message::error(sprintf(esc_html__('Please provide the billing type: %s %s', 'wiloke-listing-tools'),
                        __LINE__, __CLASS__));
                }
                catch (\Exception $e) {
                }
            }
            $billingType = self::getBillingType();
        }

        return $billingType == wilokeListingToolsRepository()->get('payment:billingTypes', true)->sub('nonrecurring');
    }

    public static function convertStripePrice($price)
    {
        $zeroDecimal = self::getField('stripe_zero_decimal');
        $zeroDecimal = empty($zeroDecimal) ? 1 : $zeroDecimal;

        return number_format($price / $zeroDecimal, 2);
    }

    public static function getSymbol($currency)
    {
        return wilokeListingToolsRepository()
            ->get('wiloke-submission:currencySymbol', true)
            ->sub(strtoupper($currency));
    }

    public static function getPackageType($packageType)
    {
        switch ($packageType) {
            case 'promotion':
                $packageType = esc_html__('Promotion', 'wiloke-listing-tools');
                break;
            case 'listing_plan':
                $packageType = esc_html__('Listing Plan', 'wiloke-listing-tools');
                break;
            default:
                $packageType = str_replace('_', ' ', $packageType);
                $packageType = ucfirst($packageType);
                break;
        }

        return $packageType;
    }

    public static function canUserTrial($planID, $userID = null)
    {
        if (DebugStatus::status('WILOKE_ALWAYS_PAY')) {
            return true;
        }

        $userID = empty($userID) ? get_current_user_id() : $userID;
        $aPlansIDs = GetSettings::getUserMeta($userID, wilokeListingToolsRepository()->get('user:usedTrialPlans'));

        return empty($aPlansIDs) || !in_array($planID, $aPlansIDs);
    }

    public static function renderPrice($price, $currency = '', $isNegative = false, $symbol = '')
    {
        if (empty($symbol)) {
            $currency = empty($currency) ? GetWilokeSubmission::getField('currency_code') : $currency;
            $symbol = self::getSymbol($currency);
        }
        $position = self::getField('currency_position');

        if (strpos($price, '-') !== false) {
            $price = str_replace('-', '', $price);
            $isNegative = true;
        }

        $price = apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/framework/helpers/render-price/price',
            $price
        );
        $symbol = apply_filters('wilcity/filter/symbol', $symbol);
        switch ($position) {
            case 'left':
                $priceHTML = $symbol . $price;
                break;
            case 'right':
                $priceHTML = $price . $symbol;
                break;
            case 'left_space':
                $priceHTML = $symbol . ' ' . $price;
                break;
            default:
                $priceHTML = $price . ' ' . $symbol;
                break;
        }

        return $isNegative ? '-' . $priceHTML : $priceHTML;
    }

    public static function getSubmissionPlanID()
    {
        return Session::getSession(wilokeListingToolsRepository()->get('payment:storePlanID'));
    }

    public static function isCancelPage()
    {
        global $post;
        $cancelID = self::getField('cancel');

        return isset($post->ID) && $cancelID == $post->ID;
    }

    public static function isPlanExists($planID)
    {
        $aPostTypes = General::getPostTypes(false, false);
        foreach ($aPostTypes as $postType => $aPostType) {
            $aPlanIDs = self::getAddListingPlans($postType . '_plans');
            if (is_array($aPlanIDs) && in_array($planID, $aPlanIDs)) {
                return true;
            }
        }

        return false;
    }

    public static function isFreeAddListing()
    {
        $mode = GetWilokeSubmission::getField('add_listing_mode');

        return $mode == 'free_add_listing';
    }

    public static function getPlanTrialDays($planID)
    {
        $aPlanSettings = GetSettings::getPlanSettings($planID);

        return isset($aPlanSettings['trial_period']) && !empty($aPlanSettings['trial_period']) ?
            absint($aPlanSettings['trial_period']) : 0;
    }

    public static function getPlanRegularDays($planID)
    {
        $aPlanSettings = GetSettings::getPlanSettings($planID);

        return isset($aPlanSettings['regular_period']) && !empty($aPlanSettings['regular_period']) ?
            $aPlanSettings['regular_period'] : 0;
    }

    public static function getPlanFrequencyDays($planID)
    {
        $trialDays = self::getPlanTrialDays($planID);
        if (!empty($trialDays)) {
            return $trialDays;
        }

        return self::getPlanRegularDays($planID);
    }

    public static function getDefaultPostType()
    {
        $aPostTypes = GetSettings::getFrontendPostTypes();
        $aTypes = array_keys($aPostTypes);

        return $aTypes[0];
    }

    public static function getPlanTypeByPlanID($planID)
    {
        $aPostTypes = GetSettings::getFrontendPostTypes(true);
        if (empty($aPostTypes)) {
            return '';
        }

        foreach ($aPostTypes as $postTypeKey) {
            $aPlanIDs = self::getAddListingPlans($postTypeKey);
            if (in_array($planID, $aPlanIDs)) {
                return $postTypeKey . '_plan';
            }
        }

        return '';
    }

    public static function getNewPostStatus($postID)
    {
        if (get_post_status($postID) == 'publish') {
            return 'publish';
        }

        if (get_post_status($postID) === 'editing') {
            $editListingStatusMode = self::getField('published_listing_editable');
            switch ($editListingStatusMode) {
                case 'allow_trust_approved':
                    $postStatus = 'publish';
                    break;
                case 'not_allow':
                    $postStatus = 'draft';
                    break;
                default:
                    $postStatus = 'pending';
                    break;
            }
        } else {
            $newListingStatusMode = self::getField('approved_method');
            if ($newListingStatusMode == 'auto_approved_after_payment') {
                $postStatus = 'publish';
            } else {
                $postStatus = 'pending';
            }
        }

        return $postStatus;
    }

    public static function isTax()
    {
        return GetWilokeSubmission::isEnable('toggle_tax');
    }

    public static function isTaxOnPricing()
    {
        return GetWilokeSubmission::isEnable('toggle_tax_on_pricing');
    }

    public static function getTaxRate()
    {
        if (!self::isTax()) {
            return 0;
        }

        $taxRate = GetWilokeSubmission::getField('tax_rate');
        $taxRate = absint($taxRate);

        return $taxRate;
    }

    public static function getTaxTitle()
    {
        return GetWilokeSubmission::getField('tax_title');
    }

    /**
     * @param $price
     *
     * @return float
     */
    public static function calculateTax($price)
    {
        return apply_filters(
            'wiloke/filter/wiloke-listing-tools/app/Framework/Helpers/GetWilokeSubmission/calculateTax',
            round($price * self::getTaxRate() / 100, 2),
            $price,
            self::getTaxRate()
        );
    }

    public static function getOriginalPlanId($planId)
    {
        if (!defined('ICL_SITEPRESS_VERSION')) {
            return $planId;
        }
        global $sitepress;

        return wpml_object_id_filter($planId, 'listing_plan', false, $sitepress->get_default_language());
    }

    public static function isSingleAddListingType(): bool
    {
        $aListingTypes = GetSettings::getFrontendPostTypes();
        return count($aListingTypes) < 2;
    }

    /**
     * It's not included coupon
     */
    public static function calculateTotal($price)
    {
        if (!self::isTax()) {
            return $price;
        }

        $tax = self::calculateTax($price);

        return $price + $tax;
    }
}
