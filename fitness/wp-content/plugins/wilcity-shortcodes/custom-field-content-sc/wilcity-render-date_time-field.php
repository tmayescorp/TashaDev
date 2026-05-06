<?php

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Framework\Helpers\AddListingFieldSkeleton;

function wilcityRenderDateTimeField($aAtts)
{
    $aAtts = shortcode_atts(
      [
        'key'         => '',
        'is_mobile'   => '',
        'post_id'     => '',
        'description' => '',
        'extra_class' => '',
        'title'       => ''
      ],
      $aAtts
    );

    if (!empty($aAtts['post_id'])) {
        $post = get_post($aAtts['post_id']);
    } else {
        $post = \WILCITY_SC\SCHelpers::getPost();
    }

	if (apply_filters(
		'wilcity-shortcodes/default-sc/wilcity-render-date_time-field/hide-item',
		false,
		$aAtts,
		$post->ID
	)) {
		return '';
	}

    if (empty($aAtts['key']) || !class_exists('WilokeListingTools\Framework\Helpers\GetSettings') || empty($post)) {
        return '';
    }
    if (!GetSettings::isPlanAvailableInListing($post->ID, $aAtts['key'])) {
        return '';
    }
    $content = GetSettings::getPostMeta($post->ID, 'custom_'.$aAtts['key']);

    if (empty($content)) {
        return '';
    }

    $timestamp = is_numeric($content) ? $content : strtotime($content);

    $class = $aAtts['key'];
    if (!empty($aAtts['extra_class'])) {
        $class .= ' '.$aAtts['extra_class'];
    }
    $oAddListingSkeleton = new AddListingFieldSkeleton($post->post_type);
    $isHasTime           = $oAddListingSkeleton->getFieldParam($aAtts['key'], 'fieldGroups->settings->showTimePanel');

    ob_start();
    ?>
    <span class="<?php echo esc_attr($class); ?>">
        <?php echo date_i18n(get_option('date_format'), $timestamp); ?>
        <?php if ($isHasTime === 'yes') {
            echo ' '.date_i18n(get_option('time_format'), $timestamp);
        }
        ?>
   </span>
    <?php
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
}

add_shortcode('wilcity_render_date_time_field', 'wilcityRenderDateTimeField');
