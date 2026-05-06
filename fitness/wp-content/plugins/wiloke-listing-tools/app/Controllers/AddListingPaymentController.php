<?php

namespace WilokeListingTools\Controllers;

use WC_Order;
use WILCITY_SC\SCHelpers;
use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Controllers\Retrieve\RetrieveFactory;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\PlanHelper;
use WilokeListingTools\Framework\Helpers\WooCommerce;
use WilokeListingTools\Framework\Payment\CancelSubscriptionStaticFactory;
use WilokeListingTools\Framework\Payment\PaymentGatewayStaticFactory;
use WilokeListingTools\Framework\Payment\PaymentMethodInterface;
use WilokeListingTools\Framework\Payment\PayPal\PayPalExecuteNonRecurringPayment;
use WilokeListingTools\Framework\Payment\PayPal\PayPalExecuteRecurringPayment;
use WilokeListingTools\Framework\Payment\Receipt\ReceiptStaticFactory;
use WilokeListingTools\Framework\Payment\SuspendSubscriptionPlan;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;
use WilokeListingTools\Models\PlanRelationshipModel;
use WilokeListingTools\Models\UserModel;
use WP_Query;

class AddListingPaymentController extends Controller
{
    private $planID;
    private $listingID;
    private $productID;
    private $oReceipt;
    private $gateway;
    private $isNonRecurringPayment;

    public function __construct()
    {
        add_action('wp_ajax_wiloke_submission_purchase_add_listing_plan', [$this, 'purchaseAddListingPlan']);
        add_action('wp_ajax_wiloke_submission_change_plan', [$this, 'changeAddListingPlan']);
        add_action('wp_ajax_wiloke_submission_cancel_add_listing_plan', [$this, 'cancelAddListingPlan']);
        add_action('wilcity/wiloke-listing-tools/after-added-addCustomScripts', [$this, 'enqueueScripts'], 100);
        add_action('init', [$this, 'paymentExecution'], 1);
        add_action('wp_ajax_wilcity_post_type_plans', [$this, 'fetchPostTypePlans']);
        add_action('wp_ajax_wilcity_fetch_gateways', [$this, 'fetchPaymentGateways']);
        add_action('wp_ajax_wilcity_fetch_billing_type', [$this, 'getBillingType']);
        add_action('wp_ajax_fetch_listings_in_payment_id', [$this, 'fetchListingsInPaymentID']);

        add_action('woocommerce_checkout_order_processed', [$this, 'purchaseAddListingPlanThroughWooCommerce'], 5, 1);
    }

    public function fetchListingsInPaymentID()
    {
        $aMiddleware = ['verifyNonce', 'verifyPaymentID'];
        $aData = $_POST;

        $aStatus = $this->middleware($aMiddleware, [
            'paymentID' => $aData['paymentID']
        ]);

        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        if ($aStatus['status'] == 'error') {
            FileSystem::logError($aStatus['msg']);

            return $oRetrieve->error(['msg' => $aStatus['msg']]);
        }

        $paymentID = absint($aData['paymentID']);

        $aRawListingIDs = PlanRelationshipModel::getObjectIDsByPaymentID($paymentID, false, false);

        if (empty($aRawListingIDs)) {
            if (GetWilokeSubmission::isNonRecurringPayment(PaymentModel::getField('billingType', $paymentID))) {
                $oRetrieve->error([
                    'msg' => esc_html__('There is no listing belongs to this plan currently.', 'wiloke-listing-tools')
                ]);
            } else {
                $oRetrieve->error([
                    'msg' => esc_html__('There is no listing belongs to this plan currently. Warning: You will still be billed for this subscription. To end billing, Cancel the plan instead.',
                        'wiloke-listing-tools')
                ]);
            }
        }

        $aListingIDs = array_map(function ($oListing) {
            return $oListing['objectID'];
        }, $aRawListingIDs);

        if (isset($aData['postID']) && !empty($aData['postID'])) {
            array_unshift($aListingIDs, $aData['postID']);
        }

        $query = new WP_Query(
            [
                'post_type' => 'any',
                'post__in'  => $aListingIDs,
                'orderby'   => 'post__in'
            ]
        );

        if (!$query->have_posts()) {
            $oRetrieve->error([
                'msg' => esc_html__('There is no listing in this payment id', 'wiloke-listing-tools')
            ]);
        }

        $aListings = [];
        while ($query->have_posts()) {
            $query->the_post();
            $aListings[] = [
                'postTitle'  => get_the_title($query->post->ID),
                'postType'   => $query->post->post_type,
                'postStatus' => $query->post->post_status,
                'ID'         => $query->post->ID
            ];
        }

        $oRetrieve->success([
            'listings' => $aListings
        ]);
    }

    public function getBillingType()
    {
        wp_send_json_success([
            'type' => GetWilokeSubmission::getBillingType()
        ]);
    }

    public function fetchPaymentGateways()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $aGateways = GetWilokeSubmission::getGatewaysWithName(false);
        $noGateway = esc_html__('There are Payment Gateways', 'wiloke-listing-tools');

        if (empty($aGateways)) {
            $oRetrieve->error([
                'msg' => $noGateway
            ]);
        }

        $aGatewayOptions = [];
        $isUsingWooCommerce = false;
        foreach ($aGateways as $gateway => $name) {
            if ($isUsingWooCommerce) {
                continue;
            }

            if ($gateway == 'woocommerce') {
                $isUsingWooCommerce = true;
            } else {
                $aGatewayOptions[] = [
                    'label' => $name,
                    'id'    => $gateway
                ];
            }
        }

        $oRetrieve->success([
            'aGateways'          => $aGatewayOptions,
            'isUsingWooCommerce' => $isUsingWooCommerce
        ]);
    }

    public function fetchPostTypePlans()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        if (!isset($_GET['paymentID']) || empty($_GET['paymentID'])) {
            $oRetrieve->error([
                'msg' => esc_html__('The payment ID is required', 'wiloke-listing-tools')
            ]);
        }

        $paymentID = absint($_GET['paymentID']);
        $listingID = PlanRelationshipModel::getLastObjectIDByPaymentID($paymentID);
        $aPlanIDs = GetWilokeSubmission::getAddListingPlans(GetWilokeSubmission::getPlanKeyByListingID($listingID));
        if (empty($aPlanIDs)) {
            $oRetrieve->error([
                'msg' => esc_html__('We found no plans in this listing type.', 'wiloke-listing-tools')
            ]);
        }

        $query = new WP_Query(
            [
                'post_type'   => 'listing_plan',
                'post_status' => 'publish',
                'post__in'    => $aPlanIDs,
                'orderby'     => 'post__in'
            ]
        );

        if (!$query->have_posts()) {
            $oRetrieve->error([
                'msg' => esc_html__('We found no plans in this listing type.', 'wiloke-listing-tools')
            ]);
        }

        $aPlans = [];
        while ($query->have_posts()) {
            $query->the_post();
            global $post;
            $aPlanSettings = GetSettings::getPlanSettings($post->ID);
            $remainingItems = UserModel::getRemainingItemsOfPlans($post->ID);
            $period = empty($aPlanSettings['regular_period']) ? esc_html__('Forever', 'wiloke-listing-tools') :
                sprintf(esc_html__('%d days', 'wiloke-listing-tools'), $aPlanSettings['regular_period']);
            $trialPeriod = empty($aPlanSettings['trial_period']) ? '' : sprintf(esc_html__('%d trial days',
                'wiloke-listing-tools'), $aPlanSettings['trial_period']);

            $productID = GetSettings::getPostMeta($query->post->ID, 'woocommerce_association');

            $aPlans[] = [
                'postTitle'      => get_the_title($post->ID),
                'content'        => $post->post_content,
                'ID'             => $post->ID,
                'price'          => SCHelpers::renderPlanPrice($aPlanSettings['regular_price'],
                    $aPlanSettings, $productID),
                'period'         => $period,
                'trialPeriod'    => $trialPeriod,
                'availability'   => sprintf(esc_html__('%d Listing(s)', 'wiloke-listing-tools'),
                    $aPlanSettings['availability_items']),
                'remainingItems' => empty($remainingItems) ? '' :
                    sprintf(esc_html__('%d remaining item(s)', 'wiloke-listing-tools'),
                        $remainingItems)
            ];
        }
        wp_reset_postdata();

        $oRetrieve->success([
            'aPlans' => $aPlans
        ]);
    }

    public function paymentExecution()
    {
        if (
            !isset($_GET['category']) || !in_array($_GET['category'], ['addlisting', 'paidClaim'])
            || !isset($_GET['category']) || empty($_GET['category'])
            || !isset($_GET['token']) || empty($_GET['token'])
            || Session::getSession('waiting_for_paypal_execution') !== 'yes'
        ) {
            return false;
        }

        $paymentID = PaymentMetaModel::getPaymentIDByToken($_GET['token']);
        $billingType = PaymentModel::getField('billingType', $paymentID);
        if (GetWilokeSubmission::isNonRecurringPayment($billingType)) {
            $oPayPalMethod = new PayPalExecuteAddListingPayment(new PayPalExecuteNonRecurringPayment());
        } else {
            $oPayPalMethod = new PayPalExecuteAddListingPayment(new PayPalExecuteRecurringPayment());
        }

        if (!$oPayPalMethod) {
            return false;
        }

        if ($oPayPalMethod->verify()) {
            $aResponse = $oPayPalMethod->execute();
            if ($aResponse['status'] == 'error') {
                Session::setSession('payment_error', $aResponse['msg']);
                FileSystem::logError($aResponse['msg'], __CLASS__, __METHOD__);
            }
        }
    }

    public function enqueueScripts()
    {
        if (is_page_template('wiloke-submission/checkout.php')) {
            wp_enqueue_script('proceedPayment', WILOKE_THEME_URI . 'assets/production/js/proceedPayment.min.js',
                ['jquery'],
                WILOKE_THEMEVERSION, true);
        }

        if (!GetWilokeSubmission::isGatewaySupported('stripe') || is_home()) {
            return false;
        }
    }

    /**
     * Using Stripe API v3: It's required in EU
     *
     * @see   https://stripe.com/docs/payments/checkout/server#create-one-time-payments
     * @since 1.1.7.6
     */
    public function createSession($billingType = null)
    {
        $this->isNonRecurringPayment = GetWilokeSubmission::isNonRecurringPayment($billingType);
        $aPaymentMethod = PaymentGatewayStaticFactory::get(
            $this->gateway,
            $this->isNonRecurringPayment
        );

        if ($aPaymentMethod['status'] == 'success') {
            /**
             * @var PaymentMethodInterface $oPaymentGateway
             */
            $oPaymentGateway = $aPaymentMethod['oPaymentMethod'];

            return $oPaymentGateway->proceedPayment($this->oReceipt);
        }

        return $aPaymentMethod;
    }

    public function guardAddListingPlan($aData): array
    {
        $aMiddleware = ['verifyNonce', 'isSetupThankyouCancelUrl', 'verifyPaymentID'];

        return $this->middleware(
            $aMiddleware,
            [
                'paymentID' => $aData['paymentID']
            ],
            'normal'
        );
    }

    public function changeAddListingPlan()
    {
        $aData = wp_parse_args(
            $_POST,
            [
                'paymentID'                 => '',
                'newPlanID'                 => '',
                'currentPlanID'             => '',
                'postIds'                   => '',
                'cancelCurrentSubscription' => 'no'
            ]
        );
        $oRetrieve = RetrieveFactory::retrieve();

        $aStatus = $this->guardAddListingPlan($aData);
        if ($aStatus['status'] == 'error') {
            return $oRetrieve->error($aData);
        }

        if ($currentPaymentID = absint($aData['paymentID'])) {
            $listingID = PlanRelationshipModel::getLastObjectIDByPaymentID($currentPaymentID);

            if (empty($listingID)) {
                return $oRetrieve->error([
                    'msg' => esc_html__('There is no listing belongs to this plan currently', 'wiloke-listing-tools')
                ]);
            }
            $listingType = get_post_type($listingID);
        } else {
            if (empty($aData['postIds'])) {
                return $oRetrieve->error([
                    'msg' => esc_html__('We could not find the plan type', 'wiloke-listing-tools')
                ]);
            }

            $aParsePostIds = explode(',', $aData['postIds']);
            $listingID = trim(end($aParsePostIds));
            $listingType = get_post_type($listingID);
        }

        if (empty($listingType)) {
            return $oRetrieve->error([
                'msg' => esc_html__('We could not find the plan type', 'wiloke-listing-tools')
            ]);
        }

        $newPlanID = absint($aData['newPlanID']);

        $aStatus = $this->middleware(['isPlanExists'], [
            'listingType' => $listingType,
            'planID'      => $newPlanID
        ]);

        if (empty($aData['currentPlanID'])) {
            $msg = esc_html__('The current plan id is required', 'wiloke-listing-tools');
            FileSystem::logError($msg);

            return $oRetrieve->error(['msg' => $msg]);
        }

        $currentPlanID = absint($aData['currentPlanID']);

        if ($aStatus['status'] == 'error') {
            return $oRetrieve->error(['msg' => $aStatus['msg']]);
        }

        $aPostIds = $aData['postIds'];
        if (empty($aData['postIds'])) {
            $aPostIds = PlanRelationshipModel::getObjectIDsByPaymentID($currentPaymentID, true);
            if (empty($aPostIds)) {
                return $oRetrieve->error([
                    'msg' => esc_html__('You have to select 1 listing at least',
                        'wiloke-listing-tools')
                ]);
            }
        }

        $maybeHasPaymentID = PaymentModel::getPaymentIDHasRemainingItemsByPlanID($newPlanID);
        $totalUpgradePost = count($aPostIds);

        $aBeforeRedirect = [];
        if (!empty($currentPaymentID) && $aData['cancelCurrentSubscription'] === 'yes') {
            // Cancel this plan if it's recurring payment and it's keeping active, pending status
            $paymentStatus = PaymentModel::getField('status', $currentPaymentID);
            if (in_array($paymentStatus, ['pending', 'active']) &&
                !GetWilokeSubmission::isNonRecurringPayment(
                    PaymentModel::getField('billingType', $currentPaymentID)
                )
            ) {
                try {
                    $aSuspendedStatus = (new SuspendSubscriptionPlan())->setPaymentID($currentPaymentID)->suspend();
                    if ($aSuspendedStatus['status'] === 'error') {
                        return $oRetrieve->error($aSuspendedStatus);
                    }

                    $aBeforeRedirect = [
                        'beforeRedirect' => [
                            'msg' => sprintf(esc_html__('The current payment subscription has been suspended successfully. Please complete new payment session to new subscription',
                                'wiloke-listing-tools'))
                        ]
                    ];
                }
                catch (\Exception $oE) {
                    return $oRetrieve->error([
                        'msg' => $oE->getMessage()
                    ]);
                }
            }
        }

        if (!empty($maybeHasPaymentID)) {
            if ($totalUpgradePost > $maybeHasPaymentID['remainingItems']) {
                return $oRetrieve->error([
                    'msg' => sprintf(
                        esc_html__(
                            'Error: Number of upgrade listings has been exceeded Number of remaining listings. Solution: You purchased this plan before and there is/are %d remaining item(s) now, please deduce the number of upgrade listings to smaller or equal to %d',
                            'wiloke-listing-tools'
                        ),
                        $maybeHasPaymentID['remainingItems'], $maybeHasPaymentID['remainingItems']
                    )
                ]);
            } else {
                /**
                 * @PlanRelationshipController:changeListingsToNewPlan 1
                 * @PostController            :changedListingToAnotherPurchasedPlan 10
                 */
                $aStatus = apply_filters(
                    'wilcity/wiloke-listing-tools/change-listings-to-another-purchased-plan',
                    [
                        'status' => '',
                        'msg'    => ''
                    ],
                    [
                        'postIDs'      => $aPostIds,
                        'paymentID'    => $maybeHasPaymentID['paymentID'],
                        'planID'       => $newPlanID,
                        'oldPlanID'    => $currentPlanID,
                        'oldPaymentID' => $currentPaymentID
                    ]
                );

                if ($aStatus['status'] == 'error') {
                    $oRetrieve->error($aStatus);
                }

                $msg = esc_html__(
                    'Congratulations, Your listings have been changed to new plan!',
                    'wiloke-listing-tools'
                );
                $aListings = [];
                foreach ($aPostIds as $postID) {
                    $aListings[] = [
                        'postTitle'  => sprintf(esc_html__('Listing Title: %s', 'wiloke-listing-tools'),
                            get_the_title($postID)),
                        'postStatus' => sprintf(esc_html__('Listing Status: %s', 'wiloke-listing-tools'),
                            get_post_status($postID)),
                        'planInfo'   => sprintf(esc_html__('Switched from %s to %s', 'wiloke-listing-tools'),
                            get_the_title($currentPlanID), get_the_title($newPlanID))
                    ];
                }

                $oRetrieve->success(apply_filters(
                    'wilcity/wiloke-listing-tools/changed-plan-message',
                    [
                        'msg'      => $msg,
                        'listings' => $aListings,
                        'warning'  => esc_html__(
                            'Warning: You will still be billed for this subscription even there is no listing belongs to it. To end billing, Cancel the plan instead',
                            'wiloke-listing-tools'
                        )
                    ]
                ));
            }
        } else {
            $aPlanSettings = GetSettings::getPlanSettings($newPlanID);
            if (!empty($aPlanSettings['availability_items']) &&
                $totalUpgradePost > $aPlanSettings['availability_items']
            ) {
                return $oRetrieve->error([
                    'msg' => sprintf(
                        esc_html__(
                            'Number of upgrade listings has been exceeded Availability Items of this plan. You can upgrade maximum %s listings',
                            'wiloke-listing-tools'
                        ),
                        $aPlanSettings['availability_items']
                    )
                ]);
            }
            // Starting set session
            Session::setPaymentPlanID($newPlanID);
            Session::setPaymentObjectID(implode(',', $aPostIds));
            Session::setPaymentCategory('addlisting');

            $aPostsApproved = [];
            foreach ($aPostIds as $objectID) {
                if (get_post_status($objectID) === 'publish') {
                    $aPostsApproved[] = $objectID;
                }
            }

            if (!empty($aPostsApproved)) {
                Session::setFocusObjectsApprovedImmediately($aPostIds);
            }

            $this->planID = $newPlanID;
            $this->listingID = Session::getPaymentObjectID();
            $aMiddleware = ['isGatewaySupported', 'isSetupThankyouCancelUrl'];

            if (PlanHelper::isFreePlan($this->planID)) {
                $this->gateway = 'free';
            } else {
                $this->gateway = isset($_POST['gateway']) ? $_POST['gateway'] : '';
            }

            $aStatus = $this->middleware($aMiddleware, [
                'gateway'     => $this->gateway,
                'planID'      => $this->planID,
                'listingType' => get_post_type($aPostIds[0])
            ]);

            if ($aStatus['status'] == 'error') {
                return $oRetrieve->error(['msg' => $aStatus['msg']]);
            }

            /**
             * If We are using WooCommerce, We will handle it via WooCommerce, it's a special case
             */
            if ($this->gateway == 'woocommerce') {
                $this->productID = GetSettings::getPostMeta($this->planID, 'woocommerce_association');
                if (empty($this->productID) || get_post_type($this->productID) != 'product') {
                    return $oRetrieve->error([
                        'msg' => sprintf(
                            esc_html__('You have to assign a product to %s plan', 'wiloke-listing-tools'),
                            get_the_title($this->planID)
                        )
                    ]);
                }

                /*
                * @hooked WooCommerceController:removeProductFromCart
                */
                do_action('wiloke-listing-tools/before-redirecting-to-cart', $this->productID);
                Session::setProductID($this->productID);

                return $oRetrieve->success([
                    'redirectTo' => GetSettings::getCartUrl($this->planID),
                    'gateway'    => $this->gateway
                ]);
            }

            $this->oReceipt = ReceiptStaticFactory::get('addlisting', [
                'planID'     => $this->planID,
                'userID'     => User::getCurrentUserID(),
                'couponCode' => ''
            ]);

            Session::setChangedPlanID($this->planID);
            $this->oReceipt->setupPlan();
            $aResponse = $this->createSession();

            if ($aResponse['status'] == 'success') {
                return $oRetrieve->success($aResponse + $aBeforeRedirect);
            }

            Session::getChangedPlanID(true);

            return $oRetrieve->error($aResponse);
        }
    }

    public function cancelAddListingPlan()
    {
        $aMiddleware = ['verifyNonce', 'isSetupThankyouCancelUrl', 'verifyPaymentID'];

        $aStatus = $this->middleware($aMiddleware, [
            'paymentID' => $_POST['paymentID'],
            'isBoolean' => true
        ]);

        $oRetrieve = new RetrieveController(new AjaxRetrieve());
        if ($aStatus['status'] == 'error') {
            return $oRetrieve->error(['msg' => $aStatus['msg']]);
        }

        FileSystem::logSuccess(sprintf('Processing cancel %d id', $_POST['paymentID']));

        $gateway = PaymentModel::getField('gateway', $_POST['paymentID']);
        $oPaymentMethod = CancelSubscriptionStaticFactory::get($gateway);

        $aStatus = $oPaymentMethod->execute($_POST['paymentID']);

        if ($aStatus['status'] == 'success') {
            return $oRetrieve->success(['msg' => $aStatus['msg']]);
        }

        return $oRetrieve->error(['msg' => $aStatus['msg']]);
    }

    private function verifyPurchaseAddListingPlan($aData): array
    {
        $this->planID = Session::getPaymentPlanID();
        $this->listingID = Session::getPaymentObjectID();

        // May be multiple listings
        $aParseListing = explode(',', $this->listingID);
        $aParseListing = array_map(function ($listing) {
            return trim($listing);
        }, $aParseListing);

        $aMiddleware = ['isGatewaySupported', 'isPlanExists', 'isSetupThankyouCancelUrl'];
        $this->gateway = isset($aData['gateway']) ? $aData['gateway'] : '';

        $aMiddlewareOptions = [
            'gateway'     => $this->gateway,
            'planID'      => $this->planID,
            'listingType' => get_post_type($aParseListing[0])
        ];

        if (empty($this->listingID)) {
            $planType = GetWilokeSubmission::getPlanTypeByPlanID($this->planID);
            if (!empty($planType)) {
                $aMiddlewareOptions['planType'] = $planType . 's';
            }
        }

        return $this->middleware($aMiddleware, $aMiddlewareOptions, 'normal');
    }

    public function purchaseAddListingPlan()
    {
        $aData = empty($aData) ? $_POST : $aData;

        /**
         * SessionController@clearSessionBeforePaymentProcessing
         */
        do_action('wilcity/wiloke-listing-tools/before-payment-processing', $aData);

        $aStatus = $this->verifyPurchaseAddListingPlan($aData);
        $oRetrieve = RetrieveFactory::retrieve();

        if ($aStatus['status'] == 'error') {
            if (!empty($this->listingID)) {
                $msg = sprintf(
                    __('Re-edit %s listing', 'wiloke-listing-tools'),
                    '<a href="' . get_permalink($this->listingID) . '">' . get_the_title($this->listingID) . '</a>'
                );
            } else {
                $msg = sprintf(
                    __('<a href="%s">Got to Listing Dashboard</a>', 'wiloke-listing-tools'),
                    GetWilokeSubmission::getDashboardUrl('dashboard_page', 'listings')
                );
            }
            return $oRetrieve->error(
                [
                    'msg' => $aStatus['msg'] . ' ' . $msg
                ]
            );
        }

        $this->oReceipt = ReceiptStaticFactory::get('addlisting', [
            'planID'     => $this->planID,
            'userID'     => User::getCurrentUserID(),
            'couponCode' => $aData['couponCode'],
            'aRequested' => $_REQUEST
        ]);

        $this->oReceipt->setupPlan();
        /**
         * If it's onetime payment and 100% discount
         */
        if (empty($this->oReceipt->getTotal()) && GetWilokeSubmission::isNonRecurringPayment()) {
            $this->oReceipt->focusSetGateway('free');
            $this->gateway = 'free';
        }

        $aStatus = $this->createSession();

        if ($aStatus['status'] == 'success') {
            if ($this->gateway === 'free') {
                $aStatus = $aStatus + ['gateway' => 'free'];
            }

            return $oRetrieve->success($aStatus);
        }

        return $oRetrieve->error($aStatus);
    }

    public function purchaseAddListingPlanThroughWooCommerce($orderID)
    {
        $oOrder = new WC_Order($orderID);
        $aItems = $oOrder->get_items();

        foreach ($aItems as $aItem) {
            $productID = $aItem['product_id'];
            $this->planID = PlanRelationshipModel::getPlanIDByProductID($productID);
            // If $planID is not empty, which means it's Add Listing Plan Submission
            if (!empty($this->planID)) {
                Session::setPaymentPlanID($this->planID);
                $aData['gateway'] = 'woocommerce';

                $aStatus = $this->verifyPurchaseAddListingPlan($aData);

                $oRetrieve = new RetrieveController(new AjaxRetrieve());
                if ($aStatus['status'] == 'error') {
                    return $oRetrieve->error(['msg' => $aStatus['msg']]);
                }

                $this->oReceipt = ReceiptStaticFactory::get('addlisting', [
                    'planID'     => $this->planID,
                    'userID'     => User::getCurrentUserID(),
                    'couponCode' => isset($aData['couponCode']) ? $aData['couponCode'] : '',
                    'productID'  => $productID,
                    'orderID'    => $orderID,
                    'aRequested' => $_REQUEST
                ]);
                $this->oReceipt->setupPlan();

                $isNonRecurringPayment = !WooCommerce::isSubscriptionProduct($productID);
                $aResponse = $this->createSession($isNonRecurringPayment);

                $oRetrieve = new RetrieveController(new AjaxRetrieve());
                if ($aResponse['status'] != 'success') {
                    return $oRetrieve->error($aResponse);
                }
            }
        }
    }
}
