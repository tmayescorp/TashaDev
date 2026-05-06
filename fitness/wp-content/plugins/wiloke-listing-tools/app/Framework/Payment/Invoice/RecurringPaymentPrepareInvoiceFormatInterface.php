<?php

namespace WilokeListingTools\Framework\Payment\Invoice;

interface RecurringPaymentPrepareInvoiceFormatInterface
{
    /**
     * It's a subscription ID like Stripe subscription or PayPal subscription id
     *
     * @return $this
     */
    public function setToken();
}
