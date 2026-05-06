<?php

namespace WilokeListingTools\Middleware;


use WilokeListingTools\Framework\Routing\InterfaceMiddleware;

class IsPublishedPost implements InterfaceMiddleware {
	public $msg;
	public function handle( array $aOptions ) {
        if (empty($aOptions['postID'])){
            $this->msg = esc_html__('Oops! This post does not exist!', 'wiloke-listing-tools');
            return false;
        }

        if ( (get_post_field('post_status', $aOptions['postID']) != 'publish') ){
            $this->msg = esc_html__('Oops! This post does not exist!', 'wiloke-listing-tools');
            return false;
        }

        return true;
	}
}
