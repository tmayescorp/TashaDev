<?php
if (!defined('ABSPATH')) {
    exit;
}

class WilokeSocialNetworks
{
    static public $aSocialNetworks
        = [
            'facebook',
            'twitter',
            'tumblr',
            'vk',
            'odnoklassniki',
            'youtube',
            'vimeo',
            'rutube',
            'dribbble',
            'instagram',
            'flickr',
            'pinterest',
            'medium',
            'tripadvisor',
            'wikipedia',
            'stumbleupon',
            'livejournal',
            'linkedin',
            'skype',
            'bloglovin',
            'whatsapp',
            'soundcloud',
            'line',
            'spotify',
            'strava',
            'yelp',
            'snapchat',
            'telegram',
            'tiktok',
            'discord',

        ];
    static public $aSocialNetworksFull
        = [
            'facebook'      => [
                'label'    => 'Facebook',
                'labelUrl' => 'Facebook Url',
                'id'       => 'facebook',
                'icon'     => ''
            ],
            'twitter'       => [
                'label'    => 'Twitter',
                'labelUrl' => 'Twitter Url',
                'id'       => 'twitter',
                'icon'     => ''
            ],
            'tumblr'        => [
                'label'    => 'Tumblr',
                'labelUrl' => 'Tumblr Url',
                'id'       => 'tumblr',
                'icon'     => ''
            ],
            'vk'            => [
                'label'    => 'VK',
                'labelUrl' => 'VK Url',
                'id'       => 'vk',
                'icon'     => ''
            ],
            'odnoklassniki' => [
                'label'    => 'Odnoklassniki',
                'labelUrl' => 'Odnoklassniki Url',
                'id'       => 'odnoklassniki',
                'icon'     => ''
            ],
            'youtube'       => [
                'label'    => 'Youtube',
                'labelUrl' => 'Youtube Url',
                'id'       => 'youtube',
                'icon'     => ''
            ],
            'vimeo'         => [
                'label'    => 'Vimeo',
                'labelUrl' => 'Vimeo Url',
                'id'       => 'vimeo',
                'icon'     => ''
            ],
            'rutube'        => [
                'label'    => 'Rutube',
                'labelUrl' => 'Rutube Url',
                'id'       => 'rutube',
                'icon'     => ''
            ],
            'dribbble'      => [
                'label'    => 'Dribbble',
                'labelUrl' => 'Dribbble Url',
                'id'       => 'dribbble',
                'icon'     => ''
            ],
            'instagram'     => [
                'label'    => 'Instagram',
                'labelUrl' => 'Instagram Url',
                'id'       => 'instagram',
                'icon'     => ''
            ],
            'flickr'        => [
                'label'    => 'Flickr',
                'labelUrl' => 'Flickr Url',
                'id'       => 'flickr',
                'icon'     => ''
            ],
            'pinterest'     => [
                'label'    => 'Pinterest',
                'labelUrl' => 'Pinterest Url',
                'id'       => 'pinterest',
                'icon'     => ''
            ],
            'medium'        => [
                'label'    => 'Medium',
                'labelUrl' => 'Medium Url',
                'id'       => 'medium',
                'icon'     => ''
            ],
            'tripadvisor'   => [
                'label'    => 'Tripadvisor',
                'labelUrl' => 'Tripadvisor Url',
                'id'       => 'tripadvisor',
                'icon'     => ''
            ],
            'wikipedia'     => [
                'label'    => 'Wikipedia',
                'labelUrl' => 'Wikipedia Url',
                'id'       => 'wikipedia',
                'icon'     => ''
            ],
            'stumbleupon'   => [
                'label'    => 'Stumbleupon',
                'labelUrl' => 'Stumbleupon Url',
                'id'       => 'stumbleupon',
                'icon'     => ''
            ],
            'livejournal'   => [
                'label'    => 'Livejournal',
                'labelUrl' => 'Livejournal Url',
                'id'       => 'livejournal',
                'icon'     => ''
            ],
            'linkedin'      => [
                'label'    => 'Linkedin',
                'labelUrl' => 'Linkedin Url',
                'id'       => 'linkedin',
                'icon'     => ''
            ],
            'skype'         => [
                'label'    => 'Skype',
                'labelUrl' => 'Skype',
                'id'       => 'skype',
                'icon'     => ''
            ],
            'bloglovin'     => [
                'label'    => 'Bloglovin',
                'labelUrl' => 'Bloglovin Url',
                'id'       => 'bloglovin',
                'icon'     => 'fa fa-heart'
            ],
            'whatsapp'      => [
                'label'    => 'Whatsapp',
                'labelUrl' => 'Whatsapp Url',
                'id'       => 'whatsapp',
                'icon'     => ''
            ],
            'soundcloud'    => [
                'label'    => 'Soundcloud',
                'labelUrl' => 'Soundcloud Url',
                'id'       => 'soundcloud',
                'icon'     => ''
            ],
            'line'          => [
                'label'    => 'Line',
                'labelUrl' => 'Line Url',
                'id'       => 'line',
                'icon'     => ''
            ],
            'spotify'       => [
                'label'    => 'Spotify',
                'labelUrl' => 'Spotify Url',
                'id'       => 'spotify',
                'icon'     => ''
            ],
            'yelp'          => [
                'label'    => 'Yelp',
                'labelUrl' => 'Yelp Url',
                'id'       => 'yelp',
                'icon'     => ''
            ],
            'snapchat'      => [
                'label'    => 'Snapchat',
                'labelUrl' => 'Snapchat Url',
                'id'       => 'snapchat',
                'icon'     => ''
            ],
            'telegram'      => [
                'label'    => 'Telegram',
                'labelUrl' => 'Telegram Url',
                'id'       => 'telegram',
                'icon'     => ''
            ],
            'tiktok'        => [
                'label'    => 'Tiktok',
                'labelUrl' => 'Tiktok Url',
                'id'       => 'tiktok',
                'icon'     => ''
            ],
            'discord'       => [
                'label'    => 'Discord',
                'labelUrl' => 'Discord Url',
                'id'       => 'discord',
                'icon'     => ''
            ],
            'strava'        => [
                'label'    => 'Strava',
                'labelUrl' => 'Strava Url',
                'id'       => 'strava',
                'icon'     => ''
            ],
        ];

    function __constructor()
    {
    }

    static public function createMetaboxConfiguration($prefix)
    {
        $aSettings = [];

        foreach (self::$aSocialNetworks as $social) {
            if ($social == 'google-plus') {
                $name = 'Google+';
            } else {
                $name = ucfirst($social);
            }

            $aSettings[] = [
                'name' => $name,
                'id'   => $prefix . $social,
                'type' => 'text_url'
            ];
        }

        return $aSettings;
    }

    static function getExcludeNetworks()
    {
        $aThemeOptions = Wiloke::getThemeOptions(true);
        if (!isset($aThemeOptions['wiloke_exclude_social_networks']) ||
            empty($aThemeOptions['wiloke_exclude_social_networks'])) {
            return false;
        }

        $aParse = explode(',', $aThemeOptions['wiloke_exclude_social_networks']);

        return array_map(function ($val) {
            return strtolower(trim($val));
        }, $aParse);
    }

    public static function getPickupSocialOptions()
    {
        $aExcludes = self::getExcludeNetworks();
        if (empty($aExcludes)) {
            return array_values(self::$aSocialNetworksFull);
        }

        foreach ($aExcludes as $social) {
            unset(self::$aSocialNetworksFull[$social]);
        }

        return array_values(self::$aSocialNetworksFull);
    }

    static function getUsedSocialNetworks()
    {
        $aExclude = self::getExcludeNetworks();
        self::$aSocialNetworks = apply_filters('wilcity/filter/social/networks', self::$aSocialNetworks);
        if (!empty($aExclude)) {
            self::$aSocialNetworks = array_diff(self::$aSocialNetworks, $aExclude);
        }

        return self::$aSocialNetworks;
    }

    static public function render_setting_field()
    {
        $aSocials = [];

        $aSocials[] = [
            'id'          => 'wiloke_exclude_social_networks',
            'type'        => 'text',
            'title'       => 'Exclude Social Networks',
            'subtitle'    => 'The social networks that are listed in this field will not be displayed on the front-page.',
            'description' => 'Each social network is seperated by a comma. For example: facebook,twitter,google-plus.',
            'default'     => ''
        ];

        self::getUsedSocialNetworks();

        foreach (self::$aSocialNetworks as $key) {
            if ($key == 'google-plus') {
                $socialName = 'Google+';
            } else {
                $socialName = ucfirst($key);
            }
            $key = 'social_network_' . $key;

            $aSocials[] = [
                'id'       => $key,
                'type'     => 'text',
                'title'    => $socialName,
                'subtitle' => esc_html__('Social icon will not display if you leave empty', 'wilcity'),
                'default'  => ''
            ];
        }

        return $aSocials;
    }

    static public function render_socials($aData, $separated = '')
    {
        global $wiloke;
        if (empty($aData)) {
            return;
        }

        if (!empty($separated)) {
            ob_start();
        }

        foreach (self::$aSocialNetworks as $key) {
            $icon = $key;
            if ($icon == 'bloglovin') {
                $icon = 'heart';
            }

            $socialIcon = 'fa fa-' . str_replace('_', '-', $icon);

            $key = 'social_network_' . $key;
            if (isset($wiloke->aThemeOptions[$key]) && !empty($wiloke->aThemeOptions[$key])) {
                $separated = isset($last) && $last == $key ? '' : $separated;
                do_action('wiloke_hook_before_render_social_network');
                if (has_filter('wiloke_filter_social_network')) {
                    echo apply_filters('wiloke_filter_social_network', $wiloke->aThemeOptions[$key], $socialIcon,
                        $separated);
                } else {
                    ?>
                <a class="<?php echo esc_attr($aData['linkClass']); ?>"
                   href="<?php echo esc_url($wiloke->aThemeOptions[$key]); ?>"
                   rel="noopener"
                   rel="noreferrer"
                   target="_blank"><i class="<?php echo esc_attr($socialIcon); ?>"></i>
                    </a><?php echo esc_html($separated); ?>
                    <?php
                }
                do_action('wiloke_hook_after_render_social_network');
            }
        }

        if (!empty($separated)) {
            $content = ob_get_contents();
            ob_end_clean();
            $content = rtrim($content, $separated);
            Wiloke::ksesHTML($content);
        }
    }
}

new WilokeSocialNetworks();
