<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Payment\FreePlan\FreePlanWebhook;
use WilokeListingTools\Framework\Payment\PayPal\PayPalWebhook;
use WilokeListingTools\Framework\Payment\Stripe\StripeWebhook;
use WilokeListingTools\Framework\Payment\DirectBankTransfer\DirectBankTransferWebhook;
use WilokeListingTools\Framework\Payment\Stripe\WebhookLog;
use WilokeListingTools\Framework\Payment\WebhookHandler;
use WilokeListingTools\Framework\Payment\WooCommerce\WooCommerceWebhook;
use WilokeListingTools\Framework\Routing\Controller;

class WebhookController extends Controller
{
    public function __construct()
    {
	    add_action('init', [$this, 'stripeWebhook']);
	    add_action('init', [$this, 'paypalWebhook']);
	    add_action('init', [$this, 'woocommerceWebhook']);
	    add_action('init', [$this, 'freePlanWebhook']);
    }

    public function freePlanWebhook()
    {
        $oEvent = new FreePlanWebhook();
        (new WebhookHandler($oEvent))->run();
    }

    public function woocommerceWebhook()
    {
        $oEvent = new WooCommerceWebhook();
        (new WebhookHandler($oEvent))->run();
    }

    public function paypalWebhook()
    {
        $oEvent = new PayPalWebhook();
        (new WebhookHandler($oEvent))->run();
    }

    public function stripeWebhook()
    {
        new WebhookLog();
        $oEvent = new StripeWebhook();
        (new WebhookHandler($oEvent))->run();
    }
}
