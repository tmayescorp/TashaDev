<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Framework\Routing\Controller;
use WilokeListingTools\Framework\Store\Session;

class NoticeController extends Controller
{
    public function __construct()
    {
        add_action('admin_notices', [$this, 'shouldNotActiveAllPageBuilder']);
        add_action('admin_notices', [$this, 'shouldNotUseUTCTimezone']);
        add_action('wilcity/after-open-body', [$this, 'shouldNotActiveAllPageBuilder']);
        add_action('wilcity/after-open-body', [$this, 'printTopNotifications']);
    }

    public function shouldNotUseUTCTimezone()
    {
        $timezone = get_option('timezone_string');

        if (empty($timezone) || strpos($timezone, 'UTC') !== false) {
            ?>
            <div class="notice notice-error" style="padding: 20px; border-left:  4px solid #dc3232; color: red;">
                Please use Timezone String instead of UTC timezone offset: Settings -> General -> Timezone.
            </div>
            <?php
        }
    }

    public function printTopNotifications()
    {
        $aTopNotifications = Session::getSession('top-notifications');
        if (empty($aTopNotifications) || !is_array($aTopNotifications)) {
            return false;
        }

        foreach ($aTopNotifications as $order => $aNotifications) {
            ?>
            <div class="wilcity-top-notifications" style="padding: 20px; border-left:  4px solid #dc3232;">
                <ul>
                    <li class="<?php echo esc_attr($aNotifications['type']); ?>"><?php echo \Wiloke::ksesHTML($aNotifications['msg']); ?></li>
                </ul>
            </div>
            <?php
            if (isset($aNotifications['numberOfDisplays'])) {
                $total = absint($aNotifications['numberOfDisplays']) - 1;
                if ($total < 1) {
                    unset($aTopNotifications[$order]);
                } else {
                    $aTopNotifications[$order]['numberOfDisplays'] = $total;
                }
            }
        }

        if (!empty($aTopNotifications)) {
            Session::setTopOfNotifications($aTopNotifications);
        } else {
            Session::destroySession('top-notifications');
        }
    }

    public function shouldNotActiveAllPageBuilder()
    {
        if (!class_exists('Vc_Manager')) {
            return '';
        }

        if (class_exists('KingComposer') && defined('ELEMENTOR_VERSION')) {
            if (!is_admin() && !current_user_can('administrator')) {
                return '';
            }
            ?>
            <div class="notice notice-error" style="padding: 20px; border-left:  4px solid #dc3232; color: red;">
                You SHOULD NOT activate all page builders at the same time. We recommend choosing 1 page builder that is
                family with you and disable the rest. Please read <a
                    href="https://documentation.wilcity.com/knowledgebase/wilcity-page-builder/" target="_blank"
                    style="color: red;">Wilcity Page Builder</a> to know more.
            </div>
            <?php
        }
    }
}
