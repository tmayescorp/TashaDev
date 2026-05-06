<?php

namespace WilokeListingTools\Framework\Helpers;

use WilokeListingTools\Models\PaymentModel;

class WooCommerce
{
    public static function getCartItems()
    {
        return function_exists('WC') && !is_null(WC()->cart) ? WC()->cart->get_cart() : false;
    }

    /**
     * @param $productID
     *
     * @return array
     */
    public static function getListingIDsByMyRoomID($productID)
    {
        global $wpdb;

        $aListingID = $wpdb->get_var(
            $wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='wilcity_my_room' AND meta_value=%d",
                $productID)
        );

        return empty($aListingID) ? [] : [absint($aListingID)];
    }

    /**
     * @param $productID
     *
     * @return array
     */
    public static function getListingIDsByMyProductID($productID)
    {
        global $wpdb;
        $aRawProductIDs =
            $wpdb->get_results("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='wilcity_my_products' AND meta_value LIKE '%".
                               $wpdb->esc_like($productID)."%'",
                ARRAY_A
            );

        if (empty($aRawProductIDs)) {
            return [];
        }

        $aProductIDs = [];
        foreach ($aRawProductIDs as $aProductID) {
            $aProductIDs[] = absint($aProductID['post_id']);
        }

        return $aProductIDs;
    }

    /**
     * @param $productID
     *
     * @return array|bool|string|null
     */
    public static function getListingIDsByProductID($productID)
    {
        $oProduct = wc_get_product($productID);
        if (empty($oProduct) || is_wp_error($oProduct)) {
            return false;
        }

        if ($oProduct->is_type('booking')) {
            return self::getListingIDsByMyRoomID($productID);
        } else {
            return self::getListingIDsByMyProductID($productID);
        }
    }

    protected static function isProductInCart($productID)
    {
        $aCartItems = self::getCartItems();

        if (empty($aCartItems)) {
            return false;
        }

        $status = false;
        foreach ($aCartItems as $aCartItem) {
            if ($aCartItem['product_id'] == $productID) {
                $status = true;
                break;
            }
        }

        return $status;
    }

    public static function getCartKey($productID)
    {
        $aCartItems = self::getCartItems();
        if (empty($aCartItems)) {
            return false;
        }

        $cartKey = '';

        foreach ($aCartItems as $aCartItem) {
            if ($aCartItem['product_id'] == $productID) {
                $cartKey = $aCartItem['key'];
                break;
            }
        }

        return $cartKey;
    }

    /*
     * Checks a given order to see if it was used to purchase a WC_Subscription object via checkout.
     *
     * @return bool
     * @since 1.2.0
     */
    public static function isSubscription($orderID)
    {
        if (!function_exists('wcs_order_contains_subscription') || !wcs_order_contains_subscription($orderID)) {
            return false;
        }

        return true;
    }

    public static function isProductSubscription($productID)
    {
        return !class_exists('WC_Subscriptions_Product') || \WC_Subscriptions_Product::is_subscription($productID);
    }

    /**
     * Get latest Subscription ID by Order ID
     *
     * @return int
     * @var $orderID WooCommerce Order ID
     * @since 1.2.0
     *
     */
    public static function getLatestSubscriptionIDByOrderID($orderID, $subscriptionStatus = 'any')
    {
        global $wpdb;
        $subscriptionID = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID from $wpdb->posts WHERE post_parent=%d AND post_status=%s and post_type='shop_subscription' ORDER BY ID DESC LIMIT 1",
                $orderID, $subscriptionStatus
            )
        );

        return empty($subscriptionID) ? $subscriptionID : absint($subscriptionID);
    }

    /**
     * Count total subscriptions of Order
     *
     * @return int
     * @var $orderID WooCommerce Order ID
     * @since 1.2.0
     *
     */
    public static function countSubscriptionsByOrderID($orderID, $subscriptionStatus = 'any')
    {
        global $wpdb;
        $subscriptionID = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT count(ID) from $wpdb->posts WHERE post_parent=%d AND post_status=%s and post_type='shop_subscription'",
                $orderID, $subscriptionStatus
            )
        );

        return empty($subscriptionID) ? $subscriptionID : absint($subscriptionID);
    }

    /**
     * Convert Period To day
     *
     * @return int
     * @var $length
     * @var $period
     *
     * @since 1.2.0
     *
     */
    public static function convertPeriodToDays($length, $period)
    {
        $days_in_cycle = 0;
        switch ($period) {
            case 'week' :
                $days_in_cycle = 7 * $length;
                break;
            case 'day' :
                $days_in_cycle = $length;
                break;
            case 'month' :
                $days_in_cycle = gmdate('t') * $length;
                break;
            case 'year' :
                $days_in_cycle = (365 + gmdate('L')) * $length;
                break;
        }

        return absint($days_in_cycle);
    }

    /**
     * Checks a given product id to see it's Subscription Product or not
     *
     * @return bool
     * @since 1.2.0
     */
    public static function isSubscriptionProduct($productID)
    {
        return class_exists('WC_Subscriptions_Product') && \WC_Subscriptions_Product::is_subscription($productID);
    }

    /**
     * Get WooCommerce Product ID by Plan ID
     *
     * @return int
     * @since 1.2.0
     */
    public static function getWooCommerceAliasByPlanID($planID)
    {
        $productID = GetSettings::getPostMeta($planID, 'woocommerce_association');

        return empty($productID) ? 0 : absint($productID);
    }

    public static function isWooCommercePlan($planID)
    {
        return !empty(self::getWooCommerceAliasByPlanID($planID));
    }

    /*
     * Checks a give order to see if it is Purchase Listing Plan session or not
     *
     * @since 1.2.0
     * @return bool
     */
    public static function isPurchaseListingPlan($orderID)
    {
        $aPaymentIDs = PaymentModel::getPaymentIDsByWooOrderID($orderID);
        if (empty($aPaymentIDs)) {
            return false;
        }

        return true;
    }

    private static function getSubscriptionIDByOrderID($order_id)
    {
        $subscriptions_ids = wcs_get_subscriptions_for_order($order_id);
        // We get the related subscription for this order
        foreach ($subscriptions_ids as $subscription_id => $subscription_obj) {
            if ($subscription_obj->order->id == $order_id) {
                return $subscription_obj;
            }
        }
    }

    /**
     * Get Order Status
     *
     * @return string
     * @since 1.2.0
     */
    public static function getOrderStatus($orderID)
    {
        $oOrder = wc_get_order($orderID);

        return $oOrder->get_status();
    }

    /**
     * Is activating Subscription
     *
     * @return bool
     * @var $subscriptionID
     * @since 1.2.0
     */
    public static function isActivateSubscription($subscriptionID)
    {
        $oSubscription = new \WC_Subscription($subscriptionID);

        return $oSubscription->get_status() == 'active';
    }

    /**
     * Check whether this order is completed or not
     *
     * @since 1.2.0
     */
    public static function isCompletedOrder($orderID)
    {
        return self::getOrderStatus($orderID) == 'completed';
    }

    /*
     * Get trial duration day
     *
     * @return int
     * @since 1.2.0
     */
    public static function getNextBillingDate($oOrder)
    {

    }

    /*
     * Get trial duration day
     *
     * @return int
     * @since 1.2.0
     */
    public static function getBillingType($orderID)
    {
        return self::isSubscription($orderID) ?
            wilokeListingToolsRepository()->get('payment:billingTypes', true)->sub('recurring') :
            wilokeListingToolsRepository()->get('payment:billingTypes', true)->sub('nonrecurring');
    }
}
