<?php
global $post, $wilcityArgs;

use WilokeListingTools\Frontend\SingleListing;

$aParseSC = explode('|', $wilcityArgs['key']);

if (!isset($aParseSC[1])) {
    return '';
}
$scKey = '';
if (isset($wilcityArgs['listing_content']) && !empty($wilcityArgs['listing_content'])) {
    $shortcode = SingleListing::parseCustomFieldSC($wilcityArgs['listing_content'], $aParseSC[0]);
} else {
    $shortcode = '[wilcity_render_'.$aParseSC[1].'_field key="'.$aParseSC[0].'" postID="'.$post->ID.
                 '" wrapper_classes="col-sm-6 col-md-4 col-lg-3"]';
}

ob_start();
echo do_shortcode($shortcode);
$content = ob_get_contents();
ob_end_clean();
if (empty($content)) {
    return '';
}

?>
<div class="content-box_module__333d9">
    <header class="content-box_header__xPnGx clearfix">
        <div class="wil-float-left">
            <h4 class="content-box_title__1gBHS"><i
                    class="<?php echo esc_html($wilcityArgs['icon']); ?>"></i><span><?php echo esc_html($wilcityArgs['name']); ?></span>
            </h4>
        </div>
    </header>
    <div class="content-box_body__3tSRB">
        <?php if (!in_array($aParseSC[1], ['multiple-checkbox', 'select'])) : ?>
        <div class="row" data-col-xs-gap="10">
        <?php endif; ?>
            <?php echo $content; ?>
        <?php if (!in_array($aParseSC[1], ['multiple-checkbox', 'select'])) : ?>
        </div>
        <?php endif; ?>
    </div>
</div>
