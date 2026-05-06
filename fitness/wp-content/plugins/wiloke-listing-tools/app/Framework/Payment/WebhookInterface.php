<?php

namespace WilokeListingTools\Framework\Payment;

interface WebhookInterface
{
    /**
     * This function will watch web hook, catch the raw data and parse it
     *
     * @return mixed
     */
    public function observer();

    /**
     * Verifying a webhook event
     *
     * @return mixed
     */
    public function verify();

    /*
     * After passing verify step, We will parse webhook status and depends on the status, We will run the following
     * function
     */
    public function handler();
}
