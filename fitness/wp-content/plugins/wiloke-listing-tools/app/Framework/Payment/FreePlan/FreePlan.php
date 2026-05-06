<?php
namespace WilokeListingTools\Framework\Payment\FreePlan;

use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Controllers\RetrieveController;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Payment\CreatedPaymentHook;
use WilokeListingTools\Framework\Payment\PaymentMethodInterface;
use WilokeListingTools\Framework\Payment\Receipt\ReceiptStructureInterface;
use WilokeListingTools\Framework\Store\Session;
use WilokeListingTools\Models\PaymentMetaModel;

class FreePlan implements PaymentMethodInterface
{
    public $gateway = 'free';
    /**
     * @var ReceiptStructureInterface
     */
    protected $oReceipt;
    protected $aConfiguration;
    public $aBankAccounts = [];
    protected $paymentID;
    protected $token;
    protected $postID;
    protected $category;
    protected $subscriptionID;
    protected $isRefunded;
    protected $nextBillingDateGMT;
    protected $newPaymentStatus;
    protected $isTrial = false;
    
    public function __construct()
    {
    }
    
    public function getBillingType()
    {
        return wilokeListingToolsRepository()->get('payment:billingTypes', true)->sub('nonrecurring');
    }
    
    protected function getPostID()
    {
        $this->postID = Session::getPaymentObjectID(false);
        
        return $this->postID;
    }
    
    protected function generateToken()
    {
        $this->token = md5($this->gateway.$this->getCategory().time());
    }
    
    public function getCategory()
    {
        $this->category = Session::getPaymentCategory(false);
        
        return $this->category;
    }
    
    /**
     * @param $billingType
     *
     * @return string
     */
    protected function thankyouUrl()
    {
        return $this->oReceipt->getThankyouURL([
            [
                'planID'                     => $this->oReceipt->getPlanID(),
                'postID'                     => $this->postID,
                'newStatus'                  => $this->newPaymentStatus,
                'paymentID'                  => $this->paymentID,
                'wiloke-submission-listener' => $this->gateway,
                'category'                   => $this->category,
                //                'gateway'                    => 'free'
            ]
        ]);
    }
    
    /**
     * @param ReceiptStructureInterface $oReceipt
     *
     * @return mixed
     */
    public function proceedPayment(ReceiptStructureInterface $oReceipt)
    {
        $oRetrieve = new RetrieveController(new NormalRetrieve());
        
        $this->oReceipt = $oReceipt;
        $this->getPostID();
        $this->generateToken();
        $this->getCategory();
        
        $oAddPaymentHook = new CreatedPaymentHook(new FreePlanCreatedPaymentHook($this));
        $oAddPaymentHook->doSuccess();
        
        $this->paymentID = PaymentMetaModel::getPaymentIDByToken($this->token);
        
        if (empty($this->paymentID)) {
            return $oRetrieve->error(
                [
                    'msg' => esc_html__('We could not insert a Payment ID', 'wiloke-listing-tools'),
                ]
            );
        }
        
        $this->newPaymentStatus = 'succeeded';
        
        return $oRetrieve->success(
            [
                'redirectTo' => $this->thankyouUrl()
            ]
        );
    }
    
    public function __get($name)
    {
        return $this->$name;
    }
    
    public function __isset($name)
    {
        return !empty($this->$name);
    }
}
