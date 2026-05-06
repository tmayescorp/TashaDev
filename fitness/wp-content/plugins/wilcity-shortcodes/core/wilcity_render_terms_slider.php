<?php

use WILCITY_SC\SCHelpers;
use WilokeListingTools\Framework\Helpers\GetSettings;

function wilcity_render_terms_slider($aAtts)
{
    if (!isset($aAtts[$aAtts['taxonomy'].'s']) || empty($aAtts[$aAtts['taxonomy'].'s'])) {
        return '';
    }
    
    $aTermIDs = SCHelpers::getAutoCompleteVal($aAtts[$aAtts['taxonomy'].'s']);
    if (isset($aArgs['include']) && !empty($aArgs['include'])) {
        $aArgs['number'] = count($aArgs['include']);
        unset($aArgs['include']);
    }
    
    $aTerms = get_terms(
      [
        'taxonomy' => $aAtts['taxonomy'],
        'include'  => $aTermIDs,
        'orderby'  => 'include',
        'number'   => count($aTermIDs)
      ]
    );
    
    if (empty($aTerms) || is_wp_error($aTerms)) {
        return '';
    }
    
    $aRandomIcon = ['creative-icon1.png', 'creative-icon2.png', 'creative-icon3.png', 'creative-icon4.png'];
    $aPickupIcon = $aRandomIcon;
    $aItems      = [];
    
    $aItemsOnScreen = [
      'lg' => $aAtts['items_on_lg_screen'],
      'md' => $aAtts['items_on_md_screen'],
      'sm' => $aAtts['items_on_sm_screen']
    ];
    
    foreach ($aTerms as $oTerm) {
        if (empty($aPickupIcon)) {
            $aPickupIcon = $aRandomIcon;
        }
        $randIndex = array_rand($aRandomIcon);
        unset($aPickupIcon[$randIndex]);
        
        $bgImg     = GetSettings::getTermMeta($oTerm->term_id, 'featured_image');
        $slideIcon = GetSettings::getTermMeta($oTerm->term_id, 'slider_icon');
        
        $aItems[] = [
          'title'       => $oTerm->name,
          'link'        => SCHelpers::getTermLink($aAtts, $oTerm),
          'description' => $oTerm->description,
          'bgImg'       => empty($bgImg) ? '' : $bgImg,
          'icon1'       => empty($slideIcon) ? '' : $slideIcon,
          'icon2'       => get_template_directory_uri().'/assets/img/icons/'.$aPickupIcon[$randIndex]
        ];
    }
    ?>
    <div id="<?php echo uniqid('wil-slider-') ?>"
         class="wil-slider"
         data-items="<?php echo base64_encode(json_encode($aItems)); ?>"
         data-items-per-row="<?php echo esc_attr(base64_encode(json_encode($aItemsOnScreen))); ?>">
        <wil-slider :items="items" :items-per-row="itemsPerRow"></wil-slider>
    </div>
    <?php
}
