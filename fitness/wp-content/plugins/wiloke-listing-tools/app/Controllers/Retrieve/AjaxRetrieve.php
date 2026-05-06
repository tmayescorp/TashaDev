<?php

namespace WilokeListingTools\Controllers\Retrieve;

class AjaxRetrieve implements RetrieveInterface
{
    /**
     * @param array $aData
     *
     * @return bool
     */
    public function success($aData = [])
    {
        wp_send_json_success($aData);
        
        return true;
    }
    
    /**
     * @param array $aData
     *
     * @return bool
     */
    public function error($aData = [])
    {
        wp_send_json_error($aData);
        
        return false;
    }
}
