<?php


namespace WilokeListingToolsTests\Controllers\AddListing;


use WilokeListingTools\Controllers\AddListingPaymentController;
use WilokeListingTools\Controllers\ChangePlanStatusController;
use WilokeListingTools\Framework\Helpers\App;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Store\Session;

class NonRecurringPaymentTest extends TestAddListingCommon
{
    /**
     * @return array
     */
    public function testAddPremiumListing()
    {
        App::get('EmailController')->aSentHistory = [];
        $this->removeAllTestData();
        $this->runCommandSetup();

        $this->createWilcityUser($this->aContributor);
        $this->setUserLogin($this->aContributor['username']);
        $oAddListingControllerMock = $this->getMockBuilder('WilokeListingTools\Controllers\AddListingController')
            ->setMethods(['verifyCSRF'])
            ->getMock();
        $oAddListingControllerMock->expects($this->any())
            ->method('verifyCSRF')
            ->will($this->returnValue(true));

        $_POST = array_merge(
            $this->getDummyAddListingData(),
            [
                'planID' => $this->premiumPlanID
            ]
        );
        /**
         * #[ArrayShape(['redirectTo' => 'string', 'listingID' => 'int', 'planID' => 'int', 'status' =>
         * 'success|error', 'oAddListingMock' => 'object'])]
         */
        $aResponse = $oAddListingControllerMock->handlePreview();
        $aResponse['listingID'] = abs($aResponse['listingID']);

        $this->assertEquals('success', $aResponse['status']);
        $this->setIsTestFlag($aResponse['listingID']);

        return array_merge(
            [
                'freePlanID'      => $this->freePlanId,
                'premiumPlanID'   => $this->premiumPlanID,
                'advancedPlanID'  => $this->advancedPlanID,
                'oAddListingMock' => $oAddListingControllerMock
            ],
            $aResponse
        );
    }


    /**
     * @depends testAddPremiumListing
     *
     * @param $aResponse ['redirectTo' => 'string', 'listingID' => 'int', 'planID' => 'int', 'status' =>
     * 'success|error'], 'freePlanId' => 'int', 'premiumPlanId' => 'int']
     */
    public function testListingAfterSubmittingToPreview($aResponse)
    {
        $this->assertEquals(
            get_post_status($aResponse['listingID']),
            'unpaid',
            'The post status should be assigned to "unpaid"'
        );

        $this->assertEquals(Session::getPaymentObjectID(), $aResponse['listingID'], 'Missing listingID session');
        $this->assertEquals(Session::getPaymentPlanID(), $aResponse['premiumPlanID'], 'Missing planID session');
        $this->assertNotEquals(Session::getPaymentPlanID(), Session::getPaymentObjectID(),
            'Plan ID and Object ID is the same. It seems there is a mistake while caching the value');

        return $aResponse;
    }

    /**
     * @depends testListingAfterSubmittingToPreview
     */
    public function testSubmitListing($aData)
    {
        $this->addNeedReviewAfterListingSubmitted();
        $aData['oAddListingMock']->handleSubmit();
        $this->assertEquals($aData['status'], 'success');
        $belongsTo = GetSettings::getListingBelongsToPlan($aData['listingID']);
        $this->assertEquals($aData['premiumPlanID'], $belongsTo);

        $this->assertTrue(get_post_status($aData['listingID']) === 'unpaid');

        return $aData;
    }

    /**
     * @depends testSubmitListing
     * @param $aData
     */
    public function testPurchasePlan($aData)
    {
        $_POST['gateway'] = 'banktransfer';
        $_POST['action'] = 'wiloke_submission_purchase_add_listing_plan';
        $_POST['planID'] = $aData['premiumPlanID'];
        $_POST['couponCode'] = '';

        $oAddListingPaymentController = new AddListingPaymentController();
        $aResponse = $oAddListingPaymentController->purchaseAddListingPlan();

        $this->assertEquals($aResponse['status'], 'success');
        $aData['paymentID'] = $aResponse['paymentID'];
        $this->assertTrue(get_post_status($aData['listingID']) === 'unpaid');
        return $aData;
    }

    /**
     * @param $aData
     * @depends testPurchasePlan
     */
    public function testPaymentSucceed($aData)
    {
        $this->addNeedReviewAfterListingSubmitted();
        $oChangePlanStatusController = new ChangePlanStatusController();
        $this->setUserLogin('admin');
        $_POST = $aData;
        $_POST['newStatus'] = 'succeeded';
        $_POST['oldStatus'] = 'pending';
        $_POST['gateway'] = 'banktransfer';

        ## Before the payment is changed to success status, the Listing should keep unpaid status
        $this->assertTrue($this->hasDeleteUnpaidEvent($aData['listingID']));
        $this->assertEquals(get_post_status($aData['listingID']), 'unpaid');
        $aResponse = $oChangePlanStatusController->changeBankTransferPaymentStatus();
        ## The delete unpaid listing should be set

        ## Now, We will change to success status, and the Listing should be moved to pending status
        $this->assertEquals('success', $aResponse['status']);
        $this->assertEquals(get_post_status($aData['listingID']), 'pending');
        ## The delete unpaid listing should be removed
        $this->assertFalse($this->hasDeleteUnpaidEvent($aData['listingID']));

        ## Now, the Administrator will approve this listing
        wp_publish_post($aData['listingID']);
        ## The expiry value event should be set (After 2 minutes, it will be triggered
        $this->assertTrue($this->hasExpiryValueEvent($aData['listingID']));
        ## Then, We will trigger it
        $this->triggerExpiryValue($aData['listingID']);
        ## And the Post Expiry should be set
        $this->assertFalse($this->hasExpiryValueEvent($aData['listingID']));
        $this->assertNotEmpty(GetSettings::getListingExpiryTimestamp($aData['listingID']));
        $this->assertFalse($this->hasPostExpiryEvents($aData['listingID']));

        ## Next, we will trigger set Expiry Event
        $this->triggerExpiryEvent($aData['listingID']);
        $this->assertTrue($this->hasPostExpiryEvents($aData['listingID']));

        ## Finally, this listing will be expired
        $this->triggerPostExpired($aData['listingID']);
        $this->assertEquals(get_post_status($aData['listingID']), 'expired');

        ## The admin must be received an email when the listing is submitted
        $this->assertArrayHasKey('notifyListingPendingToAdmin', App::get('EmailController')->aSentHistory);
        ## And the customer should be received an expired email
        $this->assertArrayHasKey('listingExpired', App::get('EmailController')->aSentHistory);
        $this->assertArrayHasKey('notifyListingApprovedToAdmin', App::get('EmailController')->aSentHistory);
        $this->assertArrayHasKey('notifyListingApprovedToCustomer', App::get('EmailController')->aSentHistory);

        $this->assertEquals(1, App::get('EmailController')->aSentHistory['notifyListingApprovedToAdmin']['count']);
        $this->assertEquals(1, App::get('EmailController')->aSentHistory['notifyListingApprovedToCustomer']['count']);


        return $aData;
    }
}
