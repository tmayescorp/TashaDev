<?php
namespace WilokeListingTools\Controllers;


use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Payment\Billable;
use WilokeListingTools\Framework\Payment\Checkout;
use WilokeListingTools\Framework\Payment\DirectBankTransfer\DirectBankTransferNonRecurringPayment;
use WilokeListingTools\Framework\Payment\DirectBankTransfer\DirectBankTransferRecurringPayment;
use WilokeListingTools\Framework\Payment\Receipt;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\PaymentMetaModel;

class DirectBankTransferController extends Controller {
	public $gateway = 'banktransfer';
	public $planID;
	public $userID;

	public function   __construct(){
	}
}

