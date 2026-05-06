<?php
namespace WilokeListingToolsTests\Controllers\AddListing;

session_start();
use WilokeListingTools\Controllers\AddListingController;
use WilokeListingTools\Framework\Helpers\App;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;

class FreeAddListingTest extends TestAddListingCommon
{
    /**
     * Step 1: Submitting a listing with Free Plan to Preview Mode.
     */
    public function testHandleSubmitToPreviewScreen(): array
    {
        $this->removeAllTestData();
        $this->runCommandSetup();

        $this->setUserLogin($this->aContributor['username']);

        $oAddListingControllerMock = $this->getMockBuilder('WilokeListingTools\Controllers\AddListingController')
            ->setMethods(['verifyCSRF'])
            ->getMock();
        $oAddListingControllerMock->expects($this->any())->method('verifyCSRF')->will($this->returnValue(true));

        $_POST = array_merge(
            $this->getDummyAddListingData(),
            [
                'planID' => $this->freePlanId
            ]
        );
        /**
         * #[ArrayShape(['redirectTo' => 'string', 'listingID' => 'int', 'planID' => 'int', 'status' =>
         * 'success|error', 'oAddListingMock' => 'object'])]
         */
        $aResponse = $oAddListingControllerMock->handlePreview();
        $aResponse['listingID'] = abs($aResponse['listingID']);
        $belongsToPlanId = GetSettings::getListingBelongsToPlan($aResponse['listingID']);

        $this->assertEquals('success', $aResponse['status'], isset($aResponse['msg']) ? $aResponse['msg'] : 'Failled');
        $this->assertNotEmpty($belongsToPlanId);
        $this->assertEquals($belongsToPlanId, $this->freePlanId);
        $this->assertEquals(get_post_status($aResponse['listingID']), 'unpaid');

        $this->setIsTestFlag($aResponse['listingID']);
        return array_merge(
            [
                'noExpiryPlanID'  => $this->noExpiryPlanId,
                'freePlanID'      => $this->freePlanId,
                'premiumPlanID'   => $this->premiumPlanID,
                'advancedPlanID'  => $this->advancedPlanID,
                'oAddListingMock' => $oAddListingControllerMock
            ],
            $aResponse
        );
    }

    /**
     * @depends testHandleSubmitToPreviewScreen
     */
    public function testSubmitToReview($aInfo)
    {
        extract($aInfo);
        $this->createWilcityUser($this->aContributor);
        $this->setUserLogin($this->aContributor['username']);
        /**
         * @var AddListingController $oAddListingMock
         */
        $this->addNeedReviewAfterListingSubmitted();
        $aResponse = $oAddListingMock->handleSubmit();
        $this->assertEquals(get_post_status($aInfo['listingID']), 'pending');

        return $aInfo;
    }

    /**
     * @depends testSubmitToReview
     * @param $aInfo
     */
    public function testExpiryValueEvent($aInfo)
    {
        ## The  listing expiry should be emptied after publishing
        $expiryValue = GetSettings::getListingExpiryTimestamp($aInfo['listingID']);
        $this->assertEmpty($expiryValue);

        wp_update_post(
            [
                'ID'          => $aInfo['listingID'],
                'post_status' => 'publish'
            ]
        );

        ## The Post Expiry event is not existed until now.
        $this->assertFalse($this->hasTriggerPostExpiryEvent($aInfo['listingID']));

        ## There is a schedule event that cares of Listing Expiry will be set.
        ## After 2 minutes, this event will be triggered and Listing Expiry will be updated
        $this->assertTrue($this->hasExpiryValueEvent($aInfo['listingID']));
        $this->triggerExpiryValue($aInfo['listingID']);

        ## Now, the Listing Expiry is set.
        $expiryValue = GetSettings::getListingExpiryTimestamp($aInfo['listingID']);
        $this->assertNotEmpty($expiryValue);
        $this->assertFalse($this->hasExpiryValueEvent($aInfo['listingID']));

        ## After Post Expiry is set, Trigger Post Expiry Event will be set as well.
        ## After this event is set, Post Expiry Event will be set
        $this->assertTrue($this->hasTriggerPostExpiryEvent($aInfo['listingID']));

        ## Now We will trigger Post Expiry
        $this->assertFalse($this->hasPostExpiryEvents($aInfo['listingID']));
        $this->assertFalse($this->hasAlmostPostExpiryEvents($aInfo['listingID']));
        $this->triggerExpiryEvent($aInfo['listingID']);
        ## And Post Expiry and Post Almost Expiry should be set
        $this->assertFalse($this->hasTriggerPostExpiryEvent($aInfo['listingID']));
        $this->assertTrue($this->hasPostExpiryEvents($aInfo['listingID']));
//        $this->assertTrue($this->hasAlmostPostExpiryEvents($aInfo['listingID']));

        ## Trigger Post Expiry
        $this->triggerPostExpired($aInfo['listingID']);
        $this->assertEquals('expired', get_post_status($aInfo['listingID']));
        return $aInfo;
    }

    public function isContributor($postAuthorId): bool
    {
        $oUser = new \WP_User($postAuthorId);

        return in_array('contributor', $oUser->roles);
    }

    /**
     * @depends testSubmitToReview
     */
    public function testDuplicateEmail($aInfo)
    {
        App::get('EmailController')->aSentHistory = [];

        $this->assertFalse(get_post_status($aInfo['listingID']) === 'publish');

        wp_update_post(
            [
                'ID'           => $aInfo['listingID'],
                'post_content' => 'Hello',
                'post_status'  => 'publish'
            ]
        );

        $oAfter = get_post($aInfo['listingID']);

        $this->assertTrue($this->isContributor($oAfter->post_author));
        $aListingKeys = General::getPostTypeKeys(false, false, true);
        $this->assertNotEmpty($aListingKeys);
        $this->assertTrue(in_array($oAfter->post_type, $aListingKeys));

        $planID = GetSettings::getListingBelongsToPlan($oAfter->ID);
        $this->assertIsNumeric($planID);
        $this->assertTrue(get_post_type($planID) === 'listing_plan');

        $this->assertArrayHasKey('notifyListingApprovedToCustomer', App::get('EmailController')->aSentHistory);
        $this->assertEquals(1, App::get('EmailController')->aSentHistory['notifyListingApprovedToCustomer']['count']);

        wp_update_post(
            [
                'ID'          => $aInfo['listingID'],
                'post_status' => 'publish'
            ]
        );
        $this->assertEquals(1, App::get('EmailController')->aSentHistory['notifyListingApprovedToCustomer']['count']);

        return $aInfo;
    }

    /**
     * @depends testDuplicateEmail
     */
    public function testExpiryListingEmail($aInfo)
    {
        App::get('EmailController')->aSentHistory = [];

        wp_update_post(
            [
                'ID'          => $aInfo['listingID'],
                'post_status' => 'expired'
            ]
        );
        $this->assertArrayHasKey('listingExpired', App::get('EmailController')->aSentHistory);

        return $aInfo;
    }

    /**
     * @depends testExpiryListingEmail
     */
    public function testListingPendingEmail($aInfo)
    {
        App::get('EmailController')->aSentHistory = [];

        wp_update_post(
            [
                'ID'          => $aInfo['listingID'],
                'post_status' => 'pending'
            ]
        );

        wp_update_post(
            [
                'ID'          => $aInfo['listingID'],
                'post_status' => 'pending'
            ]
        );

        $this->assertArrayHasKey('notifyListingPendingToAdmin', App::get('EmailController')->aSentHistory);
        $this->assertEquals(1, App::get('EmailController')->aSentHistory['notifyListingPendingToAdmin']['count']);

        return $aInfo;
    }

    /**
     * @depends testListingPendingEmail
     */
    public function testSubmitNoExpiryListingNeedToBeReviewed($aInfo)
    {
        extract($aInfo);
        $this->addNeedReviewAfterListingSubmitted();
        $this->createWilcityUser($this->aContributor);
        $this->setUserLogin($this->aContributor['username']);

        $oAddListingControllerMock = $this->getMockBuilder('WilokeListingTools\Controllers\AddListingController')
            ->setMethods(['verifyCSRF'])
            ->getMock();
        $oAddListingControllerMock->expects($this->any())->method('verifyCSRF')->will($this->returnValue(true));

        $aData = $this->getDummyAddListingData();
        $aListingData = json_decode($aData['data'], true);
        $aListingData['header']['listing_title'] = 'No Expiry Listing';
        $aData['data'] = json_encode($aListingData);

        $_POST = array_merge(
            $aData,
            [
                'planID' => $noExpiryPlanID
            ]
        );

        /**
         * #[ArrayShape(['redirectTo' => 'string', 'listingID' => 'int', 'planID' => 'int', 'status' =>
         * 'success|error', 'oAddListingMock' => 'object'])]
         */
        $aResponse = $oAddListingControllerMock->handlePreview();
        $listingId = abs($aResponse['listingID']);
        $belongsToPlanId = GetSettings::getListingBelongsToPlan($aResponse['listingID']);

        $this->assertEquals('success', $aResponse['status'], isset($aResponse['msg']) ? $aResponse['msg'] : 'Failed');
        $this->assertNotEmpty($belongsToPlanId);
        $this->assertEquals($belongsToPlanId, $noExpiryPlanID);
        $this->assertEquals(get_post_status($listingId), 'unpaid');

        $this->setIsTestFlag($listingId);

        $oAddListingControllerMock->handleSubmit();
        $this->assertEquals(get_post_status($listingId), 'pending');

        $expiryValue = GetSettings::getListingExpiryTimestamp($listingId);
        $this->assertEmpty($expiryValue);

        $this->setUserLogin('admin');
        wp_update_post(
            [
                'ID'          => $listingId,
                'post_status' => 'publish'
            ]
        );

        $this->assertTrue($this->hasExpiryValueEvent($listingId));
        $this->triggerExpiryValue($listingId);
        $expiryValue = GetSettings::getListingExpiryTimestamp($listingId);
        $this->assertEmpty($expiryValue);
        $this->assertFalse($this->hasAlmostPostExpiryEvents($listingId));
        $this->assertFalse($this->hasExpiryValueEvent($listingId));

        return $aInfo;
    }


    /**
     * @depends testSubmitNoExpiryListingNeedToBeReviewed
     */
    public function testSubmitNoExpiryListingApprovedImmediately($aInfo)
    {
        extract($aInfo);
        $this->addAutoApprovedImmediateAfterListingSubmitted();
        $this->createWilcityUser($this->aContributor);
        $this->setUserLogin($this->aContributor['username']);

        $oAddListingControllerMock = $this->getMockBuilder('WilokeListingTools\Controllers\AddListingController')
            ->setMethods(['verifyCSRF'])
            ->getMock();
        $oAddListingControllerMock->expects($this->any())->method('verifyCSRF')->will($this->returnValue(true));

        $aData = $this->getDummyAddListingData();
        $aListingData = json_decode($aData['data'], true);
        $aListingData['header']['listing_title'] = 'No Expiry Listing';
        $aData['data'] = json_encode($aListingData);

        $_POST = array_merge(
            $aData,
            [
                'planID' => $noExpiryPlanID
            ]
        );

        /**
         * #[ArrayShape(['redirectTo' => 'string', 'listingID' => 'int', 'planID' => 'int', 'status' =>
         * 'success|error', 'oAddListingMock' => 'object'])]
         */
        $aResponse = $oAddListingControllerMock->handlePreview();
        $listingId = abs($aResponse['listingID']);
        $belongsToPlanId = GetSettings::getListingBelongsToPlan($aResponse['listingID']);

        $this->assertEquals('success', $aResponse['status'], isset($aResponse['msg']) ? $aResponse['msg'] : 'Failed');
        $this->assertNotEmpty($belongsToPlanId);
        $this->assertEquals($belongsToPlanId, $noExpiryPlanID);
        $this->assertEquals(get_post_status($listingId), 'unpaid');

        $this->setIsTestFlag($listingId);

        $oAddListingControllerMock->handleSubmit();
        $this->assertEquals(get_post_status($listingId), 'publish');

        $expiryValue = GetSettings::getListingExpiryTimestamp($listingId);
        $this->assertEmpty($expiryValue);

        $this->assertTrue($this->hasExpiryValueEvent($listingId));
        $this->triggerExpiryValue($listingId);
        $expiryValue = GetSettings::getListingExpiryTimestamp($listingId);
        $this->assertEmpty($expiryValue);
        $this->assertFalse($this->hasAlmostPostExpiryEvents($listingId));
        $this->assertFalse($this->hasExpiryValueEvent($listingId));

        return $aInfo;
    }
}
