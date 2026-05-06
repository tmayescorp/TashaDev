<?php

namespace WilokeListingTools\Controllers;

use Stripe\BillingPortal\Session;
use WilokeListingTools\Controllers\Retrieve\AjaxRetrieve;
use WilokeListingTools\Controllers\Retrieve\NormalRetrieve;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Framework\Payment\Stripe\StripePortal;
use WilokeListingTools\Framework\Payment\Stripe\StripeRecurringPaymentRetrieve;
use WilokeListingTools\Framework\Payment\Stripe\StripeUpdatePlan;
use WilokeListingTools\Framework\Payment\StripePayment;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;
use WilokeListingTools\Models\UserModel;

class StripeController extends Controller
{
    public function __construct()
    {
        $aBillingTypes = wilokeListingToolsRepository()->get('payment:billingTypes', false);
        foreach ($aBillingTypes as $billingType) {
            add_action('wilcity/wiloke-listing-tools/' . $billingType . '/payment-completed',
                [$this, 'setStripeChargeID']);
        }

        /**
         * When purchase first listing plan via Stripe, a Plan will be created on Stripe. This plan will save
         * Period Day and Trial day, Admin may change it under Website -> Listing Plans -> We will update it if
         * there is a changed
         *
         */
        add_action('update_postmeta', [$this, 'updateStripePlan'], 10, 4);
        add_action('wilcity/wiloke-listing-tools/app/updated-wiloke-submission', [$this, 'createStripeCustomerPortal']);
        add_filter(
            'wiloke-listing-tools/filter/app/Controllers/UserController/supportStripePortal',
            [$this, 'checkSupportStripePortal'],
            10,
            2
        );
    }

    public function checkSupportStripePortal($url, $aInfo): ?string
    {
        $subscriptionID = PaymentMetaModel::getSubscriptionID($aInfo['paymentID']);

        if (empty($subscriptionID)) {
            return $url;
        }

        $aCustomerResponse = (new StripeRecurringPaymentRetrieve())->findCustomerID($subscriptionID);

        if ($aCustomerResponse['status'] == 'success') {
            $aStripePortalGeneration = $this->generateStripePortal($aCustomerResponse['id']);
            if ($aStripePortalGeneration['status'] == 'success') {
                return $aStripePortalGeneration['url'];
            }
        }

        return $url;
    }

    public function generateStripePortal($stripeCustomerID)
    {
        $oRetrieve = new RetrieveController(new NormalRetrieve());
        try {
            $oSession = Session::create([
                'customer'   => $stripeCustomerID,
                'return_url' => home_url('/'),
            ]);

            return $oRetrieve->success(['url' => $oSession->url]);
        }
        catch (\Exception $oException) {
            return $oRetrieve->error([
                'msg' => sprintf(esc_html__('Sorry, We could not generate the Stripe Portal URL. Reason: %s',
                    'wiloke-listing-tools'), $oException->getMessage())
            ]);
        }
    }

    private function findCustomerId($userID)
    {
        $stripeCustomerID = UserModel::getStripeID($userID);
        if (empty($stripeCustomerID)) {
            $lastPaymentID = PaymentModel::getLastRecurringPaymentID($userID, 'stripe');
            if (empty($lastPaymentID)) {
                return null;
            }

            $subscriptionID = PaymentMetaModel::getSubscriptionID($lastPaymentID);

            if (empty($subscriptionID)) {
                return null;
            }

            $aCustomerResponse = (new StripeRecurringPaymentRetrieve())->findCustomerID($subscriptionID);

            if ($aCustomerResponse['status'] != 'success') {
                return null;
            }

            return $aCustomerResponse['id'];
        }

        return null;
    }

    private function allowCreatePorttal($privacyUrl, $termsOfServiceUrl): bool
    {
        if (!GetSettings::getOptions('added_stripe_portal')) {
            $aStripePortalInfo = GetSettings::getOptions('stripe_portal_business_info');
            if (empty($aStripePortalInfo)) {
                return true;
            }

            if ($aStripePortalInfo['privacyUrl'] != $privacyUrl ||
                $termsOfServiceUrl != $aStripePortalInfo['termsOfServiceUrl']) {
                return true;
            }
        }

        return false;
    }

    public function createStripeCustomerPortal()
    {
        if (current_user_can('administrator')) {
            $privacyUrl = GetWilokeSubmission::getField('stripe_privacy_policy_url');
            $termsOfServiceUrl = GetWilokeSubmission::getField('stripe_terms_of_service_url');

            if ($privacyUrl && $termsOfServiceUrl) {
                if ($this->allowCreatePorttal($privacyUrl, $termsOfServiceUrl)) {
                    $oStripePortal = new StripePortal();
                    $aResponse = $oStripePortal->setupPortal($privacyUrl, $termsOfServiceUrl);

                    if ($aResponse['status'] == 'success') {
                        SetSettings::setOptions('stripe_portal_business_info', [
                            'privacyUrl'        => $privacyUrl,
                            'termsOfServiceUrl' => $termsOfServiceUrl
                        ]);
                        SetSettings::setOptions('added_stripe_portal', true);
                    }
                }
            }
        }
    }

    public function updateStripePlan($metaID, $postID, $metaKey, $metaValue)
    {
        try {
            if (!General::isAdmin() || !current_user_can('administrator')) {
                return false;
            }

            if (get_post_type($postID) !== 'listing_plan') {
                return false;
            }
            $oStripeUpdate = new StripeUpdatePlan(get_post_field('post_name', $postID));

            $aNewSettings = isset($_POST['wiloke_custom_field']) && is_array($_POST['wiloke_custom_field']) ?
                $_POST['wiloke_custom_field'] : [];

            $aCurrentSettings = GetSettings::getPlanSettings($postID);
            if ($oStripeUpdate->hasPlan()) {
                $aArgs = ['regular_price', 'trial_period', 'regular_period'];
                foreach ($aArgs as $args) {
                    if (empty($aCurrentSettings) ||
                        (
                            isset($aNewSettings['add_listing_plan'][$args]) &&
                            $aNewSettings['add_listing_plan'][$args] != $aCurrentSettings[$args]
                        )
                    ) {
                        switch ($args) {
                            case 'trial_period':
                                $oStripeUpdate->setUpdateTrialDays(sanitize_text_field($aNewSettings['add_listing_plan'][$args]));
                                break;
                        }
                    }
                }

                if ($oStripeUpdate->getArgs()) {
                    $aStatus = $oStripeUpdate->updatePlan();
                }
            }
        }
        catch (\Exception $exception) {

        }
    }

    /**
     * This setting is needed for Refund feature
     */
    public function setStripeChargeID($aInfo)
    {
        if (!GetWilokeSubmission::isNonRecurringPayment($aInfo['billingType'])) {
            return false;
        }

        $gateway = PaymentModel::getField('gateway', $aInfo['paymentID']);

        if ($gateway !== 'stripe') {
            return false;
        }

        $aPaymentMetaInfo = PaymentMetaModel::getPaymentInfo($aInfo['paymentID']);

        if (isset($aPaymentMetaInfo['chargeID'])) {
            PaymentMetaModel::setStripeChargeID($aInfo['paymentID'], $aPaymentMetaInfo['chargeID']);
        }
    }
}
