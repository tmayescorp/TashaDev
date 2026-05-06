<?php

namespace WilokeListingTools\Controllers\Retrieve;

interface RetrieveInterface
{
    /**
     * @param array $aData
     *
     * @return mixed
     */
    public function success($aData = []);
    
    /**
     * @param array $aData
     *
     * @return mixed
     */
    public function error($aData = []);
}
