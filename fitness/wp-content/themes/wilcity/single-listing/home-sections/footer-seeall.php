<?php
use Wilcity\Ultils\ListItems\WilSwitchTabBtn;
global $wilcityArgs, $wilcityTabKey, $wilParentPost;
if (!isset($wilcityArgs['status']) || $wilcityArgs['status'] === 'no') {
    return '';
}

$oSwitchTabBtn = new WilSwitchTabBtn;

if ($wilParentPost instanceof WP_Post) {
    $post = $wilParentPost;
}

$oSwitchTabBtn = $oSwitchTabBtn->setConfiguration([
    'type'              => 'wilSwitchTabBtn',
    'tabKey'            => $wilcityTabKey,
    'wrapperClasses'    => 'list_link__2rDA1 text-ellipsis color-primary--hover wil-text-center',
    'btnName'           => esc_html__('See All', 'wilcity'),
    'hasWrapperForIcon' => 'no'
]);

try {
    echo '<footer class="content-box_footer__kswf3">'.$oSwitchTabBtn->render().'</footer>';
} catch (Exception $e) {
}
