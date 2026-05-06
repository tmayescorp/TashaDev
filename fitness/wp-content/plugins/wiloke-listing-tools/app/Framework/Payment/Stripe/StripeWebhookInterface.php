<?php

namespace WilokeListingTools\Framework\Payment\Stripe;

interface StripeWebhookInterface
{
    public function observer();
    public function createHandler();
}
