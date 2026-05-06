<?php

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\General;
use WILCITY_SC\SCHelpers;

function wilcityRenderImageField($aAtts)
{
    $aAtts = shortcode_atts(
      [
        'post_id'     => '',
        'key'         => '',
        'is_mobile'   => '',
        'description' => '',
        'extra_class' => '',
        'title'       => ''
      ],
      $aAtts
    );

    if (!empty($aAtts['post_id'])) {
        $post = get_post($aAtts['post_id']);
    } else {
        $post = SCHelpers::getPost();
    }

	if (apply_filters(
		'wilcity-shortcodes/default-sc/wilcity-render-image-field/hide-item',
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

    $imgID = GetSettings::getPostMeta($post->ID, 'custom_'.$aAtts['key'].'_image_id'); // it's _id before

    if (empty($imgID)) {
        $url   = GetSettings::getPostMeta($post->ID, 'custom_'.$aAtts['key'].'_id');
        $title = $post->post_title;
        if (empty($url)) {
            return '';
        }
    } else {
        $title = get_post_field('post_title', $imgID);
        $url   = wp_get_attachment_image_url($imgID, 'large');
    }

    $class = $aAtts['key'];
    if (!empty($aAtts['extra_class'])) {
        $class .= ' '.$aAtts['extra_class'];
    }

    $link = GetSettings::getPostMeta($post->ID, 'custom_'.$aAtts['key'].'_link_to');

    if (empty($link)) {
        return '<img class="'.esc_attr($class).'" src="'.esc_url($url).'" alt="'.esc_attr($title).'">';
    }

    $rel    = 'nofollow';
    $target = '_blank';
    if (strpos($link, home_url('/')) !== false) {
        $rel    = 'dofollow';
        $target = '_self';
    }
    ob_start();
    ?>
    <a target="<?php echo $target; ?>" rel="<?php echo $rel; ?>" href="<?php echo esc_url($link); ?>">
        <img class="<?php echo esc_attr($class) ?>"
             src="<?php echo esc_url($url); ?>"
             alt="<?php echo esc_attr($title); ?>"/>
    </a>
    <?php
    return ob_get_clean();
}

add_shortcode('wilcity_render_image_field', 'wilcityRenderImageField');
