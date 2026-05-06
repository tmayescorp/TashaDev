<?php

namespace WilcityWidgets\App;

class Instagram extends \WP_Widget {
    public $aDef = array('title' => 'Instagram', 'number_of_photos' => 3, 'cache_interval' => '');

	/**
     * Instagram constructor.
     */
	public function __construct()
    {
        $args = array('classname' => 'widget_instagram widget_wiloke_instagram widget_photo', 'description' => '');
        parent::__construct("wiloke_instagram", WILCITY_WIDGET . 'Instagram Feed ', $args);
    }

    /**
     * @return string|void
     * @param array $aInstance
     */
	public function form($aInstance)
	{
		$aInstance            = wp_parse_args( $aInstance, $this->aDef );
		$aInstagramSettings   = get_option('wiloke_instagram_settings');
		if ( isset($aInstagramSettings['access_token']) && !empty($aInstagramSettings['access_token']) )
		{
			?>
			<p>
				<label for="<?php echo $this->get_field_id('title'); ?>">Title</label>
				<input type="text" class="widefat" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" value="<?php echo esc_attr($aInstance['title']); ?>">
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('number_of_photos'); ?>">Number Of Photos</label>
				<input type="text" class="widefat" name="<?php echo $this->get_field_name('number_of_photos'); ?>" id="<?php echo $this->get_field_id('number_of_photos'); ?>" value="<?php echo esc_attr($aInstance['number_of_photos']); ?>">
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('cache_interval'); ?>">Cache Interval</label>
				<input type="text" class="widefat" name="<?php echo $this->get_field_name('cache_interval'); ?>" id="<?php echo $this->get_field_id('cache_interval'); ?>" value="<?php echo esc_attr($aInstance['cache_interval']); ?>">
				<i>Leave empty to clear cache</i>
			</p>
			<?php
		}else{
			?>
			<p>
				<code class="wiloke-help">Instagram Access Token is required. <a target="_blank" href="<?php echo esc_url(admin_url('options-general.php?page=wiloke-instagram')) ?>">Click me to provide it</a></code>
			</p>
			<?php
		}
	}

    /**
     * @return array
     * @param array $aOldinstance
     * @param array $aNewinstance
     */
	public function update($aNewinstance, $aOldinstance)
	{
		$aInstance = $aOldinstance;
		foreach ( $aNewinstance as $key => $val )
		{
			if ( $key == 'number_of_photos' )
			{
				$aInstance[$key] = (int)$val;
			}else{
				$aInstance[$key] = strip_tags($val);
			}
		}

		return $aInstance;
	}

    /**
     * @param array $atts
     * @param array $aInstance
     */
	public function widget( $atts, $aInstance )
	{
		$aInstance                  = wp_parse_args($aInstance, $this->aDef);
		$aInstagramSettings         = get_option('wiloke_instagram_settings');
		$aInstance['access_token']  = isset($aInstagramSettings['access_token']) ? $aInstagramSettings['access_token'] : '';
		$cacheInstagram = null;

		echo $atts['before_widget'];

		if ( !empty($aInstance['title']) )
		{
			echo $atts['before_title'] . esc_html($aInstance['title']) . $atts['after_title'];
		}
		?>
		<div class="widget-gallery">
            <?php
            if (empty($aInstance['access_token'])) {
                if (current_user_can('edit_theme_options')) {
                    echo 'Please config your Instagram';
                }
            } else {
                if (!empty($aInstagramSettings['userid'])) {
                    if (!empty($aInstance['cache_interval'])) {
                        $cacheInstagram = get_transient('wiloke_cache_instagram_' . $aInstagramSettings['userid']);
                    } else {
                        delete_transient('wiloke_cache_instagram_' . $aInstagramSettings['userid']);
                    }
                    if (!empty($cacheInstagram)) {
                        echo $cacheInstagram;
                    } else {
                        $content = $this->parseInstagramFeed(
                            $aInstagramSettings['userid'], $aInstagramSettings['access_token'],
                            $aInstance['number_of_photos']
                        );
                        echo $content;
                        if (!empty($aInstance['cache_interval'])) {
                            set_transient(
                                'wiloke_cache_instagram_' . $aInstagramSettings['userid'], $content,
                                absint($aInstance['cache_interval'])
                            );
                        }
                    }
                }
            }
            ?>
		</div>
		<?php
		echo $atts['after_widget'];
	}

    /**
     * @return string
     * @param     $accessToken
     * @param int $count
     * @param     $userID
     */
	public function parseInstagramFeed($userID, $accessToken, $count=6)
	{
        $aArgs = array(
            'decompress' => false,
            'timeout' => 30,
            'sslverify' => true,
            'limit' => $count
        );

		return $this->getPhotos($userID, $accessToken, $aArgs);
	}

    /**
     * @return string
     * @param $accessToken
     * @param $aArgs
     * @param $userID
     */
	public function getPhotos($userID, $accessToken, $aArgs)
	{
		$url   = 'https://graph.instagram.com/'.$userID.'/media';
        $getInstagram = wp_remote_get(
            add_query_arg(
                wp_parse_args(
                    $aArgs,
                    [
                        'fields' => 'caption,media_url,thumbnail_url,permalink,timestamp,media_type,username',
                        'access_token' => $accessToken,
                        'scope' => 'user_profile,user_media'
                    ]
                ),
                esc_url_raw($url)
            )
        );

		if ( !is_wp_error($getInstagram) )
		{
			$getInstagram = wp_remote_retrieve_body($getInstagram);
			$getInstagram = json_decode($getInstagram);
			if (!empty($getInstagram) )
			{
				$out = '';
				foreach ($getInstagram->data as $oInstagram)
				{
					$caption = isset($oInstagram->caption) ? $oInstagram->caption : 'Instagram';

					$out .= '<div class="widget-gallery__item"><a href="'.esc_url($oInstagram->permalink).'" target="_blank" style="background-image: url('. esc_url($oInstagram->media_url) .')"><img src="'.esc_url($oInstagram->media_url).'" alt="'.esc_attr($caption).'" /></a></div>';
				}

				return $out;
			}
		}

		return '';
	}
}