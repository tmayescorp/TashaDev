<?php

namespace WilokeListingTools\Middleware;

use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Routing\InterfaceMiddleware;

class IsSetupThankyouCancelUrl implements InterfaceMiddleware
{
    public $msg;
    
    public function handle(array $aOptions)
    {
        if (empty(GetWilokeSubmission::getField('thankyou', false))) {
            $this->msg = esc_html__(
                'A thankyou page is required: Wiloke Submission -> Thank you',
                'wiloke-listing-tools'
            );
            
            return false;
        }
        
        if (empty(GetWilokeSubmission::getField('cancel', false))) {
            $this->msg = esc_html__(
                'A cancel page is required: Wiloke Submission -> Thank you',
                'wiloke-listing-tools'
            );
            
            return false;
        }
        
        return true;
    }
}
