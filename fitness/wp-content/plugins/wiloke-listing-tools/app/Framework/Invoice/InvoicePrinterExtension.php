<?php

namespace WilokeListingTools\Framework\Invoice;

use Konekt\PdfInvoice\InvoicePrinter;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\InvoiceModel;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;
use WilokeThemeOptions;

class InvoicePrinterExtension extends InvoiceAbstract implements InvoiceInterface
{
    protected $aArgs;
    protected $aInvoiceData;
    protected $aAvailableLocates = ['de', 'en', 'es', 'fr', 'it', 'lt', 'nl', 'pl', 'tr'];

    /**
     * @param $aInvoiceData
     * @param $aArgs
     *
     * @return void|InvoiceInterface
     */
    public function setup($aInvoiceData, $aArgs)
    {
        $this->parentSetup($aInvoiceData, $aArgs);

        return $this;
    }

    protected function isValidLocate($locate)
    {
        return in_array($locate, $this->aAvailableLocates);
    }

    public function print()
    {
        $oInvoicePrinter = new InvoicePrinter(
            WilokeThemeOptions::getOptionDetail('invoice_size'),
            $this->aArgs['currency'],
            $this->isValidLocate($this->aArgs['locate']) ? $this->aArgs['locate'] : 'en'
        );
        do_action('wiloke/wiloke-listing-tools/app/Framework/Invoice/InvoicePrinterExtension/configuration',
            $oInvoicePrinter, $this->aInvoiceData);
        $oInvoicePrinter->setColor(GetSettings::getThemeColor(false));

        $dateFormat = get_option('date_format');

        $aInvoiceLogo = WilokeThemeOptions::getOptionDetail('invoice_logo');
        $billingDate = date_i18n($dateFormat, strtotime($this->aInvoiceData['created_at']));
        $billingTime = date_i18n(get_option('time_format'), strtotime($this->aInvoiceData['created_at']));

        if (is_array($aInvoiceLogo) && isset($aInvoiceLogo['id']) && !empty($aInvoiceLogo['id'])) {
            $invoiceLogoUrl = get_attached_file($aInvoiceLogo['id']);
        } else {
            $aSiteLogo = WilokeThemeOptions::getOptionDetail('general_logo');
            if (is_array($aSiteLogo) && isset($aSiteLogo['url']) && !empty($aSiteLogo['url'])) {
                $invoiceLogoUrl = $aSiteLogo['url'];
            }
        }

        /* Header settings */
        if (!empty($invoiceLogoUrl)) {
            $oInvoicePrinter->setLogo($invoiceLogoUrl);
        }

        $oInvoicePrinter->setType(WilokeThemeOptions::getOptionDetail('invoice_type'));

        $invoiceReference = WilokeThemeOptions::getOptionDetail('invoice_reference');

        if (!empty($invoiceReference)) {
            $invoiceReference = str_replace('%invoiceID%', $this->aInvoiceData['ID'], $invoiceReference);
            $oInvoicePrinter->setReference($invoiceReference);
        }

        $oInvoicePrinter->setDate($billingDate);   //Billing Date
        $oInvoicePrinter->setTime($billingTime);   //Billing Time

        # Seller Information
        $aSellerNameInfo = [];
        if ($sellerCompanyName = WilokeThemeOptions::getOptionDetail('invoice_seller_company_name')) {
            $aSellerNameInfo[] = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $sellerCompanyName);
        }
        if ($sellerCompanyAddress = WilokeThemeOptions::getOptionDetail('invoice_seller_company_address')) {
            $aSellerNameInfo[] = $sellerCompanyAddress;
        }
        if ($sellerCompanyCityAndCountry
            = WilokeThemeOptions::getOptionDetail('invoice_seller_company_city_country')
        ) {
            $aSellerNameInfo[] = $sellerCompanyCityAndCountry;
        }
        $oInvoicePrinter->setFrom(apply_filters(
            'wilcity/wiloke-listing-tools/filter/invoince/seller-info',
            $aSellerNameInfo
        ));

        # Purchaser Information
        $paymentID = InvoiceModel::getField('paymentID', $this->aInvoiceData['ID']);
        $userID = PaymentModel::getField('userID', $paymentID);

        $aPurchaserInfo = [];
        $oPurchaserInfo = new \WP_User($userID);

        if (!empty($oPurchaserInfo->billing_company)) {
            $aPurchaserInfo[] = $oPurchaserInfo->billing_company;
        } else {
            $fullName = $oPurchaserInfo->first_name . ' ' . $oPurchaserInfo->last_name;
            if (empty($fullName)) {
                $aPurchaserInfo[] = $oPurchaserInfo->display_name;
            } else {
                $aPurchaserInfo[] = $fullName;
            }
        }

        $address = $oPurchaserInfo->billing_address_1;
        if (empty($oPurchaserInfo->billing_address_1)) {
            $address = User::getAddress($userID);
        }

        if (!empty($address)) {
            $aPurchaserInfo[] = $address;
        }

        if (!empty($oPurchaserInfo->billing_city)) {
            $aPurchaserInfo[] = $oPurchaserInfo->billing_city . ' ' . $oPurchaserInfo->billing_country;
        } else if (!empty($oPurchaserInfo->billing_country)) {
            $aPurchaserInfo[] = $oPurchaserInfo->billing_country;
        }
        $oInvoicePrinter->setTo(apply_filters(
            'wilcity/wiloke-listing-tools/filter/invoince/purchaser-info',
            $aPurchaserInfo,
            $userID
        ));

        $planName = get_the_title($this->aArgs['planID']);
        if (empty($planName)) {
            $planName = PaymentMetaModel::get($this->aInvoiceData['paymentID'], 'planName');
        }
        $planName = utf8_decode($planName);

        $oInvoicePrinter->addItem(
            $planName,
            GetWilokeSubmission::getPackageType(
                PaymentModel::getField('packageType',
                    $this->aInvoiceData['paymentID']
                )
            ),
            1,
            $this->aInvoiceData['tax'],
            $this->aInvoiceData['total'],
            $this->aInvoiceData['discount'],
            $this->aInvoiceData['total']
        );

        $oInvoicePrinter->addTotal(esc_html__('Sub Total', 'wiloke-listing-tools'), $this->aInvoiceData['subTotal']);
        $oInvoicePrinter->addTotal(esc_html__('Discount', 'wiloke-listing-tools'), $this->aInvoiceData['discount']);
        $oInvoicePrinter->addTotal(esc_html__('Total', 'wiloke-listing-tools'), $this->aInvoiceData['total'], true);

        if ($badge = WilokeThemeOptions::getOptionDetail('invoice_badge')) {
            $oInvoicePrinter->addBadge($badge);
        }

        if ($invoiceTitle = WilokeThemeOptions::getOptionDetail('invoice_notice_title')) {
            $oInvoicePrinter->addTitle($invoiceTitle);
        }

        if ($invoiceDesc = WilokeThemeOptions::getOptionDetail('invoice_notice_description')) {
            $oInvoicePrinter->addParagraph($invoiceDesc);
        }

        if (!empty($sellerCompanyName)) {
            $oInvoicePrinter->setFooternote(html_entity_decode($sellerCompanyName));
        }

        $fileName = WilokeThemeOptions::getOptionDetail('invoice_download_file_name');
        if (!empty($fileName)) {
            $fileName = str_replace(
                    ['%invoiceID%', '%invoiceDate%'],
                    [$this->aInvoiceData['ID'], date('m-d-y', strtotime($this->aInvoiceData['created_at']))],
                    $fileName
                ) . '.pdf';
        } else {
            $fileName = 'INV-' . date('m-d-y', strtotime($this->aInvoiceData['created_at'])) . '.pdf';
        }

        if ($this->aArgs['outputType'] == 'F') {
            $userPath
                = FileSystem::getUserFolderDir(PaymentModel::getField('userID', $this->aInvoiceData['paymentID']));
            $fileDir = trailingslashit($userPath) . $fileName;
            try {
                $oInvoicePrinter->render($fileDir, $this->aArgs['outputType']);

                return FileSystem::getUserFolderUrl(PaymentModel::getField('userID',
                        $this->aInvoiceData['paymentID'])) .
                    $fileName;
            }
            catch (\Exception $exception) {
                return '';
            }
        }

        try {
            $oInvoicePrinter->render($fileName, $this->aArgs['outputType']);

            return true;
        }
        catch (\Exception $exception) {
            if (current_user_can('administrator') || (defined('WP_DEBUG') && WP_DEBUG)) {
                var_export($exception->getMessage());
                die;
            }

            return false;
        }
    }
}
