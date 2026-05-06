<?php

namespace WilcityWidgets\App;

use WILCITY_SC\SCHelpers;
use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;

class ListingBelongsToEvent extends \WP_Widget
{
	public $aDef = ['title' => '', 'img_size' => 'medium'];

	public function __construct()
	{
		parent::__construct('wilcity_listing_belongs_to_event', WILCITY_WIDGET . ' Listing Belongs To Event');
	}

	public function form($aInstance)
	{
		$aInstance = wp_parse_args($aInstance, $this->aDef);
		?>
        <div class="widget-group">
            <label for="<?php echo $this->get_field_id('title'); ?>">Title</label>
            <input type="text" class="widefat" name="<?php echo $this->get_field_name('title'); ?>"
                   id="<?php echo $this->get_field_id('title'); ?>"
                   value="<?php echo esc_attr($aInstance['title']); ?>">
        </div>

        <div class="widget-group">
            <label for="<?php echo $this->get_field_id('img_size'); ?>">Image Size</label>
            <input type="text" class="widefat" name="<?php echo $this->get_field_name('img_size'); ?>"
                   id="<?php echo $this->get_field_id('img_size'); ?>"
                   value="<?php echo esc_attr($aInstance['img_size']); ?>">
            <p>EG: Enter in image size key like large, medium, thumbnail</p>
        </div>
		<?php
	}

	public function widget($aAtts, $aInstance)
	{
		global $post;
		if (!General::isPostTypeInGroup($post->post_type, 'event')) {
			return false;
		}

		$postParent = wp_get_post_parent_id($post->ID);
		$aPostTypes = General::getPostTypeKeys(false, true);
		if (empty($postParent) || !in_array(get_post_type($postParent), $aPostTypes)) {
			return '';
		}

		$aArgs = [
			'p'           => $postParent,
			'post_status' => 'publish',
			'post_type'   => $aPostTypes

		];
		$query = new \WP_Query($aArgs);
		if (!$query->have_posts()) {
			wp_reset_postdata();

			return '';
		}

		$aInstance['img_size'] = SCHelpers::parseImgSize($aInstance['img_size']);

		echo $aAtts['before_widget'];
		if (!empty($aInstance['title'])) : echo $aAtts['before_title']; ?>
            <i class="la la-th-list"></i><span><?php echo esc_html($aInstance['title']); ?>
			<?php echo $aAtts['after_title']; endif; ?>
        <div class="widget-post">
            <?php
            while ($query->have_posts()) {
	            $query->the_post();
	            wilcity_render_grid_item($query->post, [
		            'img_size' => $aInstance['img_size'],
		            'isSlider' => false,
		            'style'    => 'grid'
	            ]);
            }
            wp_reset_postdata();
            ?>
        </div>
		<?php
		echo $aAtts['after_widget'];
	}

	public function update($aNewInstance, $aOldInstance)
	{
		$aInstance = $aOldInstance;
		foreach ($aNewInstance as $key => $val) {
			$aInstance[$key] = strip_tags($val);
		}

		return $aInstance;
	}
}
