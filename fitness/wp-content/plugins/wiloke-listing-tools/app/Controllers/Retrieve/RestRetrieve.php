<?php

namespace WilokeListingTools\Controllers\Retrieve;

class RestRetrieve implements RetrieveInterface
{
    /**
     * @param array $aData
     *
     * @return array
     */
    public function success($aData = [])
    {
        $aData['status'] = 'success';
        
        return $aData;
    }
    
    /**
     * @param array $aData
     *
     * @return array
     */
    public function error($aData = [])
    {
        $aData['status'] = 'error';
        
        return $aData;
    }
}
