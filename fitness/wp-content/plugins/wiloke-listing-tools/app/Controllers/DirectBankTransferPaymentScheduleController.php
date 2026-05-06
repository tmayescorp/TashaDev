<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;

/**
 * Class DirectBankTransferPaymentScheduleController
 * @package WilokeListingTools\Controllers
 */
class DirectBankTransferPaymentScheduleController extends Controller
{
	/**
	 * @var array|mixed
	 */
	private $aConfiguration;
	/**
	 * @var array
	 */
	private $aAlmostBillingDate = ['first', 'second', 'third'];
	/**
	 * @var array
	 */
	private $aOutOfBillingDate = ['first', 'second', 'third'];
	/**
	 * @var string
	 */
	private $almostBillingDateBTHook = 'wilcity/wiloke-listing-tools/app/Controllers/DirectBankTransferPaymentScheduleController/
	handlePaymentCompleted/bank-transfer/almost-billing-date';

	/**
	 * @var string
	 */
	private $outOfBillingDateBTHook = 'wilcity/wiloke-listing-tools/app/Controllers/DirectBankTransferPaymentScheduleController/
	handlePaymentCompleted/bank-transfer/out-of-billing-date';

	/**
	 * @var string
	 */
	private $cancelSupscriptionHook = 'wilcity/wiloke-listing-tools/app/Controllers/DirectBankTransferPaymentScheduleController/cancelSubscription';

	/**
	 * DirectBankTransferPaymentScheduleController constructor.
	 */
	public function __construct()
	{
		add_action('wilcity/wiloke-listing-tools/RecurringPayment/payment-gateway-completed', [$this, 'handleSubscriptionPaymentCompleted']);
	}

	/**
	 * @param $aArgs
	 * @return bool
	 */
	public function handleSubscriptionPaymentCompleted($aArgs)
	{
		if (isset($aArgs['gateway']) && $aArgs['gateway'] !== 'banktransfer') {
			return false;
		}

		$this->aConfiguration = GetWilokeSubmission::getAll();
		$timeInterval = $this->aConfiguration['bank_transfer_time_interval_reminder'];
		$nextBillingDate = $aArgs['nextBillingDateGMT'];
		$aParams = [
			'paymentID' => $aArgs['paymentID']
		];
		$iCount = count($this->aAlmostBillingDate);
		foreach ($this->aAlmostBillingDate as $ordinary) {
			wp_clear_scheduled_hook(
        $this->almostBillingDateBTHook.'/'.$ordinary, 
        [$aParams]
      );

			wp_schedule_single_event(
        $nextBillingDate - $timeInterval * $iCount * 86400, 
        $this->almostBillingDateBTHook . '/' .	$ordinary, 
        [$aParams]
      );
			$iCount = $iCount - 1;
		}

		$iCount = 1;
		foreach ($this->aOutOfBillingDate as $ordinary) {
			wp_clear_scheduled_hook($this->outOfBillingDateBTHook. '/' .
				$ordinary, [$aParams]);
			wp_schedule_single_event($nextBillingDate +
				$timeInterval * $iCount * 86400, $this->outOfBillingDateBTHook. '/' .
				$ordinary, [$aParams]);
			$iCount = $iCount + 1;
		}

		$cancelDate = 86400 * (count($this->aOutOfBillingDate) + 1) * $timeInterval + $nextBillingDate;
		wp_clear_scheduled_hook($this->cancelSupscriptionHook, [$aParams]);
		wp_schedule_single_event($cancelDate, $this->cancelSupscriptionHook, [$aParams]);
	}
}
