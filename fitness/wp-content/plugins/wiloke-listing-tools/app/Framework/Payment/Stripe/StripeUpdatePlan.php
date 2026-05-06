<?php

namespace WilokeListingTools\Framework\Payment\Stripe;

use Exception;
use Stripe\Plan;
use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Controllers\RetrieveController;
use WilokeListingTools\Framework\Payment\StripePayment;

final class StripeUpdatePlan extends StripePayment
{
    /**
     * @var String $planID This is post type slug
     */
    private $planID;
    private $oPlan;
    private $aArgs;
    private $oRetrieve;

    public function __construct($planID)
    {
        $this->planID = $planID;
        $oNormalRetrieve = new NormalRetrieve();
        $this->oRetrieve = new RetrieveController($oNormalRetrieve);
    }

    public function setUpdateTrialDays($days)
    {
        if (empty($days)) {
            $this->aArgs['trial_period_days'] = null;
        } else {
            $this->aArgs['trial_period_days'] = absint($days);
        }
        return $this;
    }

    public function getPlan()
    {
        try {
            $aStatus = $this->setApiContext();
            if ($aStatus['status'] == 'error') {
                return $this->oRetrieve->error([
                    'msg' => 'This plan does not exist'
                ]);
            }
            $this->oPlan = Plan::retrieve($this->planID);
            return $this->oRetrieve->success([]);
        } catch (Exception $oException) {
            return $this->oRetrieve->error([
                'msg' => $oException->getMessage()
            ]);
        }
    }

    public function hasPlan(): bool
    {
        $aStatus = $this->getPlan();
        return ($aStatus['status'] == 'success');
    }

    public function getArgs()
    {
        return $this->aArgs;
    }

    public function updatePlan()
    {
        try {
            Plan::update(
                $this->planID,
                $this->aArgs
            );

            return $this->oRetrieve->success([
                'msg' => sprintf('The plan %s has been updated', $this->planID)
            ]);

        } catch (Exception $oException) {
            return $this->oRetrieve->error([
                'msg' => $oException->getMessage()
            ]);
        }
    }
}
