<?php
function wilcityRenderListFeatureItem($aOption)
{
    $color = preg_replace('/\b\s+\b/', ',', $aOption['oIcon']['color']);
    ?>
    <?php if (empty($aOption['oIcon']['color'])) : ?>
    <div class="icon-box-1_icon__3V5c0 rounded-circle">
     
        <i style="color: #fff" class="<?php echo esc_attr($color)?>"></i>
    </div>
<?php else: ?>
    <div class="icon-box-1_icon__3V5c0 rounded-circle">
        <i style="color: <?php echo esc_attr($color)?>"
           class="<?php echo esc_attr($aOption['oIcon']['icon']); ?>"></i>
    </div>
<?php endif;
}

function wilcityListFeaturesSC($atts)
{
    $aAtts = shortcode_atts(
      [
        'options'         => '',
        'wrapper_classes' => 'col-sm-4  col-sm-4-clear',
        'extra_class'     => '',
        'return_format'   => 'html'
      ],
      $atts
    );
    
    if (empty($aAtts['options'])) {
        return '';
    }
    
    $aOptions = json_decode($aAtts['options'], true);
    $aOptions = apply_filters('wilcity/wilcity-shortcodes/filter/list-features', $aOptions, $aAtts);
    
    if (empty($aOptions)) {
        return '';
    }
    
    if (isset($aAtts['returnFormat']) && $aAtts['returnFormat'] === 'json') {
        return json_encode($aOptions);
    }
    
    if (has_filter('wilcity/filter/wilcity-shortcodes/wilcity-list-features/output')) {
        $output = apply_filters(
          'wilcity/filter/wilcity-shortcodes/wilcity-list-features/output',
          '',
          $aOptions,
          $atts
        );
        
        if ($output !== 'continue') {
            echo $output;
            
            return true;
        }
    }
    
    $class = 'row';
    $class .= ' '.$aAtts['extra_class'];
    ?>
    <div class="<?php echo esc_attr(apply_filters('wilcity/filter/wilcity-shortcodes/wilcity-list-features/row',
      $class, $aOptions));
    ?>">
        <?php foreach ($aOptions as $aOption) : ?>
            <div class="<?php echo esc_attr($aAtts['wrapper_classes']); ?>">
                <div class="icon-box-1_module__uyg5F three-text-ellipsis mt-20 mt-sm-15">
                    <div class="icon-box-1_block1__bJ25J">
                        <?php if (!empty($aOption['oIcon']['icon'])) : ?>
                            <?php wilcityRenderListFeatureItem($aOption); ?>
                        <?php endif; ?>
                        <?php if (!isset($aOption['unChecked']) || $aOption['unChecked'] == 'no') : ?>
                            <div class="icon-box-1_text__3R39g"><?php echo html_entity_decode(esc_html($aOption['name'])); ?></div>
                        <?php else: ?>
                            <div class="icon-box-1_text__3R39g un-checked"
                                 style="text-decoration: line-through"><?php echo html_entity_decode(esc_html
                                ($aOption['name'])); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

add_shortcode('wilcity_list_features', 'wilcityListFeaturesSC');
