<?php


namespace WilokeListingToolsTests\Controllers;


use PHPUnit\Framework\TestCase;
use WilokeListingTools\Controllers\PromotionController;
use WilokeListingTools\Framework\Helpers\App;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\SetSettings;

class PromotionControllerTest extends CommonController
{
    private $promotionId;
    private $listingId;


    /**
     * Next Step: Get all promotion plans
     * @return array[]
     */
    private function getPlans(): array
    {
        return [
            [
                'name'               => 'Listing Sidebar',
                'price'              => 10,
                'duration'           => 1,
                'position'           => 'listing_sidebar',
                'conditional'        => 'everywhere',
                'id'                 => 'sidebar',
                'value'              => 'yes',
                'productAssociation' => ''
            ],
            [
                'name'               => 'Blog Sidebar',
                'price'              => 10,
                'duration'           => 10,
                'position'           => 'blog_sidebar',
                'conditional'        => 'everywhere',
                'id'                 => '',
                'value'              => 'yes',
                'productAssociation' => ''
            ]
        ];
    }

    protected function getPromotionScheduleEvents(): array
    {
        return $this->getEvents(
            $this->getPrivateProperty(
                new PromotionController(),
                'handlePromotionEvent'
            )
        );
    }

    protected function getHandlePromotionPositionExpiryScheduleEvents(): array
    {
        return $this->getEvents(
            $this->getPrivateProperty(
                new PromotionController(),
                'handlePromotionPositionExpiry'
            )
        );
    }

    protected function getPromotionExpiryScheduleEvents(): array
    {
        return $this->getEvents(
            $this->getPrivateProperty(
                new PromotionController(),
                'handlePromotionPositionExpiry')
        );
    }

    /**
     * @param array{0: promotionId, 1: listingId, 2: positionMetaKey} $aArgs
     * @return bool
     */
    public function hasHandlePromotionPositionExpiryScheduleEvents($aArgs): bool
    {
        $aSchedules = $this->getHandlePromotionPositionExpiryScheduleEvents();

        foreach ($aSchedules as $aInfo) {
            if ($aArgs == $aInfo['args']) {
                return true;
            }
        }

        return false;
    }

    public function hasPromotionScheduleEvent($postId): bool
    {
        $aSchedules = $this->getPromotionScheduleEvents();

        foreach ($aSchedules as $aInfo) {
            if (in_array($postId, $aInfo['args'])) {
                return true;
            }
        }

        return false;
    }

    public function hasPromotionExpiryScheduleEvent($postId): bool
    {
        $aSchedules = $this->getPromotionExpiryScheduleEvents();

        foreach ($aSchedules as $aInfo) {
            if (in_array($postId, $aInfo['args'])) {
                return true;
            }
        }

        return false;
    }

    public function triggerPromotionScheduleEvent($promotionId)
    {
        App::get('PromotionController')->handlePromotion($promotionId);
    }

    /**
     * @param array{0: promotionId, 1: listingId, 2: positionMetaKey} $aArgs
     * @return bool
     */
    public function triggerPromotionExpiryScheduleEvent($aArgs)
    {
        App::get('PromotionController')
            ->clearPromotionPositionAfterExpirationDate($aArgs[0], $aArgs[1], $aArgs[2]);
    }

    protected function setupPromotionPlans()
    {
        $aResponse = $this->ajaxLogin('admin')->ajaxPost([
            'action'      => 'wiloke_save_promotion_settings',
            'plans'       => $this->getPlans(),
            'toggle'      => 'enable',
            'description' => 'This is my promotion description'
        ]);

        $this->assertTrue($aResponse['success'], 'The promotion plans have setup successfully');
    }

    /**
     * Create a listing
     */
    private function createListing()
    {
        $this->listingId = wp_insert_post(
            [
                'post_title'  => 'Test Listing Promotion',
                'post_status' => 'publish',
                'author'      => $this->getAdminId(),
                'post_type'   => 'listing'
            ]
        );
        $this->listingId = (int)$this->listingId;
        $this->setIsTestFlag($this->listingId);
    }

    /**
     * Next Step: Create a Promotion
     */
    private function createPromotion()
    {
        $this->promotionId = wp_insert_post(
            [
                'post_title'  => 'Test Promotion',
                'post_status' => 'draft',
                'post_type'   => 'promotion',
                'author'      => $this->getAdminId()
            ]
        );

        $this->promotionId = (int)$this->promotionId;
        $this->setIsTestFlag($this->promotionId);
    }

    /**
     * Step 1: In this step, We will create a promotion (draft status) and listing. Then the Promotion will be
     * published.
     * @return array
     * @throws \ReflectionException
     */
    public function testBoostListing(): array
    {
        $this->deleteTestData();
        $this->setupPromotionPlans();
        $this->createListing();
        $this->createPromotion();

        SetSettings::setPostMeta($this->promotionId, 'listing_id', $this->listingId);

        foreach ($this->getPlans() as $aPlan) {
            SetSettings::setPostMeta(
                $this->promotionId,
                $this->invokeMethod(
                    new PromotionController(),
                    'generatePositionMetaKey',
                    [$aPlan]
                ),
                strtotime('+ ' . $aPlan['duration'] . ' days')
            );
        }

        wp_update_post([
            'ID'          => $this->promotionId,
            'post_status' => 'publish'
        ]);

        ## Prepare handle Promotion Event should be set now (It will be triggered after 2 minutes)
        $this->assertTrue($this->hasPromotionScheduleEvent($this->promotionId));

        return [
            'promotionId' => $this->promotionId,
            'listingId'   => $this->listingId
        ];
    }

    /**
     * Listing belongs to Promotion: After the above steps have been completed, Promotion Id must be added to Listing Id
     *
     * @depends testBoostListing
     * @param array $aItems
     * @return array
     */
    public function testBelongsTo(array $aItems): array
    {
        $this->triggerPromotionScheduleEvent($aItems['promotionId']);
        $belongsTo = GetSettings::getPostMeta(
            $aItems['listingId'],
            'belongs_to_promotion',
            '',
            'int',
            true
        );

        $this->assertEquals(
            (int)$aItems['promotionId'],
            (int)$belongsTo
        );

        return $aItems;
    }

    /**
     * Make sure that all Promotion Plans are being added to Listing: In this step, We will check to see if all
     * Promotion Plans are added to the Listing or not. If they are already added, the code is good.
     *
     * @depends testBelongsTo
     * @param array $aItems
     * @return array
     * @throws \ReflectionException
     */
    public function testPromotionPlansAppendedToListing(array $aItems): array
    {
        $oPromotion = App::get('PromotionController');
        foreach ($this->getPlans() as $aPlan) {
            $aArgs = [
                $aItems['promotionId'],
                $aItems['listingId'],
                $this->invokeMethod($oPromotion, 'generatePositionMetaKey', [$aPlan]),
            ];

            $this->assertTrue(
                $this->hasHandlePromotionPositionExpiryScheduleEvents($aArgs),
                sprintf('The promotion %s does not belong to %s', $aPlan['name'], get_the_title($aItems['listingId']))
            );

            $value = GetSettings::getPostMeta(
                $aItems['listingId'],
                $this->invokeMethod($oPromotion, 'generatePositionMetaKey', [$aPlan]),
                '',
                '',
                true
            );

            $this->assertNotEmpty($value);

            $this->triggerPromotionExpiryScheduleEvent($aArgs);

            $value = GetSettings::getPostMeta(
                $aItems['listingId'],
                $this->invokeMethod($oPromotion, 'generatePositionMetaKey', [$aPlan]),
                '',
                '',
                true
            );


            $this->assertEmpty($value);
        }

        return $aItems;
    }
}
