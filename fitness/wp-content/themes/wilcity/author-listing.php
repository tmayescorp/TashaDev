<?php
get_header();
?>
    <!-- Content-->
    <div id="<?php echo esc_attr(apply_filters('wilcity/filter/id-prefix', 'wilcity-author-listing')); ?>"
         class="wil-content">
        <div class="author-hero_module__1u4Pt">
            <div class="author-hero_wrap__KG0cu">
                <?php get_template_part('author-listing/header-image'); ?>
                <?php get_template_part('author-listing/author-info'); ?>
            </div>
            <?php get_template_part('author-listing/navigation'); ?>
        </div>


        <section class="wil-section bg-color-gray-2 pt-30">
            <div class="container">
                <?php
                $mode = get_query_var('mode');
                $mode = empty($mode) ? 'about' : $mode;

                switch ($mode) {
                    case 'about':
                        get_template_part('author-listing/about');
                        break;
                    case 'event':
                        get_template_part('author-listing/events');
                        break;
                    default:
                        if (has_action('wilcity/author-listing/'.$mode)) {
                            do_action('wilcity/author-listing/'.$mode);
                        } else {
                            get_template_part('author-listing/listings');
                        }
                        break;
                }
                ?>
            </div>
        </section>

    </div>
<?php
do_action('wilcity/before-close-root');
get_footer();
