<?php
function wilcityRenderBoxIcon1($aAtts)
{
    $aAtts = shortcode_atts(
      [
        'wrapper_classes' => 'icon-box-1_module__uyg5F',
        'inner_wrapper'   => 'icon-box-1_block1__bJ25J',
        'content_classes' => 'icon-box-1_text__3R39g',
        'icon_classes'    => 'icon-box-1_icon__3V5c0',
        'icon'            => 'la la-leaf',
        'margin'          => 'mb-10',
        'icon_style'      => 'rounded-circle',
        'name'            => '',
        'link'            => '',
        'target'          => '_self',
        'color'           => ''
      ],
      $aAtts
    );
    
    $wrapperClasses = $aAtts['wrapper_classes'].' '.$aAtts['margin'];
    $iconClasses    = $aAtts['icon_classes'].' '.$aAtts['icon_style'];
    ob_start();
    ?>
    <div class="<?php echo esc_attr($wrapperClasses); ?>">
        <div class="<?php echo esc_attr($aAtts['inner_wrapper']); ?>">
            <?php if (!empty($aAtts['icon'])) : ?>
                <?php if (empty($aAtts['color'])) : ?>
                    <div class="<?php echo esc_attr($iconClasses); ?>">
                        <i class="<?php echo esc_attr($aAtts['icon']); ?>"></i>
                    </div>
                <?php else: ?>
                    <div style="color: <?php echo esc_attr($aAtts['color']); ?>"
                         class="<?php echo esc_attr($iconClasses); ?>">
                        <i class="<?php echo esc_attr($aAtts['icon']); ?>"></i>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="<?php echo esc_attr($aAtts['content_classes']); ?>">
                <?php if (!empty($aAtts['link'])): ?>
                    <a target="<?php echo esc_attr($aAtts['target']); ?>" href="<?php echo esc_url($aAtts['link']); ?>">
                        <?php echo esc_html($aAtts['name']); ?>
                    </a>
                <?php else: ?>
                    <?php echo esc_html($aAtts['name']); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php
    $content = ob_get_contents();
    ob_end_clean();
    
    return $content;
}

add_shortcode('wilcity_render_box_icon1', 'wilcityRenderBoxIcon1');
