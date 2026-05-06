<?php
global $post;
$msg = '';
$instaId = '';

if (defined('WILOKE_INSTAFEEDHUB_VERSION')) {
    $instaId = \WilokeInstagramFeedhub\Helpers\InstafeedHub::getInstaId();
    if (empty($instaId)) {
        $msg = esc_html__('Please setup Instafeed Hub for this block', 'wilcity');
        if (!\WilokeInstagramFeedhub\Helpers\InstafeedHub::getInstaSettings($instaId)) {
            $msg = esc_html__('Please log into instafeedhub.com, then Edit this Instagram and click Save button again',
                'wilcity');
            $instaId = '';
        }
    }
} else {
    $msg = esc_html__('Wilcity InstafeedHub plugin is required', 'wilcity');
}
?>

<wil-single-nav-wrapper v-if="currentTab === 'instafeedhub'" :settings="getNavigation('instafeedhub')"
                        id="single-instafeedhub">
    <template v-slot:default="{settings}">
        <wil-single-nav-instafeed-hub :post-id="<?php echo abs($post->ID); ?>"
                                      :insta-id="<?php echo empty($instaId) ? 0 : abs($instaId); ?>"
                                      msg="<?php echo esc_attr($msg); ?>"
                                      :settings="settings"></wil-single-nav-instafeed-hub>
    </template>
</wil-single-nav-wrapper>
