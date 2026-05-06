<?php

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\Time as WilokeTime;
use WilokeListingTools\Models\UserModel;
use WilokeListingTools\Framework\Helpers\Time;
use WILCITY_SC\SCHelpers;

function wilcity_render_event_item($post, $aAtts)
{
	$aAtts['img_size'] = SCHelpers::parseImgSize($aAtts['img_size']);
	$imgUrl = GetSettings::getFeaturedImg($post->ID, $aAtts['img_size']);

	$aEventCalendarSettings = GetSettings::getEventSettings($post->ID);
	if (empty($aEventCalendarSettings)) {
	    return '';
    }

	$address = GetSettings::getAddress($post->ID);
	$classes
		= $aAtts['maximum_posts_on_lg_screen'] . ' js-grid-item ' . $aAtts['maximum_posts_on_md_screen'] . ' ' .
		$aAtts['maximum_posts_on_sm_screen'] . ' ' . $aAtts['maximum_posts_on_xs_screen'];

	$frequency = $aEventCalendarSettings['frequency'];
	?>
    <div class="<?php echo esc_attr($classes); ?>">
        <!-- event_module__2zicF wil-shadow -->
        <div class="event_module__2zicF wil-shadow mb-30 mb-sm-20 js-event"
             data-id="<?php echo esc_attr($post->ID); ?>">
            <header class="event_header__u3oXZ">
                <a href="<?php echo get_permalink($post); ?>">
					<?php SCHelpers::renderLazyLoad($imgUrl, [
						'divClass' => 'event_img__1mVnG pos-a-full bg-cover',
						'alt'      => $post->post_title
					]); ?>
                </a>
            </header>
            <div class="js-grid-item-body event_body__BfZIC">
                <?php do_action('wilcity-shortcodes/core/wil_render_event_items/after/event-body', $post,
                    $aEventCalendarSettings, $frequency); ?>
                <div class="event_calendar__2x4Hv">
                    <span class="event_month__S8D_o color-primary"><?php echo date_i18n('M', $aEventCalendarSettings['startTimestamp']); ?></span>
                    <span class="event_date__2Z7TH"><?php echo date_i18n('d', $aEventCalendarSettings['startTimestamp']); ?></span>
					<?php do_action('wilcity/event/grid/event_item/after-event-start-time', $post, $frequency); ?>
                </div>
                <div class="event_content__2fB-4">
                    <h2 class="event_title__3C2PA">
                        <a href="<?php echo get_permalink($post); ?>"><?php echo get_the_title($post->ID); ?></a>
                    </h2>
                    <ul class="event_meta__CFFPg list-none">
						<?php do_action('wilcity/event/grid/after-open-meta-data', $post); ?>
                        <li class="event_metaList__1bEBH text-ellipsis wil-event-frequent">
                            <span>
                                <?php
                                if ($frequency == 'weekly') {
	                                $specifyDay = $aEventCalendarSettings['specifyDays'];
	                                $dayName = wilokeListingToolsRepository()->get('general:aDayOfWeek', true)
		                                ->sub($specifyDay);
	                                echo sprintf(esc_html__('Every %s', 'wilcity-shortcodes'), $dayName) . ', ' .
		                                esc_html(Time::toDateFormat($aEventCalendarSettings['startTimestamp'])) .
		                                ' - ' .
		                                esc_html(Time::toDateFormat($aEventCalendarSettings['endTimestamp']));
                                } else {
	                                echo esc_html(Time::toDateFormat($aEventCalendarSettings['startTimestamp'])) .  ' - ' . esc_html(Time::toDateFormat($aEventCalendarSettings['endTimestamp']));
                                }
                                ?>
                            </span>
							<?php do_action('wilcity/event/grid/event_item/meta-list', $post, $frequency); ?>
                        </li>
						<?php
						if (!empty($address)) :
							$mapUrl = GetSettings::getAddress($post->ID, true);
							?>
                            <li class="event_metaList__1bEBH text-ellipsis">
                                <a href="<?php echo esc_url($mapUrl); ?>"
                                   target="_blank"><span><?php echo esc_html($address); ?></span></a>
                            </li>
						<?php endif; ?>
						<?php SCHelpers::renderInterested($post); ?>
						<?php do_action('wilcity/event/grid/before-close-meta-data', $post); ?>
                    </ul>
                </div>
            </div>
            <footer class="js-grid-item-footer event_footer__1TsCF">
				<?php SCHelpers::renderHostedBy($post); ?>
                <div class="event_right__drLk5 pos-a-center-right">
					<?php SCHelpers::renderInterestedPeople($post); ?>
                </div>
            </footer>
        </div><!-- End / event_module__2zicF wil-shadow -->
    </div>
	<?php
}
