<?php

namespace WilokeListingTools\Middleware;

use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Routing\InterfaceMiddleware;

class IsPlanExists implements InterfaceMiddleware
{
    public $msg;

    public function handle(array $aOptions)
    {
        if (isset($aOptions['planType']) && !empty($aOptions['planType'])) {
            $planKey = $aOptions['planType'];
        } else if (isset($aOptions['listingType']) && !empty($aOptions['listingType'])) {
            $planKey = $aOptions['listingType'] . '_plans';
        } else {
            $planKey = '';
        }

        if (empty($planKey) || empty($aOptions['planID'])) {
            $this->msg = esc_html__('ERROR: A plan is required', 'wiloke-listing-tools');
            return false;
        }

        if (get_post_field('post_status', $aOptions['planID']) == 'publish' && isset($aOptions['listingID'])) {
            if (in_array(get_post_status($aOptions['listingID']), ['expired', 'editing', 'publish'])) {
                return true;
            }
        }

        $this->msg = esc_html__('ERROR: This plan does not exist.', 'wiloke-listing-tools');
        $aCustomerPlans = GetWilokeSubmission::getField($planKey, false, true);

        if (empty($aCustomerPlans)) {
            return false;
        }

        $aCustomerPlans = explode(',', $aCustomerPlans);
        foreach ($aCustomerPlans as $supportedPlanId) {
            $originalPlanId = GetWilokeSubmission::getOriginalPlanId($supportedPlanId);
            if ($originalPlanId == $aOptions['planID']) {
                if (get_post_field('post_status', $originalPlanId) != 'publish') {
                    return true;
                }
            }
        }


        return true;
    }
}
