<?php
namespace WilokeListingTools\Framework\Payment\WooCommerce;

use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Controllers\RetrieveController;
use WilokeListingTools\Framework\Helpers\WooCommerce;
use WilokeListingTools\Models\PaymentModel;

class WooCommerceCancelRecurringPayment extends WooCommercePayment
{
    protected $orderID;
    protected $setNote;
    public $msg;
    /**
     * @var RetrieveController
     */
    protected $oRetrieve;

    private function cancelSubscription()
    {
        $this->orderID  = absint($this->orderID);
        $subscriptionID = WooCommerce::getLatestSubscriptionIDByOrderID($this->orderID, 'wc-active');
        if (empty($subscriptionID)) {
            $this->msg = esc_html__('We could not find any Subscription ID of this payment', 'wiloke-listing-tools');

            return false;
        }

        $oSubscription = wcs_get_subscription($subscriptionID);
        try{
            $oSubscription->update_status('pending-cancel');
            $this->msg = esc_html__('The payment has been cancelled successfully', 'wiloke-listing-tools');
            return true;
        }catch (\Exception $oExeption) {
            $this->msg = esc_html__('We could not cancel this subscription', 'wiloke-listing-tools');
            return false;
        }
    }

    public function execute($paymentID)
    {
        $oRetrieve     = new RetrieveController(new NormalRetrieve());
        $this->orderID = PaymentModel::getField('wooOrderID', $paymentID);
        if (empty($this->orderID)) {
            return $oRetrieve->error(
                [
                    'msg' => esc_html__('We could not find WooCommerce Order of this payment', 'wiloke-listing-tools')
                ]
            );
        }

        $status = $this->cancelSubscription();

        if ($status) {
            return $oRetrieve->success(['msg' => $this->msg]);
        }

        return $oRetrieve->error(['msg' => $this->msg]);
    }
}
