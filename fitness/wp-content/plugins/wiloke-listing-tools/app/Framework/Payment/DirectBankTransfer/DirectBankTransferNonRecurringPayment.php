<?php
namespace WilokeListingTools\Framework\Payment\DirectBankTransfer;


use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Controllers\Retrieve\RetrieveFactory;
use WilokeListingTools\Controllers\RetrieveController;
use WilokeListingTools\Framework\Payment\CreatedPaymentHook;
use WilokeListingTools\Framework\Payment\PaymentMethodInterface;
use WilokeListingTools\Framework\Payment\Receipt\ReceiptStructureInterface;
use WilokeListingTools\Models\PaymentMetaModel;

class DirectBankTransferNonRecurringPayment extends DirectBankTransferPayment implements PaymentMethodInterface {
	protected $oReceipt;

	public function getBillingType() {
		return wilokeListingToolsRepository()->get('payment:billingTypes', true)->sub('nonrecurring');
	}

    /**
     * @param ReceiptStructureInterface $oReceipt
     *
     * @return array
     */
	public function proceedPayment(ReceiptStructureInterface $oReceipt) {
		$this->oReceipt = $oReceipt;
		$aStatus = $this->setupConfiguration();

        $oRetrieve = RetrieveFactory::retrieve('normal');
        if ($aStatus['status'] == 'error') {
            return $aStatus;
        }

        $this->getPostID();
        $this->generateToken();

        $oAddPaymentHook = new CreatedPaymentHook(new DirectBankTransferNonRecurringCreatedPaymentHook($this));
        $oAddPaymentHook->doSuccess();

        $this->paymentID = PaymentMetaModel::getPaymentIDByToken($this->token);

        return $oRetrieve->success(
            [
                'msg'        => esc_html__('The payment has been created successfully.', 'wiloke-listing-tools'),
                'redirectTo' => $this->thankyouUrl(),
                'paymentID'  => $this->paymentID,
                'gateway'    => $this->gateway
            ]
        );
	}

}
