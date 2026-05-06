<?php

namespace WilokeListingTools\Controllers\Retrieve;

abstract class RetrieveAbstract
{
    /**
     * @var RetrieveInterface
     */
    protected $oRetrieve;
    
    public function __construct(RetrieveInterface $oRetrieve)
    {
        $this->oRetrieve = $oRetrieve;
    }
    
    public function setImplement(RetrieveInterface $oRetrieve)
    {
        $this->oRetrieve = $oRetrieve;
    }
    
    /**
     * @param        $aData
     * @param string $status
     *
     * @return mixed
     */
    public function get($aData, $status = 'success') {
        if ($status == 'error') {
            return $this->oRetrieve->error($aData);
        } else {
            return $this->oRetrieve->success($aData);
        }
    }
}
