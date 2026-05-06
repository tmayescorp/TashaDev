<?php

namespace WilokeListingTools\Framework\Payment;


interface SuspendInterface {
    /**
     * @param $paymentID
     *
     * @return mixed
     */
	public function setPaymentID($paymentID);
	public function suspend();
}
