<?php
namespace WILCITY_SC\Controllers;

class KCFrontEndController
{
    public function __construct()
    {
        add_filter('the_content', [$this, 'resolveKCShortcodeIssue'], 10, 1);
    }
    
    public function resolveKCShortcodeIssue($content)
    {
        global $post;
        if (!function_exists('kc_do_shortcode') || empty($post)) {
            return $content;
        }
    
        if (get_post_meta($post->ID, '_elementor_edit_mode', true) === 'builder') {
            return $content;
        }
        
        if (strpos($content, 'kc-elm') === false) {
            return $content;
        }
        
        $rawContent = get_post_field('post_content_filtered', $post->ID);
        if (empty($rawContent)) {
            return $content;
        }
        
        return kc_do_shortcode($rawContent);
    }
}
