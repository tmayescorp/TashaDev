<?php

namespace WilokeListingTools\Register;

trait WilokeSubmissionConfiguration
{
    public $parentSlug = 'wiloke-submission';
    public $postPerPages = 10;
    public $total = 0;
    public $detailSlug = 'detail';
    public $addNewOrder;
    public $editOrder;
    protected $sessionKey = 'wiloke_listgo_save_payment_ID';
    public $aFilterByDate = [
        'any'        => 'Any',
        'this_week'  => 'This Week',
        'this_month' => 'This Month',
        'period'     => 'Period'
    ];
    
    public function isDoNotAllowChangeStatus($status)
    {
        return in_array($status, ['cancelled', 'refunded']);
    }
    
    public function getPartial($file)
    {
        include WILOKE_LISTING_TOOL_DIR.'app/Register/views/'.$this->slug.'/'.$file;
    }
}
