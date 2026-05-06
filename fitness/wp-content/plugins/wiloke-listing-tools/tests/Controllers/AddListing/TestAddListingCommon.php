<?php


namespace WilokeListingToolsTests\Controllers\AddListing;


use WilokeListingTools\Controllers\PostController;
use WilokeListingTools\Framework\Helpers\App;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\SetSettings;
use WilokeListingTools\Register\WilokeSubmission;
use WilokeListingToolsTests\Controllers\CommonController;

class TestAddListingCommon extends CommonController
{
    public $postId;
    public $freePlanId;
    public $premiumPlanID;
    public    $advancedPlanID;
    public    $thankyouId;
    public    $cancelId;
    public    $contributorId;
    protected $aHooks
        = [
            'need_update_schedule',
            'post_expiry',
            'post_almost_expiry',
            'delete_unpaid_listing',
            'f_notice_delete_unpaid_listing',
            's_notice_delete_unpaid_listing',
            't_notice_delete_unpaid_listing',
            'wilcity_set_expiry_post_value_schedule',
            'wilcity_set_expiry_post_event_schedule',
        ];
    private   $checkoutId;
    /**
     * @var int|\WP_Error
     */
    protected $noExpiryPlanId;

    public function getDeleteUnpaidEvents(): array
    {
        $oPostController = new PostController();

        return $this->getEvents(
            $this->getPrivateProperty($oPostController, 'deleteUnpaidListing')
        );
    }

    public function hasDeleteUnpaidEvent($postId): bool
    {
        $aSchedules = $this->getDeleteUnpaidEvents();

        foreach ($aSchedules as $aInfo) {
            if (in_array($postId, $aInfo['args'])) {
                return true;
            }
        }

        return false;
    }

    public function getExpiryValueEvents(): array
    {
        $oPostController = new PostController();

        return $this->getEvents(
            $this->getPrivateProperty($oPostController, 'setExpiryPostValueSchedule')
        );
    }

    public function getTriggerPostExpiryEvent(): array
    {
        $oPostController = new PostController();

        return $this->getEvents(
            $this->getPrivateProperty($oPostController, 'setExpiryPostEventSchedule')
        );
    }

    public function getPostExpiryEvents(): array
    {
        $oPostController = new PostController();

        return $this->getEvents(
            $this->getPrivateProperty($oPostController, 'expirationKey')
        );
    }

    public function getPostAlmostExpiry(): array
    {
        $oPostController = new PostController();

        return $this->getEvents(
            $this->getPrivateProperty($oPostController, 'almostExpiredKey')
        );
    }

    public function hasExpiryValueEvent($postId): bool
    {
        $aSchedules = $this->getExpiryValueEvents();
        foreach ($aSchedules as $aInfo) {
            if (in_array($postId, $aInfo['args'])) {
                return true;
            }
        }

        return false;
    }

    public function hasPostExpiryEvents($postId): bool
    {
        $aSchedules = $this->getPostExpiryEvents();
        foreach ($aSchedules as $aInfo) {
            if (in_array($postId, $aInfo['args'])) {
                return true;
            }
        }

        return false;
    }

    public function hasAlmostPostExpiryEvents($postId): bool
    {
        $aSchedules = $this->getPostAlmostExpiry();
        foreach ($aSchedules as $aInfo) {
            if (in_array($postId, $aInfo['args'])) {
                return true;
            }
        }

        return false;
    }

    public function hasTriggerPostExpiryEvent($postId): bool
    {
        $aSchedules = $this->getTriggerPostExpiryEvent();
        foreach ($aSchedules as $aInfo) {
            if (in_array($postId, $aInfo['args'])) {
                return true;
            }
        }

        return false;
    }

    public function triggerExpiryValue($listingId)
    {
        App::get('PostController')->maybeUpdatePostExpiryValue($listingId);
    }

    public function triggerPostExpired($listingId)
    {
        App::get('PostController')->postExpired($listingId);
    }

    public function triggerExpiryEvent($listingId)
    {
        App::get('PostController')->setNextRecheckPostExpiryEvent($listingId);
    }

    public function createListing()
    {
        $aData = [
            'post_title'   => 'My Listing',
            'post_content' => 'This is my content',
            'post_author'  => $this->getAccountInfo($this->aContributorAccount['username'])->ID,
            'post_status'  => 'publish',
            'post_type'    => 'listing'
        ];

        $this->postId = wp_insert_post($aData);
        $this->setIsTestFlag($this->postId);

        return is_wp_error($this->postId) ? 0 : $this->postId;
    }

    public function createThankyouPage(): int
    {
        $aData = [
            'post_title'   => 'Thankyou',
            'post_content' => 'This is my content',
            'post_author'  => $this->getAdminId(),
            'post_status'  => 'publish',
            'post_type'    => 'page'
        ];

        $this->thankyouId = wp_insert_post($aData);
        update_post_meta($this->thankyouId, '_page_template', 'wiloke-submission/thankyou.php');
        $this->setIsTestFlag($this->thankyouId);

        return is_wp_error($this->thankyouId) ? 0 : $this->thankyouId;
    }

    public function createCheckoutPage(): int
    {
        $aData = [
            'post_title'   => 'Checkout',
            'post_content' => 'This is my content',
            'post_author'  => $this->getAdminId(),
            'post_status'  => 'publish',
            'post_type'    => 'page'
        ];

        $this->checkoutId = wp_insert_post($aData);
        update_post_meta($this->checkoutId, '_page_template', 'wiloke-submission/checkout.php');
        $this->setIsTestFlag($this->checkoutId);

        return is_wp_error($this->checkoutId) ? 0 : $this->checkoutId;
    }

    public function createCancelPage(): int
    {
        $aData = [
            'post_title'   => 'Cancel',
            'post_content' => 'This is my content',
            'post_author'  => $this->getAdminId(),
            'post_status'  => 'publish',
            'post_type'    => 'page'
        ];

        $this->cancelId = wp_insert_post($aData);
        update_post_meta($this->cancelId, '_page_template', 'wiloke-submission/cancel.php');
        $this->setIsTestFlag($this->cancelId);

        return is_wp_error($this->cancelId) ? 0 : $this->cancelId;
    }

    public function createNoExpiryPlan()
    {
        $aData = [
            'post_title'   => 'No Expiry Plan',
            'post_content' => 'This is my content',
            'post_author'  => $this->getAdminId(),
            'post_status'  => 'publish',
            'post_type'    => 'listing_plan'
        ];

        $this->noExpiryPlanId = wp_insert_post($aData);

        SetSettings::setPostMeta(
            $this->noExpiryPlanId,
            'add_listing_plan',
            [
                'regular_price'             => 0,
                'regular_period'            => 0,
                'availability_items'        => 3,
                'toggle_featured_image'     => 'enable',
                'toggle_cover_image'        => 'enable',
                'toggle_logo'               => 'enable',
                'toggle_sidebar_statistics' => 'enable'
            ]
        );
        $this->setIsTestFlag($this->noExpiryPlanId);

        return is_wp_error($this->noExpiryPlanId) ? 0 : $this->noExpiryPlanId;
    }

    public function createFreePlan()
    {
        $aData = [
            'post_title'   => 'Free Plan',
            'post_content' => 'This is my content',
            'post_author'  => $this->getAdminId(),
            'post_status'  => 'publish',
            'post_type'    => 'listing_plan'
        ];

        $this->freePlanId = wp_insert_post($aData);

        SetSettings::setPostMeta(
            $this->freePlanId,
            'add_listing_plan',
            [
                'regular_price'             => 0,
                'regular_period'            => 1,
                'availability_items'        => 3,
                'toggle_featured_image'     => 'enable',
                'toggle_cover_image'        => 'enable',
                'toggle_logo'               => 'enable',
                'toggle_sidebar_statistics' => 'enable'
            ]
        );
        $this->setIsTestFlag($this->freePlanId);

        return is_wp_error($this->freePlanId) ? 0 : $this->freePlanId;
    }

    public function addAutoApprovedImmediateAfterListingSubmitted()
    {
        $aSettings = GetWilokeSubmission::getAll(true);
        $aSettings['approved_method'] = 'auto_approved_after_payment';
        SetSettings::setOptions(WilokeSubmission::$optionKey, $aSettings);
    }

    public function addNeedReviewAfterListingSubmitted()
    {
        $aSettings = GetWilokeSubmission::getAll(true);
        $aSettings['approved_method'] = 'manual_review';
        SetSettings::setOptions(WilokeSubmission::$optionKey, $aSettings);
    }

    public function addManualReviewAfterListingSubmitted()
    {
        $aSettings = GetWilokeSubmission::getAll(true);
        $aSettings['approved_method'] = 'manual_review';
        SetSettings::setOptions(WilokeSubmission::$optionKey, $aSettings);
    }

    public function addRequiredSettingsToWilokeSubmission()
    {
        $aSettings = GetWilokeSubmission::getAll(true);
        $aSettings['thankyou'] = $this->createThankyouPage();
        $aSettings['cancel'] = $this->createCancelPage();
        $aSettings['checkout'] = $this->createCheckoutPage();
        $aSettings['payment_gateways'] = 'paypal,stripe,banktransfer';
        $aSettings['bank_transfer_account_name_1'] = 'ABC';
        $aSettings['bank_transfer_account_number_1'] = '123';
        $aSettings['bank_transfer_name_1'] = 'NGUYEN NGUYEN';
        $aSettings['bank_transfer_short_code_1'] = 'ABC';
        $aSettings['bank_transfer_iban_1'] = 'ABC';
        $aSettings['bank_transfer_swift_1'] = 'ABC';

        SetSettings::setOptions(WilokeSubmission::$optionKey, $aSettings);
    }

    public function addPlansToWilokeSubmission()
    {
        $aSettings = GetWilokeSubmission::getAll(true);
        if (isset($aSettings['listing_plans'])) {
            $aPlans = explode(',', $aSettings['listing_plans']);
            foreach ($aPlans as $planIndex => $planId) {
                if (get_post_status($planId) !== 'publish') {
                    unset($aPlans[$planIndex]);
                }
            }
        } else {
            $aPlans = [];
        }

        $aPlans[] = $this->freePlanId;
        $aPlans[] = $this->premiumPlanID;
        $aPlans[] = $this->advancedPlanID;
        $aPlans[] = $this->noExpiryPlanId;

        $aSettings['listing_plans'] = implode(',', $aPlans);

        SetSettings::setOptions(WilokeSubmission::$optionKey, $aSettings);
    }

    public function removePlanFromWilokeSubmission()
    {
        $aPlanIds = $this->getAllTestPlans();
        $aSettings = GetWilokeSubmission::getAll();

        if (isset($aSettings['listing_plans'])) {
            $aPlans = explode(',', $aSettings['listing_plans']);
        } else {
            $aPlans = [];
        }
        $aPlans = array_filter($aPlans, function ($planId) use ($aPlanIds) {
            return !in_array($planId, $aPlanIds);
        });
        $aSettings['listing_plans'] = implode(',', $aPlans);

        SetSettings::setOptions(WilokeSubmission::$optionKey, $aSettings);
    }

    public function createPremiumPlan()
    {
        $aData = [
            'post_title'   => 'Premium Plan',
            'post_content' => 'This is my content',
            'post_author'  => $this->getAdminId(),
            'post_status'  => 'publish',
            'post_type'    => 'listing_plan'
        ];

        $this->premiumPlanID = wp_insert_post($aData);
        $this->setIsTestFlag($this->premiumPlanID);

        SetSettings::setPostMeta(
            $this->premiumPlanID,
            'add_listing_plan',
            [
                'regular_price'             => 10,
                'availability_items'        => 3,
                'toggle_featured_image'     => 'enable',
                'toggle_cover_image'        => 'enable',
                'toggle_logo'               => 'enable',
                'regular_period'            => 3,
                'toggle_sidebar_statistics' => 'enable'
            ]
        );

        return is_wp_error($this->premiumPlanID) ? 0 : $this->premiumPlanID;
    }

    public function createAdvancedPlan()
    {
        $aData = [
            'post_title'   => 'Advanced Plan',
            'post_content' => 'This is my content',
            'post_author'  => $this->getAdminId(),
            'post_status'  => 'publish',
            'post_type'    => 'listing_plan'
        ];

        $this->advancedPlanID = wp_insert_post($aData);
        $this->setIsTestFlag($this->advancedPlanID);

        SetSettings::setPostMeta(
            $this->advancedPlanID,
            'add_listing_plan',
            [
                'regular_price'             => 20,
                'availability_items'        => 3,
                'toggle_featured_image'     => 'enable',
                'toggle_cover_image'        => 'enable',
                'toggle_logo'               => 'enable',
                'regular_period'            => 3,
                'toggle_sidebar_statistics' => 'enable'
            ]
        );

        return is_wp_error($this->advancedPlanID) ? 0 : $this->advancedPlanID;
    }

    public function updateAddListingSettings()
    {
        $addListingSettings = file_get_contents(
            $this->aGeneralSettings['SAMPLE_DATA_DIR'] .
            'addlisting-settings.json'
        );
        $aData = json_decode(stripslashes($addListingSettings), true);
        $postType = 'listing';
        SetSettings::setOptions(General::getUsedSectionKey($postType), $aData['settings'], true);
    }

    public function runCommandSetup()
    {
        foreach ($this->aHooks as $hook) {
            wp_unschedule_hook($hook);
        }

        $this->removeAllTestData();
        $this->contributorId = $this->createWilcityUser($this->aContributor);
        $this->updateAddListingSettings();
        $this->createFreePlan();
        $this->createNoExpiryPlan();
        $this->createPremiumPlan();
        $this->createAdvancedPlan();
        $this->addPlansToWilokeSubmission();
        $this->addRequiredSettingsToWilokeSubmission();
    }

    public function getAllTestPlans(): array
    {
        $query = new \WP_Query([
            'post_type'      => 'listing_plan',
            'posts_per_page' => -1,
            'post_status'    => ['trash', 'unpaid', 'publish', 'expired'],
            'meta_query'     => [
                [
                    'meta_key'   => 'is_test',
                    'meta_value' => 'yes'
                ]
            ]
        ]);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();

                $aPlanIds[] = abs($query->post->ID);
            }
        } else {
            $aPlanIds = [];
        }

        return $aPlanIds;
    }

    public function removeAllTestData()
    {
        $this->deleteTestData();
        $this->removePlanFromWilokeSubmission();
    }

    public function getDummyAddListingData()
    {
        return [
            'data'                  => file_get_contents($this->aGeneralSettings['SAMPLE_DATA_DIR'] .
                'addlisting.json'),
            'planID'                => '',
            'listingID'             => '',
            'listingType'           => 'listing',
            'wilcityAddListingCsrf' => wp_create_nonce('wilcity-submit-listing'),
            'action'                => 'wilcity_handle_review_listing'
        ];
    }

}
