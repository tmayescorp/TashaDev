<?php

namespace WilokeListingTools\Framework\Payment\Stripe;

use WilokeListingTools\Framework\Helpers\FileSystem;

class WebhookLog
{
    private $errorLog = 'stripe-error.log';
    private $successLog = 'stripe.log';

    public function __construct()
    {
        add_action(
            'wilcity/wiloke-listing-tools/app/Framework/Payment/Stripe/StripeWebhook/error',
            [$this, 'writeLogError']
        );

        add_action(
            'wilcity/wiloke-listing-tools/app/Framework/Payment/Stripe/StripeWebhook/success',
            [$this, 'writeLogSuccess']
        );
    }

    private function rewriteLogMsg($aResponse)
    {
        $msg = '';
        if (isset($aResponse['class'])) {
            $msg .= 'Class: '.$aResponse['class'];
        }

        if (isset($aResponse['method'])) {
            $msg .= "\r\n".'Method: '.$aResponse['class'];
        }

        if (isset($aResponse['codeStatus'])) {
            $msg .= "\r\n".'codeStatus: '.$aResponse['codeStatus'];
        }

        if (isset($aResponse['msg'])) {
            $msg .= "\r\n".'Message: '.$aResponse['msg'];
        }

        return $msg;
    }

    public function writeLogSuccess($aResponse)
    {
        FileSystem::logPayment($this->successLog, $this->rewriteLogMsg($aResponse));
    }

    public function writeLogError($aResponse)
    {
        FileSystem::logPaymentError($this->errorLog, $this->rewriteLogMsg($aResponse));
    }
}
