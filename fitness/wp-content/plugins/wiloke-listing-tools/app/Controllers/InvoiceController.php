<?php

namespace WilokeListingTools\Controllers;

use WC_Order;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\Logger;
use WilokeListingTools\Framework\Helpers\WooCommerce;
use WilokeListingTools\Framework\Invoice\InvoiceInterface;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Models\InvoiceMetaModel;
use WilokeListingTools\Models\InvoiceModel;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;
use WilokeListingTools\Models\PlanRelationshipModel;

class InvoiceController extends Controller
{
    public function __construct()
    {
        add_action('wp_ajax_delete_all_invoices', [$this, 'deleteAllInvoices']);
        add_action('wp_ajax_download_invoice', [$this, 'downloadInvoice']);
        add_filter('wilcity/theme-options/configurations', [$this, 'addInvoiceSettingsToThemeOptions']);
        add_filter(
            'wilcity/wiloke-listing-tools/invoice-attachment',
            [$this, 'generateInvoiceDownloadFilePath'],
            10,
            2
        );
        add_action('init', [$this, 'emailDownloadInvoiceAsPdf']);
        add_action('init', [$this, 'newDownloadInvoiceAsPDF'], 1);
        add_action('wilcity_delete_invoice', [$this, 'deleteInvoiceAfterSending'], 10, 3);

        $aBillingTypes = wilokeListingToolsRepository()->get('payment:billingTypes', false);
        foreach ($aBillingTypes as $billingType) {
            add_action(
                'wilcity/wiloke-listing-tools/' . $billingType . '/payment-completed',
                [$this, 'prepareInsertInvoice']
            );
            add_action(
                'wilcity/wiloke-listing-tools/' . $billingType . '/payment-refunded',
                [$this, 'prepareInsertInvoice']
            );
        }

        add_filter('wilcity-login-with-social/after_login_redirect_to', [$this, 'maybeRedirectToDownloadInvoice'], 100);
        add_action('admin_init', [$this, 'adminDownloadInvoice']);
    }

    /*
     * This link will be inserted to customer email, and when clicking on this link, it will redirect this
     * home page and this function will handle it
     *
     * @since 1.2.0
     */
    public function emailDownloadInvoiceAsPdf()
    {
        if (!isset($_GET['action']) || $_GET['action'] != 'download_invoice' || empty($_GET['invoice'])) {
            return false;
        }

        $aParseInvoice = maybe_unserialize(base64_decode($_GET['invoice']));
        $url = esc_url(FileSystem::getWilcityFolderUrl() . implode('/', $aParseInvoice) . '.pdf');

        header("Content-type:application/pdf");
        header("Content-Disposition: attachment;filename=" . trim($aParseInvoice[1]) . '.pdf');
        readfile($url);
        die();
    }

    public function maybeRedirectToDownloadInvoice($redirectTo)
    {
        $_redirectTo = Session::getSession('redirectTo', true);
        if (empty($_redirectTo)) {
            return $redirectTo;
        }

        $oInfo = json_encode($_redirectTo);

        $redirectTo = add_query_arg(
            [
                'action'    => $oInfo->action,
                'invoiceID' => $oInfo->invoiceID,
                'userID'    => $oInfo->userID
            ],
            home_url('/')
        );

        return $redirectTo;
    }

    public function deleteInvoiceAfterSending($userID, $invoiceID, $url)
    {
        $userFolderDir = FileSystem::getUserFolderDir($userID);
        $aParsedUrl = explode('/', $url);
        $fileName = end($aParsedUrl);
        $userFolderDir = rtrim($userFolderDir, '/');
        $aParsedUserFolderDir = explode('/', $userFolderDir);
        $subFolder = end($aParsedUserFolderDir);

        FileSystem::deleteFile($fileName, $subFolder);
    }

    public function adminDownloadInvoice()
    {
        if (
            !isset($_GET['action'])
            || $_GET['action'] != 'admin_download_invoice'
            || !isset($_GET['invoiceID']) || empty($_GET['invoiceID'])
        ) {
            return false;
        }

        $paymentID = InvoiceModel::getField('paymentID', $_GET['invoiceID']);
        $userID = PaymentModel::getField('userID', $paymentID);

        $this->generateInvoice(
            ['userID' => $userID, 'invoiceID' => $_GET['invoiceID']]
        );
        die;
    }

    public function newDownloadInvoiceAsPDF()
    {
        if (
            !isset($_GET['action'])
            || $_GET['action'] != 'new_download_invoice' ||
            empty($_GET['invoiceID']) ||
            empty($_GET['userID'])
        ) {
            return false;
        }

        $invoiceID = absint($_GET['invoiceID']);

        if (!is_user_logged_in()) {
            \WilokeMessage::message(
                [
                    'msg'        => esc_html__(
                        'In order to download invoice, you have to log into your account first',
                        'wiloke-listing-tools'
                    ),
                    'status'     => 'info',
                    'msgIcon'    => 'la la-bullhorn',
                    'hasMsgIcon' => true
                ]
            );

            Session::setSession('redirectTo', json_encode([
                'action'    => 'new_download_invoice',
                'invoiceID' => $invoiceID,
                'userID'    => absint($_GET['userID'])
            ]));

            return false;
        }

        $paymentID = InvoiceModel::getField('paymentID', $invoiceID);
        $userID = PaymentModel::getField('userID', $paymentID);

        if ($userID != get_current_user_id()) {
            wp_die(esc_html__('You do not have permission to access this page', 'wiloke-listing-tools'));
        }

        $url = $this->generateInvoice(
            ['userID' => $_GET['userID'], 'invoiceID' => $invoiceID]
        );

        if ($url) {
            wp_schedule_single_event(time() + 300, 'wilcity_delete_invoice', [$userID, $invoiceID, $url]);
            wp_safe_redirect($url);
            exit;
        } else {
            \WilokeMessage::message([
                'msg'        => esc_html__('Error: We could not generate this invoice', 'wiloke-listing-tools'),
                'status'     => 'danger',
                'msgIcon'    => 'la la-bullhorn',
                'hasMsgIcon' => true
            ]);
        }
    }

    /*
     * Adding Invoice Settings To Theme Options
     *
     * @since 1.2.0
     */
    public function addInvoiceSettingsToThemeOptions($aOptions)
    {
        $aOptions[] = wilokeListingToolsRepository()->get('invoice-themeoptions');

        return $aOptions;
    }

    /*
     * Generate Invoice
     *
     * @since 1.2.0
     * @var $outputType  I => Display on browser, D => Force Download, F => local path save, S => return document as string
     * @var $aData Array | params: invoiceID, userID
     */
    protected function generateInvoice($aData, $outputType = 'D')
    {
        $locale = get_locale();
        $aLocale = explode('_', $locale);

        $userID = isset($aData['userID']) && !empty($aData['userID']) ? $aData['userID'] : '';

        $aInvoice = InvoiceModel::getInvoiceDetails($aData['invoiceID'], $userID, true);

        if (empty($aInvoice)) {
            return false;
        }

        $planID = PaymentModel::getField('planID', $aInvoice['paymentID']);

        $currency = apply_filters(
            'wilcity/wiloke-listing-tools/generateInvoice/currency',
            html_entity_decode(GetWilokeSubmission::getSymbol(
                strtoupper($aInvoice['currency'])),
                ENT_COMPAT,
                'UTF-8'
            )
        );

        $aArgs = wp_parse_args(
            [
                'currency'   => $currency,
                'locate'     => $aLocale[0],
                'planID'     => $planID,
                'outputType' => $outputType
            ]
        );

        $invoiceMachine = apply_filters(
            'wilcity/filter/wiloke-listing-tools/invoice/printer-machine',
            'WilokeListingTools\Framework\Invoice\InvoicePrinterExtension'
        );

        /**
         * @var InvoiceInterface $oInvoice
         */
        $oInvoice = new $invoiceMachine();

        return $oInvoice->setup($aInvoice, $aArgs)->print();
    }

    /*
     * Generating a invoice, and save it to download file path. It's useful for Gmail Attachment
     *
     * @since 1.0
     */
    public function generateInvoiceDownloadFilePath($path, $aInvoiceInfo)
    {
        return $this->generateInvoice($aInvoiceInfo, 'F');
    }

    /*
     * New Invoice (Make your site more prefessional
     *
     * @since 1.2.0
     */
    public function downloadInvoice()
    {
        if (!isset($_POST['invoiceID']) || empty($_POST['invoiceID'])) {
            header('HTTP/1.0 403 Forbidden');
        }

        if (!is_user_logged_in()) {
            header('HTTP/1.0 403 Forbidden');
        }

        $status = $this->generateInvoice($_POST);
        if (!$status) {
            header('HTTP/1.0 403 Forbidden');
        }
    }

    private function insertInvoice($paymentID, $aData)
    {
        $token = '';
        $invoiceID = '';

        if (isset($aData['token']) && !empty($aData['token'])) {
            $token = $aData['token'];
            unset($aData['token']);
            $invoiceID = InvoiceMetaModel::getInvoiceIDByToken($token);
        }

        if (!empty($invoiceID)) {
            if (isset($aData['isRefunded']) && $aData['isRefunded']) {
                if ($aData['subTotal'] > 0) {
                    $aData['subTotal'] = '-' . $aData['subTotal'];
                }

                if ($aData['total'] > 0) {
                    $aData['total'] = '-' . $aData['total'];
                }

                $status = InvoiceModel::update(
                    $invoiceID,
                    $paymentID,
                    $aData
                );

                if (!$status) {
                    FileSystem::logError(
                        'Invoice: We could not update Invoice' . $invoiceID,
                        __CLASS__,
                        __METHOD__
                    );
                } else {
                    FileSystem::logSuccess(
                        'Invoice: Updated Invoice' . $invoiceID,
                        __CLASS__,
                        __METHOD__
                    );
                }
            } else {
                FileSystem::logSuccess(
                    'Invoice: Invoice is already existed' . $invoiceID,
                    __CLASS__,
                    __METHOD__
                );

                return false;
            }

        } else {
            $invoiceID = InvoiceModel::set(
                $paymentID,
                $aData
            );
        }

        if ($invoiceID) {
            FileSystem::logSuccess(
                'Invoice: Inserted Invoice ' . $invoiceID,
                __CLASS__,
                __METHOD__
            );

            if (!empty($token)) {
                InvoiceMetaModel::setInvoiceToken($invoiceID, $token);
            }

            do_action('wilcity/inserted-invoice', [
                'paymentID' => $paymentID,
                'total'     => $aData['total'],
                'subTotal'  => $aData['subTotal'],
                'tax'       => $aData['tax'],
                'currency'  => $aData['currency'],
                'discount'  => $aData['discount'],
                'invoiceID' => $invoiceID
            ]);
        } else {
            FileSystem::logError('We could not insert invoice. Payment ID:' . $paymentID . ' Data: ' .
                json_encode($aData));
        }

        return $invoiceID;
    }

    /**
     * @param $aInfo
     *
     * @return bool
     */
    public function prepareInsertInvoice($aInfo)
    {
        $paymentID = PaymentModel::getField('ID', $aInfo['paymentID']);
//        Logger::writeLog('Invoice for Payment ID: ' . $paymentID);
        if (empty($paymentID)) {
            $errorMsg = sprintf(
                esc_html__('This payment does not exist %s', 'wiloke-listing-tools'),
                $aInfo['paymentID']
            );
            FileSystem::logError($errorMsg, __CLASS__, __METHOD__);

            return false;
        }

        if (!isset($aInfo['aInvoiceFormat']) || empty($aInfo['aInvoiceFormat'])) {
            return false;
        }

        $this->insertInvoice($aInfo['paymentID'], $aInfo['aInvoiceFormat']);
    }

    public function deleteAllInvoices()
    {
        $this->middleware('[isAdministrator]');
        InvoiceModel::deleteAll();

        wp_send_json_success();
    }

    /**
     *
     */
    private function insertWilokeSubmissionInvoiceAfterWooCommerceOrderCreated($aData)
    {
        $oOrder = new WC_Order($aData['orderID']);

        $aItems = $oOrder->get_items();

        $packageType = PaymentModel::getPackageTypeByOrderID($aData['orderID']);
        if ($packageType == 'promotion') {
            $aPaymentIDs = PaymentModel::getPaymentIDsByWooOrderID($aData['orderID']);
            $order = 0;
            foreach ($aItems as $aItem) {
                $paymentID = $aPaymentIDs[$order]['ID'];
                $invoiceID = InvoiceModel::getInvoiceIDByPaymentID($paymentID);

                if (empty($invoiceID)) {
                    InvoiceModel::set(
                        $paymentID,
                        [
                            'currency' => $oOrder->get_currency(),
                            'subTotal' => $aItem['subtotal'],
                            'discount' => floatval($aItem['subtotal']) - floatval($aItem['total']),
                            'tax'      => $aItem['total_tax'],
                            'total'    => $aItem['total']
                        ]
                    );
                }
                $order++;
            }
        } else {
            foreach ($aItems as $aItem) {
                $productID = $aItem['product_id'];
                $planID = PlanRelationshipModel::getPlanIDByProductID($productID);
                //$payment = PlanRelationshipModel::getPlanIDByProductID($productID);

                if (!empty($planID)) {
                    $paymentID = PaymentModel::getPaymentIDByOrderIDAndPlanID($aData['orderID'], $planID);
                    $invoiceID = InvoiceModel::getInvoiceIDByPaymentID($paymentID);

                    if (empty($invoiceID)) {
                        InvoiceModel::set(
                            $paymentID,
                            [
                                'currency' => $oOrder->get_currency(),
                                'subTotal' => $aItem['subtotal'],
                                'discount' => floatval($aItem['subtotal']) - floatval($aItem['total']),
                                'tax'      => $aItem['total_tax'],
                                'total'    => $aItem['total']
                            ]
                        );
                    }
                }
            }
        }
    }

    /**
     * Inserting Invoice after WooCommerce Subscription Order is created
     */
    public function insertNewInvoiceAfterPaymentViaWooCommerceSubscriptionRenewed(\WC_Subscription $that)
    {
        $orderID = $that->get_parent_id();
        $this->insertWilokeSubmissionInvoiceAfterWooCommerceOrderCreated([
            'orderID' => $orderID
        ]);
    }

    /*
     * Inserting Invoice after payment has been completed
     * It's for NonRecurring Payment only
     *
     * @since 1.0
     */
    public function insertNewInvoiceAfterPayViaWooCommerceSucceeded($aData)
    {
        if (WooCommerce::isSubscription($aData['orderID'])) {
            return false;
        }

        $this->insertWilokeSubmissionInvoiceAfterWooCommerceOrderCreated($aData);
    }

    /*
     * For Direct Bank Transfer Only
     */
    public function update($aInfo)
    {
        if (
            $aInfo['newStatus'] != 'active' && $aInfo['newStatus'] != 'succeeded' &&
            $aInfo['gateway'] != 'banktransfer'
        ) {
            return false;
        }

        $aTransactionInfo = PaymentMetaModel::getPaymentInfo($aInfo['paymentID']);

        if (
            GetWilokeSubmission::isNonRecurringPayment($aInfo['billingType']) ||
            (!GetWilokeSubmission::isNonRecurringPayment($aInfo['billingType']) && $aInfo['newStatus'] == 'active' &&
                $aInfo['oldStatus'] == 'processing')
        ) {
            InvoiceModel::set(
                $aInfo['paymentID'],
                [
                    'currency' => $aTransactionInfo['currency'],
                    'subTotal' => $aTransactionInfo['subTotal'],
                    'discount' => $aTransactionInfo['discount'],
                    'tax'      => $aTransactionInfo['tax'],
                    'total'    => $aTransactionInfo['total']
                ]
            );
        }
    }
}
