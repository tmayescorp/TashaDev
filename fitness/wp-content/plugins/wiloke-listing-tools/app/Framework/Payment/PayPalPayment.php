<?php

namespace WilokeListingTools\Framework\Payment;

use PayPal\Api\ShippingAddress;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use WilokeListingTools\Framework\Helpers\DebugStatus;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\Message;
use WilokeListingTools\Framework\Payment\Receipt\ReceiptStructureInterface;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Framework\Upload\Upload;
use WilokeListingTools\Models\PaymentMetaModel;

abstract class PayPalPayment
{
    protected $aConfiguration;
    protected $oApiContext;
    protected $paymentDescription;
    protected $maxFailedPayments;
    protected $gateway = 'paypal';
    protected $paymentID;
    protected $token;
    protected $postID;
    protected $category;
    protected $oPayment;
    protected $oPayer;

    /**
     * @var ReceiptStructureInterface
     */
    protected $oReceipt;

    protected function getCategory()
    {
        $this->category = Session::getSession(wilokeListingToolsRepository()->get('payment:category'), false);

        return $this->category;
    }

    protected function getPostID()
    {
        $this->postID = Session::getSession(wilokeListingToolsRepository()->get('payment:sessionObjectStore'),
            false);

        return $this->postID;
    }

    /**
     * @param $billingType
     *
     * @return string
     */
    protected function thankyouUrl($billingType)
    {
        $aArgs = [
            'billingType' => $billingType,
            'planID'      => $this->oReceipt->getPlanID(),
            'postID'      => $this->getPostID(),
            'category'    => $this->getCategory()
        ];

        $promotionID = Session::getSession('promotionID', true);
        if (!empty($promotionID)) {
            $aArgs['promotionID'] = $promotionID;
        }

        return $this->oReceipt->getThankyouURL($aArgs);
    }

    /**
     * @param $billingType
     *
     * @return string
     */
    protected function cancelUrl($billingType)
    {
        return $this->oReceipt->getCancelUrl([
            'billingType' => $billingType,
            'planID'      => $this->oReceipt->getPlanID(),
            'postID'      => $this->getPostID(),
            'category'    => $this->getCategory()
        ]);
    }

    protected function notifyUrl()
    {
        return add_query_arg(
            [
                'wiloke-submission-listener' => $this->gateway
            ],
            home_url('/')
        );
    }

    public function parseTokenFromApprovalUrl($approvalUrl)
    {
        $aParseData = explode('token=', $approvalUrl);
        $this->token = trim($aParseData[1]);
    }

    public function getToken()
    {
        return $this->token;
    }

    protected function setShippingAddress()
    {
        $aUserInfo = get_userdata($this->oReceipt->getUserID());
        $shippingAddress = new ShippingAddress();
        $hasShippingAddressInfo = false;

        if (isset($aUserInfo['meta']['wiloke_address']) && !empty($aUserInfo['meta']['wiloke_address'])) {
            $shippingAddress->setLine1($aUserInfo['meta']['wiloke_address']);
            $hasShippingAddressInfo = true;
        }

        if (isset($aUserInfo['meta']['wiloke_city']) && !empty($aUserInfo['meta']['wiloke_city'])) {
            $shippingAddress->setCity($aUserInfo['meta']['wiloke_city']);
            $hasShippingAddressInfo = true;
        }

        if (isset($aUserInfo['meta']['wiloke_state']) && !empty($aUserInfo['meta']['wiloke_state'])) {
            $shippingAddress->setCity($aUserInfo['meta']['wiloke_state']);
            $hasShippingAddressInfo = true;
        }

        if (isset($aUserInfo['meta']['wiloke_country']) && !empty($aUserInfo['meta']['wiloke_country'])) {
            $shippingAddress->setPostalCode($aUserInfo['meta']['wiloke_country']);
            $hasShippingAddressInfo = true;
        }

        if (isset($aUserInfo['meta']['wiloke_zipcode']) && !empty($aUserInfo['meta']['wiloke_zipcode'])) {
            $shippingAddress->setPostalCode($aUserInfo['meta']['wiloke_zipcode']);
            $hasShippingAddressInfo = true;
        }

        return $hasShippingAddressInfo ? $shippingAddress : false;
    }

    protected function checkPayPalAPI($mode)
    {
        if ($mode == 'sandbox') {
            $clientIDKey = 'paypal_sandbox_client_id';
            $secretKey = 'paypal_sandbox_secret';
        } else {
            $clientIDKey = 'paypal_live_client_id';
            $secretKey = 'paypal_live_secret';
        }

        $msg = esc_html__('The PayPal has not configured yet!', 'wiloke-listing-tools');

        if (empty($this->aConfiguration[$clientIDKey]) || empty($this->aConfiguration[$secretKey])) {
            Message::error($msg);
        }
    }

    protected function setupConfiguration()
    {
        $this->aConfiguration = GetWilokeSubmission::getAll();
        $msg = esc_html__('The PayPal has not configured yet!', 'wiloke-listing-tools');
        if (!GetWilokeSubmission::isGatewaySupported($this->gateway)) {
            Message::error($msg);
        }

        $isDebug = $this->aConfiguration['toggle_debug'] == 'enable';
        $this->checkPayPalAPI($this->aConfiguration['mode']);

        if (!DebugStatus::status('WP_PAYPAL_FOCUS_LIVE') && $this->aConfiguration['mode'] == 'sandbox') {

            $this->oApiContext = new ApiContext(
                new OAuthTokenCredential(
                    $this->aConfiguration['paypal_sandbox_client_id'],
                    $this->aConfiguration['paypal_sandbox_secret']
                )
            );

            $aPayPalConfiguration = [
                'mode'                    => 'sandbox',
                'http.CURLOPT_SSLVERSION' => 'CURL_SSLVERSION_TLSv2'
            ];

            if ($isDebug) {
                $aPayPalConfiguration = array_merge($aPayPalConfiguration, [
                    'log.LogEnabled' => true,
                    'log.LogLevel'   => 'DEBUG',
                    'log.FileName'   => Upload::getFolderDir('wilcity') .
                        GetWilokeSubmission::getField('paypal_logfilename')
                ]);
            }
            $this->oApiContext->setConfig($aPayPalConfiguration);
        } else {
            $this->oApiContext = new ApiContext(
                new OAuthTokenCredential(
                    trim($this->aConfiguration['paypal_live_client_id']),
                    trim($this->aConfiguration['paypal_live_secret'])
                )
            );

            $aPayPalConfiguration = [
                'mode'                    => 'live',
                'http.CURLOPT_SSLVERSION' => 'CURL_SSLVERSION_TLSv2'
            ];

            $aPayPalConfiguration = array_merge($aPayPalConfiguration, [
                'log.LogEnabled' => true,
                'log.LogLevel'   => 'ERROR',
                'log.FileName'   => Upload::getFolderDir('wilcity') .
                    GetWilokeSubmission::getField('paypal_logfilename')
            ]);

            $this->oApiContext->setConfig($aPayPalConfiguration);
        }

        $this->paymentDescription = $this->aConfiguration['paypal_agreement_text'];
        $this->maxFailedPayments = $this->aConfiguration['paypal_maximum_failed'];
    }

    protected function getConfiguration($field = '')
    {
        if (!empty($field)) {
            return $this->aConfiguration[$field];
        }

        return $this->aConfiguration;
    }

    protected function setup()
    {
        $this->setupConfiguration();
    }

    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        return false;
    }

    public function __isset($name)
    {
        return !empty($this->$name);
    }

    public function getPaymentID()
    {
        return $this->paymentID;
    }

    public function getInvoiceFormat()
    {
//        return $this->aIn;
    }
}
