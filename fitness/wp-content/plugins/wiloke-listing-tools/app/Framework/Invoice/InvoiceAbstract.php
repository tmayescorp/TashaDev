<?php

namespace WilokeListingTools\Framework\Invoice;

use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\InvoiceModel;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;

class InvoiceAbstract
{
    protected $aArgs = [];
    protected $aColumnItemNames = [];
    protected $aThemeOptions = [];
    protected $invoiceReference = [];
    protected $aInvoiceData = [];
    protected $aNeedCurrency = ['tax', 'discount', 'tax', 'total', 'subTotal'];
    protected $aNegativePrice = ['discount'];
    protected $aCustomerInfo = [];
    protected $aSellerInfo = [];
    protected $color;
    protected $bgColor;
    protected $bodyColumnWidth;
    
    /**
     * @param $aInvoiceData
     * @param $aArgs
     */
    public function parentSetup($aInvoiceData, $aArgs)
    {
        $this->aInvoiceData = $aInvoiceData;
        $this->aArgs        = $aArgs;
        
        $this->setPackage()->setPackageType()->setQuantities();
        $this->setInvoiceReference();
        $this->setColumnNames();
        $this->setColor();
        $this->setBackgroundColor();
        $this->setCustomerInfo();
        $this->setSellerInfo();
        $this->setBodyColumnWidth();
    }
    
    protected function getOutputType()
    {
        return isset($this->aArgs['outputType']) ? $this->aArgs['outputType'] : 'D';
    }
    
    protected function getFileName()
    {
        $fileName = \WilokeThemeOptions::getOptionDetail('invoice_download_file_name');
        if (!empty($fileName)) {
            $fileName = str_replace(
                            ['%invoiceID%', '%invoiceDate%'],
                            [$this->aInvoiceData['ID'], date('m-d-y', strtotime($this->aInvoiceData['created_at']))],
                            $fileName
                        ).'.pdf';
        } else {
            $fileName = 'INV-'.date('m-d-y', strtotime($this->aInvoiceData['created_at'])).'.pdf';
        }
        
        if ($this->getOutputType() == 'F') {
            $userPath = FileSystem::getUserFolderDir(
                PaymentModel::getField('userID', $this->aInvoiceData['paymentID'])
            );
            $fileName = trailingslashit($userPath).$fileName;
        }
  
        return $fileName;
    }
    
    protected function getSiteTitle()
    {
        return get_option('site_title');
    }
    
    public function getPackageType()
    {
        return GetWilokeSubmission::getPackageType(
            PaymentModel::getField('packageType',
                $this->aInvoiceData['paymentID']
            )
        );
    }
    
    public function setPackage()
    {
        $this->aInvoiceData['package'] = $this->getPackageName();
        
        return $this;
    }
    
    public function setPackageType()
    {
        $this->aInvoiceData['packageType'] = $this->getPackageType();
        
        return $this;
    }
    
    public function setQuantities()
    {
        $this->aInvoiceData['quantities'] = $this->getQuantities();
        
        return $this;
    }
    
    public function getPackageName()
    {
        $planName = get_the_title($this->aArgs['planID']);
        if (empty($planName)) {
            $planName = PaymentMetaModel::get($this->aInvoiceData['paymentID'], 'planName');
        }
        
        return $planName;
    }
    
    public function getQuantities()
    {
        return 1;
    }
    
    public function setBodyColumnWidth()
    {
        $totalItems            = count($this->aColumnItemNames);
        $this->bodyColumnWidth = round(100 / $totalItems, 2).'%';
        
        return $this;
    }
    
    public function getBillingDate()
    {
        $dateFormat = get_option('date_format');
        
        return date_i18n($dateFormat, strtotime($this->aInvoiceData['created_at']));
    }
    
    public function getColumnNames()
    {
        return $this->aColumnItemNames;
    }
    
    /**
     * @return $this
     */
    protected function setColumnNames()
    {
        $this->aColumnItemNames = apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Framework/Invoice/column-names',
            [
                'ID'       => esc_html__('ID', 'wilcity-invoice-addon'),
                'package'  => esc_html__('Product', 'wilcity-invoice-addon'),
                'discount' => esc_html__('Discount', 'wilcity-invoice-addon'),
                'tax'      => esc_html__('Tax', 'wilcity-invoice-addon'),
                'subTotal' => esc_html__('Sub Total', 'wilcity-invoice-addon'),
                'total'    => esc_html__('Total', 'wilcity-invoice-addon'),
            ]
        );
        
        return $this;
    }
    
    public function getInvoiceData($key)
    {
        if (isset($this->aInvoiceData[$key])) {
            if (in_array($key, $this->aNeedCurrency)) {
                return GetWilokeSubmission::renderPrice($this->aInvoiceData[$key], '', $this->isNegativePrice($key));
            }
            
            return $this->aInvoiceData[$key];
        }
        
        return $this;
    }
    
    protected function getLogoUrl()
    {
        $aLogo = \WilokeThemeOptions::getOptionDetail('invoice_logo');
        if (is_array($aLogo) && isset($aLogo['url'])) {
            return $aLogo['url'];
        }
        
        return '';
    }
    
    protected function hasLogo()
    {
        $logo = $this->getLogoUrl();
        
        return !empty($logo);
    }
    
    protected function isNegativePrice($key)
    {
        return in_array($key, $this->aNegativePrice);
    }
    
    protected function setInvoiceReference()
    {
        $this->invoiceReference = str_replace('%invoiceID%', $this->aInvoiceData['ID'],
            \WilokeThemeOptions::getOptionDetail('invoice_reference'));
        
        return $this;
    }
    
    public function getInvoiceReference()
    {
        return $this->invoiceReference;
    }
    
    /**
     * @param $key
     * @param $val
     *
     * @return $this
     */
    public function addCustomerInfo($key, $val)
    {
        $this->aCustomerInfo[$key] = $val;
        
        return $this;
    }
    
    protected function setCustomerInfo()
    {
        $paymentID     = InvoiceModel::getField('paymentID', $this->aInvoiceData['ID']);
        $userID        = PaymentModel::getField('userID', $paymentID);
        $aCustomerInfo = new \WP_User($userID);
        
        if (!empty($aCustomerInfo->billing_company)) {
            $this->aCustomerInfo['billing_company'] = $aCustomerInfo->billing_company;
        } else {
            $fullName = $aCustomerInfo->first_name.' '.$aCustomerInfo->last_name;
            if (empty($fileName)) {
                $this->aCustomerInfo[] = $aCustomerInfo->display_name;
            } else {
                $this->aCustomerInfo[] = $fullName;
            }
        }
        
        $address = $aCustomerInfo->billing_address_1;
        if (empty($aCustomerInfo->billing_address_1)) {
            $address = User::getAddress($userID);
        }
        
        if (!empty($address)) {
            $this->aCustomerInfo[] = $address;
        }
        
        if (!empty($aCustomerInfo->billing_city)) {
            $this->aCustomerInfo[] = $aCustomerInfo->billing_city.' '.$aCustomerInfo->billing_country;
        } else if (!empty($aCustomerInfo->billing_country)) {
            $this->aCustomerInfo[] = $aCustomerInfo->billing_country;
        }
    }
    
    protected function addSellerInfo($key, $val)
    {
        $this->aSellerInfo[$key] = $val;
        
        return $this;
    }
    
    protected function getSellerInfo()
    {
        return $this->aSellerInfo;
    }
    
    protected function setSellerInfo()
    {
        $aKeys = [
            'invoice_seller_company_name',
            'invoice_seller_company_address',
            'invoice_seller_company_city_country'
        ];
        
        foreach ($aKeys as $key) {
            $val = \WilokeThemeOptions::getOptionDetail($key);
            if (!empty($val)) {
                $this->aSellerInfo[$key] = $val;
            }
        }
        
        return $this;
    }
    
    /**
     * @return $this
     */
    public function setColor()
    {
        $this->color = apply_filters(
            'wilcity/filter/wilcity-invoice-addon/theme-color',
            GetSettings::getThemeColor(false)
        );
        
        return $this;
    }
    
    public function setBackgroundColor()
    {
        $this->bgColor = apply_filters(
            'wilcity/filter/wilcity-invoice-addon/bg-color',
            '#fff'
        );
        
        return $this;
    }
    
    public function getCustomerInfo()
    {
        return $this->aCustomerInfo;
    }
}
