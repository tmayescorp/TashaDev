<?php
namespace WilokeListingTools\Framework\Payment\WooCommerce;

use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Controllers\RetrieveController;
use WilokeListingTools\Framework\Helpers\Message;
use WilokeListingTools\Framework\Payment\CreatedPaymentHook;
use WilokeListingTools\Framework\Payment\PaymentMethodInterface;
use WilokeListingTools\Framework\Payment\Receipt\ReceiptStructureInterface;
use WilokeListingTools\Framework\Payment\WooCommerce\WooCommerceNonRecurringCreatedPaymentHook;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Models\PaymentMetaModel;

class WooCommerceRecurringPaymentMethod extends WooCommercePayment implements PaymentMethodInterface
{
    public function getBillingType()
    {
        return wilokeListingToolsRepository()->get('payment:billingTypes', true)->sub('recurring');
    }
    
    /**
     * @param ReceiptStructureInterface $oReceipt
     *
     * @return array
     */
    public function proceedPayment(ReceiptStructureInterface $oReceipt)
    {
        $oRetrieve = new RetrieveController(new NormalRetrieve());
        
        $this->oReceipt = $oReceipt;
        $this->setup();
        $this->token     = uniqid($this->gateway.'_');
        $this->orderID   = $this->oReceipt->getOrderID();
        $this->productID = $this->oReceipt->getProductID();
        $this->getPostID();
        
        if (empty($this->orderID)) {
            return $oRetrieve->error([
                'msg' => esc_html__('The Order ID is required', 'wiloke-listing-tools')
            ]);
        }
        
        $oAddPaymentHook = new CreatedPaymentHook(new WooCommerceNonRecurringCreatedPaymentHook($this));
        $oAddPaymentHook->doSuccess();
        
        $this->paymentID = PaymentMetaModel::getPaymentIDByToken($this->token);
        if (empty($this->paymentID)) {
            return $oRetrieve->error([
                'msg' => esc_html__('Could not insert Payment History', 'wiloke-listing-tools')
            ]);
        }
        return $oRetrieve->success(
            [
                'status'      => 'pending',
                'gateway'     => $this->gateway,
                'billingType' => $this->getBillingType(),
                'paymentID'   => $this->paymentID,
                'planID'      => $this->oReceipt->getPlanID(),
                'orderID'     => $this->oReceipt->getOrderID(),
                'productID'   => $this->oReceipt->getProductID()
            ]
        );
    }
}
