<?php

use WilokeListingTools\Frontend\SingleListing;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Frontend\User as WilokeUser;
use \WilokeListingTools\Framework\Helpers\General;
use \Wilcity\Ultils\ListItems\Lists;

global $post;
$aTabs                 = SingleListing::getNavOrder();
$aTabs                 = General::unSlashDeep($aTabs);
$aTabs                 = apply_filters('wilcity/single-listing/tabs', $aTabs, $post);
$url                   = get_permalink($post->ID);
$aListingConfiguration = wilcityGetConfig('listing');

$aNavigation = $aListingConfiguration['singleListingNavigation'];

foreach ($aTabs as $aTab) {
    // Skipe these tabs
    if (in_array($aTab['key'], ['coupon', 'google_adsense_1', 'google_adsense_2'])
        || !isset($aTab['status']) || $aTab['status'] === 'no') {
        continue;
    }

    if ($aTab['key'] === 'tags') {
        $tabKey = 'listing_tag';
    } else if (isset($aTab['category']) && $aTab['category'] === 'taxonomy') {
        $tabKey = $aTab['taxonomy'];
    } elseif (isset($aTab['taxonomy']) && taxonomy_exists($aTab['taxonomy'])) {
        $tabKey = $aTab['taxonomy'];
    } else {
        $tabKey = str_replace('wilcity_single_navigation_', '', $aTab['key']);
    }

    $aNavigation[] = [
        'type'           => 'li',
        'wrapperClasses' => 'list_item__3YghP wil-single-nav'.$tabKey,
        'children'       => [
            [
                'type'    => 'wilSwitchTabBtn',
                'icon'    => $aTab['icon'],
                'btnName' => $aTab['name'],
                'tabKey'  => $tabKey
            ]
        ]
    ];
}

if (WilokeUser::isPostAuthor($post, true)) {
    $aNavigation[] = [
        'type'           => 'li',
        'wrapperClasses' => 'list_item__3YghP wil-single-nav-listing-settings',
        'children'       => [
            [
                'type'    => 'wilSwitchTabBtn',
                'icon'    => 'la la-cog',
                'tabKey'  => 'listing-settings',
                'btnName' => esc_html__('Listing Settings', 'wilcity')
            ]
        ]
    ];
}
?>
<div class="detail-navtop_module__zo_OS js-detail-navtop">
    <div class="container">
        <?php
        if ($buttonLink = GetSettings::getPostMeta($post->ID, 'button_link')) :
            $buttonLink = esc_attr($buttonLink);
            $target = "_blank";
            if (is_email($buttonLink)) {
                $buttonLink = "mailto:".$buttonLink;
                $target     = "_self";
            } else {
                $parseLink = str_replace(['.', '-'], ['', ''], $buttonLink);
                if (strpos($parseLink, 'http') === false && preg_match('/[0-9]/', $parseLink, $aMatch)) {
                    $buttonLink = "tel:".$buttonLink;
                    $target     = "_self";
                } else {
                    $target = esc_url($target);
                }
            }
            ?>
            <div class="detail-navtop_right__KPAlw">
                <a class="wil-btn wil-btn--primary2 wil-btn--round wil-btn--md wil-btn--block"
                   rel="nofollow"
                   target="<?php echo esc_attr($target); ?>>"
                   href="<?php echo $buttonLink; ?>">
                    <i class="<?php echo esc_attr(GetSettings::getPostMeta($post->ID, 'button_icon')); ?>"></i>
                    <?php echo esc_html(GetSettings::getPostMeta($post->ID, 'button_name')); ?>
                </a>
            </div>
        <?php endif; ?>

        <nav class="detail-navtop_nav__1j1Ti" style="min-height: 73px;">
            <?php do_action('wilcity/single-listing/before/navigation'); ?>
            <?php
            $oLists = new Lists();
            try {
                echo $oLists->setWrapperEl('ul')
                            ->setWrapperClasses('list_module__1eis9 list-none list_horizontal__7fIr5')
                            ->setConfiguration($aNavigation)
                            ->beforeRenderElements()
                            ->render()
                ;
            } catch (Exception $e) {
                WilokeMessage::message([
                    'type' => 'danger',
                    'msg'  => $e->getMessage()
                ]);
            }
            ?>
            <?php do_action('wilcity/single-listing/after/navigation'); ?>
        </nav>
    </div>
</div>
