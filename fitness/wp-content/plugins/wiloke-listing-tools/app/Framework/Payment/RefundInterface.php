<?php

namespace WilokeListingTools\Framework\Payment;

interface RefundInterface
{
    public function getChargedID();
    public function refund($paymentID);
}
