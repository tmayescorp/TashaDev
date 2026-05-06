<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Frontend\User;

class AuthorPageController extends Controller
{
    public static $aAvailablePostTypes = [];

    public function __construct()
    {
        add_action('init', [$this, 'rewriteAuthorModeUrl'], 10, 0);
        add_filter('query_vars', [$this, 'addModeQueryVar'], 10, 1);
        add_filter('author_rewrite_rules', [$this, 'filterAuthorRewriteRules']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_filter('wpseo_opengraph_image', [$this, 'replaceAvatarUrl']);
    }

    public function replaceAvatarUrl($url)
    {
        if (!is_author()) {
            return $url;
        }
        $oAuthor = get_user_by('slug', get_query_var('author_name'));
        $avatar  = User::getCoverImage($oAuthor->ID);
        if (!empty($avatar)) {
            return $avatar;
        }

        return $url;
    }

    public function enqueueScripts()
    {
        if (!is_author()) {
            return false;
        }

        $postType     = get_query_var('mode');
        $postsPerPage = 9;
        $aArgs        = [
            'post_type'      => $postType,
            'posts_per_page' => $postsPerPage,
            'post_status'    => 'publish',
            'paged'          => get_query_var('paged', 1),
            'author__in'     => [get_query_var('author')]
        ];
        $query        = new \WP_Query($aArgs);

        wp_register_script(
            'wilcity-author',
            get_template_directory_uri().'/assets/production/js/author.min.js',
            ['bundle', 'underscore'],
            WILOKE_LISTING_TOOL_VERSION,
            true
        );

        wp_localize_script('wilcity-author', 'WIL_AUTHOR', [
            'queryArgs' => $aArgs,
            'maxPosts'  => absint($query->found_posts),
            'maxPages'  => absint($query->max_num_pages)
        ]);
        wp_enqueue_script('wilcity-author');
    }

    public static function navigationWrapperClass($currentTab, $tabKey)
    {
        if (strpos($tabKey, '|') !== false) {
            $aTabKeys = explode('|', $tabKey);
            foreach ($aTabKeys as $key) {
                if (($key == 'empty' && empty($currentTab)) || $currentTab == $key) {
                    return 'list_item__3YghP active';
                }
            }
        } else {
            if ($currentTab == $tabKey) {
                return 'list_item__3YghP active';
            }
        }

        return 'list_item__3YghP';
    }

    public static function getAuthorPostTypes($authorID)
    {
        if (isset(self::$aAvailablePostTypes[$authorID])) {
            return self::$aAvailablePostTypes[$authorID];
        }

	    $isIncludedDefaults =  apply_filters('wilcity/filter/getAuthorPostTypes/isIncludedDefault', false);
	    $aPostTypes = General::getPostTypes($isIncludedDefaults);

        foreach ($aPostTypes as $postType => $aInfo) {
            $totalPosts = count_user_posts($authorID, $postType, true);
            if (!empty($totalPosts)) {
                $aInfo['totalPosts']                             = $totalPosts;
                self::$aAvailablePostTypes[$authorID][$postType] = $aInfo;
            }
        }

        if (isset(self::$aAvailablePostTypes[$authorID])) {
            return self::$aAvailablePostTypes[$authorID];
        }

        self::$aAvailablePostTypes[$authorID] = false;

        return self::$aAvailablePostTypes[$authorID];
    }

    public static function getAuthorMode($mode)
    {
        return apply_filters('wilcity/filter_author_mode', $mode);
    }

    public function rewriteAuthorModeUrl()
    {
        add_rewrite_rule('^author/([^/]+)/([^/]+)/?$', 'index.php?author_name=$matches[1]&mode=$matches[2]', 'top');
    }

    public function addModeQueryVar($vars)
    {
        $vars[] = 'mode';

        return $vars;
    }

    public function filterAuthorRewriteRules($rules)
    {
        return array_merge([
            self::getAuthorMode('about').'/([^/]+)/([^/]+)/?$' => 'index.php?author_name=$matches[1]&mode=$matches[2]'
        ], $rules);
    }
}
