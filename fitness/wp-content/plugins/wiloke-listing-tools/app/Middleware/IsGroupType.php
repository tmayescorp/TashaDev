<?php

namespace WilokeListingTools\Middleware;


use WilokeListingTools\Framework\Helpers\General;
use WilokeListingTools\Framework\Routing\InterfaceMiddleware;

class IsGroupType implements InterfaceMiddleware
{
    public $msg;

    public function handle(array $aOptions)
    {
        $this->msg = esc_html__('This post type does not exist', 'wiloke-listing-tools');

        $groupType = General::getPostTypeGroup(get_post_type($aOptions['postID']));

        if ($groupType != $aOptions['groupType']) {
            return false;
        }
        return true;
    }
}
