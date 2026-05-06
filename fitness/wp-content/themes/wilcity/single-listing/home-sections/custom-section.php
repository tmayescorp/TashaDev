<?php global $wilcityArgs;
$scKey = str_replace('wilcity_single_navigation_', '', $wilcityArgs['key']);

if (empty($wilcityArgs['content'])) {
    return '';
}
$content = \WilokeListingTools\Frontend\SingleListing::parseCustomFieldSC($wilcityArgs['content'], $scKey);
$content = do_shortcode(stripslashes($content));
if (!empty($typeClass)) {
    $scKey .= ' '.$typeClass;
}

if (!empty(trim($content))) :
    ?>
    <div class="content-box_module__333d9 wilcity-single-listing-custom-content-box <?php echo esc_attr($scKey); ?>">
        <?php get_template_part('single-listing/home-sections/section-heading'); ?>
        <div class="content-box_body__3tSRB">
            <div class="row" data-col-xs-gap="10">
                <?php echo $content; ?>
            </div>
        </div>
    </div>
<?php endif; ?>


