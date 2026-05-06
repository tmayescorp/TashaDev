<?php
namespace WilokeListingTools\Framework\Store;

use WilokeListingTools\Framework\Helpers\DebugStatus;
use WilokeListingTools\Framework\Helpers\Validation;

class Session
{
    protected static $isSessionStarted = false;
    protected static $expiration = 900;

    protected static function generatePrefix($name)
    {
        return str_replace('_', '-', wilokeListingToolsRepository()->get('general:prefix').$name);
    }

    public static function sessionStart($sessionID = null)
    {
	    global $pagenow;
	    if ($pagenow == 'site-health.php' || (is_admin() && isset($_GET['page']) && $_GET['page'] == 'site-health')) {
	        return false;
	    }

        if (!headers_sent() && (session_status() == PHP_SESSION_NONE || session_status() === 1)) {
            session_start();
        } else {
            if (!headers_sent() && empty(session_id()) && !empty($sessionID)) {
                session_id($sessionID);
            }
        }
    }

    public static function getSessionID()
    {
        session_start();
        var_export(session_id());
    }

    public static function setSession($name, $value, $sessionID = null)
    {
        $value = maybe_serialize($value);
        if (DebugStatus::status('WILOKE_STORE_WITH_DB')) {
            set_transient(self::generatePrefix($name), $value, self::$expiration);
        } else {
            if (empty(session_id())) {
                self::sessionStart($sessionID);
            }
            $_SESSION[self::generatePrefix($name)] = $value;
        }
    }

    public static function getSession($name, $thenDestroy = false)
    {
        if (DebugStatus::status('WILOKE_STORE_WITH_DB')) {
            $value = get_transient(self::generatePrefix($name));
        } else {
            self::sessionStart(self::generatePrefix($name));
            $value = isset($_SESSION[self::generatePrefix($name)]) ? $_SESSION[self::generatePrefix($name)] : '';
        }

        if (empty($value)) {
            return false;
        }

        if ($thenDestroy) {
            self::destroySession($name);
        }

        return maybe_unserialize($value);
    }

    /**
     * @param $name
     * @param $aMsg ['type' => '', 'msg' => '']
     */
    public static function addTopNotifications($name, $aMsg)
    {
        $notifications = self::getSession('top-notifications');
        if (empty($notifications)) {
            self::setSession(
                'top-notifications',
                [
                    $name => $aMsg
                ]
            );
        } else {
            if (is_string($notifications) && Validation::isValidJson($notifications)) {
                $aNotifications        = Validation::getJsonDecoded();
                $aNotifications[$name] = $aMsg;
                self::setSession('top-notifications', $aNotifications);
            } else if(is_array($notifications)) {
                $aNotifications        = Validation::getJsonDecoded();
                $aNotifications[$name] = $aMsg;
                self::setSession('top-notifications', $aNotifications);
            }
        }
    }

    public static function setTopOfNotifications($aMsgs)
    {
        self::setSession('top-notifications', $aMsgs);
    }

    public static function deleteAllSessions()
    {
        self::sessionStart();

        Session::destroySession(wilokeListingToolsRepository()->get('payment:sessionObjectStore'));
        Session::destroySession(wilokeListingToolsRepository()->get('payment:storePlanID'));
        Session::destroySession(wilokeListingToolsRepository()->get('addlisting:isAddingListingSession'));
        Session::destroySession(wilokeListingToolsRepository()->get('payment:sessionRelationshipStore'));
        Session::destroySession(wilokeListingToolsRepository()->get('payment:sessionObjectStore'));
    }

    public static function destroySession($name = null)
    {
        self::sessionStart();
        if (DebugStatus::status('WILOKE_STORE_WITH_DB')) {
            delete_transient(self::generatePrefix($name));
        } else {
            if (!empty(self::generatePrefix($name))) {
                unset($_SESSION[self::generatePrefix($name)]);
            } else {
                session_destroy();
            }
        }
    }

    /**
     * @param bool $thenDestroy
     *
     * @return bool|mixed
     */
    public static function getPaymentPlanID($thenDestroy = false)
    {
        return Session::getSession(wilokeListingToolsRepository()->get('payment:storePlanID'), $thenDestroy);
    }

    /**
     * @param bool $thenDestroy
     *
     * @return bool|mixed
     */
    public static function getPaymentObjectID($thenDestroy = false)
    {
        return Session::getSession(wilokeListingToolsRepository()->get('payment:sessionObjectStore'), $thenDestroy);
    }

    /**
     * @param bool $thenDestroy
     *
     * @return bool|mixed
     */
    public static function getPaymentCategory($thenDestroy = false)
    {
        return Session::getSession(wilokeListingToolsRepository()->get('payment:category'), $thenDestroy);
    }

    /**
     * @param bool $thenDestroy
     *
     * @return bool|mixed
     */
    public static function getProductID($thenDestroy = false)
    {
        return Session::getSession(wilokeListingToolsRepository()->get('payment:associateProductID'), $thenDestroy);
    }

    /**
     * @param bool $thenDestroy
     *
     * @return bool|mixed
     */
    public static function getClaimID($thenDestroy = false)
    {
        return Session::getSession(wilokeListingToolsRepository()->get('claim:sessionClaimID'), $thenDestroy);
    }

    public static function getChangedPlanID($thenDestroy = false)
    {
        return Session::getSession(wilokeListingToolsRepository()->get('payment:changedPlanID'), $thenDestroy);
    }

    public static function getPaymentID($theyDestroy = false)
    {
        return Session::getSession(wilokeListingToolsRepository()->get('payment:paymentID'), $theyDestroy);
    }

    public static function setPaymentPlanID($planID)
    {
        Session::setSession(wilokeListingToolsRepository()->get('payment:storePlanID'), $planID);
    }

    /**
     * @param $listingID
     */
    public static function setPaymentObjectID($listingID)
    {
        Session::setSession(wilokeListingToolsRepository()->get('payment:sessionObjectStore'), $listingID);
    }

    public static function setFocusObjectsApprovedImmediately($listingIDs)
    {
        Session::setSession(wilokeListingToolsRepository()->get('payment:focusapprovedimmediately'), $listingIDs);
    }

    public static function getFocusObjectsApprovedImmediately()
    {
        $aListings = Session::getSession(wilokeListingToolsRepository()->get('payment:focusapprovedimmediately'), true);

        return empty($aListings) ? [] : $aListings;
    }

    /**
     * @param $category
     */
    public static function setPaymentCategory($category)
    {
        Session::setSession(wilokeListingToolsRepository()->get('payment:category'), $category);
    }

    /**
     * @param $productID
     */
    public static function setProductID($productID)
    {
        Session::setSession(wilokeListingToolsRepository()->get('payment:associateProductID'), $productID);
    }

    /**
     * @param $paymentID
     */
    public static function setPaymentID($paymentID)
    {
        Session::setSession(wilokeListingToolsRepository()->get('payment:paymentID'), $paymentID);
    }

    /**
     * @param $claimID
     */
    public static function setClaimID($claimID)
    {
        Session::setSession(wilokeListingToolsRepository()->get('claim:sessionClaimID'), $claimID);
    }

    public static function setChangedPlanID($newPlanID)
    {
        Session::setSession(wilokeListingToolsRepository()->get('payment:changedPlanID'), $newPlanID);
    }
}
