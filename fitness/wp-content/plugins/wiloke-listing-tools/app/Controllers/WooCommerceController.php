<?php

namespace WilokeListingTools\Controllers;

use Exception;
use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Controllers\Retrieve\RestRetrieve;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Helpers\WooCommerce;
use WilokeListingTools\Framework\Payment\CreatedPaymentHook;
use WilokeListingTools\Framework\Payment\PaymentGatewayStaticFactory;
use WilokeListingTools\Framework\Payment\PaymentMethodInterface;
use WilokeListingTools\Framework\Payment\ProceededPaymentHook;
use WilokeListingTools\Framework\Payment\Receipt;
use WilokeListingTools\Framework\Payment\Receipt\ReceiptStructureInterface;
use WilokeListingTools\Framework\Payment\WooCommerce\WooCommerceChangePlan;
use WilokeListingTools\Framework\Payment\WooCommerce\WooCommerceNonRecurringCreatedPaymentHook;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;
use WilokeListingTools\Models\PlanRelationshipModel;
use WP_User_Query;

class WooCommerceController extends Controller
{
    public    $planID;
    public    $productID;
    public    $orderID;
    public    $isNonRecurringPayment;
    public    $nextBillingDateGMT;
    public    $aPaymentMeta;
    public    $oReceipt;
    protected $aPaymentIDs;
    public    $gateway            = 'woocommerce';
    private   $excludeFromShopKey = 'exclude_from_shop';
    public    $listingID;
    public    $billingType;

    public function __construct()
    {
        //		add_action('wp_ajax_wiloke_change_plan_via_woocommerce', array($this, 'changePlan'));
        add_action('wiloke-listing-tools/before-redirecting-to-cart', [$this, 'removeProductsFromCart'], 10, 1);
        add_filter('woocommerce_add_to_cart_validation', [$this, 'cleanEverythingBeforeAddProductToCart'], 0);
        add_action('woocommerce_add_to_cart', [$this, 'removeAssociatePlanItems'], 0);
        //        add_action('wiloke-listing-tools/payment-via-woocommerce', [$this, 'preparePayment'], 10, 2);

        add_action('woocommerce_thankyou', [$this, 'updateCategoryToOrderMeta'], 10, 1);
        //		add_action( 'woocommerce_order_status_pending', array($this, 'updateCategoryToOrderMeta'), 10, 1 );

        add_filter('woocommerce_payment_complete_order_status', [$this, 'autoCompleteOrder'], 10, 2);
        add_action('woocommerce_single_product_summary', [$this, 'removeGalleryOfWooBookingOnTheSidebar'], 1);
        add_action('wilcity/before-close-header-tag', [$this, 'addQuickCart']);
        add_shortcode('wilcity_mini_cart', [$this, 'wooMiniCart']);
        add_action('wiloke-listing-tools/payment-pending', [$this, 'maybeSaveOldOrderIDIfItIsChangePlanSession']);

        /*
         * Exclude Add Listing Production From Shop page
         *
         * @since 1.2.0
         */
        add_action('updated_postmeta', [$this, 'addedListingProductToExcludeFromShopPage'], 10, 4);
        add_action('woocommerce_product_query', [$this, 'modifyWooQueryToExcludeShopPage'], 10);

        add_filter('wilcity/woocommerce/content-single-product/before-single-product-summary',
            [$this, 'willNotShowUpBeforeSingleProductIfIsBookingWidget']);
        add_filter('wilcity/woocommerce/content-single-product/after-single-product-summary',
            [$this, 'willNotShowUpBeforeSingleProductIfIsBookingWidget']);
        add_filter('wilcity/woocommerce/content-single-product/after-single-product',
            [$this, 'willNotShowUpBeforeSingleProductIfIsBookingWidget']);

        //        add_action('wp_ajax_get_listing_products', [$this, 'getListingProducts']);
        //        add_action('wp_ajax_nopriv_get_listing_products', [$this, 'getListingProducts']);
        add_action('wp_ajax_nopriv_add_product_to_cart', [$this, 'addProductToCart']);
        add_action('wp_ajax_add_product_to_cart', [$this, 'addProductToCart']);
        add_action('wp_ajax_nopriv_remove_product_to_cart', [$this, 'removeProductFromCart']);
        add_action('wp_ajax_remove_product_to_cart', [$this, 'removeProductFromCart']);
        add_action('wp_ajax_nopriv_set_product_to_cart', [$this, 'setProductToCart']);
        add_action('wp_ajax_set_product_to_cart', [$this, 'setProductToCart']);

        add_action('wp_ajax_wilcity_checkout_products', [$this, 'processCheckout']);
        add_action('wp_ajax_nopriv_wilcity_checkout_products', [$this, 'processCheckout']);

        add_action('wp_ajax_wilcity_fetch_woo_taxonomies', [$this, 'fetchWooTaxonomies']);
        add_action('wp_ajax_wilcity_search_order_id', [$this, 'searchOrderId']);
    }


    public function searchOrderId()
    {
        global $wpdb;
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        $fieldType = isset($_GET['fieldtype']) ? trim($_GET['fieldtype']) : 'select2';

        if (isset($_GET['status'])) {
            $aStatus = explode(',', $_GET['status']);
            $aStatus = array_map(function ($role) use ($wpdb) {
                $role = strpos($role, 'wc') === 0 ? $role : 'wc-' . $role;
                return $wpdb->_real_escape(trim($role));
            }, $aStatus);
        } else {
            $aStatus = ['completed', 'processing'];
        }

        $status = '"' . implode('","', $aStatus) . '"';
        $sql = $wpdb->prepare(
            "SELECT * FROM $wpdb->posts WHERE post_type=%s AND post_status IN (" . $status . ")",
            "shop_order"
        );

        if (isset($_GET['parent'])) {
            $sql = $wpdb->prepare($sql . " AND post_parent=%d", absint($_GET['parent']));
        }

        if (isset($_GET['q']) && !empty($_GET['q'])) {
            $sql = $wpdb->prepare($sql . " AND ID LIKE %s", '%' . absint($_GET['q']) . '%');
        }

        $aRawOrders = $wpdb->get_results($sql);

        if (!empty($aRawOrders) && !is_wp_error($aRawOrders)) {
            foreach ($aRawOrders as $oPost) {
                $label = '#' . $oPost->ID . ' by ' . get_user_meta($oPost->post_author, 'nickname', true);
                switch ($fieldType) {
                    case 'select2':
                        $aAuthors[] = [
                            'id'    => $oPost->ID,
                            'text'  => $label,
                            'label' => $label
                        ];
                        break;
                }
            }
        } else {
            $oRetrieve->error(['msg' => esc_html__('We found no order', 'wiloke-listing-tools')]);
        }

        $oRetrieve->success(['results' => $aAuthors]);
    }

    public function fetchWooTaxonomies()
    {
        $aTaxonomies = get_object_taxonomies('product', 'objects');
        if (isset($_GET['mode'])) {

            if ($_GET['mode'] == 'option') {
                $aOptions = [];
                foreach ($aTaxonomies as $taxonomy => $oTaxonomy) {
                    if (in_array($taxonomy, ['product_cat', 'product_tag'])) {
                        $aOptions[] = [
                            'name'  => $oTaxonomy->name,
                            'value' => $taxonomy
                        ];
                    }
                }

                wp_send_json_success([
                    'results' => $aOptions
                ]);
            }
        }
    }

    public function processCheckout()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $aData = $_POST;
        $aData['isBoolean'] = true;

        $status = $this->middleware(['VerifyNonce'], $aData);

        if (!$status) {
            return $oRetrieve->error([
                'msg' => esc_html__('Invalid code', 'wiloke-listing-tools')
            ]);
        }

        if (!isset($aData['productIDs']) || empty($aData['productIDs'])) {
            return $oRetrieve->error([
                'msg' => esc_html__('You must select 1 product at least', 'wiloke-listing-tools')
            ]);
        }

        foreach ($aData['productIDs'] as $productID) {
            WC()->cart->add_to_cart($productID, 1);
        }

        return $oRetrieve->success([
            'redirectTo' => wc_get_checkout_url()
        ]);
    }

    public function getProductJson(\WooCommerce $product = null, $productID = null, $type = '')
    {
        $product = empty($product) ? wc_get_product($productID) : $product;
        $salePrice = $product->get_sale_price();
        $regularPrice = $product->get_regular_price();

        return apply_filters('wilcity/wiloke-listing-tools/WooCommerceController/filter/getProductJson', [
            'ID'                => $product->get_id(),
            'title'             => $product->get_title(),
            'thumbnail'         => get_the_post_thumbnail_url($product->get_id(), 'thumbnail'),
            'salePriceHTML'     => $salePrice ? wc_price($salePrice) : '',
            'salePrice'         => floatval($salePrice),
            'regularPriceHTML'  => wc_price($product->get_regular_price()),
            'regularPrice'      => floatval($regularPrice),
            'priceHTML'         => $product->get_price_html(),
            'price'             => floatval($product->get_price()),
            'averageRating'     => number_format($product->get_average_rating(), 2),
            'dataAverageRating' => floatval($product->get_average_rating()),
            'ratingCounts'      => $product->get_rating_counts(),
            'cartKey'           => WooCommerce::getCartKey($productID)
        ], $product, $type);
    }

    public function removeProductFromCart()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        $status = $this->middleware(['verifyNonce'], [
            'isBoolean' => true
        ]);

        if (!$status) {
            $oRetrieve->error(
                [
                    'msg' => esc_html__('Invalid security code', 'wiloke-listing-tools')
                ]
            );
        }

        if (!isset($_POST['cartKey']) || empty($_POST['cartKey'])) {
            $oRetrieve->error(
                [
                    'msg' => esc_html__('The Cart Key is required', 'wiloke-listing-tools')
                ]
            );
        }

        $status = WC()->cart->remove_cart_item($_POST['cartKey']);

        if ($status) {
            $oRetrieve->success(
                [
                    'msg' => esc_html__('Removed the product from cart', 'wiloke-listing-tools')
                ]
            );
        }

        $oRetrieve->error(
            [
                'msg' => esc_html__('We could not remove this product from cart!', 'wiloke-listing-tools')
            ]
        );
    }

    public function setProductToCart()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        $status = $this->middleware(['verifyNonce', 'isPostType', 'isPublishedPost', 'verifyCartKey'], [
            'isBoolean' => true,
            'postID'    => $_POST['productID'],
            'postType'  => 'product',
            'cartKey'   => $_POST['cartKey']
        ]);

        if (!$status) {
            $oRetrieve->error(
                [
                    'msg' => esc_html__('We could not insert this product to cart!', 'wiloke-listing-tools')
                ]
            );
        }

        $cartKey = $_POST['cartKey'];
        $quantity = isset($_POST['quantity']) && !empty($_POST['quantity']) ? absint($_POST['quantity']) : 1;

        try {
            $status = WC()->cart->set_quantity($cartKey, $quantity);
            if ($status) {
                $oRetrieve->success(
                    [
                        'msg'     => sprintf(esc_html__('Added %s to cart', 'wiloke-listing-tools'),
                            get_the_title($_POST['productID'])),
                        'cartKey' => $cartKey
                    ]
                );
            }

            $oRetrieve->error(
                [
                    'msg' => esc_html__('We could not insert this product to cart!', 'wiloke-listing-tools')
                ]
            );
        }
        catch (Exception $e) {
            $oRetrieve->error(
                [
                    'msg' => $e->getMessage()
                ]
            );
        }
    }

    public function addProductToCart()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        $status = $this->middleware(['verifyNonce', 'isPostType', 'isPublishedPost'], [
            'isBoolean' => true,
            'postID'    => $_POST['productID'],
            'postType'  => 'product'
        ]);

        if (!$status) {
            $oRetrieve->error(
                [
                    'msg' => esc_html__('We could not insert this product to cart!', 'wiloke-listing-tools')
                ]
            );
        }

        $productID = $_POST['productID'];
        $quantity = isset($_POST['quantity']) && !empty($_POST['quantity']) ? absint($_POST['quantity']) : 1;

        try {
            $cartKey = WC()->cart->add_to_cart($productID, $quantity);
            if ($cartKey) {
                $oRetrieve->success(
                    [
                        'msg'     => sprintf(esc_html__('Added %s to cart', 'wiloke-listing-tools'), get_the_title
                        ($productID)),
                        'cartKey' => $cartKey
                    ]
                );
            }

            $oRetrieve->error(
                [
                    'msg' => esc_html__('We could not insert this product to cart!', 'wiloke-listing-tools')
                ]
            );
        }
        catch (Exception $e) {
            $oRetrieve->error(
                [
                    'msg' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * @param $status
     *
     * @return bool
     */
    public function willNotShowUpBeforeSingleProductIfIsBookingWidget($status)
    {
        return General::$isBookingFormOnSidebar ? false : $status;
    }

    /**
     * @param $metaID
     * @param $postID
     * @param $metaKey
     * @param $metaVal
     */
    public function addedListingProductToExcludeFromShopPage($metaID, $postID, $metaKey, $metaVal)
    {
        if ($metaKey == 'wilcity_woocommerce_association') {
            SetSettings::setPostMeta($metaVal, $this->excludeFromShopKey, 'yes');
        }
    }

    /**
     * @param $query
     *
     * @return bool
     */
    public function modifyWooQueryToExcludeShopPage($query)
    {
        if (!is_shop() && !is_product_category() && !is_product_tag()) {
            return false;
        }

        $aMetaQueries = $query->get('meta_query');
        $aMetaQueries = empty($aMetaQueries) ? [] : $aMetaQueries;
        $aMetaQueries[] = [
            'relation' => 'OR',
            [
                'key'     => 'wilcity_exclude_from_shop',
                'compare' => 'NOT EXISTS'
            ],
            [
                'key'     => 'wilcity_exclude_from_shop',
                'value'   => 'yes',
                'compare' => '!='
            ]
        ];

        $query->set('meta_query', $aMetaQueries);
    }

    /*
     * If it's change plan session, We will save old order id to Payment Meta.
     * This step is very important, because We will upgrade Listings that belong to old order plan to new order plan
     */
    public function maybeSaveOldOrderIDIfItIsChangePlanSession($aInfo)
    {
        $oldOrderID = Session::getSession(wilokeListingToolsRepository()->get('payment:wooOldOrderID'), true);

        if (!empty($oldOrderID)) {
            PaymentMetaModel::set($aInfo['paymentID'], 'oldOrderID', $oldOrderID);
        } else {
            // If the old plan is Free Plan
            $oldPaymentID = Session::getSession(wilokeListingToolsRepository()->get('payment:oldPaymentID'), false);
            if (!empty($oldPaymentID)) {
                if (PaymentModel::getField('userID', $oldPaymentID) == User::getCurrentUserID()) {
                    PaymentMetaModel::set($aInfo['paymentID'], 'oldPaymentID', $oldPaymentID);
                }
            }
        }
    }

    /*
     * Change WooCommerce Subscription Plan
     *
     * @since 1.1.7.3
     */
    public function changePlan()
    {
        if (!isset($_POST['newPlanID']) || !isset($_POST['currentPlanID']) || !isset($_POST['paymentID']) ||
            !isset($_POST['postType'])
        ) {
            wp_send_json_error([
                'msg' => esc_html__('ERROR: The new plan, current plan, post type and payment ID are required',
                    'wiloke-listing-tools')
            ]);
        }

        $userID = get_current_user_id();
        $this->middleware(['isMyPaymentSession'], [
            'paymentID' => absint($_POST['paymentID']),
            'userID'    => $userID
        ]);

        $oWooCommerceChangePlan
            = new WooCommerceChangePlan($userID, $_POST['paymentID'], $_POST['newPlanID'], $_POST['currentPlanID'],
            $_POST['postType']);
        $aStatus = $oWooCommerceChangePlan->execute();

        if ($aStatus['status'] == 'success') {
            wp_send_json_success($aStatus);
        } else {
            wp_send_json_error($aStatus);
        }
    }

    public function wooMiniCart()
    {
        ob_start();
        $this->addQuickCart();
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public function addQuickCart()
    {
        if (class_exists('woocommerce')) {
            // Is this the cart page?
            if (is_cart() || !is_object(WC()->cart) || WC()->cart->get_cart_contents_count() == 0 || is_page_template
                ('dashboard/index.php')
            ) {
                return false;
            }
            ?>
            <div class="header_cartWrap__bOA2i active widget woocommerce widget_shopping_cart">
                <div class="header_cartBtn__1gAQU">
                    <span class="<?php echo esc_attr(apply_filters('wilcity/filter/class-prefix',
                        'wilcity-total-cart-item')); ?>"><?php echo esc_html(WC()->cart->get_cart_contents_count()); ?></span>
                    <div class="header_cartIcon__18VjH">
                        <i class="la la-shopping-cart"></i>
                    </div>
                    <div class="header_product__1q6pw product-cart-js">
                        <header class="header_cartHeader__2LxzS"><h4 class="header_cartTitle__l46ln"><i
                                    class="la la-shopping-cart"></i><?php echo esc_html__('Total Items',
                                    'wiloke-listing-tools'); ?> <span
                                    class="<?php echo esc_attr(apply_filters('wilcity/filter/class-prefix',
                                        'wilcity-total-cart-item')); ?>"><?php echo esc_html(WC()->cart->get_cart_contents_count()); ?></span>
                            </h4></header>

                        <div class="widget_shopping_cart_content">
                            <?php woocommerce_mini_cart(); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
    }

    public function removeGalleryOfWooBookingOnTheSidebar()
    {
        if (General::$isBookingFormOnSidebar) {
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_title', 5);
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
            remove_action('woocommerce_template_single_price', 'woocommerce_template_single_price', 15);
        }
    }

    public function autoCompleteOrder($status, $orderID)
    {
        $order = wc_get_order($orderID);
        $paymentMethod = get_post_meta($orderID, '_payment_method', true);
        // No updated status for orders delivered with Bank wire, Cash on delivery and Cheque payment methods.

        if (in_array($paymentMethod, ['bacs', 'cod', 'cheque'])) {
            return $status;
        } elseif ($order->has_status('processing')) {
            $paymentID = PaymentModel::getPaymentIDsByWooOrderID($orderID, true);
            if (!empty($paymentID)) {
                $status = 'completed';
            }
        }

        return $status;
    }

    public function cleanEverythingBeforeAddProductToCart($cart_item_data)
    {

        if (!isset($_GET['add-to-cart']) || empty($_GET['add-to-cart']) && !is_cart()) {
            return $cart_item_data;
        }

        global $woocommerce;

        $planID = PlanRelationshipModel::getPlanIDByProductID($_GET['add-to-cart']);
        if (empty($planID)) {
            return $cart_item_data;
        }

        $woocommerce->cart->empty_cart();

        return true;
    }

    public function removeAssociatePlanItems()
    {
        global $woocommerce;
        if ($woocommerce->cart->get_cart_contents_count() == 0) {
            return false;
        }

        $productID = Session::getProductID();
        if (empty($productID)) {
            return false;
        }

        foreach ($woocommerce->cart->get_cart() as $cartItemKey => $aCardItem) {
            $planID = PlanRelationshipModel::getPlanIDByProductID($productID);
            if (empty($planID)) {
                continue;
            }

            if ($aCardItem['product_id'] != $productID) {
                $woocommerce->cart->remove_cart_item($cartItemKey);
            }
        }
    }

    public function removeProductsFromCart($productIDs)
    {
        global $woocommerce;
        foreach ($woocommerce->cart->get_cart() as $cartItemKey => $aCardItem) {
            if (is_array($productIDs)) {
                if (in_array($aCardItem['product_id'], $productIDs)) {
                    $woocommerce->cart->remove_cart_item($cartItemKey);
                }
            } else {
                if ($aCardItem['product_id'] == $productIDs) {
                    $woocommerce->cart->remove_cart_item($cartItemKey);
                }
            }
        }
    }

    public function updateCategoryToOrderMeta($orderID)
    {
        $paymentID = PaymentModel::getPaymentIDsByWooOrderID($orderID, true);
        if (empty($paymentID)) {
            return false;
        }

        $category = Session::getPaymentCategory();

        if (!empty($category)) {
            try {
                wc_update_order_item_meta($orderID, '_wilcity_plan_category', $category);
            }
            catch (Exception $e) {
            }
        }
    }
}
