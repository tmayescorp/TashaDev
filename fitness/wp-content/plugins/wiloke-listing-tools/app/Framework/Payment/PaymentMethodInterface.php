<?php
namespace WilokeListingTools\Framework\Payment;

use WilokeListingTools\Framework\Payment\Receipt\ReceiptStructureInterface;

interface PaymentMethodInterface
{
    public function proceedPayment(ReceiptStructureInterface $receipt);
    
    public function getBillingType();
}
