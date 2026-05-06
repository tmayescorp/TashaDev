<?php

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Models\UserModel;
use WilokeListingTools\Models\FavoriteStatistic;

global $wilcityWrapperClass, $post, $event;
$wilcityWrapperClass = empty($wilcityWrapperClass) ? 'col-sm-6' : $wilcityWrapperClass;

$interestedClass = UserModel::isMyFavorite($post->ID) ? 'la la-star color-primary' : 'la la-star-o';
$totalInterested = FavoriteStatistic::countFavorites($post->ID);
$aMapInformation = GetSettings::getListingMapInfo($post->ID);

if (!$event instanceof  WP_Post) {
    $event = $post;
}
?>
<div class="<?php echo esc_attr($wilcityWrapperClass) . ' js-listing-grid wilcity-grid'; ?>">
    <?php wilcity_render_event_item($event, [
        'img_size'                   => 'large',
        'maximum_posts_on_lg_screen' => '',
        'maximum_posts_on_md_screen' => '',
        'maximum_posts_on_sm_screen' => '',
    ]); ?>
</div>
