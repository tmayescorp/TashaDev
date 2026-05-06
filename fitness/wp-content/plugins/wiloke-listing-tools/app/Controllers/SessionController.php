<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Helpers\FileSystem;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Framework\Store\Session;

class SessionController extends Controller
{
    public function __construct()
    {
        add_action('wiloke-submission/payment-succeeded-and-updated-everything', [$this, 'deletePaymentSessions']);
        add_action(
            'wilcity/wiloke-listing-tools/payment-succeeded-and-updated-everything',
            [$this, 'deletePaymentSessions'],
            100
        );
        
        add_action('wiloke/submitted-listing', [$this, 'maybeDeletePaymentSessions'], 100);
        //        add_action('wilcity/wiloke-listing-tools/stripe/created-section', [$this, 'destroySessionBeforeInsertingPayment'], 99);
        
        add_action('wilcity/wiloke-listing-tools/before/insert-payment',
            [$this, 'destroySessionBeforeInsertingPayment'], 99);
        
        add_action(
            'wilcity/wiloke-listing-tools/before-payment-processing',
            [$this, 'clearSessionBeforePaymentProcessing']
        );
        
        add_action(
            'wilcity/wiloke-listing-tools/before-add-listing',
            [$this, 'clearSessionBeforeAddingListing']
        );
    }
    
    public function clearSessionBeforeAddingListing()
    {
        $this->deletePaymentSessions();
    }
    
    public function clearSessionBeforePaymentProcessing()
    {
        Session::destroySession('waiting_for_paypal_execution');
        Session::destroySession(wilokeListingToolsRepository()->get('payment:changedPlanID'));
    }
    
    public function destroySessionBeforeInsertingPayment($aInfo)
    {
        if (isset($aInfo['gateway']) && $aInfo['gateway'] == 'woocommerce') {
            return false;
        }
        
        FileSystem::logSuccess('Cleared category, sessionRelationshipStore session', __CLASS__);
        Session::destroySession(wilokeListingToolsRepository()->get('payment:category'));
        Session::destroySession(wilokeListingToolsRepository()->get('payment:sessionRelationshipStore'));
    }
    
    public function maybeDeletePaymentSessions($aInfo)
    {
        if (!empty($aInfo['aUserPlan'])) {
            $this->deletePaymentSessions();
        }
    }
    
    public function deletePaymentSessions()
    {
        FileSystem::logSuccess('Cleared all session', __CLASS__);
        
        Session::destroySession(wilokeListingToolsRepository()->get('payment:storePlanID'));
        Session::destroySession(wilokeListingToolsRepository()->get('payment:sessionObjectStore'));
        Session::destroySession(wilokeListingToolsRepository()->get('payment:associateProductID'));
        Session::destroySession(wilokeListingToolsRepository()->get('addlisting:isAddingListingSession'));
        Session::destroySession(wilokeListingToolsRepository()->get('payment:paypalTokenAndStoreData'));
        Session::destroySession(wilokeListingToolsRepository()->get('payment:sessionRelationshipStore'));
        Session::destroySession('errorPayment');
        Session::destroySession('wiloke_submission_listing_type');
        Session::destroySession('wiloke_payment_category');
        Session::destroySession('waiting_for_paypal_execution');
        Session::destroySession(wilokeListingToolsRepository()->get('payment:changedPlanID'));
    }
}
