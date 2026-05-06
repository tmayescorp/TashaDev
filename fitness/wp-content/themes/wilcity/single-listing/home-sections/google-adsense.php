<?php global $wilcityArgs;
$content = do_shortcode("[wilcity_google_adsense]");

if (!empty($content)) :
    ?>
    <div class="content-box_module__333d9 wilcity-single-listing-ads-box">
        <?php if ($wilcityArgs['isShowBoxTitle'] == 'yes') {
            get_template_part('single-listing/home-sections/section-heading');
        } ?>
        <div class="content-box_body__3tSRB">
            <div class="row" data-col-xs-gap="10">
                <?php echo $content; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
