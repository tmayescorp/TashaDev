<?php


namespace WilokeListingTools\Register\RegisterMenu;


use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\SemanticUi;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;
use WilokeListingTools\Register\WilokeSubmissionConfiguration;

class RegisterAddNewPayment
{
    use WilokeSubmissionConfiguration;

    public  $slug = 'wilcity-addnew-payment';
    public  $paymentID;
    public  $aPaymentDetails;
    private $aCacheSettings
                  = [
            'planID'      => '',
            'userID'      => '',
            'billingType' => '',
            'gateway'     => '',
            'wooOrderID'  => ''
        ];

    public  $errMsg;
    private $aRequired
        = [
            'planID',
            'userID',
            'billingType',
            'gateway'
        ];

    public function __construct()
    {
        add_action('admin_init', [$this, 'createNewPayment']);
        add_action('admin_menu', [$this, 'register'], 20);
    }

    public function createNewPayment()
    {
        if (!isset($_POST['action']) || $_POST['action'] != 'add_new_payment' || !current_user_can('administrator')) {
            return false;
        }

        foreach ($this->aRequired as $item) {
            if (empty($_POST[$item])) {
                $this->errMsg = sprintf(__('The field %s is required', 'wiloke-listing-tools'), $item);
                return false;
            }
        }

        if (!GetWilokeSubmission::isPlanExists($_POST['planID'])) {
            $this->errMsg = __('Invalid plan id', 'wiloke-listing-tools');
            return false;
        }

        if ($_POST['gateway'] === 'woocommerce') {
            if ((empty($_POST['wooOrderID']))) {
                $this->errMsg = __('The order id is required', 'wiloke-listing-tools');
                return false;
            }

            if ($paymentId = PaymentModel::getPaymentIDsByWooOrderID($_POST['wooOrderID'], true)) {
                $this->errMsg
                    = sprintf(__('This order has been assigned to %s already. You need to remove the payment id to create a new payment',
                    'wiloke-listing-tools'), $paymentId);
                return false;
            }
        }

        $aPaymentData = [
            'userID'      => $_POST['userID'],
            'planID'      => $_POST['planID'],
            'packageType' => 'listing_plan',
            'gateway'     => $_POST['gateway'],
            'status'      => GetWilokeSubmission::isNonRecurringPayment($_POST['billingType']) ? 'succeeded' : 'active',
            'billingType' => $_POST['billingType'],
        ];

        setcookie('cache_payment_info', json_encode($aPaymentData));

        $id = PaymentModel::insertPaymentHistory($aPaymentData, $_POST['wooOrderID']);
        if (empty($id)) {
            $this->errMsg = __('We could not insert the payment', 'wiloke-listing-tools');
            return false;
        }

        $aPaymentData['paymentID'] = $id;
        $aPaymentData['category'] = 'addlisting';
        $aPaymentData['postType'] = $_POST['listingType'];

        $aPlanSettings = GetSettings::getPlanSettings($_POST['planID']);

        $aPaymentMeta = [
            'paymentID'         => $id,
            'category'          => 'addlisting',
            'postType'          => $_POST['postType'],
            'regularPeriodDays' => $aPlanSettings['regular_period'],
            'trialPeriodDays' => $aPlanSettings['trial_period']
        ];
        PaymentMetaModel::setPaymentInfo($aPaymentData['paymentID'], $aPaymentMeta);
        do_action(
            'wilcity/wiloke-listing-tools/' . $_POST['billingType'] . '/payment-completed',
            $aPaymentData
        );

        wp_safe_redirect(add_query_arg(['page' => $this->detailSlug, 'paymentID' => $id], admin_url('admin.php')));
        exit;
    }

    public function register()
    {
        add_submenu_page(
            $this->parentSlug,
            esc_html__('Add New Payment', 'wiloke'),
            esc_html__('Add New Payment', 'wiloke'),
            'administrator',
            $this->slug,
            [$this, 'addNewPaymentArea']
        );
    }

    public function addNewPaymentArea()
    {
        ?>
        <div id="wiloke-submission-wrapper" class="wrap">
            <?php
            if (!empty($this->errMsg)) {
                SemanticUi::renderDesc(['desc' => $this->errMsg, 'desc_status' => 'red']);
            }
            ?>
            <?php if (!isset($_GET['listing_type']) || empty($_GET['listing_type'])): ?>
                <form action="<?php echo esc_url(admin_url('admin.php')); ?>" method="GET"
                      class="form ui">
                    <?php
                    SemanticUi::renderHiddenField([
                        'heading' => '',
                        'name'    => 'page',
                        'value'   => $this->slug
                    ]);

                    SemanticUi::renderSelectField([
                        'heading' => esc_html__('Select Listing Type', 'wiloke-listing-tools'),
                        'name'    => 'listing_type',
                        'options' => General::getPostTypeOptions(false),
                        'value'   => ''
                    ]);

                    SemanticUi::renderButton([
                        'name'  => esc_html__('Continue', 'wiloke-listing-tools'),
                        'class' => 'green',
                        'id'    => 'wilcity-select-listing-type'
                    ]);
                    ?>
                </form>
            <?php else :
                if (isset($_COOKIE['cache_payment_info'])) {
                    $aCacheSettings = json_decode(stripslashes($_COOKIE['cache_payment_info']), true);

                    if (is_array($aCacheSettings)) {
                        $this->aCacheSettings = wp_parse_args($aCacheSettings, $this->aCacheSettings);
                    }
                }
                ?>
                <form id="wiloke-submission-change-customer-order" class="form ui" action="" method="POST">
                    <h3 class="ui dividing header"><?php esc_html_e('Order Information', 'wiloke'); ?></h3>
                    <div class="two fields">
                        <?php
                        SemanticUi::renderTextField(
                            [
                                'id'          => 'listingType',
                                'name'        => 'listingType',
                                'heading'     => esc_html__('Listing Type', 'wiloke'),
                                'value'       => $_GET['listing_type'],
                                'is_readonly' => true
                            ]
                        );

                        SemanticUi::renderSelectField(
                            [
                                'name'    => 'planID',
                                'heading' => esc_html__('Select Plan', 'wiloke-listing-tools'),
                                'value'   => $this->aCacheSettings['planID'],
                                'options' => GetWilokeSubmission::getPlanOptions($_GET['listing_type'])
                            ]
                        );
                        ?>
                    </div>
                    <div class="two fields">
                        <?php
                        SemanticUi::renderHiddenField(
                            [
                                'id'          => 'action',
                                'name'        => 'action',
                                'value'       => 'add_new_payment',
                                'is_readonly' => true
                            ]
                        );

                        SemanticUi::renderSelectTwoField(
                            [
                                'id'       => 'userID',
                                'name'     => 'userID',
                                'heading'  => esc_html__('Customer', 'wiloke'),
                                'desc'     => esc_html__('Search by username', 'wiloke-listing-tools'),
                                'classes'  => 'wiloke-select2',
                                'value'    => $this->aCacheSettings['userID'],
                                'dataAtts' => [
                                    'data-action'    => 'wilcity_admin_search_user',
                                    'data-roles'     => 'contributor,seller,administrator',
                                    'data-fieldtype' => 'select2'
                                ],
                                'options'  => []
                            ]
                        );

                        SemanticUi::renderSelectField(
                            [
                                'id'      => 'billingType',
                                'name'    => 'billingType',
                                'heading' => esc_html__('Billing Type', 'wiloke'),
                                'value'   => !empty($this->aCacheSettings['billingType']) ?
                                    GetWilokeSubmission::getBillingType() : $this->aCacheSettings['userID'],
                                'options' => [
                                    'NonRecurringPayment' => 'Non-Recurring Payment (One Time Payment)',
                                    'RecurringPayment'    => 'Recurring Payment (Subscription)'
                                ]
                            ]
                        );
                        ?>
                    </div>
                    <div class="two fields">
                        <?php
                        SemanticUi::renderSelectField(
                            [
                                'id'      => 'gateway',
                                'name'    => 'gateway',
                                'heading' => esc_html__('Gateway', 'wiloke'),
                                'options' => [
                                    ''             => '---',
                                    'woocommerce'  => 'WooCommerce',
                                    'banktransfer' => 'Bank Transfer'
                                ],
                                'value'   => $this->aCacheSettings['gateway']
                            ]
                        );

                        SemanticUi::renderSelectTwoField(
                            [
                                'id'          => 'wooOrderID',
                                'name'        => 'wooOrderID',
                                'heading'     => esc_html__('Order Id', 'wiloke'),
                                'desc'        => esc_html__('Assign an order id to this payment. Note that you should use this feature if the gateway is woocommerce. Search by Order ID',
                                    'wiloke-listing-tools'),
                                'desc_status' => 'info',
                                'classes'     => 'wiloke-select2',
                                'value'       => $this->aCacheSettings['wooOrderID'],
                                'dataAtts'    => [
                                    'data-action'    => 'wilcity_search_order_id',
                                    'data-status'    => 'completed,processing',
                                    'data-parent'    => 0,
                                    'data-fieldtype' => 'select2'
                                ],
                                'options'     => []
                            ]
                        );
                        ?>
                    </div>

                    <?php
                    SemanticUi::renderButton([
                        'name'  => esc_html__('Submit', 'wiloke-listing-tools'),
                        'class' => 'green',
                        'id'    => 'wilcity-submit-payment'
                    ]);
                    ?>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }
}
