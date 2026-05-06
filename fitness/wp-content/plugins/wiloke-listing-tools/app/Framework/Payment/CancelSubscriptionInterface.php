<?php

namespace WilokeListingTools\Framework\Payment;

interface CancelSubscriptionInterface
{
    public function execute($paymentID);
}
