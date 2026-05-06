<?php

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\TermSetting;

class WilokeHelpers
{
    public static $aTermByPostID;

    public static function ngettext($singular, $two, $biggerThanTwo, $val)
    {
        $val = abs($val);
        if ($val <= 1) {
            return $singular;
        } else if ($val == 2) {
            return $two;
        } else {
            return $biggerThanTwo;
        }
    }

    public static function pagination($wp_query)
    {
        ?>
        <?php if (!empty($wp_query->max_num_pages)) : ?>
        <nav>
            <?php
            $big = 999999999; // need an unlikely integer
            echo paginate_links([
                'base'               => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                'format'             => '?paged=%#%',
                'current'            => max(1, get_query_var('paged')),
                'total'              => $wp_query->max_num_pages,
                'before_page_number' => '',
                'prev_next'          => true,
                'prev_text'          => '<i class="la la-angle-left"></i>',
                'next_text'          => '<i class="la la-angle-right"></i>',
                'type'               => 'list'
            ]);
            ?>
        </nav>
    <?php endif; ?>
        <?php
    }

    public static function getTermGradient($oTerm, $setDefault = true)
    {
        $leftBg = GetSettings::getTermMeta($oTerm->term_id, 'left_gradient_bg');
        $rightBg = GetSettings::getTermMeta($oTerm->term_id, 'right_gradient_bg');
        $tiltedDegrees = GetSettings::getTermMeta($oTerm->term_id, 'gradient_tilted_degrees');

        if ((empty($leftBg) || empty($rightBg) || empty($tiltedDegrees)) && $setDefault) {
            $leftBg = empty($leftBg) ? '#006bf7' : $leftBg;
            $rightBg = empty($rightBg) ? '#f06292' : $rightBg;
            $tiltedDegrees = empty($tiltedDegrees) ? -10 : $tiltedDegrees;
        }

        return [
            'leftBg'        => $leftBg,
            'rightBg'       => $rightBg,
            'tiltedDegrees' => $tiltedDegrees
        ];
    }

    /**
     * @param int $postID
     * @param string $taxonomy
     * @param bool $getLastTermOnly
     * @param array $aAtts
     *
     * @return mixed
     */
    public static function getTermByPostID(int $postID, string $taxonomy, bool $getLastTermOnly = true, $aAtts = [])
    {
        $lastTermPrefix = $getLastTermOnly ? 'yes' : 'no';
        $key = $taxonomy . $postID . $lastTermPrefix;

        if (isset($aAtts[$taxonomy]) && !empty($aAtts[$taxonomy])) {
            $oTerm = null;
            if (!is_string($aAtts[$taxonomy])) {
                $oTerm = get_term_by('slug', $aAtts[$taxonomy]);
            } else if (is_numeric($aAtts[$taxonomy])) {
                $oTerm = get_term($aAtts[$taxonomy]);
            }

            if (!empty($oTerm) && !is_wp_error($oTerm)) {
                return apply_filters(
                    'wilcity/filter/wiloke-listing-tools/getTermByPostID',
                    $oTerm,
                    $postID,
                    $taxonomy,
                    $getLastTermOnly
                );
            }
        }

        if (isset(self::$aTermByPostID[$key])) {
            return apply_filters(
                'wilcity/filter/wiloke-listing-tools/getTermByPostID',
                self::$aTermByPostID[$key],
                $postID,
                $taxonomy,
                $getLastTermOnly
            );
        }

        if ($getLastTermOnly) {
            $termID = GetSettings::getPrimaryTermIDOfPost($postID, $taxonomy);
            if (!empty($termID)) {
                self::$aTermByPostID[$key] = get_term($termID, $taxonomy);
            } else {
                self::$aTermByPostID[$key] = false;
            }

            return self::$aTermByPostID[$key];
        }

        $aTerms = wp_get_post_terms($postID, $taxonomy);
        if (empty($aTerms) || is_wp_error($aTerms)) {
            self::$aTermByPostID[$key] = false;
        } else {
            self::$aTermByPostID[$key] = $aTerms;
        }

        return apply_filters(
            'wilcity/filter/wiloke-listing-tools/getTermByPostID',
            self::$aTermByPostID[$key],
            $postID,
            $taxonomy,
            $getLastTermOnly
        );
    }

    public static function getVimeoThumbnail($vimeoID)
    {
        $url = is_ssl() ? 'https://vimeo.com/api/v2/video/' . $vimeoID . '.php' :
            'http://vimeo.com/api/v2/video/' . $vimeoID . '.php';
        $response = wp_remote_get(esc_url_raw($url));
        $aResponse = maybe_unserialize(wp_remote_retrieve_body($response));

        return [
            'thumbnail_small' => $aResponse[0]['thumbnail_small'],
            'thumbnail'       => $aResponse[0]['thumbnail_medium'],
            'thumbnail_large' => $aResponse[0]['thumbnail_large'],
        ];
    }

    /*
     * @param $postID
     * @param $tfKey: Theme Options Key
     */
    public static function getFeaturedImg($postID, $size = 'large', $tfKey = '')
    {
        $featuredImg = get_the_post_thumbnail_url($postID, $size);
        if (!empty($featuredImg)) {
            return apply_filters('wilcity/featured_image_url', $featuredImg);
        }

        global $wiloke;
        if (isset($wiloke->aThemeOptions[$tfKey]) && isset($wiloke->aThemeOptions[$tfKey]['id'])) {
            return wp_get_attachment_image_url($wiloke->aThemeOptions[$tfKey]['id'], $size);
        }

        return '';
    }

    public static function getImgPostMeta($postID, $key, $tfKey = '', $size = 'large')
    {
        $imgID = GetSettings::getPostMeta($postID, $key . '_id');

        if (!empty($imgID)) {
            return wp_get_attachment_image_url($imgID, $size);
        }

        global $wiloke;

        if (isset($wiloke->aThemeOptions[$tfKey]) && !empty($wiloke->aThemeOptions[$tfKey])) {
            return wp_get_attachment_image_url($wiloke->aThemeOptions[$tfKey]['id'], $size);
        }
    }

    public static function getPostMeta($postID, $key, $tfKey = '')
    {
        $aData = GetSettings::getPostMeta($postID, $key);
        if (!empty($aData)) {
            return $aData;
        }

        global $wiloke;
        if (isset($wiloke->aThemeOptions[$tfKey])) {
            return $wiloke->aThemeOptions[$tfKey];
        }

        return '';
    }

    public static function getAttachmentImg($postID, $key, $tfKey = '', $size = 'large')
    {
        $attachmentURL = wp_get_attachment_image_url($postID, $key, $size);

        if (!empty($attachmentURL)) {
            return apply_filters('wilcity/attachment_image_url', $attachmentURL);
        }

        global $wiloke;
        if (!isset($wiloke->aThemeOptions[$tfKey])) {
            return '';
        }

        return wp_get_attachment_image_url($wiloke->aThemeOptions[$tfKey]['id'], $size);
    }

    public static function getTermOriginFocusOnImgIcon($oTerm)
    {
        if (TermSetting::hasRealTermIconImage($oTerm)) {
            return [
                'type' => 'image',
                'url'  => TermSetting::getTermImageIcon($oTerm)
            ];
        }

        if (TermSetting::hasRealTermIconIcon($oTerm)) {
            $iconColor = TermSetting::getTermIconColor($oTerm);
            $iconCode = TermSetting::getTermIconIcon($oTerm);
            return [
                'type'      => 'icon',
                'icon'      => $iconCode,
                'name'      => $iconCode,
                'color'     => $iconColor,
                'iconColor' => $iconColor
            ];
        }

        $aIcon = \WilokeThemeOptions::getOptionDetail($oTerm->taxonomy . '_icon_image');

        if (is_array($aIcon) && isset($aIcon['url']) && !empty($aIcon['url'])) {
            return [
                'type' => 'image',
                'url'  => $aIcon['url']
            ];
        }

        $iconColor = WilokeThemeOptions::getColor($oTerm->taxonomy . '_icon_color');
        $iconCode = WilokeThemeOptions::getOptionDetail($oTerm->taxonomy . '_icon');

        return [
            'type'      => 'icon',
            'icon'      => $iconCode,
            'name'      => $iconCode,
            'color'     => $iconColor,
            'iconColor' => $iconColor
        ];
    }

    public static function getTermOriginFocusOnFontIcon($oTerm): array
    {
        if (TermSetting::hasRealTermIconIcon($oTerm)) {
            $iconColor = TermSetting::getTermIconColor($oTerm);
            $iconCode = TermSetting::getTermIconIcon($oTerm);
            return [
                'type'      => 'icon',
                'icon'      => $iconCode,
                'name'      => $iconCode,
                'color'     => $iconColor,
                'iconColor' => $iconColor
            ];
        }

        if (TermSetting::hasRealTermIconImage($oTerm)) {
            return [
                'type' => 'image',
                'url'  => TermSetting::getTermImageIcon($oTerm)
            ];
        }

        $defaultIcon = WilokeThemeOptions::getOptionDetail($oTerm->taxonomy . '_icon');
        if (!empty($defaultIcon)) {
            $iconColor = WilokeThemeOptions::getColor($oTerm->taxonomy . '_icon_color');

            return [
                'type'      => 'icon',
                'icon'      => $defaultIcon,
                'color'     => $iconColor,
                'iconColor' => $iconColor
            ];
        }

        $aIcon = \WilokeThemeOptions::getOptionDetail($oTerm->taxonomy . '_icon_image');

        if (is_array($aIcon) && isset($aIcon['url']) && !empty($aIcon['url'])) {
            return [
                'type' => 'image',
                'url'  => $aIcon['url']
            ];
        }

        return [
            'type'      => 'icon',
            'icon'      => 'la la-image',
            'color'     => '',
            'iconColor' => ''
        ];
    }

    /**
     * @param      $oTerm
     * @param bool $isFocusOnImgIcon
     *
     * @return array
     */
    public static function getTermOriginalIcon($oTerm, $isFocusOnImgIcon = true)
    {
        if ($isFocusOnImgIcon) {
            return self::getTermOriginFocusOnImgIcon($oTerm);
        }

        return self::getTermOriginFocusOnFontIcon($oTerm);
    }

    /**
     * @param        $oTerm
     * @param string $iconWrapper
     * @param bool $hasLink
     * @param array $query_arg
     *
     * @return string
     */
    public static function getTermIcon($oTerm, $iconWrapper = '', $hasLink = true, $query_arg = [])
    {
        $aIcon = self::getTermOriginalIcon($oTerm, false);
        $termLink = get_term_link($oTerm->term_id);

        if (!empty($query_arg) && is_array($query_arg)) {
            $termLink = add_query_arg($query_arg, $termLink);
        }

        if (isset($aIcon['type']) && $aIcon['type'] == 'image') {
            if ($hasLink) {
                return '<a href="' . esc_url($termLink) . '" title="' . esc_attr($oTerm->name) .
                    '"><div class="bg-transparent ' . esc_attr($iconWrapper) . '"><img src="' . esc_url($aIcon['url']) .
                    '" alt="' . esc_attr($oTerm->name) . '"></div><div class="icon-box-1_text__3R39g">' .
                    esc_html($oTerm->name) . '</div></a>';
            } else {
                return '<img src="' . esc_url($aIcon['url']) . '" alt="' . esc_attr($oTerm->name) . '">';
            }
        }

        if (empty($aIcon)) {
            $aIcon['icon'] = apply_filters('wilcity/' . $oTerm->taxonomy . '/icon', 'la la-file-picture-o');
            $aIcon['color'] = GetSettings::getTermMeta($oTerm->term_id, 'icon_color');
        }

        if ($hasLink) {
            if (!empty($aIcon['color'])) {
                return '<a href="' . esc_url($termLink) . '" title="' . esc_attr($oTerm->name) . '"><div class="' .
                    esc_attr($iconWrapper) . '" style="background-color: ' . esc_attr($aIcon['color']) .
                    '"><i class="' .
                    esc_attr($aIcon['icon']) . '"></i></div><div class="icon-box-1_text__3R39g">' .
                    esc_html($oTerm->name) . '</div></a>';
            } else {
                return '<a href="' . esc_url($termLink) . '" title="' . esc_attr($oTerm->name) . '"><div class="' .
                    esc_attr($iconWrapper) . '"><i class="' . esc_attr($aIcon['icon']) .
                    '"></i></div><div class="icon-box-1_text__3R39g">' . esc_html($oTerm->name) . '</div></a>';
            }

        } else {
            if (!empty($iconColor)) {
                return '<div class="' . esc_attr($iconWrapper) . '" style="background-color: ' .
                    esc_attr($aIcon['color']) .
                    '"><i class="' . esc_attr($aIcon['icon']) . '"></i></div>';
            } else {
                return '<div class="' . esc_attr($iconWrapper) . '"><i class="' . esc_attr($aIcon['icon']) .
                    '"></i></div>';
            }
        }
    }

    public static function getTermFeaturedImage($oTerm, $imgSize = 'large')
    {
        $featuredImgID = GetSettings::getTermMeta($oTerm->term_id, 'featured_image_id');
        if (!empty($featuredImgID)) {
            $featuredImg = wp_get_attachment_image_url($featuredImgID, $imgSize);
        }

        if (isset($featuredImg) && !empty($featuredImg)) {
            return $featuredImg;
        }

        $featuredImg = GetSettings::getTermMeta($oTerm->term_id, 'featured_image');

        if (empty($featuredImg)) {
            $aThemeOptions = Wiloke::getThemeOptions();
            switch ($oTerm->taxonomy) {
                case 'listing_location':
                    if (isset($aThemeOptions['listing_location_featured_image']) &&
                        isset($aThemeOptions['listing_location_featured_image']['id'])) {
                        $featuredImg
                            = wp_get_attachment_image_url($aThemeOptions['listing_location_featured_image']['id'],
                            $imgSize);
                    }
                    break;
                case 'listing_cat':
                    if (isset($aThemeOptions['listing_cat_featured_image']) &&
                        isset($aThemeOptions['listing_cat_featured_image']['id'])) {
                        $featuredImg
                            = wp_get_attachment_image_url($aThemeOptions['listing_cat_featured_image']['id'], $imgSize);
                    }
                    break;
                case 'listing_tag':
                    if (isset($aThemeOptions['listing_tag_featured_image']) &&
                        isset($aThemeOptions['listing_tag_featured_image']['id'])) {
                        $featuredImg
                            = wp_get_attachment_image_url($aThemeOptions['listing_tag_featured_image']['id'], $imgSize);
                    }
                    break;
                default:
                    if (isset($aThemeOptions['listing_featured_image']) &&
                        isset($aThemeOptions['listing_featured_image']['id'])) {
                        $featuredImg
                            = wp_get_attachment_image_url($aThemeOptions['listing_featured_image']['id'], $imgSize);
                    }
                    break;
            }
        }

        return $featuredImg;
    }
}
