<?php

namespace WilokeListingTools\Controllers;

use WilokeListingTools\Controllers\Retrieve\RetrieveAbstract;

class RetrieveController extends RetrieveAbstract
{
    /**
     * @param $aData
     *
     * @return mixed
     */
    public function error($aData)
    {
        return $this->get($aData, 'error');
    }
    
    /**
     * @param $aData
     *
     * @return mixed
     */
    public function success($aData)
    {
        return $this->get($aData, 'success');
    }
}
