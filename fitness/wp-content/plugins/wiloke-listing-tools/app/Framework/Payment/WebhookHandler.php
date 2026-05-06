<?php

namespace WilokeListingTools\Framework\Payment;

class WebhookHandler
{
    private $oWebhook;

    public function __construct(WebhookInterface $webhook)
    {
        $this->oWebhook = $webhook;
    }

    public function run()
    {
        $this->oWebhook->observer();
    }
}
