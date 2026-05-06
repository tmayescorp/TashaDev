<?php

namespace WilokeListingTools\Framework\Payment\Stripe;

use Stripe\TaxRate;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Payment\StripePayment;

class StripeTax extends StripePayment
{
    protected $oTaxRetrieve;

    public function __construct()
    {
        $this->setApiContext();
    }

    protected function getTaxRateKey()
    {
        return GetSettings::getOptions('stripe_tax_rate');
    }

    public function retrieveTaxRate()
    {
        if ($this->oTaxRetrieve instanceof \Stripe\TaxRate) {
            return $this->oTaxRetrieve;
        }

        if ($taxRate = $this->getTaxRateKey()) {
            try {
                $this->oTaxRetrieve = \Stripe\TaxRate::retrieve(
                    $taxRate
                );

                return $this->oTaxRetrieve;
            } catch (\Exception $oEx) {
                return false;
            }
        }

        return false;
    }

    protected function retrieveTaxRateInfo($info)
    {
        $this->retrieveTaxRate();

        if (empty($this->oTaxRetrieve)) {
            false;
        }

        return isset($this->oTaxRetrieve->{$info}) ? $this->oTaxRetrieve->{$info} : false;
    }

    public function getCreatedTaxPercentage()
    {
        $this->retrieveTaxRate();

        return $this->retrieveTaxRateInfo('percentage');
    }

    public function getCreatedDisplayName()
    {
        $this->retrieveTaxRate();

        return $this->retrieveTaxRateInfo('display_name');
    }

    public function getCreatedID()
    {
        $this->retrieveTaxRate();

        return $this->retrieveTaxRateInfo('id');
    }

    public function getTaxRateID()
    {
        return $this->createTaxRate();
    }

    protected function createTaxRate()
    {
        try {
            if ($this->retrieveTaxRate()) {
                // because stripe convert 1 to 1.0
                $currentRate = GetWilokeSubmission::getTaxRate();
                if (!is_integer($currentRate)) {
                    $currentRate = round($currentRate, 2);
                }

                if ($this->getCreatedTaxPercentage() == $currentRate) {
                    return $this->getCreatedID();
                }
            }

            $taxTitle = GetWilokeSubmission::getField('taxTitle');
            $taxTitle = empty($taxTitle) ? 'Tax Rate' : $taxTitle;

            $oTaxRate = \Stripe\TaxRate::create([
                'display_name' => $taxTitle,
                'percentage'   => GetWilokeSubmission::getTaxRate(),
                'inclusive'    => false,
            ]);
            SetSettings::setOptions('stripe_tax_rate', $oTaxRate->id);

            return $oTaxRate->id;
        } catch (\Exception $oE) {
            return false;
        }
    }
}
