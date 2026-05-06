<?php

namespace WilokeListingTools\MetaBoxes;


use WilokeListingTools\Framework\Helpers\GetSettings;

class Promotion
{
    protected $aPromotionPlans;

    public function __construct()
    {
        add_action('cmb2_admin_init', [$this, 'renderMetaboxFields']);
    }

    public function renderMetaboxFields()
    {
        $aMetaBoxes = wilokeListingToolsRepository()->get('promotion-metaboxes');
        $aPlanSettings = GetSettings::getPromotionPlans();

        $aAdditionalFields = [];

        if (empty($aPlanSettings)) {
            return false;
        }


        foreach ($aPlanSettings as $key => $aPlan) {
            $desc = "";
            if (isset($_GET['post'])) {
                $expiryOn = GetSettings::getListingExpiryTimestamp($_GET['post']);
                $expiryOn = date_i18n(get_option("date_format") . " " . get_option("time_format"), strtotime($expiryOn));
                $desc
                    = esc_html__(sprintf("The expiry value is stored  by UTC timezone. Based on your site timezone, it's %s",
                    $expiryOn), "wiloke-listing-tools");
            }

            $aAdditionalFields[] = [
                'type' => 'text_datetime_timestamp',
                'id' => 'wilcity_promote_' . $key,
                'description' => $desc,
                'name' => 'Position ' . $aPlan['name'] . ' Until'
            ];
        }

        $aMetaBoxes['promotion_information']['fields'] = array_merge($aMetaBoxes['promotion_information']['fields'],
            $aAdditionalFields);

        foreach ($aMetaBoxes as $aMetaBox) {
            new_cmb2_box($aMetaBox);
        }
    }
}
