<?php

namespace WilokeListingTools\Framework\Payment\DirectBankTransfer;

use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Controllers\RetrieveController;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Payment\ProceededPaymentHook;
use WilokeListingTools\Framework\Payment\WebhookInterface;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;

class DirectBankTransferWebhook extends DirectBankTransferPayment implements WebhookInterface
{
	private $newStatus;
	protected $oPaymentMeta;
	protected $oPaymentInfo;
	protected $aPaymentMetaInfo;
	protected $focusIncreaseNextBillingDate = false;

	public function observer()
	{
		// TODO: Implement observer() method.
	}

	public function verify()
	{
		$oRetrieve = new RetrieveController(new NormalRetrieve());
		if (
			!isset($_POST['paymentID'])
			|| empty($_POST['paymentID'])
			|| !PaymentModel::getField('ID', $_POST['paymentID'])
		) {
			return $oRetrieve->error(
				[
					'msg' => esc_html__('The payment ID is required.', 'wiloke-listing-tools')
				]
			);
		}

		if (!isset($_POST['newStatus']) || empty($_POST['newStatus'])) {
			return $oRetrieve->error(
				[
					'msg' => esc_html__('The new status is required.', 'wiloke-listing-tools')
				]
			);
		}

		$this->newStatus = sanitize_text_field(trim($_POST['newStatus']));
		$this->paymentID = sanitize_text_field(trim($_POST['paymentID']));

		return $oRetrieve->success([]);
	}

	public function focusIncreaseNextBillingDate()
	{
		$this->focusIncreaseNextBillingDate = true;
	}

	public function getNextBillingDateGMT()
	{
		return $this->nextBillingDateGMT;
	}

	public function handler()
	{
		$oRetrieve = new RetrieveController(new NormalRetrieve());
		$aStatus   = $this->verify();
		if ($aStatus['status'] == 'error') {
			return $aStatus;
		}
		$this->billingType      = PaymentModel::getField('billingType', $this->paymentID);
		$this->aPaymentMetaInfo = PaymentMetaModel::getPaymentInfo($this->paymentID);
		$this->oPaymentInfo     = PaymentModel::getPaymentInfo($this->paymentID);

		if ($this->newStatus == 'active' && !GetWilokeSubmission::isNonRecurringPayment($this->billingType)) {
			$nextBillingDate   = PaymentMetaModel::getNextBillingDateGMT($this->paymentID);
			$regularPeriodDays = $this->aPaymentMetaInfo['regularPeriodDays'].' days';
			if (empty($nextBillingDate)) {
				$trialPeriods = $this->aPaymentMetaInfo['trialPeriodDays'];
				if (!empty($trialPeriods)) {
					$this->nextBillingDateGMT = Time::timestampUTCNow('+ '.$trialPeriods.' days');
					$this->isTrial            = true;
				} else {
					$this->nextBillingDateGMT = Time::timestampUTCNow('+ '.$regularPeriodDays);
				}
			} else {
				if ($this->focusIncreaseNextBillingDate) {
					if ($nextBillingDate > current_time('timestamp', true)) {
						$this->nextBillingDateGMT = Time::timestampUTC($nextBillingDate, $regularPeriodDays);
					} else {
						$this->nextBillingDateGMT = Time::timestampUTCNow('+ '.$regularPeriodDays);
					}
				} else {
					$this->nextBillingDateGMT = Time::timestampUTCNow('+ '.$regularPeriodDays);
				}
			}
		}

		if (GetWilokeSubmission::isNonRecurringPayment($this->billingType)) {
			$oProceedWebhook       = new ProceededPaymentHook(
				new DirectBankTransferProceededNonRecurringPaymentHook($this)
			);
			$oPrePareInvoiceFormat = new DirectBankTransferNonRecurringPrepareInvoiceFormat($this);
		} else {
			$oProceedWebhook       = new ProceededPaymentHook(
				new DirectBankTransferProceededRecurringPaymentHook($this)
			);
			$oPrePareInvoiceFormat = new DirectBankTransferNonRecurringPrepareInvoiceFormat($this);
		}
		$this->token = PaymentMetaModel::getPaymentTokenByPaymentID($this->paymentID);
		switch ($this->newStatus) {
			case 'succeeded':
				$this->aInvoiceFormat = $oPrePareInvoiceFormat->prepareInvoiceParam()->getParams();
				$oProceedWebhook->doCompleted();
				break;
			case 'active':
				$this->generateSubscriptionID();
				$this->aInvoiceFormat = $oPrePareInvoiceFormat->prepareInvoiceParam()->getParams();
				$oProceedWebhook->doCompleted();
				break;
			case 'pending':
				break;
			case 'cancelled':
				$oProceedWebhook->doCancelled();
				break;
			case 'refunded':
				$this->isRefunded     = true;
				$this->aInvoiceFormat = $oPrePareInvoiceFormat->prepareInvoiceParam()->getParams();

				$oProceedWebhook->doRefunded();
				break;
		}

		if ($this->isTrial) {
			PaymentMetaModel::removeTrialPeriods($this->paymentID);
		}

		return $oRetrieve->success([
			'paymentID' => $this->paymentID,
			'status'    => $this->newStatus
		]);
	}
}
