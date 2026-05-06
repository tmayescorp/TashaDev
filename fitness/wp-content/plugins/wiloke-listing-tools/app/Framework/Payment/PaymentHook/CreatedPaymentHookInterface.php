<?php

namespace WilokeListingTools\Framework\Payment\PaymentHook;

interface CreatedPaymentHookInterface
{
    public function success();
    public function error();
}
