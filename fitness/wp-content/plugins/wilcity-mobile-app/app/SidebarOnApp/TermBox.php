<?php
namespace WILCITY_APP\SidebarOnApp;

class TermBox
{
    public function __construct()
    {
        add_filter('wilcity/mobile/sidebar/terms_box', [$this, 'render'], 10, 2);
    }
    
    public function render($aTerms, $aAtts)
    {
        $aInfo = [];
        foreach ($aTerms as $order => $oTerm) {
            if (empty($oTerm) || is_wp_error($oTerm)) {
                continue;
            }
            $aInfo[]                  = \WilokeHelpers::getTermOriginalIcon($oTerm);
            $aInfo[$order]['name']    = $oTerm->name;
            $aInfo[$order]['term_id'] = $oTerm->term_id;
            $aInfo[$order]['slug']    = $oTerm->slug;
        }
        
        return json_encode($aInfo);
    }
}
