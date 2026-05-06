<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Controllers\Retrieve\RetrieveFactory;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\Message;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Payment\DirectBankTransfer\DirectBankTransferWebhook;
use WilokeListingTools\Framework\Payment\PayPal\PayPalCancelRecurringPayment;
use WilokeListingTools\Framework\Payment\RefundFactory;
use WilokeListingTools\Framework\Payment\Stripe\StripeCancelRecurringPayment;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;

class ChangePlanStatusController extends Controller
{
    private $isExtendNextBillingDate = false;

    public function __construct()
    {
        add_action('wp_ajax_change_sale_status', [$this, 'changeSaleStatus']);
        add_action('wp_ajax_cancel_subscription', [$this, 'cancelSubscription']);
        add_action(
            'wp_ajax_change_banktransfer_order_status_NonRecurringPayment',
            [$this, 'changeBankTransferPaymentStatus']
        );
        add_action(
            'wp_ajax_change_banktransfer_order_status_RecurringPayment',
            [$this, 'changeBankTransferPaymentStatus']
        );
        add_action('wp_ajax_refund_sale', [$this, 'refundSale']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);

        add_action('wp_ajax_extend_next_billing_date', [$this, 'extendNextBillingDate']);
    }

    public function validatePaymentID($paymentID)
    {
        if (empty($paymentID)) {
            $oRetrieve = new RetrieveController(new AjaxRetrieve());
            $oRetrieve->error(
                [
                    'msg' => esc_html__('The payment ID is required.', 'wiloke-listing-tools')
                ]
            );
        }
    }

    public function isBankTransferGateway($gateway)
    {
        if (empty($gateway) || $gateway != 'banktransfer') {
            $oRetrieve = new RetrieveController(new AjaxRetrieve());
            $oRetrieve->error(
                [
                    'msg' => esc_html__('This payment gateway does not support this feature', 'wiloke-listing-tools')
                ]
            );
        }
    }

    public function isRefundedStatus($status)
    {
        if ($status == 'refunded') {
            $oRetrieve = new RetrieveController(new AjaxRetrieve());
            $oRetrieve->error(
                [
                    'msg' => esc_html__('The payment ID is required.', 'wiloke-listing-tools')
                ]
            );
        }
    }

    public function enqueueScripts()
    {
        wp_enqueue_script('plan-controller', WILOKE_LISTING_TOOL_URL . 'admin/source/js/plan-controller.js', ['jquery'],
            WILOKE_LISTING_TOOL_VERSION, true);
    }

    public function isCancelledOrRefundedStatus($status)
    {
        if (in_array($status, ['cancelled', 'refunded'])) {
            try {
                Message::error(esc_html__('This status is permanent, you can not change that.',
                    'wiloke-listing-tools'));
            }
            catch (\Exception $e) {
            }
        }

        return true;
    }

    public function refundSale()
    {
        $oRetrieve = new RetrieveController(new AjaxRetrieve());

        $this->middleware(['isAdministrator']);

        $currentStatus = PaymentModel::getField('status', $_POST['paymentID']);
        $this->isCancelledOrRefundedStatus($currentStatus);

        $oPayment = RefundFactory::get($_POST['gateway']);

        if (empty($oPayment)) {
            $oRetrieve->error(
                [
                    'msg' => esc_html__('This gateway does not support this feature', 'wiloke-listing-tools')
                ]
            );
        }

        $aStatus = $oPayment->refund($_POST['paymentID']);

        if ($aStatus['status'] == 'error') {
            return $oRetrieve->error($aStatus);
        }

        $oRetrieve->success($aStatus);
    }

    public function cancelSubscription()
    {
        $this->middleware(['isAdministrator']);

        $currentStatus = PaymentModel::getField('status', $_POST['paymentID']);
        $this->isCancelledOrRefundedStatus($currentStatus);

        if (empty($_POST['paymentID'])) {
            wp_send_json_error(
                [
                    'msg' => esc_html__('The payment ID is required', 'wiloke-listing-tools')
                ]
            );
        }

        if (empty($_POST['gateway'])) {
            wp_send_json_error(
                [
                    'msg' => esc_html__('The gateway is required', 'wiloke-listing-tools')
                ]
            );
        }

        switch ($_POST['gateway']) {
            case 'paypal':
                $oCancelPayPal = new PayPalCancelRecurringPayment();
                $aResponse = $oCancelPayPal->execute($_POST['paymentID']);

                if ($aResponse['status'] = 'success') {
                    do_action('wiloke-listing-tools/payment-cancelled', [
                        'paymentID'    => $_POST['paymentID'],
                        'gateway'      => 'paypal',
                        'order_status' => 'cancelled'
                    ]);

                    wp_send_json_success(
                        [
                            'order_status' => 'cancelled'
                        ]
                    );
                } else {
                    wp_send_json_error([
                        'msg' => strip_tags($aResponse['msg'])
                    ]);
                }
                break;
            case 'stripe':
                $oCancelStripe = new StripeCancelRecurringPayment();
                $aResponse = $oCancelStripe->execute($_POST['paymentID']);

                if ($aResponse['status'] = 'success') {
                    do_action('wiloke-listing-tools/payment-cancelled', [
                        'paymentID'    => $_POST['paymentID'],
                        'gateway'      => 'stripe',
                        'order_status' => 'cancelled'
                    ]);

                    wp_send_json_success(
                        [
                            'order_status' => 'cancelled'
                        ]
                    );
                } else {
                    wp_send_json_error([
                        'msg' => strip_tags($aResponse['msg'])
                    ]);
                }
                break;
            default:
                wp_send_json_error(
                    [
                        'msg' => esc_html__('Wrong Payment Gateway', 'wiloke-listing-tools')
                    ]
                );
                break;
        }
    }

    public function changeSaleStatus()
    {
        $this->middleware(['isAdministrator']);

        if (empty($_POST['paymentID'])) {
            wp_send_json_error(
                [
                    'msg' => esc_html__('The payment ID is required', 'wiloke-listing-tools')
                ]
            );
        }

        $currentStatus = PaymentModel::getField('status', $_POST['paymentID']);

        if (in_array($currentStatus, ['cancelled', 'refunded'])) {
            wp_send_json_error(
                [
                    'msg' => esc_html__('This plan has been cancelled. You can change the status anymore.',
                        'wiloke-listing-tools')
                ]
            );
        }

        if ($_POST['newStatus'] == 'cancelled_and_unpublish_listing') {
            $newStatus = 'cancelled';
        } else {
            $newStatus = $_POST['newStatus'];
        }

        PaymentModel::updatePaymentStatus($newStatus, $_POST['paymentID']);

        /*
         * @PaymentMetaController:setNewNextBillingDateGMT 5
         * @UserPlanController:deletePlanIfIsCancelled
         * @UserPlanController:updateUserPlanIfSucceededOrActivate
         * @InvoiceController:update
         */
        do_action(
            'wiloke-listing-tools/changed-payment-status',
            [
                'billingType' => wilokeListingToolsRepository()->get('payment:billingTypes', true)->sub('nonrecurring'),
                'newStatus'   => $_POST['newStatus'],
                'oldStatus'   => $_POST['currentStatus'],
                'paymentID'   => $_POST['paymentID']
            ]
        );

        wp_send_json_success([
            'msg' => [
                'order_status'      => $_POST['newStatus'],
                'next_billing_date' => Time::toAtom(PaymentMetaModel::getNextBillingDateGMT($_POST['paymentID']))
            ]
        ]);
    }

    public function extendNextBillingDate()
    {
        $this->isExtendNextBillingDate = true;

        $this->changeBankTransferPaymentStatus();
    }

    public function changeBankTransferPaymentStatus()
    {
        $oRetrieve = RetrieveFactory::retrieve();

        $status = $this->middleware(
            ['isAdministrator'],
            [
                'isBoolean' => true
            ]
        );

        if (!$status) {
            return $oRetrieve->error([
                'msg' => 'Forbidden'
            ]);
        }

        $this->validatePaymentID($_POST['paymentID']);
        $this->isBankTransferGateway('banktransfer');

        $oWebhook = new DirectBankTransferWebhook();

        if ($this->isExtendNextBillingDate) {
            $oWebhook->focusIncreaseNextBillingDate();
        }

        $aStatus = $oWebhook->handler();
        if ($aStatus['status'] == 'error') {
            return $oRetrieve->error($aStatus);
        }

        $aData = $_POST;
        $aData['nextBillingDateGMT'] = Time::toDateFormat($oWebhook->getNextBillingDateGMT());
        $aData['status'] = 'active';

        do_action(
            'wiloke-listing-tools/app/Controllers/after/ChangePlanStatusController',
            $aData
        );

        return $oRetrieve->success(
            [
                'info' => [
                    'order_status'      => $_POST['newStatus'],
                    'next_billing_date' => Time::toDateFormat($oWebhook->getNextBillingDateGMT())
                ]
            ]
        );
    }
}
