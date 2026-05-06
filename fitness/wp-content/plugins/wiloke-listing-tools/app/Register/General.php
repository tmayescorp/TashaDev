<?php

namespace WilokeListingTools\Register;

class General
{
    public function __construct()
    {
        add_action('wp_ajax_get_posts_by_post_types', [$this, 'getPosts']);
    }
    
    public function getPosts()
    {
        $aPostTypes = explode(',', $_GET['postTypes']);
        
        $query = new \WP_Query([
            'post_type'      => $aPostTypes,
            'post_status'    => 'publish',
            's'              => $_GET['s'],
            'posts_per_page' => 30
        ]);
        
        if (!$query->have_posts()) {
            wp_send_json_error();
        }
        
        $aResponses = [];
        
        while ($query->have_posts()) {
            $query->the_post();
            $aResponses[] = [
                'value' => $query->post->ID,
                'name'  => $query->post->post_title,
                'text'  => $query->post->post_title,
            ];
        }
        wp_reset_postdata();
        
        echo json_encode([
            'success' => true,
            'results' => $aResponses
        ]);
        die();
    }
}
