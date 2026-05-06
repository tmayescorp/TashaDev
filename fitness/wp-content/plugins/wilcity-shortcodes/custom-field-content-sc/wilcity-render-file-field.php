<?php

use WILCITY_SC\SCHelpers;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\General;

function wilcityRenderFileField(array $aAtts): string
{
	$aAtts = shortcode_atts(
		[
			'post_id'     => '',
			'key'         => '',
			'is_mobile'   => '',
			'description' => '',
			'extra_class' => '',
			'item_class'  => 'col-xs-6 col-sm-3',
			'title'       => '',
			'sm_gap'      => 10
		],
		$aAtts
	);

	if (!empty($aAtts['post_id'])) {
		$post = get_post($aAtts['post_id']);
	} else {
		$post = SCHelpers::getPost();
	}

	if (apply_filters(
		'wilcity-shortcodes/default-sc/wilcity-render-file-field/hide-item',
		false,
		$aAtts,
		$post->ID
	)) {
		return '';
	}

	if (empty($aAtts['key']) || !class_exists('WilokeListingTools\Framework\Helpers\GetSettings') || empty($post)) {
		return '';
	}

	$aAttachments = GetSettings::getPostMeta($post->ID, 'custom_' . $aAtts['key'] . '_files'); // it's _id before
	if (empty($aAttachments)) {
		return '';
	}

	$wrapperClass = apply_filters('wilcity/filter/class-prefix',
		$aAtts['extra_class'] . ' wilcity-file-' . $aAtts['key']);
	ob_start();
	?>
    <div class="<?php echo esc_attr($wrapperClass); ?>">
        <div class="row" data-col-xs-gap="<?php echo esc_attr($aAtts['xs_gap']); ?>"
             data-col-sm-gap="<?php echo esc_attr($aAtts['sm_gap']); ?>">
			<?php
			if (is_array($aAttachments)) {
				foreach ($aAttachments as $id => $url) {
					$src = wp_get_attachment_url($id);
					if (empty($src)) {
						continue;
					}

					?>
                    <div class="<?php echo esc_attr($aAtts['item_class']); ?>">
                        <div style="position: relative">
                            <a href="<?php echo esc_url($src); ?>" target="_blank" style="position: absolute;
                            z-index: 999; top: 0; left: 0; right: 0; bottom: 0;"></a>
                            <div class="<?php echo esc_attr($aAtts['divClass']); ?>">
                                <embed src="<?php echo esc_url($src); ?>"/>
                            </div>
                        </div>
                    </div>
					<?php
				}
			} else {
                ?>
                <div class="<?php echo esc_attr($aAtts['item_class']); ?>">
                    <div style="position: relative">
                        <a href="<?php echo esc_url($aAttachments); ?>" target="_blank" style="position: absolute;
                            z-index: 999; top: 0; left: 0; right: 0; bottom: 0;"></a>
                        <div class="<?php echo esc_attr($aAtts['divClass']); ?>">
                            <embed src="<?php echo esc_url($aAttachments); ?>"/>
                        </div>
                    </div>
                </div>
                <?php
			}
			?>
        </div>
    </div>
	<?php
	return ob_get_clean();
}

add_shortcode('wilcity_render_file_field', 'wilcityRenderFileField');
