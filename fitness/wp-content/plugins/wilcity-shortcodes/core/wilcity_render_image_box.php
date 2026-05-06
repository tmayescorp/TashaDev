<?php
function wilcityRenderImageBoxSC($aAtts)
{

    global $pagenow;
    $inEditPage = $pagenow == 'post.php';

    $aAtts = wp_parse_args(
      $aAtts,
      [
        'wrapper_classes'         => 'textbox-6_module__9K7Kn wil-flex-column-between',
        'link'                    => '#',
        'bg_img'                  => '',
        'bg_classes'              => 'textbox-6_background__2yJHo bg-cover',
        'content_classes'         => 'textbox-6_content__2enu3',
        'heading'                 => '',
        'heading_wrapper_classes' => 'textbox-6_content__2enu3',
        'heading_classes'         => 'textbox-6_title__37ap8',
        'desc_classes'            => 'textbox-6_description__2HKbM',
        'desc'                    => ''
      ]
    );

    ?>
    <div class="textbox-6_module__9K7Kn wil-flex-column-between">
        <a href="<?php echo !$inEditPage ? esc_url($aAtts['link']) : '#' ?>" rel="<?php echo \WILCITY_SC\SCHelpers::getLinkRel
        ($aAtts['link']); ?>">
            <?php \WILCITY_SC\SCHelpers::renderLazyLoad($aAtts['bg_img'], [
              'divClass' => $aAtts['bg_classes'],
              'imgClass' => '',
              'alt'      => $aAtts['heading']
            ]); ?>

            <?php
            \WILCITY_SC\SCHelpers::renderText(
              [
                'heading'      => $aAtts['heading'],
                'desc'         => $aAtts['desc'],
                'divClass'     => $aAtts['heading_wrapper_classes'],
                'headingClass' => $aAtts['heading_classes'],
                'descClass'    => $aAtts['desc_classes'],
              ]
            );
            ?>
        </a>
    </div>
    <?php
}
