<?php
namespace WilokeListingTools\Models;

use WilokeListingTools\AlterTable\AlterTablePaymentMeta;
use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;

class PaymentMetaModel
{
    public static $tblName;

    public static function addPrefixToMetaName($metaKey)
    {
        if (strpos($metaKey, wilokeListingToolsRepository()->get('general:metaboxPrefix')) !== false) {
            return $metaKey;
        }

        return wilokeListingToolsRepository()->get('general:metaboxPrefix').$metaKey;
    }

    public static function setStripeChargeID($paymentID, $chargeID)
    {
        self::set($paymentID, wilokeListingToolsRepository()->get('payment:stripeChargedID'), $chargeID);
    }

    public static function getStripeChargeID($paymentID)
    {
        return self::get($paymentID, wilokeListingToolsRepository()->get('payment:stripeChargedID'));
    }

    public static function removeTrialPeriods($paymentID)
    {
        $aPaymentMeta                    = self::getPaymentInfo($paymentID);
        $aPaymentMeta['trialPeriodDays'] = 0;

        return self::patch(
            $paymentID,
            self::addPrefixToMetaName(wilokeListingToolsRepository()->get('payment:paymentInfo'))
            , $aPaymentMeta
        );
    }

    public static function setPromotionID($paymentID, $promotionID)
    {
        if (self::getPaymentInfo($paymentID)) {
            return self::set($paymentID, wilokeListingToolsRepository()->get('payment:promotionPaymentRelationship'),
                $promotionID);
        } else {
            return self::updatePaymentInfo($paymentID, $promotionID);
        }
    }

    public static function getPromotionID($paymentID)
    {
        return self::get($paymentID, wilokeListingToolsRepository()->get('payment:promotionPaymentRelationship'));
    }

    public static function updatePromotionID($paymentID, $promotionID)
    {
        return self::patch(
            $paymentID,
            wilokeListingToolsRepository()->get('payment:promotionPaymentRelationship'),
            $promotionID
        );
    }

    public static function setProductPaymentID($paymentID, $productID)
    {
        self::set($paymentID, wilokeListingToolsRepository()->get('addlisting:productIDPaymentID'), $productID);
    }

    public static function setPaymentSubscriptionID($paymentID, $subscriptionID)
    {
        return self::set($paymentID, wilokeListingToolsRepository()->get('payment:subscriptionID'), $subscriptionID);
    }

    public static function getSubscriptionID($paymentID)
    {
        return self::get($paymentID, wilokeListingToolsRepository()->get('payment:subscriptionID'));
    }

    public static function getPaymentIDBySubscriptionID($subscriptionID)
    {
        global $wpdb;
        self::generateTableName($wpdb);
        $tblName = self::$tblName;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT paymentID FROM {$tblName} WHERE meta_key = %s and meta_value=%s ORDER BY paymentID DESC",
                self::addPrefixToMetaName(wilokeListingToolsRepository()->get('payment:subscriptionID')),
                $subscriptionID
            //                self::addPrefixToMetaName(wilokeListingToolsRepository()->get('payment:stripeSubscriptionID')), $subscriptionID
            )
        );
    }

    /**
     * @return void
     */
    public static function generateTableName($wpdb)
    {
        self::$tblName = $wpdb->prefix.AlterTablePaymentMeta::$tblName;
    }

    /**
     * Set Payment Meta
     *
     * @param number $sessionID
     * @param string $metaKey
     * @param mixed  $val
     *
     * @return bool
     */
    public static function set($paymentID, $metaKey, $val)
    {
        global $wpdb;
        self::generateTableName($wpdb);
        if (empty(self::get($paymentID, $metaKey))) {
            $wpdb->insert(
                self::$tblName,
                [
                    'paymentID'  => absint($paymentID),
                    'meta_key'   => self::addPrefixToMetaName($metaKey),
                    'meta_value' => maybe_serialize($val)
                ],
                [
                    '%d',
                    '%s',
                    '%s'
                ]
            );

            return $wpdb->insert_id;
        } else {
            return self::patch($paymentID, self::addPrefixToMetaName($metaKey), $val);
        }
    }

    public static function patch($paymentID, $metaKey, $val)
    {
        global $wpdb;
        self::generateTableName($wpdb);

        return $wpdb->update(
            self::$tblName,
            [
                'meta_value' => maybe_serialize($val)
            ],
            [
                'paymentID' => $paymentID,
                'meta_key'  => $metaKey,
            ],
            [
                '%s'
            ],
            [
                '%d',
                '%s'
            ]
        );
    }

    public static function update($paymentID, $metaKey, $val)
    {
        return self::patch($paymentID, self::addPrefixToMetaName($metaKey), $val);
    }

    /**
     * Get Payment Meta by specifying meta key and sessionID
     *
     * @param number $paymentID
     * @param string $metaKey
     *
     * @return mixed
     */
    public static function get($paymentID, $metaKey)
    {
        global $wpdb;
        self::generateTableName($wpdb);
        $aResult = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT meta_value FROM ".self::$tblName." WHERE paymentID=%d AND meta_key=%s ORDER BY paymentID DESC",
                $paymentID, self::addPrefixToMetaName($metaKey)
            )
        );

        if (empty($aResult)) {
            return false;
        }

        return maybe_unserialize($aResult);
    }

    /**
     * @param $paymentID
     * @param $val
     *
     * @return false|int
     */
    public static function setDispute($paymentID, $val)
    {
        global $wpdb;
        self::generateTableName($wpdb);
        $status = self::set($paymentID, wilokeListingToolsRepository()->get('payment:paymentDispute'), $val);

        if ($status) {
            FileSystem::logSuccess('AddListing: Has Dispute. Payment ID:'.$paymentID.' Value:'.$val);
        }

        return $status;
    }

    /**
     * @param $paymentID
     * @param $intentID
     *
     * @return mixed
     */
    public static function setPaymentIntentID($paymentID, $intentID)
    {
        return self::set($paymentID, wilokeListingToolsRepository()->get('payment:paymentIntentID'), $intentID);
    }

    /**
     * @param $paymentID
     * @param $val
     *
     * @return false|int
     */
    public static function setDisputeInfo($paymentID, $val)
    {
        global $wpdb;
        self::generateTableName($wpdb);
        $status = self::set($paymentID, wilokeListingToolsRepository()->get('payment:paymentDisputeInfo'), $val);
        if ($status) {
            FileSystem::logSuccess('AddListing: Dispute Info. Payment ID:'.$paymentID.' Value:'.json_encode
                ($val));
        }

        return $status;
    }

    public static function getDisputeInfo($paymentID)
    {
        return self::get($paymentID, wilokeListingToolsRepository()->get('payment:paymentDisputeInfo'));
    }

    public static function getDispute($paymentID)
    {
        return self::get($paymentID, wilokeListingToolsRepository()->get('payment:paymentDispute'));
    }

    public static function getPaymentIDByDisputeID($disputeID)
    {
        global $wpdb;
        self::generateTableName($wpdb);
        $tblName = self::$tblName;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT paymentID FROM {$tblName} WHERE meta_key = %s and meta_value=%s",
                self::addPrefixToMetaName(wilokeListingToolsRepository()->get('payment:paymentDispute')), $disputeID
            )
        );
    }

    public static function setPaymentToken($paymentID, $token)
    {
        return self::set($paymentID, wilokeListingToolsRepository()->get('payment:paymentTokenID'), $token);
    }

    public static function getPaymentIDByToken($token)
    {
        return self::getPaymentIDByMetaValue(
            wilokeListingToolsRepository()->get('payment:paymentTokenID'),
            $token
        );
    }

    public static function getPaymentTokenByPaymentID($paymentID)
    {
        return self::get(
            $paymentID,
            wilokeListingToolsRepository()->get('payment:paymentTokenID')
        );
    }

    /**
     * @param $paymentID
     *
     * @return mixed
     */
    public static function getPaymentInfo($paymentID)
    {
        return self::get($paymentID, wilokeListingToolsRepository()->get('payment:paymentInfo'));
    }

    /**
     * @param $paymentID
     *
     * @return mixed
     */
    public static function updatePaymentInfo($paymentID, $val)
    {
        return self::update($paymentID, wilokeListingToolsRepository()->get('payment:paymentInfo'), $val);
    }

    public static function setPaymentInfo($paymentID, $val): bool
    {
        return self::set($paymentID, wilokeListingToolsRepository()->get('payment:paymentInfo'), $val);
    }

    /*
     * @param $paymentID
     */
    public static function getNextBillingDateGMT($paymentID)
    {
        return self::get($paymentID, wilokeListingToolsRepository()->get('addlisting:nextBillingDateGMT'));
    }

    /*
     * @param $nextBillingDateGMT
     * @param $paymentID
     */
    public static function setNextBillingDateGMT($nextBillingDateGMT, $paymentID)
    {
        self::set($paymentID, wilokeListingToolsRepository()->get('addlisting:nextBillingDateGMT'),
            $nextBillingDateGMT);
    }

    public static function delete($paymentID, $metaKey)
    {
        global $wpdb;
        self::generateTableName($wpdb);
        $metaKey = self::addPrefixToMetaName($metaKey);

        $status = $wpdb->delete(
            self::$tblName,
            [
                'meta_key'  => $metaKey,
                'paymentID' => $paymentID
            ],
            [
                '%s',
                '%d'
            ]
        );

        if (empty($status)) {
            return false;
        }

        return $paymentID;
    }

    public static function getPostTypeByPlanID($planID)
    {
        $aPostTypes = GetSettings::getFrontendPostTypes(true);
        foreach ($aPostTypes as $postType) {
            $planIDs = GetWilokeSubmission::getField($postType.'_plans');
            if (empty($planIDs)) {
                continue;
            }

            $aPlanIDs = explode(',', $planIDs);
            if (in_array($planID, $aPlanIDs)) {
                return $postType;
            }
        }
    }

    /**
     * Get Session ID by specifying token
     *
     * @param string $metaKey
     * @param string $metaValue
     *
     * @return mixed
     */
    public static function getPaymentIDByMetaValue($metaKey, $metaValue)
    {
        global $wpdb;
        self::generateTableName($wpdb);

        $sessionID = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT paymentID FROM ".self::$tblName." WHERE meta_value=%s AND meta_key=%s ORDER BY ID DESC",
                $metaValue, self::addPrefixToMetaName($metaKey)
            )
        );

        if (empty($sessionID)) {
            return false;
        }

        return $sessionID;
    }

    /**
     * @param $intent
     *
     * @return bool|mixed
     */
    public static function getPaymentIDByIntentID($intentID)
    {
        return self::getPaymentIDByMetaValue(
            wilokeListingToolsRepository()->get('payment:paymentIntentID'),
            $intentID
        );
    }

    /**
     * Get Session ID By Meta Value
     *
     * @param string $metaValue
     *
     * @return number $sessionID
     */
    public static function getSessionWhereEqualToMetaValue($metaKey, $metaVal)
    {
        global $wpdb;
        self::generateTableName($wpdb);

        $sessionID = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT paymentID FROM ".self::$tblName." WHERE meta_key=%s AND meta_value=%s",
                self::addPrefixToMetaName($metaKey), $metaVal
            )
        );

        return absint($sessionID);
    }
}
