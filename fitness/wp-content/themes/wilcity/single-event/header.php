<?php
global $post;

use Wilcity\Ultils\ListItems\Link;
use Wilcity\Ultils\ListItems\WilSocialSharingBtn;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Models\UserModel;
use WilokeListingTools\Models\FavoriteStatistic;
use WilokeListingTools\Frontend\User as WilokeUser;
use WilokeListingTools\Framework\Helpers\HTML;
use WilokeListingTools\Models\EventModel;

$interestedClass = UserModel::isMyFavorite($post->ID) ? 'la la-star color-primary' : 'la la-star-o';
$totalInterested = FavoriteStatistic::countFavorites($post->ID);
$aButtons = wilcityGetConfig('btn');
$oLink = new Link();

$hostedBy = GetSettings::getPostMeta($post->ID, 'hosted_by');
if (!empty($hostedBy)) {
    $hostedByProfileURL = GetSettings::getPostMeta($post->ID, 'hosted_by_profile_url');
    $hostedByProfileURL = empty($hostedByProfileURL) ? '#' : $hostedByProfileURL;
    $hostedByTarget = GetSettings::getEventHostedByTarget($hostedByProfileURL);
} else {
    $hostedByProfileURL = WilokeUser::url($post->post_author);
    $hostedBy = WilokeUser::getField('display_name', $post->post_author);
    $hostedByTarget = '_self';
}
$aMapInformation = GetSettings::getListingMapInfo($post->ID);
$aEventDate = EventModel::getEventDate($post->ID);

?>
<header class="event-detail-content_header__VdI5m">
    <?php do_action('wilcity/single-event/before/event-header', $post); ?>
    <?php if (has_post_thumbnail($post->ID)) : ?>
        <div class="event-detail-content_img__2hZQO">
            <?php the_post_thumbnail($post->ID, 'large'); ?>
        </div>
    <?php endif; ?>
    <?php do_action('wilcity/single-event/after/event-header', $post); ?>
    <div class="event-detail-content_firstItem__3vz2x">
        <h1 class="event-detail-content_title__asKJI"><?php the_title(); ?></h1>
        <div class="event-detail-content_meta__1dBc1 wilcity-hosted-by">
            <span>
                <?php
                esc_html_e('Hosted By', 'wilcity');
                echo $oLink->setConfiguration([
                    'wrapperClasses'    => 'color-dark-2',
                    'btnName'           => $hostedBy,
                    'link'              => $hostedByProfileURL,
                    'btnWrapperClasses' => 'nothing',
                    'btnTarget'         => $hostedByTarget
                ])->render();
                ?>
            </span>
            <?php if (!empty($totalInterested)) : ?>
                <span><?php echo HTML::reStyleText($totalInterested) . ' ' .
                        ($totalInterested > 1 ? esc_html__('people interested', 'wilcity') :
                            esc_html__('person interested', 'wilcity')); ?></span>
            <?php endif; ?>

        </div>
        <div class="listing-detail_rightButton__30xaS clearfix">
            <a class="<?php echo esc_attr(apply_filters('wilcity/filter/class-prefix',
                'wilcity-js-favorite wil-btn wil-btn--border wil-btn--round wil-btn--sm is-event')); ?>"
               href="#"
               data-post-id="<?php echo esc_attr($post->ID); ?>">
                <i class="<?php echo esc_attr($interestedClass); ?>"></i> <?php esc_html_e('Interested', 'wilcity'); ?>
            </a>

            <?php
            $buttonLink = GetSettings::getPostMeta($post->ID, 'button_link');
            if (!empty($buttonLink)) {
                $buttonName = GetSettings::getPostMeta($post->ID, 'button_name');
                $buttonIcon = GetSettings::getPostMeta($post->ID, 'button_icon');
                $buttonName = $buttonName ?? esc_html__('Buy ticket', 'wilcity');
                $buttonIcon = $buttonIcon ?? 'la la-ticket';
                ?>
                <a class="wil-btn ml-10 wil-btn--border wil-btn--round wil-btn--sm wil-event-custom-button"
                   href="<?php echo esc_url($buttonLink) ?>"
                   target="_blank"
                   rel="nofollow">
                    <i class="<?php echo esc_attr($buttonIcon); ?>"></i> <?php echo esc_html($buttonName); ?>
                </a>
                <?php
            }

            ?>

            <?php if ($aEventDate && is_array($aMapInformation) && !empty($aMapInformation)) : ?>
                <wil-toggle-controller wrapper-classes="d-inline pos-r"
                                       btn-classes="wil-btn wil-btn--border wil-btn--round wil-btn--sm"
                                       btn-name="<?php esc_attr_e('Add to my calendar', 'wilcity'); ?>">
                    <template v-slot:default="{isOpen}">
                        <wil-add-to-calendar v-if="isOpen"
                                             :start-timestamp="<?php echo abs($aEventDate['startTimestamp'] *
                                                 1000); ?>"
                                             :end-timestamp="<?php echo abs($aEventDate['endTimestamp'] * 1000); ?>"
                                             title="<?php echo esc_attr($post->post_title); ?>"
                                             location="<?php echo esc_attr($aMapInformation['address']); ?>"
                        >
                        </wil-add-to-calendar>
                    </template>
                </wil-toggle-controller>
            <?php endif; ?>

            <?php
            $oWilSocialSharing = new WilSocialSharingBtn();
            try {
                echo $oWilSocialSharing->setConfiguration(
                    [
                        'postId' => $post->ID
                    ]
                )->render();
            }
            catch (Exception $e) {
                echo $e->getMessage();
            }
            ?>
        </div>
    </div>

    <?php do_action('wilcity/single-event/calendar', $post); ?>
    <?php do_action('wilcity/single-event/meta-data', $post); ?>

</header>
